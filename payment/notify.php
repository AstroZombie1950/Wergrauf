<?php
/* payment/notify.php — обработчик POST-уведомлений от Ozon Acquiring
   Верифицирует подпись, обновляет статус заказа, шлёт Telegram */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

// --- Логируем всё входящее для отладки ---
$log_file = $_SERVER['DOCUMENT_ROOT'] . '/data/payment_notify.log';
$raw      = file_get_contents('php://input');

file_put_contents($log_file,
	date('Y-m-d H:i:s') . ' RAW: ' . $raw . "\n",
	FILE_APPEND
);

$data = json_decode($raw, true);

if (!$data) {
	http_response_code(400);
	exit;
}

// --- Верификация подписи уведомления ---
// Для самостоятельных платежей (СБП без заказа):
// SHA256("{accessKey}|||{extTransactionID}|{amount}|{currencyCode}|{notificationSecretKey}")
$access_key   = OZON_ACCESS_KEY;
$notify_key   = OZON_NOTIFICATION_SECRET;

// extTransactionID — это наш extId (order_id)
$ext_tx_id    = $data['extTransactionID'] ?? ($data['extTransactionId'] ?? '');
$amount       = $data['amount']       ?? '';
$currency     = $data['currencyCode'] ?? '';
$incoming_sign = $data['requestSign'] ?? '';

$digest = $access_key . '|||' . $ext_tx_id . '|' . $amount . '|' . $currency . '|' . $notify_key;
$expected_sign = hash('sha256', $digest);

file_put_contents($log_file,
	date('Y-m-d H:i:s') . ' SIGN_CHECK: expected=' . $expected_sign . ' got=' . $incoming_sign . "\n",
	FILE_APPEND
);

if (!hash_equals($expected_sign, $incoming_sign)) {
	// Подпись не совпала — отвечаем 200 чтобы Ozon не слал повторно, но ничего не делаем
	file_put_contents($log_file,
		date('Y-m-d H:i:s') . " SIGN_MISMATCH — ignoring\n",
		FILE_APPEND
	);
	http_response_code(200);
	exit;
}

// --- Определяем order_id ---
// При самостоятельной интеграции extTransactionID — это наш extId из createPayment
$order_id = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($ext_tx_id));

if (!$order_id) {
	file_put_contents($log_file,
		date('Y-m-d H:i:s') . " NO_ORDER_ID\n",
		FILE_APPEND
	);
	http_response_code(200);
	exit;
}

$order_file = ORDERS_DIR . $order_id . '.json';

if (!file_exists($order_file)) {
	file_put_contents($log_file,
		date('Y-m-d H:i:s') . " ORDER_NOT_FOUND: $order_id\n",
		FILE_APPEND
	);
	http_response_code(200);
	exit;
}

$order  = json_decode(file_get_contents($order_file), true);
$status = $data['status'] ?? '';

file_put_contents($log_file,
	date('Y-m-d H:i:s') . " ORDER=$order_id STATUS=$status\n",
	FILE_APPEND
);

// --- Обновляем статус ---
if ($status === 'Completed' && $order['status'] !== 'paid') {
	$order['status']     = 'paid';
	$order['paid_at']    = time();
	$order['paid_date']  = date('d.m.Y H:i');
	$order['payment_method'] = $data['paymentMethod'] ?? 'SBP';

	file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

	// Уведомление в Telegram
	notify_paid($order);
}

http_response_code(200);
exit;

/* ===== TELEGRAM ===== */

function notify_paid(array $order): void {
	if (TG_BOT_TOKEN === 'TG_BOT_TOKEN') return;

	$c     = $order['customer'];
	$total = number_format($order['total'], 0, '.', ' ') . ' ₽';
	$text  = "✅ <b>Заказ {$order['id']} ОПЛАЧЕН</b>\n\n"
		. "👤 {$c['name']}\n"
		. "📞 {$c['phone']}\n"
		. "💰 {$total}\n"
		. "💳 " . ($order['payment_method'] ?? 'СБП') . "\n\n"
		. "🔗 " . SITE_URL . "/order/?id={$order['id']}";

	$url  = 'https://api.telegram.org/bot' . TG_BOT_TOKEN . '/sendMessage';
	$post = http_build_query([
		'chat_id'    => TG_CHAT_ID,
		'text'       => $text,
		'parse_mode' => 'HTML',
	]);

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => $post,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 10,
		CURLOPT_SSL_VERIFYPEER => false,
	]);
	curl_exec($ch);
	curl_close($ch);
}
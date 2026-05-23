<?php
/* payment/notify.php — обработчик POST-уведомлений от Ozon Acquiring
   Обрабатывает оба типа: самостоятельная интеграция (СБП) и Ozon Pay Checkout */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/data/payment_notify.log';
$raw      = file_get_contents('php://input');

file_put_contents($log_file, date('Y-m-d H:i:s') . ' RAW: ' . $raw . "\n", FILE_APPEND);

$data = json_decode($raw, true);

if (!$data) {
	http_response_code(400);
	exit;
}

$incoming_sign = $data['requestSign'] ?? '';
$status        = $data['status']     ?? '';
$amount        = $data['amount']     ?? '';
$currency      = $data['currencyCode'] ?? '643';

// --- Определяем тип уведомления и находим order_id ---
// Тип 1: самостоятельная оплата (СБП) — есть extTransactionID
// Тип 2: Ozon Pay Checkout — есть orderID и extOrderID
$is_checkout = isset($data['orderID']) && !isset($data['extTransactionID']);

if ($is_checkout) {
	// Checkout: extOrderID = наш order_id
	$order_id      = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($data['extOrderID'] ?? ''));
	$ozon_order_id = $data['orderID']      ?? '';
	$transaction_id = $data['transactionID'] ?? '';
	$transaction_uid = $data['transactionUID'] ?? '';

	// Подпись для уведомления о попытке оплаты заказа:
	// SHA256("{accessKey}|{orderID}|{transactionID}|{extOrderID}|{amount}|{currencyCode}|{notificationSecretKey}")
	$digest = OZON_ACCESS_KEY . '|' . $ozon_order_id . '|' . $transaction_id . '|' . $order_id . '|' . $amount . '|' . $currency . '|' . OZON_NOTIFICATION_SECRET;
} else {
	// СБП самостоятельная: extTransactionID = наш order_id
	$order_id       = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($data['extTransactionID'] ?? ''));
	$transaction_uid = $data['transactionUID'] ?? '';

	// SHA256("{accessKey}|||{extTransactionID}|{amount}|{currencyCode}|{notificationSecretKey}")
	$digest = OZON_ACCESS_KEY . '|||' . $order_id . '|' . $amount . '|' . $currency . '|' . OZON_NOTIFICATION_SECRET;
}

$expected_sign = hash('sha256', $digest);

file_put_contents($log_file,
	date('Y-m-d H:i:s') . ' TYPE=' . ($is_checkout ? 'CHECKOUT' : 'SBP')
	. ' ORDER=' . $order_id
	. ' SIGN_OK=' . ($expected_sign === $incoming_sign ? 'YES' : 'NO')
	. ' expected=' . $expected_sign
	. ' got=' . $incoming_sign . "\n",
	FILE_APPEND
);

if (!hash_equals($expected_sign, $incoming_sign)) {
	file_put_contents($log_file, date('Y-m-d H:i:s') . " SIGN_MISMATCH — ignoring\n", FILE_APPEND);
	http_response_code(200);
	exit;
}

if (!$order_id) {
	file_put_contents($log_file, date('Y-m-d H:i:s') . " NO_ORDER_ID\n", FILE_APPEND);
	http_response_code(200);
	exit;
}

$order_file = ORDERS_DIR . $order_id . '.json';

if (!file_exists($order_file)) {
	file_put_contents($log_file, date('Y-m-d H:i:s') . " ORDER_NOT_FOUND: $order_id\n", FILE_APPEND);
	http_response_code(200);
	exit;
}

$order = json_decode(file_get_contents($order_file), true);

file_put_contents($log_file,
	date('Y-m-d H:i:s') . " ORDER=$order_id STATUS=$status\n",
	FILE_APPEND
);

// --- Обновляем статус ---
if ($status === 'Completed' && $order['status'] !== 'paid') {
	$order['status']         = 'paid';
	$order['paid_at']        = time();
	$order['paid_date']      = date('d.m.Y H:i');
	$order['payment_method'] = $data['paymentMethod'] ?? ($is_checkout ? 'Checkout' : 'SBP');

	file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	file_put_contents($log_file, date('Y-m-d H:i:s') . " PAID: $order_id\n", FILE_APPEND);

	notify_paid($order);
}

http_response_code(200);
exit;

/* ===== TELEGRAM ===== */

function notify_paid(array $order): void {
	if (!defined('TG_BOT_TOKEN') || TG_BOT_TOKEN === 'TG_BOT_TOKEN') return;

	$c     = $order['customer'];
	$total = number_format($order['total'], 0, '.', ' ') . ' ₽';
	$method = $order['payment_method'] ?? 'СБП';

	$text = "✅ <b>Заказ {$order['id']} ОПЛАЧЕН</b>\n\n"
		. "👤 {$c['name']}\n"
		. "📞 {$c['phone']}\n"
		. "💰 {$total}\n"
		. "💳 {$method}\n\n"
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
<?php
/* payment/check_order.php — проверяет статус заказа через Ozon API
   GET ?order_id=ORD-XXXX
   Ответ: { "status": "new|paid" } */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/data/payment_check.log';

function clog(string $msg): void {
	global $log_file;
	file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

$order_id = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($_GET['order_id'] ?? ''));

if (!$order_id) {
	echo json_encode(['status' => 'unknown']);
	exit;
}

$order_file = ORDERS_DIR . $order_id . '.json';

if (!file_exists($order_file)) {
	echo json_encode(['status' => 'unknown']);
	exit;
}

$order = json_decode(file_get_contents($order_file), true);

// Уже оплачен — сразу возвращаем
if ($order['status'] === 'paid') {
	echo json_encode(['status' => 'paid']);
	exit;
}

// Нет checkout_order_id — нечего проверять
if (empty($order['checkout_order_id'])) {
	echo json_encode(['status' => $order['status']]);
	exit;
}

// --- Запрашиваем статус у Ozon ---
$ext_id      = $order_id;
$ozon_id     = $order['checkout_order_id'];
$fingerprint = $ozon_id . $ext_id . OZON_ACCESS_KEY . OZON_SECRET_KEY;
$request_sign = hash('sha256', $fingerprint);

$payload = json_encode([
	'accessKey'   => OZON_ACCESS_KEY,
	'id'          => $ozon_id,
	'extId'       => $ext_id,
	'requestSign' => $request_sign,
]);

$ch = curl_init(OZON_API_URL . '/v1/getOrderStatus');
curl_setopt_array($ch, [
	CURLOPT_POST           => true,
	CURLOPT_POSTFIELDS     => $payload,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT        => 10,
	CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
	CURLOPT_SSL_VERIFYPEER => true,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

clog("ORDER=$order_id HTTP=$http_code RESPONSE=$response");

$result = json_decode($response, true);
$ozon_status = $result['status'] ?? '';

// STATUS_PAID — оплачен
if (in_array($ozon_status, ['STATUS_PAID', 'STATUS_AUTHORIZED']) && $order['status'] !== 'paid') {
	$order['status']         = 'paid';
	$order['paid_at']        = time();
	$order['paid_date']      = date('d.m.Y H:i');
	$order['payment_method'] = 'Ozon Pay';

	file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	clog("PAID: $order_id");

	// Telegram
	notify_paid_checkout($order);

	echo json_encode(['status' => 'paid']);
	exit;
}

echo json_encode(['status' => $order['status']]);

function notify_paid_checkout(array $order): void {
	if (!defined('TG_BOT_TOKEN') || TG_BOT_TOKEN === 'TG_BOT_TOKEN') return;

	$c     = $order['customer'];
	$total = number_format($order['total'], 0, '.', ' ') . ' ₽';
	$text  = "✅ <b>Заказ {$order['id']} ОПЛАЧЕН</b>\n\n"
		. "👤 {$c['name']}\n"
		. "📞 {$c['phone']}\n"
		. "💰 {$total}\n"
		. "💳 Ozon Pay\n\n"
		. "🔗 " . SITE_URL . "/order/?id={$order['id']}";

	$url  = 'https://api.telegram.org/bot' . TG_BOT_TOKEN . '/sendMessage';
	$post = http_build_query(['chat_id' => TG_CHAT_ID, 'text' => $text, 'parse_mode' => 'HTML']);

	$ch = curl_init($url);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post,
		CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false]);
	curl_exec($ch);
	curl_close($ch);
}
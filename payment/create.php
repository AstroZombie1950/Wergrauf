<?php
/* payment/create.php — создание платежа через Ozon Acquiring (СБП) */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/data/payment_create.log';

function wlog(string $msg): void {
	global $log_file;
	file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
	exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

wlog('REQUEST: ' . $raw);

if (empty($body['order_id'])) {
	echo json_encode(['ok' => false, 'error' => 'order_id required']);
	exit;
}

// --- Читаем заказ ---
$order_id   = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($body['order_id']));
$order_file = ORDERS_DIR . $order_id . '.json';

if (!file_exists($order_file)) {
	echo json_encode(['ok' => false, 'error' => 'Заказ не найден']);
	exit;
}

$order = json_decode(file_get_contents($order_file), true);

wlog('ORDER total=' . $order['total'] . ' status=' . $order['status']);

if ($order['status'] === 'paid') {
	echo json_encode(['ok' => false, 'error' => 'Заказ уже оплачен']);
	exit;
}

// --- Если платёж уже создавался — сбрасываем, чтобы пробовать заново ---
// (убрать эту логику после отладки)

// --- Формируем подпись ---
// Порядок по документации: extId accessKey secretKey
$ext_id       = $order_id;
$fingerprint  = $ext_id . OZON_ACCESS_KEY . OZON_SECRET_KEY;
$request_sign = hash('sha256', $fingerprint);

wlog('SIGN fingerprint=' . $fingerprint);
wlog('SIGN result=' . $request_sign);

// --- amount.value: строка в копейках (159 руб = 15900) ---
$amount_value = (string)((int)$order['total'] * 100);

// --- Тело запроса ---
$payload = [
	'accessKey'       => OZON_ACCESS_KEY,
	'extId'           => $ext_id,
	'payType'         => 'SBP',
	'redirectUrl'     => OZON_REDIRECT_URL . '?id=' . $order_id,
	'notificationUrl' => OZON_NOTIFY_URL,
	'requestSign'     => $request_sign,
	'ttl'             => OZON_PAYMENT_TTL,
	'amount'          => [
		'currencyCode' => '643',
		'value'        => $amount_value,
	],
	'userInfo'        => [
		'extId' => $order_id,
	],
];

$payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE);
wlog('PAYLOAD: ' . $payload_json);

// --- Запрос к Ozon API ---
$ch = curl_init(OZON_API_URL . '/v1/createPayment');
curl_setopt_array($ch, [
	CURLOPT_POST           => true,
	CURLOPT_POSTFIELDS     => $payload_json,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT        => 15,
	CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
	CURLOPT_SSL_VERIFYPEER => true,
]);

$response  = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

wlog('HTTP_CODE: ' . $http_code);
wlog('RESPONSE: ' . $response);

if ($curl_err) {
	wlog('CURL_ERR: ' . $curl_err);
	echo json_encode(['ok' => false, 'error' => 'Ошибка соединения с платёжным шлюзом']);
	exit;
}

$result = json_decode($response, true);

if ($http_code !== 200 || empty($result['paymentDetails']['sbp']['payload'])) {
	$msg = $result['message'] ?? ('HTTP ' . $http_code);
	wlog('ERROR: ' . $msg);
	echo json_encode(['ok' => false, 'error' => 'Ошибка создания платежа: ' . $msg]);
	exit;
}

$sbp_payload = $result['paymentDetails']['sbp']['payload'];
$payment_id  = $result['paymentDetails']['paymentId'] ?? '';

wlog('SUCCESS payment_id=' . $payment_id);

// --- Сохраняем в заказ ---
$order['payment_id']      = $payment_id;
$order['payment_payload'] = $sbp_payload;
$order['payment_created'] = time();
file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode([
	'ok'         => true,
	'payload'    => $sbp_payload,
	'payment_id' => $payment_id,
]);
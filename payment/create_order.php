<?php
/* payment/create_order.php — создание заказа через Ozon Pay Checkout
   POST JSON: { "order_id": "ORD-XXXXXXXX" }
   Ответ:     { "ok": true, "pay_link": "https://..." }
              { "ok": false, "error": "..." } */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/data/payment_order.log';

function olog(string $msg): void {
	global $log_file;
	file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
	exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

olog('REQUEST: ' . $raw);

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

olog('ORDER total=' . $order['total'] . ' status=' . $order['status']);

if ($order['status'] === 'paid') {
	echo json_encode(['ok' => false, 'error' => 'Заказ уже оплачен']);
	exit;
}

// --- Если ссылка уже создавалась — возвращаем кэш ---
if (!empty($order['checkout_pay_link']) && !empty($order['checkout_order_id'])) {
	olog('CACHED pay_link=' . $order['checkout_pay_link']);
	echo json_encode(['ok' => true, 'pay_link' => $order['checkout_pay_link']]);
	exit;
}

// --- Формируем подпись ---
// Порядок для createOrder: accessKey expiresAt extId fiscalizationType paymentAlgorithm amount.currencyCode amount.value secretKey
$ext_id             = $order_id;
$expires_at         = '';	// без срока истечения
$fiscalization_type = '';	// фискализация отключена
$payment_algorithm  = 'PAY_ALGO_SMS';	// одностадийный
$amount_currency    = '643';
$amount_value       = (string)((int)$order['total'] * 100);	// копейки

$fingerprint = $expires_at
	. $ext_id
	. $fiscalization_type
	. $payment_algorithm
	. $amount_currency
	. $amount_value
	. OZON_SECRET_KEY;

// Порядок строго по документации
$fingerprint  = OZON_ACCESS_KEY . $expires_at . $ext_id . $fiscalization_type . $payment_algorithm . $amount_currency . $amount_value . OZON_SECRET_KEY;
$request_sign = hash('sha256', $fingerprint);

olog('SIGN fingerprint=' . $fingerprint);
olog('SIGN result=' . $request_sign);

// --- Тело запроса ---
$payload = [
	'accessKey'         => OZON_ACCESS_KEY,
	'extId'             => $ext_id,
	'paymentAlgorithm'  => $payment_algorithm,
	'enableFiscalization' => false,
	'mode'              => 'MODE_SHORTENED',
	'successUrl'        => OZON_REDIRECT_URL . '?id=' . $order_id . '&paid=1',
	'failUrl'           => OZON_REDIRECT_URL . '?id=' . $order_id . '&fail=1',
	'notificationUrl'   => OZON_NOTIFY_URL,
	'requestSign'       => $request_sign,
	'amount'            => [
		'currencyCode' => $amount_currency,
		'value'        => $amount_value,
	],
];

$payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE);
olog('PAYLOAD: ' . $payload_json);

// --- Запрос к Ozon API ---
$ch = curl_init(OZON_API_URL . '/v1/createOrder');
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

olog('HTTP_CODE: ' . $http_code);
olog('RESPONSE: ' . $response);

if ($curl_err) {
	olog('CURL_ERR: ' . $curl_err);
	echo json_encode(['ok' => false, 'error' => 'Ошибка соединения с платёжным шлюзом']);
	exit;
}

$result = json_decode($response, true);

if ($http_code !== 200 || empty($result['order']['payLink'])) {
	$msg = $result['message'] ?? ('HTTP ' . $http_code);
	olog('ERROR: ' . $msg);
	echo json_encode(['ok' => false, 'error' => 'Ошибка создания заказа: ' . $msg]);
	exit;
}

$pay_link        = $result['order']['payLink'];
$checkout_order_id = $result['order']['id'] ?? '';

olog('SUCCESS pay_link=' . $pay_link . ' checkout_order_id=' . $checkout_order_id);

// --- Сохраняем в заказ ---
$order['checkout_pay_link']   = $pay_link;
$order['checkout_order_id']   = $checkout_order_id;
$order['checkout_created']    = time();
file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['ok' => true, 'pay_link' => $pay_link]);
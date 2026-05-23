<?php
/* payment/status.php — поллинг статуса заказа
   GET ?order_id=ORD-XXXX
   Ответ: { "status": "new|paid|cancelled" } */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

header('Content-Type: application/json; charset=utf-8');

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

echo json_encode(['status' => $order['status'] ?? 'unknown']);
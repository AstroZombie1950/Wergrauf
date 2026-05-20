<?php
/* cart/subscribe.php — обработчик формы подписки */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
	exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

$email = trim($_POST['email'] ?? '');

/* Валидация */
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Некорректный email']);
	exit;
}

$subscribers_file = $_SERVER['DOCUMENT_ROOT'] . '/data/subscribers.json';

/* Загружаем текущих подписчиков */
$subscribers = [];
if (file_exists($subscribers_file)) {
	$subscribers = json_decode(file_get_contents($subscribers_file), true) ?? [];
}

/* Проверяем дубли */
foreach ($subscribers as $s) {
	if (isset($s['email']) && strtolower($s['email']) === strtolower($email)) {
		echo json_encode(['ok' => true, 'message' => 'Вы уже подписаны']);
		exit;
	}
}

/* Добавляем */
$subscribers[] = [
	'email' => $email,
	'date'  => date('Y-m-d H:i:s'),
	'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
];

$ok = file_put_contents(
	$subscribers_file,
	json_encode($subscribers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

if (!$ok) {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'Ошибка сервера']);
	exit;
}

/* Дублируем на почту магазина */
if (defined('SHOP_EMAIL') && SHOP_EMAIL) {
	$subject = 'Новый подписчик — ' . $email;
	$body    = "Новый подписчик на рассылку:\n\nEmail: {$email}\nДата: " . date('d.m.Y H:i');
	$headers = 'From: noreply@wergrauf.ru' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
	@mail(SHOP_EMAIL, $subject, $body, $headers);
}

echo json_encode(['ok' => true, 'message' => 'Спасибо за подписку!']);
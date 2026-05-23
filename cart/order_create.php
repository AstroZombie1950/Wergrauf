<?php
/* cart/order_create.php — создание заказа
   Принимает JSON POST, возвращает JSON */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
	exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
	echo json_encode(['ok' => false, 'error' => 'Неверный формат данных']);
	exit;
}

// --- Валидация ---
$name   = trim($data['name']  ?? '');
$phone  = trim($data['phone'] ?? '');
$email  = trim($data['email'] ?? '');
$items  = $data['items']  ?? [];
$source = $data['source'] ?? 'cart'; // cart | one_click

$errors = [];

if (!preg_match('/^[а-яёА-ЯЁa-zA-Z ]{2,}$/u', $name)) {
	$errors[] = 'Некорректное имя';
}

$phone_digits = preg_replace('/\D/', '', $phone);
if (strlen($phone_digits) !== 11 || !str_starts_with($phone_digits, '7')) {
	$errors[] = 'Некорректный номер телефона';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$errors[] = 'Некорректный email';
}

if (empty($items)) {
	$errors[] = 'Корзина пуста';
}

if (!empty($errors)) {
	echo json_encode(['ok' => false, 'error' => implode('. ', $errors)]);
	exit;
}

// --- Формируем заказ ---
$order_id  = 'ORD-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
$timestamp = time();

// Считаем суммы
$subtotal = 0;
$order_items = [];
foreach ($items as $item) {
	$price = (int)($item['price'] ?? 0);
	$qty   = max(1, (int)($item['qty'] ?? 1));

	// Применяем промокод если был применён
	$effective_price = $price;
	$promo_applied   = !empty($item['promo_applied']);
	if ($promo_applied && !empty($item['discount_percent'])) {
		$pct = (float)$item['discount_percent'];
		$effective_price = (int)round($price * (1 - $pct / 100));
	}

	$line_total = $effective_price * $qty;
	$subtotal  += $line_total;

	$order_items[] = [
		'article'         => $item['article'] ?? '',
		'name'            => $item['name']    ?? '',
		'image'           => $item['image']   ?? '',
		'slug'            => $item['slug']    ?? '',
		'section_url'     => $item['section_url'] ?? '',
		'price'           => $price,
		'effective_price' => $effective_price,
		'qty'             => $qty,
		'line_total'      => $line_total,
		'promo_applied'   => $promo_applied,
		'discount_percent'=> $item['discount_percent'] ?? '',
	];
}

$order = [
	'id'         => $order_id,
	'created_at' => $timestamp,
	'date'       => date('d.m.Y H:i'),
	'status'     => 'new',           // new | paid | cancelled
	'source'     => $source,
	'customer'   => [
		'name'  => $name,
		'phone' => $phone,
		'email' => $email,
	],
	'items'      => $order_items,
	'subtotal'   => $subtotal,
	'total'      => $subtotal,       // здесь можно добавить стоимость доставки
];

// --- Сохраняем заказ ---
if (!is_dir(ORDERS_DIR)) mkdir(ORDERS_DIR, 0755, true);

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/debug.log', date('Y-m-d H:i:s') . " before save\n", FILE_APPEND);
$order_file = ORDERS_DIR . $order_id . '.json';
if (!file_put_contents($order_file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
	echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить заказ']);
	exit;
}

// --- Telegram ---
tg_notify($order);

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/debug.log', date('Y-m-d H:i:s') . " after tg_notify\n", FILE_APPEND);

// --- Email покупателю ---
if ($email !== '') {
	mail_customer($order);
}

// --- Email магазину ---
mail_shop($order);

echo json_encode(['ok' => true, 'order_id' => $order_id]);
exit;

/* ===== УВЕДОМЛЕНИЯ ===== */

function fmt_price(int $p): string {
	return number_format($p, 0, '.', ' ') . ' ₽';
}

function tg_notify(array $order): void {
	if (TG_BOT_TOKEN === 'TG_BOT_TOKEN') return;

	$c     = $order['customer'];
	$src   = $order['source'] === 'one_click' ? '⚡ Один клик' : '🛒 Корзина';
	$lines = '';
	foreach ($order['items'] as $item) {
		$disc   = $item['promo_applied'] ? " (-{$item['discount_percent']}%)" : '';
		$lines .= "• {$item['name']} × {$item['qty']}{$disc} = " . fmt_price($item['line_total']) . "\n";
	}

	$text = "🆕 <b>Новый заказ {$order['id']}</b> [{$src}]\n\n"
		. "👤 <b>Покупатель:</b> {$c['name']}\n"
		. "📞 <b>Телефон:</b> {$c['phone']}\n"
		. ($c['email'] ? "✉️ <b>Email:</b> {$c['email']}\n" : '')
		. "\n<b>Состав:</b>\n{$lines}"
		. "\n💰 <b>Итого:</b> " . fmt_price($order['total'])
		. "\n\n🔗 " . SITE_URL . "/order/?id={$order['id']}";

	$url  = 'https://api.telegram.org/bot' . TG_BOT_TOKEN . '/sendMessage';
	$data = http_build_query([
		'chat_id'    => TG_CHAT_ID,
		'text'       => $text,
		'parse_mode' => 'HTML',
	]);

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => $data,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 10,
		CURLOPT_SSL_VERIFYPEER => false,
	]);
	$result = curl_exec($ch);
	$err    = curl_error($ch);
	curl_close($ch);

	file_put_contents(
		$_SERVER['DOCUMENT_ROOT'] . '/data/tg_debug.log',
		date('Y-m-d H:i:s') . "\nresult: " . $result . "\nerror: " . $err . "\n\n",
		FILE_APPEND
	);
}

function mail_customer(array $order): void {
	$c       = $order['customer'];
	$subject = "Ваш заказ {$order['id']} — " . SITE_NAME;
	$lines   = '';
	foreach ($order['items'] as $item) {
		$disc   = $item['promo_applied'] ? " (скидка {$item['discount_percent']}%)" : '';
		$lines .= "<tr>
			<td style='padding:8px 12px;border-bottom:1px solid #eee'>{$item['name']}{$disc}</td>
			<td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:center'>{$item['qty']}</td>
			<td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:right'>" . fmt_price($item['line_total']) . "</td>
		</tr>";
	}

	$body = "<!DOCTYPE html><html><body style='font-family:Roboto,Arial,sans-serif;color:#36393e;margin:0;padding:0'>
	<div style='max-width:560px;margin:0 auto;padding:32px 20px'>
		<div style='font-size:22px;font-weight:700;margin-bottom:4px'>WERGRAUF</div>
		<div style='color:#8a8f9a;font-size:13px;margin-bottom:28px'>Официальный интернет-магазин</div>

		<h1 style='font-size:20px;margin:0 0 8px'>Ваш заказ принят!</h1>
		<p style='color:#6b6b6b;margin:0 0 24px'>Номер заказа: <strong>{$order['id']}</strong></p>

		<p style='margin:0 0 8px'>Здравствуйте, <strong>{$c['name']}</strong>!</p>
		<p style='color:#6b6b6b;margin:0 0 24px'>Мы получили ваш заказ и свяжемся с вами для подтверждения деталей.</p>

		<table style='width:100%;border-collapse:collapse;margin-bottom:16px'>
			<thead><tr style='background:#f4f5f7'>
				<th style='padding:10px 12px;text-align:left;font-size:12px;color:#8a8f9a'>Товар</th>
				<th style='padding:10px 12px;text-align:center;font-size:12px;color:#8a8f9a'>Кол-во</th>
				<th style='padding:10px 12px;text-align:right;font-size:12px;color:#8a8f9a'>Сумма</th>
			</tr></thead>
			<tbody>{$lines}</tbody>
		</table>

		<div style='text-align:right;font-size:18px;font-weight:700;margin-bottom:28px'>
			Итого: " . fmt_price($order['total']) . "
		</div>

		<div style='background:#f4f5f7;border-radius:10px;padding:16px;margin-bottom:24px;font-size:13px;color:#6b6b6b'>
			Информация о доставке: <a href='" . SITE_URL . "/delivery/' style='color:#385081'>wergrauf.ru/delivery</a>
		</div>

		<a href='" . SITE_URL . "/order/?id={$order['id']}' style='display:inline-block;background:#36393e;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600'>
			Просмотреть заказ
		</a>
	</div>
	</body></html>";

	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=" . MAIL_CHARSET . "\r\n";
	$headers .= "From: " . SITE_NAME . " <" . MAIL_FROM . ">\r\n";

	@mail($c['email'], $subject, $body, $headers);
}

function mail_shop(array $order): void {

	$c       = $order['customer'];
	$subject = "Новый заказ {$order['id']} — " . SITE_NAME;
	$lines   = '';
	foreach ($order['items'] as $item) {
		$lines .= "- {$item['name']} × {$item['qty']} = " . fmt_price($item['line_total']) . "\n";
	}

	$body = "Новый заказ {$order['id']}\n\n"
		. "Покупатель: {$c['name']}\n"
		. "Телефон: {$c['phone']}\n"
		. ($c['email'] ? "Email: {$c['email']}\n" : '')
		. "\nСостав:\n{$lines}"
		. "\nИтого: " . fmt_price($order['total'])
		. "\n\nСтраница заказа: " . SITE_URL . "/order/?id={$order['id']}";

	$headers  = "From: " . SITE_NAME . " <" . MAIL_FROM . ">\r\n";
	@mail(MAIL_TO, $subject, $body, $headers);
}
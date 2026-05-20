<?php
/* order/index.php — страница конкретного заказа */

require_once $_SERVER['DOCUMENT_ROOT'] . '/cart/config.php';

$order_id = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($_GET['id'] ?? ''));
$order    = null;

if ($order_id) {
	$file = ORDERS_DIR . $order_id . '.json';
	if (file_exists($file)) {
		$order = json_decode(file_get_contents($file), true);
	}
}

function oh(string $s): string {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function ofmt(int $n): string {
	return number_format($n, 0, '.', ' ') . ' ₽';
}

$status_labels = [
	'new'       => ['label' => 'Ожидает оплаты',    'color' => '#e65100', 'bg' => '#fff3e0'],
	'paid'      => ['label' => 'Оплачен',            'color' => '#2e7d32', 'bg' => '#e8f5e9'],
	'cancelled' => ['label' => 'Отменён',            'color' => '#c62828', 'bg' => '#fce4ec'],
];
$status = $order ? ($status_labels[$order['status']] ?? $status_labels['new']) : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | <?= $order ? 'Заказ ' . oh($order['id']) : 'Заказ не найден' ?></title>
	<meta name="robots" content="noindex">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/source_css/wg.css" media="all">
	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; font-family: "Roboto", Arial, sans-serif; color: #36393e; }

	.order-page { max-width: 760px; margin: 0 auto; padding: 32px 20px 60px; }

	/* Шапка заказа */
	.order-header { margin-bottom: 28px; }
	.order-header__id { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
	.order-header__date { font-size: 13px; color: #8a8f9a; margin-bottom: 14px; }
	.order-status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }

	/* Блоки */
	.order-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 16px; }
	.order-card__title { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #8a8f9a; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; }

	/* Покупатель */
	.customer-row { display: flex; gap: 8px; font-size: 14px; margin-bottom: 8px; }
	.customer-row__label { color: #8a8f9a; width: 80px; flex-shrink: 0; }

	/* Товары */
	.order-item { display: grid; grid-template-columns: 56px 1fr auto; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f4f5f7; align-items: center; }
	.order-item:last-child { border-bottom: none; }
	.order-item__img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; border: 1px solid #f0f0f0; }
	.order-item__img-ph { width: 56px; height: 56px; border-radius: 8px; background: #f4f5f7; display: flex; align-items: center; justify-content: center; font-size: 22px; }
	.order-item__name { font-size: 14px; font-weight: 500; line-height: 1.3; }
	.order-item__name a { color: inherit; text-decoration: none; }
	.order-item__name a:hover { text-decoration: underline; }
	.order-item__meta { font-size: 12px; color: #8a8f9a; margin-top: 3px; }
	.order-item__price { text-align: right; }
	.order-item__price-main { font-size: 15px; font-weight: 700; }
	.order-item__price-sub { font-size: 12px; color: #8a8f9a; }

	/* Итог */
	.total-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #6b6b6b; }
	.total-row--main { font-size: 20px; font-weight: 700; color: #36393e; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0f0f0; }

	/* Оплата */
	.pay-btn { display: block; width: 100%; height: 54px; background: #36393e; color: #fff; border: none; border-radius: 10px; font-family: inherit; font-size: 16px; font-weight: 700; cursor: pointer; text-align: center; line-height: 54px; text-decoration: none; margin-bottom: 12px; transition: background .2s; }
	.pay-btn:hover { background: #1f2226; }
	.pay-notice { font-size: 12px; color: #8a8f9a; text-align: center; line-height: 1.5; }
	.pay-notice a { color: #385081; }

	/* Не найден */
	.not-found { text-align: center; padding: 64px 20px; }
	.not-found__icon { font-size: 52px; margin-bottom: 16px; }
	.not-found__title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
	.not-found__text { color: #8a8f9a; margin-bottom: 24px; }
	</style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/head.html'); ?>

<div class="order-page">

<?php if (!$order): ?>

	<div class="not-found">
		<div class="not-found__icon">🔍</div>
		<div class="not-found__title">Заказ не найден</div>
		<div class="not-found__text">Проверьте ссылку или вернитесь на главную</div>
		<a href="/" style="display:inline-block;background:#36393e;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600">На главную</a>
	</div>

<?php else: ?>

	<!-- Шапка -->
	<div class="order-header">
		<div class="order-header__id">Заказ <?= oh($order['id']) ?></div>
		<div class="order-header__date">Оформлен <?= oh($order['date']) ?></div>
		<span class="order-status"
			style="background:<?= $status['bg'] ?>;color:<?= $status['color'] ?>">
			<?= $status['label'] ?>
		</span>
	</div>

	<!-- Оплата -->
	<?php if ($order['status'] === 'new'): ?>
	<div class="order-card">
		<div class="order-card__title">Оплата</div>
		<?php if (PAYMENT_MODE === 'pending'): ?>
			<p style="font-size:14px;color:#6b6b6b;margin:0 0 16px;line-height:1.6">
				Оплата будет доступна в ближайшее время. Пока что наш менеджер свяжется с вами для подтверждения заказа и уточнения способа оплаты.
			</p>
			<div style="background:#f4f5f7;border-radius:8px;padding:14px;font-size:13px;color:#6b6b6b">
				📞 Ожидайте звонка менеджера
			</div>
		<?php else: ?>
			<!-- Здесь будет кнопка оплаты после подключения эквайринга -->
			<a class="pay-btn" href="#payment">Перейти к оплате</a>
		<?php endif ?>
		<div class="pay-notice" style="margin-top:12px">
			Информация о доставке: <a href="/delivery/">wergrauf.ru/delivery</a>
		</div>
	</div>
	<?php endif ?>

	<!-- Покупатель -->
	<div class="order-card">
		<div class="order-card__title">Покупатель</div>
		<div class="customer-row">
			<span class="customer-row__label">Имя</span>
			<span><?= oh($order['customer']['name']) ?></span>
		</div>
		<div class="customer-row">
			<span class="customer-row__label">Телефон</span>
			<span><?= oh($order['customer']['phone']) ?></span>
		</div>
		<?php if (!empty($order['customer']['email'])): ?>
		<div class="customer-row">
			<span class="customer-row__label">Email</span>
			<span><?= oh($order['customer']['email']) ?></span>
		</div>
		<?php endif ?>
	</div>

	<!-- Товары -->
	<div class="order-card">
		<div class="order-card__title">Состав заказа</div>
		<?php foreach ($order['items'] as $item):
			$url = !empty($item['section_url']) && !empty($item['slug'])
				? $item['section_url'] . $item['slug'] . '/'
				: '';
			$has_discount = $item['promo_applied'] && $item['effective_price'] < $item['price'];
		?>
			<div class="order-item">
				<?php if (!empty($item['image'])): ?>
					<img class="order-item__img" src="<?= oh($item['image']) ?>" alt="" loading="lazy">
				<?php else: ?>
					<div class="order-item__img-ph">📦</div>
				<?php endif ?>

				<div>
					<div class="order-item__name">
						<?php if ($url): ?>
							<a href="<?= oh($url) ?>"><?= oh($item['name']) ?></a>
						<?php else: ?>
							<?= oh($item['name']) ?>
						<?php endif ?>
					</div>
					<div class="order-item__meta">
						Арт. <?= oh($item['article']) ?> · <?= $item['qty'] ?> шт.
						<?php if ($has_discount): ?> · скидка <?= oh($item['discount_percent']) ?>%<?php endif ?>
					</div>
				</div>

				<div class="order-item__price">
					<div class="order-item__price-main"><?= ofmt($item['line_total']) ?></div>
					<?php if ($has_discount): ?>
						<div class="order-item__price-sub" style="text-decoration:line-through">
							<?= ofmt($item['price'] * $item['qty']) ?>
						</div>
					<?php endif ?>
				</div>
			</div>
		<?php endforeach ?>

		<!-- Итог -->
		<?php
		$original = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $order['items']));
		$discount = $original - $order['total'];
		?>
		<div style="margin-top:8px">
			<?php if ($discount > 0): ?>
				<div class="total-row">
					<span>Без скидок</span>
					<span><?= ofmt($original) ?></span>
				</div>
				<div class="total-row">
					<span>Скидки</span>
					<span style="color:#2e7d32">−<?= ofmt($discount) ?></span>
				</div>
			<?php endif ?>
			<div class="total-row total-row--main">
				<span>Итого</span>
				<span><?= ofmt($order['total']) ?></span>
			</div>
		</div>
	</div>

<?php endif ?>

</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/foot.html'); ?>
</body>
</html>
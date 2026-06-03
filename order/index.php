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

// Флаг редиректа после Checkout
$checkout_paid = isset($_GET['paid']) && $_GET['paid'] === '1';

function oh(string $s): string {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function ofmt(int $n): string {
	return number_format($n, 0, '.', ' ') . ' ₽';
}

$status_labels = [
	'new'       => ['label' => 'Ожидает оплаты', 'color' => '#e65100', 'bg' => '#fff3e0'],
	'paid'      => ['label' => 'Оплачен',         'color' => '#2e7d32', 'bg' => '#e8f5e9'],
	'cancelled' => ['label' => 'Отменён',          'color' => '#c62828', 'bg' => '#fce4ec'],
];
$status = $order ? ($status_labels[$order['status']] ?? $status_labels['new']) : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title><?= $order ? 'Заказ ' . oh($order['id']) : 'Заказ не найден' ?> | WERGRAUF</title>
	<meta name="robots" content="noindex">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/source_css/wg.css" media="all">
	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; color: #36393e; }

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

	/* Кнопки оплаты */
	.pay-buttons { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
	.pay-btn--sbp { background: #36393e; }
	.pay-btn--checkout { background: #005bff; }
	.pay-btn--checkout:hover { background: #0047cc; }
	/* Кнопка оплаты */
	.pay-btn {
		display: block; width: 100%; height: 54px; background: #36393e; color: #fff;
		border: none; border-radius: 10px; font-family: inherit; font-size: 16px;
		font-weight: 700; cursor: pointer; text-align: center; line-height: 54px;
		text-decoration: none; margin-bottom: 12px; transition: background .2s;
	}
	.pay-btn:hover { background: #1f2226; }
	.pay-btn:disabled { opacity: .5; cursor: not-allowed; }
	/* Строка доставки */
	.delivery-notice { display: flex; align-items: center; gap: 10px; margin-top: 14px;
		padding: 12px 16px; background: #f7f8fa; border-radius: 10px;
		text-decoration: none; color: #36393e; transition: background .15s; }
	.delivery-notice:hover { background: #eef0f4; }
	.delivery-notice__icon { font-size: 18px; flex-shrink: 0; }
	.delivery-notice__text { font-size: 14px; font-weight: 500; flex: 1; }
	.delivery-notice__arrow { font-size: 16px; color: #8a8f9a; flex-shrink: 0; transition: transform .15s; }
	.delivery-notice:hover .delivery-notice__arrow { transform: translateX(3px); }

	/* QR-блок */
	.qr-wrap { display: none; text-align: center; }
	.qr-wrap.visible { display: block; }
	.qr-canvas { width: 220px; height: 220px; border-radius: 12px; border: 1px solid #f0f0f0; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
	.qr-canvas img, .qr-canvas canvas { display: block; }
	.qr-hint { font-size: 13px; color: #6b6b6b; margin-bottom: 12px; line-height: 1.5; }
	.qr-link { display: inline-block; color: #385081; font-size: 13px; font-weight: 600; text-decoration: none; }
	.qr-link:hover { text-decoration: underline; }
	.qr-timer { font-size: 12px; color: #8a8f9a; margin-top: 10px; }
	.qr-waiting { display: flex; align-items: center; gap: 8px; justify-content: center;
		font-size: 13px; color: #8a8f9a; margin-top: 14px; }
	.qr-spinner { width: 16px; height: 16px; border: 2px solid #e2e4e9;
		border-top-color: #4a4f59; border-radius: 50%; animation: spin .8s linear infinite; }
	@keyframes spin { to { transform: rotate(360deg); } }

	/* Успешная оплата */
	.pay-success { text-align: center; padding: 16px 0 8px; }
	.pay-success__icon { font-size: 48px; margin-bottom: 12px; }
	.pay-success__title { font-size: 18px; font-weight: 700; color: #2e7d32; margin-bottom: 8px; }
	.pay-success__text { font-size: 14px; color: #6b6b6b; line-height: 1.6; }

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

	<!-- Блок оплаты -->
	<?php if ($order['status'] === 'paid'): ?>

	<div class="order-card">
		<div class="order-card__title">Оплата</div>
		<div class="pay-success">
			<div class="pay-success__icon">✅</div>
			<div class="pay-success__title">Оплата прошла успешно</div>
			<div class="pay-success__text">
				Ваш заказ принят в работу. Менеджер свяжется с вами для уточнения деталей доставки.<br>
				Ожидайте звонка или сообщения.
			</div>
		</div>
	</div>

	<?php elseif ($order['status'] === 'new' && PAYMENT_MODE === 'ozon_sbp'): ?>

	<div class="order-card" id="payment-card">
		<div class="order-card__title">Оплата</div>

		<!-- Кнопки оплаты -->
		<div class="pay-buttons">
			<button class="pay-btn pay-btn--sbp" id="pay-sbp-btn" onclick="startSbpPayment()">
				Оплатить через СБП
			</button>
			<button class="pay-btn pay-btn--checkout" id="pay-checkout-btn" onclick="startCheckoutPayment()">
				💳 Оплатить картой / Ozon Pay
			</button>
		</div>

		<!-- QR-блок (скрыт до нажатия) -->
		<div class="qr-wrap" id="qr-wrap">
			<div id="qr-canvas" class="qr-canvas"></div>
			<div class="qr-hint">
				Отсканируйте QR-код камерой смартфона или через приложение банка.<br>
				На мобильном — нажмите кнопку ниже.
			</div>
			<a href="#" id="qr-mobile-link" class="qr-link">📱 Открыть в банковском приложении</a>
			<div class="qr-timer" id="qr-timer"></div>
			<div class="qr-waiting">
				<div class="qr-spinner"></div>
				<span>Ожидаем подтверждения оплаты…</span>
			</div>
		</div>

		<a class="delivery-notice" href="/delivery/">
			<span class="delivery-notice__icon">🚚</span>
			<span class="delivery-notice__text">Информация о доставке</span>
			<span class="delivery-notice__arrow">→</span>
		</a>
	</div>

	<?php elseif ($order['status'] === 'new' && PAYMENT_MODE === 'pending'): ?>

	<div class="order-card">
		<div class="order-card__title">Оплата</div>
		<p style="font-size:14px;color:#6b6b6b;margin:0 0 16px;line-height:1.6">
			Оплата будет доступна в ближайшее время. Наш менеджер свяжется с вами для подтверждения заказа и уточнения способа оплаты.
		</p>
		<div style="background:#f4f5f7;border-radius:8px;padding:14px;font-size:13px;color:#6b6b6b">
			📞 Ожидайте звонка менеджера
		</div>
		<a class="delivery-notice" href="/delivery/">
			<span class="delivery-notice__icon">🚚</span>
			<span class="delivery-notice__text">Информация о доставке</span>
			<span class="delivery-notice__arrow">→</span>
		</a>
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

<?php if (isset($order) && $order['status'] === 'new' && PAYMENT_MODE === 'ozon_sbp'): ?>
<!-- QR-библиотека (только на странице с оплатой) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function() {
	'use strict';

	const ORDER_ID      = <?= json_encode($order_id) ?>;
	const CHECKOUT_PAID = <?= $checkout_paid ? 'true' : 'false' ?>;
	const TTL        = <?= OZON_PAYMENT_TTL ?>;	// секунды
	let pollTimer    = null;
	let countTimer   = null;
	let secondsLeft  = TTL;

	/* --- Запуск оплаты --- */
	window.startSbpPayment = function() {
		const btn = document.getElementById('pay-sbp-btn');
		const btn2 = document.getElementById('pay-checkout-btn');
		btn.disabled  = true;
		btn2.disabled = true;
		btn.textContent = 'Создаём ссылку…';

		fetch('/payment/create.php', {
			method:  'POST',
			headers: { 'Content-Type': 'application/json' },
			body:    JSON.stringify({ order_id: ORDER_ID }),
		})
		.then(r => r.json())
		.then(data => {
			if (!data.ok) {
				btn.disabled    = false;
				btn.textContent = 'Оплатить через СБП';
				alert(data.error || 'Ошибка создания платежа. Попробуйте ещё раз.');
				return;
			}
			showQR(data.payload);
		})
		.catch(err => {
			btn.disabled  = false;
			btn2.disabled = false;
			btn.textContent = 'Оплатить через СБП';
			console.error('Payment fetch error:', err);
			alert('Ошибка соединения: ' + err.message + '. Проверьте интернет и попробуйте ещё раз.');
		});
	};

	/* --- Показываем QR --- */
	function showQR(payload) {
		// Скрываем кнопку
		document.getElementById('pay-sbp-btn').style.display = 'none';
		document.getElementById('pay-checkout-btn').style.display = 'none';

		// Рисуем QR через qrcodejs (new QRCode)
		const container = document.getElementById('qr-canvas');
		container.innerHTML = '';
		new QRCode(container, {
			text:          payload,
			width:         220,
			height:        220,
			colorDark:     '#36393e',
			colorLight:    '#ffffff',
			correctLevel:  QRCode.CorrectLevel.M,
		});

		// Ссылка для мобильных (payload — это ссылка вида https://qr.nspk.ru/...)
		const mobileLink = document.getElementById('qr-mobile-link');
		mobileLink.href = payload;

		// Показываем блок
		document.getElementById('qr-wrap').classList.add('visible');

		// Таймер обратного отсчёта
		updateTimer();
		countTimer = setInterval(function() {
			secondsLeft--;
			updateTimer();
			if (secondsLeft <= 0) {
				clearInterval(countTimer);
				clearInterval(pollTimer);
				document.getElementById('qr-timer').textContent = 'Время оплаты истекло. Обновите страницу для новой попытки.';
			}
		}, 1000);

		// Поллинг статуса каждые 3 сек
		pollTimer = setInterval(pollStatus, 3000);
	}

	/* --- Ozon Pay Checkout --- */
	window.startCheckoutPayment = function() {
		const btn  = document.getElementById('pay-checkout-btn');
		const btn2 = document.getElementById('pay-sbp-btn');
		btn.disabled  = true;
		btn2.disabled = true;
		btn.textContent = 'Переходим к оплате…';

		fetch('/payment/create_order.php', {
			method:  'POST',
			headers: { 'Content-Type': 'application/json' },
			body:    JSON.stringify({ order_id: ORDER_ID }),
		})
		.then(r => r.json())
		.then(data => {
			if (!data.ok) {
				btn.disabled  = false;
				btn2.disabled = false;
				btn.textContent = '💳 Оплатить картой / Ozon Pay';
				alert(data.error || 'Ошибка. Попробуйте ещё раз.');
				return;
			}
			// Редиректим на форму Ozon Pay
			window.location.href = data.pay_link;
		})
		.catch(err => {
			btn.disabled  = false;
			btn2.disabled = false;
			btn.textContent = '💳 Оплатить картой / Ozon Pay';
			alert('Ошибка соединения: ' + err.message);
		});
	};

	/* --- Поллинг --- */
	function pollStatus() {
		fetch('/payment/status.php?order_id=' + encodeURIComponent(ORDER_ID))
		.then(r => r.json())
		.then(data => {
			if (data.status === 'paid') {
				clearInterval(pollTimer);
				clearInterval(countTimer);
				// Перезагружаем страницу — PHP покажет блок успешной оплаты
				window.location.reload();
			}
		})
		.catch(() => {}); // тихо игнорируем сетевые ошибки при поллинге
	}

	/* --- Проверка статуса после Checkout-редиректа --- */
	if (CHECKOUT_PAID) {
		checkOrderStatus();
	}

	function checkOrderStatus() {
		fetch('/payment/check_order.php?order_id=' + encodeURIComponent(ORDER_ID))
		.then(r => r.json())
		.then(data => {
			if (data.status === 'paid') {
				window.location.href = '/order/?id=' + ORDER_ID;
			} else {
				setTimeout(checkOrderStatus, 3000);
			}
		})
		.catch(() => setTimeout(checkOrderStatus, 5000));
	}

	/* --- Таймер --- */
	function updateTimer() {
		const m = Math.floor(secondsLeft / 60);
		const s = secondsLeft % 60;
		const el = document.getElementById('qr-timer');
		if (el && secondsLeft > 0) {
			el.textContent = 'Ссылка действительна ещё ' + m + ':' + String(s).padStart(2, '0');
		}
	}
})();
</script>
<?php endif ?>
</body>
</html>
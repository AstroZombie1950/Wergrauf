<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | Оформление заказа</title>
	<meta name="robots" content="noindex">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/source_css/wg.css" media="all">
	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; font-family: "Roboto", Arial, sans-serif; color: #36393e; }

	.checkout-page { max-width: 900px; margin: 0 auto; padding: 32px 20px 60px; }
	.checkout-page__title { font-size: 28px; font-weight: 700; margin: 0 0 28px; }

	.checkout-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
	@media(max-width:800px){ .checkout-layout { grid-template-columns: 1fr; } }

	/* Форма */
	.checkout-form { background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
	.form-section-title { font-size: 15px; font-weight: 700; margin: 0 0 20px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }

	.form-group { margin-bottom: 18px; }
	.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 7px; }
	.form-label .req { color: #c82a20; margin-left: 2px; }
	.form-hint { font-size: 12px; color: #8a8f9a; margin-top: 4px; }

	.form-input {
		width: 100%; padding: 11px 14px; border: 1.5px solid #e2e4e9;
		border-radius: 9px; font-family: inherit; font-size: 15px;
		color: #36393e; transition: border-color .2s; background: #fff;
	}
	.form-input:focus { outline: none; border-color: #4a4f59; }
	.form-input.is-error { border-color: #c82a20; background: #fff8f8; }
	.form-input.is-ok { border-color: #2e7d32; }

	.field-error { font-size: 12px; color: #c82a20; margin-top: 5px; display: none; }

	/* Кнопка */
	.submit-btn {
		width: 100%; height: 54px; background: #36393e; color: #fff;
		border: none; border-radius: 10px; font-family: inherit;
		font-size: 16px; font-weight: 700; cursor: pointer;
		transition: background .2s; margin-top: 8px;
	}
	.submit-btn:hover { background: #1f2226; }
	.submit-btn:disabled { opacity: .5; cursor: not-allowed; }

	.submit-notice { font-size: 12px; color: #8a8f9a; text-align: center; margin-top: 12px; line-height: 1.5; }

	/* Итог */
	.checkout-summary { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06); position: sticky; top: 24px; }
	.checkout-summary__title { font-size: 15px; font-weight: 700; margin-bottom: 16px; }

	.order-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f4f5f7; align-items: center; }
	.order-item:last-child { border-bottom: none; }
	.order-item__img { width: 48px; height: 48px; border-radius: 7px; object-fit: cover; border: 1px solid #f0f0f0; flex-shrink: 0; }
	.order-item__img-ph { width: 48px; height: 48px; border-radius: 7px; background: #f4f5f7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
	.order-item__name { font-size: 13px; flex: 1; line-height: 1.3; }
	.order-item__qty { font-size: 12px; color: #8a8f9a; }
	.order-item__price { font-size: 14px; font-weight: 600; white-space: nowrap; }

	.summary-divider { border: none; border-top: 1px solid #f0f0f0; margin: 14px 0; }
	.summary-line { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #6b6b6b; }
	.summary-line--total { font-size: 18px; font-weight: 700; color: #36393e; margin-top: 4px; }

	.back-link { display: inline-flex; align-items: center; gap: 6px; color: #6b6b6b; text-decoration: none; font-size: 13px; margin-bottom: 20px; }
	.back-link:hover { color: #36393e; }

	/* Уведомление об ошибке */
	.form-global-error { background: #fde0df; border: 1px solid #ef9a9a; color: #c82a20; padding: 14px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 20px; display: none; }

	/* Редирект на пустую корзину */
	.empty-redirect { text-align: center; padding: 60px 20px; }
	</style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/head.html'); ?>

<div class="checkout-page">
	<a class="back-link" href="/cart/">← Вернуться в корзину</a>
	<h1 class="checkout-page__title">Оформление заказа</h1>

	<div id="empty-redirect" class="empty-redirect" style="display:none">
		<div style="font-size:40px;margin-bottom:12px">🛒</div>
		<div style="font-size:18px;font-weight:600;margin-bottom:8px">Корзина пуста</div>
		<a href="/shower_system/" style="display:inline-block;background:#36393e;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:8px">В каталог</a>
	</div>

	<div class="checkout-layout" id="checkout-layout" style="display:none">

		<!-- Форма -->
		<div>
			<div class="checkout-form">
				<div class="form-section-title">Контактные данные</div>

				<div class="form-global-error" id="global-error"></div>

				<div class="form-group">
					<label class="form-label" for="co-name">Ваше имя <span class="req">*</span></label>
					<input class="form-input" type="text" id="co-name" placeholder="Иван Иванов" autocomplete="name">
					<div class="field-error" id="co-name-err"></div>
				</div>

				<div class="form-group">
					<label class="form-label" for="co-phone">Телефон <span class="req">*</span></label>
					<input class="form-input" type="tel" id="co-phone" placeholder="+7 (___) ___-__-__" autocomplete="tel">
					<div class="field-error" id="co-phone-err"></div>
				</div>

				<div class="form-group">
					<label class="form-label" for="co-email">Email <span style="color:#8a8f9a;font-weight:400">(необязательно)</span></label>
					<input class="form-input" type="email" id="co-email" placeholder="ivan@example.com" autocomplete="email">
					<div class="field-error" id="co-email-err"></div>
					<div class="form-hint">Если укажете email — отправим подтверждение заказа</div>
				</div>

				<button class="submit-btn" id="submit-btn" onclick="submitOrder()">
					Оформить заказ →
				</button>
				<div class="submit-notice">
					Нажимая кнопку, вы соглашаетесь с <a href="/privacy_policy/" style="color:#385081">политикой конфиденциальности</a>
				</div>
			</div>
		</div>

		<!-- Итог -->
		<div class="checkout-summary">
			<div class="checkout-summary__title">Ваш заказ</div>
			<div id="co-items-list"></div>
			<hr class="summary-divider">
			<div class="summary-line"><span>Товаров</span><span id="co-count"></span></div>
			<div class="summary-line"><span>Скидки</span><span id="co-discount" style="color:#2e7d32"></span></div>
			<div class="summary-line summary-line--total"><span>К оплате</span><span id="co-total"></span></div>
		</div>
	</div>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/foot.html'); ?>
<script>
function fmtPrice(n) {
	return n.toLocaleString('ru-RU') + ' ₽';
}

function _e(s) {
	return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function initPage() {
	const items = cartLoad();

	if (!items.length) {
		document.getElementById('empty-redirect').style.display = 'block';
		return;
	}

	document.getElementById('checkout-layout').style.display = 'grid';
	renderSummary(items);

	// Маски
	applyPhoneMask(document.getElementById('co-phone'));
	applyNameMask(document.getElementById('co-name'));

	// Валидация на лету
	document.getElementById('co-name').addEventListener('blur', () => validateField('name'));
	document.getElementById('co-phone').addEventListener('blur', () => validateField('phone'));
	document.getElementById('co-email').addEventListener('blur', () => validateField('email'));
}

function renderSummary(items) {
	const list = document.getElementById('co-items-list');
	let original = 0, total = 0, discount = 0;

	list.innerHTML = items.map(item => {
		const eff = itemEffectivePrice(item);
		original += item.price * item.qty;
		total    += eff * item.qty;
		discount += (item.price - eff) * item.qty;
		const img = item.image
			? `<img class="order-item__img" src="${_e(item.image)}" alt="">`
			: `<div class="order-item__img-ph">📦</div>`;
		return `<div class="order-item">
			${img}
			<div class="order-item__name">${_e(item.name)}<div class="order-item__qty">× ${item.qty}</div></div>
			<div class="order-item__price">${fmtPrice(eff * item.qty)}</div>
		</div>`;
	}).join('');

	const count = items.reduce((s, i) => s + i.qty, 0);
	document.getElementById('co-count').textContent   = count + ' шт.';
	document.getElementById('co-discount').textContent = discount > 0 ? '−' + fmtPrice(discount) : '—';
	document.getElementById('co-total').textContent    = fmtPrice(total);
}

function validateField(field) {
	const nameEl  = document.getElementById('co-name');
	const phoneEl = document.getElementById('co-phone');
	const emailEl = document.getElementById('co-email');

	if (field === 'name') {
		if (!validateName(nameEl.value)) {
			setError('co-name', 'co-name-err', 'Только буквы и пробел, минимум 2 символа');
			return false;
		}
		clearError('co-name', 'co-name-err');
	}
	if (field === 'phone') {
		if (!validatePhone(phoneEl.value)) {
			setError('co-phone', 'co-phone-err', 'Введите корректный российский номер');
			return false;
		}
		clearError('co-phone', 'co-phone-err');
	}
	if (field === 'email') {
		const v = emailEl.value.trim();
		if (v !== '' && !validateEmail(v)) {
			setError('co-email', 'co-email-err', 'Некорректный email');
			return false;
		}
		clearError('co-email', 'co-email-err');
	}
	return true;
}

function setError(inputId, errId, msg) {
	document.getElementById(inputId).classList.add('is-error');
	document.getElementById(inputId).classList.remove('is-ok');
	const el = document.getElementById(errId);
	el.textContent = msg;
	el.style.display = 'block';
}

function clearError(inputId, errId) {
	document.getElementById(inputId).classList.remove('is-error');
	document.getElementById(inputId).classList.add('is-ok');
	document.getElementById(errId).style.display = 'none';
}

function submitOrder() {
	// Валидируем все поля
	const vName  = validateField('name');
	const vPhone = validateField('phone');
	const vEmail = validateField('email');

	// Хотя бы телефон или email
	const phone = document.getElementById('co-phone').value.trim();
	const email = document.getElementById('co-email').value.trim();
	if (!phone && !email) {
		setError('co-phone', 'co-phone-err', 'Укажите телефон или email');
		return;
	}

	if (!vName || !vPhone || !vEmail) return;

	const btn = document.getElementById('submit-btn');
	btn.disabled = true;
	btn.textContent = 'Оформляем заказ…';

	document.getElementById('global-error').style.display = 'none';

	fetch('/cart/order_create.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({
			name:   document.getElementById('co-name').value.trim(),
			phone:  phone,
			email:  email,
			items:  cartLoad(),
			source: 'cart',
		}),
	})
	.then(r => r.json())
	.then(data => {
		if (data.ok) {
			cartClear();
			window.location.href = '/order/?id=' + data.order_id;
		} else {
			btn.disabled = false;
			btn.textContent = 'Оформить заказ →';
			const err = document.getElementById('global-error');
			err.textContent = data.error || 'Произошла ошибка. Попробуйте ещё раз.';
			err.style.display = 'block';
		}
	})
	.catch(() => {
		btn.disabled = false;
		btn.textContent = 'Оформить заказ →';
		const err = document.getElementById('global-error');
		err.textContent = 'Ошибка соединения. Проверьте интернет и попробуйте ещё раз.';
		err.style.display = 'block';
	});
}

document.addEventListener('DOMContentLoaded', initPage);
</script>
</body>
</html>
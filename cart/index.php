<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | Корзина</title>
	<meta name="description" content="Корзина интернет-магазина WERGRAUF">
	<meta name="robots" content="noindex">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/source_css/main.css" media="all">
	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; font-family: "Roboto", Arial, sans-serif; color: #36393e; }

	.cart-page { max-width: 1100px; margin: 0 auto; padding: 32px 20px 60px; }
	.cart-page__title { font-size: 28px; font-weight: 700; margin: 0 0 8px; }
	.cart-page__count { font-size: 14px; color: #8a8f9a; margin-bottom: 32px; }

	/* Лейаут */
	.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
	@media(max-width:900px){ .cart-layout { grid-template-columns: 1fr; } }

	/* Товары */
	.cart-items { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.06); }

	.cart-item { display: grid; grid-template-columns: 80px 1fr auto; gap: 16px; padding: 20px; border-bottom: 1px solid #f0f0f0; align-items: center; }
	.cart-item:last-child { border-bottom: none; }

	.cart-item__img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 1px solid #f0f0f0; }
	.cart-item__img-placeholder { width: 80px; height: 80px; border-radius: 10px; background: #f4f5f7; display: flex; align-items: center; justify-content: center; font-size: 28px; border: 1px solid #f0f0f0; }

	.cart-item__info { min-width: 0; }
	.cart-item__name { font-size: 14px; font-weight: 500; line-height: 1.4; margin-bottom: 4px; }
	.cart-item__name a { color: inherit; text-decoration: none; }
	.cart-item__name a:hover { text-decoration: underline; }
	.cart-item__article { font-size: 12px; color: #8a8f9a; margin-bottom: 8px; }

	/* Промокод строка */
	.cart-item__promo { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 6px; }
	.promo-input { padding: 5px 10px; border: 1px solid #e2e4e9; border-radius: 6px; font-family: inherit; font-size: 12px; width: 130px; }
	.promo-input:focus { outline: none; border-color: #4a4f59; }
	.promo-btn { padding: 5px 10px; background: #4a4f59; color: #fff; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-family: inherit; }
	.promo-btn:hover { background: #385081; }
	.promo-badge { background: #e8f5e9; color: #2e7d32; font-size: 11px; padding: 3px 8px; border-radius: 5px; font-weight: 600; }
	.promo-error { font-size: 11px; color: #c82a20; }

	.cart-item__right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }

	/* Количество */
	.qty-control { display: flex; align-items: center; gap: 0; border: 1px solid #e2e4e9; border-radius: 8px; overflow: hidden; }
	.qty-btn { width: 32px; height: 32px; border: none; background: none; cursor: pointer; font-size: 16px; color: #36393e; display: flex; align-items: center; justify-content: center; transition: background .15s; }
	.qty-btn:hover { background: #f4f5f7; }
	.qty-val { width: 36px; text-align: center; font-size: 14px; font-weight: 500; border: none; outline: none; font-family: inherit; }

	/* Цена */
	.cart-item__price { text-align: right; }
	.cart-item__price-current { font-size: 16px; font-weight: 700; }
	.cart-item__price-old { font-size: 12px; color: #9e9e9e; text-decoration: line-through; }
	.cart-item__price-discount { font-size: 11px; color: #2e7d32; font-weight: 600; }

	.cart-item__del { background: none; border: none; cursor: pointer; color: #c5c8d0; font-size: 18px; padding: 4px; transition: color .15s; }
	.cart-item__del:hover { color: #c82a20; }

	/* Пустая корзина */
	.cart-empty { text-align: center; padding: 64px 20px; }
	.cart-empty__icon { font-size: 56px; margin-bottom: 16px; }
	.cart-empty__title { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
	.cart-empty__text { font-size: 14px; color: #8a8f9a; margin-bottom: 24px; }

	/* Итог */
	.cart-summary { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06); position: sticky; top: 24px; }
	.cart-summary__title { font-size: 16px; font-weight: 700; margin-bottom: 20px; }

	.summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; color: #6b6b6b; }
	.summary-row--total { font-size: 18px; font-weight: 700; color: #36393e; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0; }

	.checkout-btn { width: 100%; height: 52px; background: #36393e; color: #fff; border: none; border-radius: 10px; font-family: inherit; font-size: 16px; font-weight: 600; cursor: pointer; transition: background .2s; margin-top: 20px; }
	.checkout-btn:hover { background: #1f2226; }
	.checkout-btn:disabled { opacity: .5; cursor: not-allowed; }

	.delivery-info { margin-top: 16px; padding: 12px; background: #f4f5f7; border-radius: 8px; font-size: 12px; color: #6b6b6b; line-height: 1.5; }
	.delivery-info a { color: #385081; }

	/* Хлебные крошки */
	.breadcrumbs { font-size: 13px; color: #8a8f9a; margin-bottom: 20px; }
	.breadcrumbs a { color: inherit; text-decoration: none; }
	.breadcrumbs a:hover { text-decoration: underline; }
	.breadcrumbs span { margin: 0 6px; }

	@media(max-width:600px){
		.cart-item { grid-template-columns: 64px 1fr; }
		.cart-item__img, .cart-item__img-placeholder { width: 64px; height: 64px; }
		.cart-item__right { flex-direction: row; align-items: center; grid-column: 1 / -1; }
	}
	</style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/head.html'); ?>

<div class="cart-page">
	<nav class="breadcrumbs">
		<a href="/">Главная</a>
		<span>—</span>
		<span>Корзина</span>
	</nav>

	<h1 class="cart-page__title">Корзина</h1>
	<div class="cart-page__count" id="cart-page-count"></div>

	<!-- Пустая корзина -->
	<div class="cart-empty" id="cart-empty" style="display:none">
		<div class="cart-empty__icon">🛒</div>
		<div class="cart-empty__title">Корзина пуста</div>
		<div class="cart-empty__text">Добавьте товары из каталога</div>
		<a href="/shower_system/" style="display:inline-block;background:#36393e;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600">Перейти в каталог</a>
	</div>

	<!-- Корзина с товарами -->
	<div class="cart-layout" id="cart-layout" style="display:none">
		<div>
			<div class="cart-items" id="cart-items-list"></div>
		</div>

		<div class="cart-summary">
			<div class="cart-summary__title">Итого</div>
			<div class="summary-row"><span>Товаров</span><span id="sum-count">0</span></div>
			<div class="summary-row"><span>Стоимость</span><span id="sum-price">0 ₽</span></div>
			<div class="summary-row"><span>Скидки по промокодам</span><span id="sum-discount" style="color:#2e7d32">—</span></div>
			<div class="summary-row summary-row--total"><span>К оплате</span><span id="sum-total">0 ₽</span></div>

			<button class="checkout-btn" id="checkout-btn" onclick="goCheckout()">
				Оформить заказ
			</button>

			<div class="delivery-info">
				Курьерская доставка по Москве.<br>
				Бесплатно при заказе от 15 000 ₽.<br>
				В регионы — транспортной компанией.<br>
				<a href="/delivery/">Подробнее о доставке</a>
			</div>
		</div>
	</div>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/source_include/foot.html'); ?>
<script>
function fmtPrice(n) {
	return n.toLocaleString('ru-RU') + ' ₽';
}

function renderCart() {
	const items   = cartLoad();
	const empty   = document.getElementById('cart-empty');
	const layout  = document.getElementById('cart-layout');
	const counter = document.getElementById('cart-page-count');
	const list    = document.getElementById('cart-items-list');

	if (!items.length) {
		empty.style.display  = 'block';
		layout.style.display = 'none';
		counter.textContent  = '';
		return;
	}

	empty.style.display  = 'none';
	layout.style.display = 'grid';
	counter.textContent  = items.length + ' ' + plural(items.length, 'товар', 'товара', 'товаров');

	list.innerHTML = items.map(item => renderItem(item)).join('');
	updateSummary(items);
}

function renderItem(item) {
	const effPrice  = itemEffectivePrice(item);
	const lineTotal = effPrice * item.qty;
	const hasDisc   = item.promo_applied && item.discount_percent;
	const img       = item.image
		? `<img class="cart-item__img" src="${_e(item.image)}" alt="${_e(item.name)}" loading="lazy">`
		: `<div class="cart-item__img-placeholder">📦</div>`;
	const link      = item.section_url
		? `<a href="${_e(item.section_url)}${_e(item.slug)}/">${_e(item.name)}</a>`
		: _e(item.name);

	let promo = '';
	if (item.promo_applied) {
		promo = `<div class="cart-item__promo">
			<span class="promo-badge">✅ Промокод применён −${_e(item.discount_percent)}%</span>
		</div>`;
	} else if (item.promo_code) {
		promo = `<div class="cart-item__promo">
			<input class="promo-input" type="text" placeholder="Промокод" id="promo-${_e(item.article)}">
			<button class="promo-btn" onclick="applyPromo('${_e(item.article)}')">Применить</button>
			<span class="promo-error" id="promo-err-${_e(item.article)}"></span>
		</div>`;
	}

	return `<div class="cart-item" id="ci-${_e(item.article)}">
		${img}
		<div class="cart-item__info">
			<div class="cart-item__name">${link}</div>
			<div class="cart-item__article">Арт. ${_e(String(item.article))}</div>
			${promo}
		</div>
		<div class="cart-item__right">
			<div class="cart-item__price">
				<div class="cart-item__price-current">${fmtPrice(lineTotal)}</div>
				${hasDisc ? `<div class="cart-item__price-old">${fmtPrice(item.price * item.qty)}</div>
				<div class="cart-item__price-discount">−${_e(item.discount_percent)}%</div>` : ''}
			</div>
			<div class="qty-control">
				<button class="qty-btn" onclick="changeQty('${_e(item.article)}', -1)">−</button>
				<span class="qty-val">${item.qty}</span>
				<button class="qty-btn" onclick="changeQty('${_e(item.article)}', 1)">+</button>
			</div>
			<button class="cart-item__del" title="Удалить" onclick="removeItem('${_e(item.article)}')">×</button>
		</div>
	</div>`;
}

function updateSummary(items) {
	const count    = items.reduce((s, i) => s + i.qty, 0);
	let   original = 0, total = 0, discount = 0;

	items.forEach(item => {
		original += item.price * item.qty;
		const eff = itemEffectivePrice(item) * item.qty;
		total    += eff;
		discount += (item.price * item.qty) - eff;
	});

	document.getElementById('sum-count').textContent   = count + ' ' + plural(count, 'шт.', 'шт.', 'шт.');
	document.getElementById('sum-price').textContent   = fmtPrice(original);
	document.getElementById('sum-discount').textContent = discount > 0 ? '−' + fmtPrice(discount) : '—';
	document.getElementById('sum-total').textContent   = fmtPrice(total);
}

function changeQty(article, delta) {
	const items = cartLoad();
	const item  = items.find(i => i.article === article);
	if (!item) return;
	cartSetQty(article, item.qty + delta);
	renderCart();
}

function removeItem(article) {
	cartRemove(article);
	renderCart();
}

function applyPromo(article) {
	const input = document.getElementById('promo-' + article);
	const errEl = document.getElementById('promo-err-' + article);
	if (!input) return;

	const result = cartApplyPromo(article, input.value.trim());
	if (result.ok) {
		renderCart();
	} else {
		errEl.textContent = result.error;
	}
}

function goCheckout() {
	window.location.href = '/cart/checkout.php';
}

function plural(n, one, few, many) {
	const m = Math.abs(n) % 100;
	const m1 = m % 10;
	if (m > 10 && m < 20) return many;
	if (m1 === 1) return one;
	if (m1 >= 2 && m1 <= 4) return few;
	return many;
}

function _e(s) {
	return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', renderCart);
</script>
</body>
</html>
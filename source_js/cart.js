'use strict';
/* cart.js - cart logic via localStorage */

const CART_KEY = 'wg_cart';

/* ===== ECOMMERCE (Яндекс dataLayer) ===== */

// url раздела → категория для отчётов Метрики
const WG_SECTION_NAMES = {
	'/shower_system/':   'Душевые системы',
	'/kitchen_faucets/': 'Кухонные смесители',
	'/floor_faucets/':   'Напольные смесители',
	'/bath_faucets/':    'Смесители для ванны',
	'/sink_faucets/':    'Смесители для раковины',
	'/hygienic_shower/': 'Гигиенические души',
	'/accessories/':     'Аксессуары',
	'/towel_warmers/':   'Полотенцесушители',
	'/components/':      'Комплектующие',
};

// товар корзины → объект для ecommerce
function wgEcProduct(item, qty) {
	const p = {
		id:    String(item.article),
		name:  item.name,
		price: Number(item.price) || 0,
		brand: 'WERGRAUF',
	};
	const cat = WG_SECTION_NAMES[item.section_url];
	if (cat) p.category = cat;
	if (qty != null) p.quantity = qty;
	return p;
}

// пуш в dataLayer: action = add | remove | detail | purchase
function wgEcommerce(action, products, actionField) {
	window.dataLayer = window.dataLayer || [];
	const data = { products: products };
	if (actionField) data.actionField = actionField;
	window.dataLayer.push({ ecommerce: { currencyCode: 'RUB', [action]: data } });
}

/* ===== STORAGE ===== */

function cartLoad() {
	try {
		return JSON.parse(localStorage.getItem(CART_KEY)) || [];
	} catch {
		return [];
	}
}

function cartSave(items) {
	localStorage.setItem(CART_KEY, JSON.stringify(items));
	cartUpdateBadge();
}

/* ===== OPERATIONS ===== */

/* trackGoal=false — тихое добавление (один клик кладёт товар сам, цель там своя) */
function cartAdd(product, trackGoal = true) {
	const items = cartLoad();
	const idx   = items.findIndex(i => i.article === product.article);
	if (idx >= 0) {
		items[idx].qty += 1;
	} else {
		items.push({ ...product, qty: 1, promo_applied: false });
	}
	cartSave(items);
	cartShowToast(product.name);
	/* цель Метрики + ecommerce: добавление в корзину */
	if (trackGoal && typeof ym === 'function') ym(109618056, 'reachGoal', 'cart_add');
	if (trackGoal) wgEcommerce('add', [wgEcProduct(product, 1)]);
}

function cartRemove(article) {
	const items = cartLoad();
	const item  = items.find(i => i.article === article); // до удаления — нужен для ecommerce
	cartSave(items.filter(i => i.article !== article));
	if (item) wgEcommerce('remove', [wgEcProduct(item, item.qty)]);
}

function cartSetQty(article, qty) {
	const items = cartLoad();
	const idx   = items.findIndex(i => i.article === article);
	if (idx < 0) return;
	if (qty < 1) { cartRemove(article); return; }
	items[idx].qty = qty;
	cartSave(items);
}

function cartApplyPromo(article, code) {
	const items = cartLoad();
	const idx   = items.findIndex(i => i.article === article);
	if (idx < 0) return { ok: false, error: '\u0422\u043e\u0432\u0430\u0440 \u043d\u0435 \u043d\u0430\u0439\u0434\u0435\u043d' };

	const item = items[idx];
	if (!item.promo_code) return { ok: false, error: '\u0423 \u044d\u0442\u043e\u0433\u043e \u0442\u043e\u0432\u0430\u0440\u0430 \u043d\u0435\u0442 \u043f\u0440\u043e\u043c\u043e\u043a\u043e\u0434\u0430' };
	if (item.promo_code.toLowerCase() !== code.toLowerCase()) {
		return { ok: false, error: '\u041d\u0435\u0432\u0435\u0440\u043d\u044b\u0439 \u043f\u0440\u043e\u043c\u043e\u043a\u043e\u0434' };
	}
	if (item.promo_applied) return { ok: false, error: '\u041f\u0440\u043e\u043c\u043e\u043a\u043e\u0434 \u0443\u0436\u0435 \u043f\u0440\u0438\u043c\u0435\u043d\u0451\u043d' };

	items[idx].promo_applied = true;
	cartSave(items);
	return { ok: true, discount: item.discount_percent };
}

function cartClear() {
	cartSave([]);
}

function cartCount() {
	return cartLoad().reduce((sum, i) => sum + i.qty, 0);
}

/* ===== CALCULATIONS ===== */

function itemEffectivePrice(item) {
	if (item.promo_applied && item.discount_percent) {
		const pct = parseFloat(item.discount_percent) || 0;
		return Math.round(item.price * (1 - pct / 100));
	}
	return item.price;
}

function cartTotal() {
	return cartLoad().reduce((sum, i) => sum + itemEffectivePrice(i) * i.qty, 0);
}

/* ===== UI ===== */

function cartUpdateBadge() {
	const count = cartCount();
	document.querySelectorAll('.js-cart-badge').forEach(el => {
		el.textContent = count;
		el.style.display = count > 0 ? 'flex' : 'none';
		/* opacity управляется через .not-empty на родителе в main.css */
		const icon = el.closest('.cart__icon');
		if (icon) icon.classList.toggle('not-empty', count > 0);
	});
}

function cartShowToast(name) {
	let wrap = document.getElementById('cart-toast-wrap');
	if (!wrap) {
		wrap = document.createElement('div');
		wrap.id = 'cart-toast-wrap';
		wrap.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
		document.body.appendChild(wrap);
	}

	if (!document.getElementById('wg-toast-style')) {
		const s = document.createElement('style');
		s.id = 'wg-toast-style';
		s.textContent = '@keyframes wg-toast-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}@keyframes wg-toast-out{from{opacity:1}to{opacity:0;transform:translateY(10px)}}';
		document.head.appendChild(s);
	}

	const toast = document.createElement('div');
	toast.style.cssText = 'background:#1b5e20;color:#fff;padding:12px 18px;border-radius:10px;font-family:Roboto,sans-serif;font-size:14px;box-shadow:0 4px 16px rgba(0,0,0,.2);display:flex;gap:10px;align-items:center;animation:wg-toast-in .25s ease;max-width:300px;pointer-events:all;';
	toast.innerHTML = '<span>\u2705</span><div><div style="font-weight:600">\u0414\u043e\u0431\u0430\u0432\u043b\u0435\u043d\u043e \u0432 \u043a\u043e\u0440\u0437\u0438\u043d\u0443</div><div style="font-size:12px;opacity:.85;margin-top:2px">' + _escHtml(name) + '</div></div>';

	wrap.appendChild(toast);
	setTimeout(() => {
		toast.style.animation = 'wg-toast-out .2s ease forwards';
		toast.addEventListener('animationend', () => toast.remove(), { once: true });
	}, 3000);
}

function _escHtml(str) {
	return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ===== ONE CLICK POPUP ===== */

function oneClickOpen(product) {
	const existing = cartLoad().find(i => i.article === product.article);
	if (!existing) cartAdd(product, false); // тихо: цель one_click сработает на /order/

	document.getElementById('one-click-overlay')?.remove();

	if (!document.getElementById('one-click-style')) {
		const s = document.createElement('style');
		s.id = 'one-click-style';
		s.textContent = [
			'#one-click-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;animation:wg-toast-in .2s ease;}',
			'#one-click-modal{background:#fff;border-radius:16px;padding:28px;max-width:400px;width:100%;font-family:Roboto,sans-serif;box-shadow:0 12px 40px rgba(0,0,0,.2);position:relative;}',
			'.oc-title{font-size:18px;font-weight:700;margin-bottom:6px;color:#36393e;}',
			'.oc-sub{font-size:13px;color:#8a8f9a;margin-bottom:20px;}',
			'.oc-label{display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:#36393e;}',
			'.oc-input{width:100%;padding:10px 14px;border:1.5px solid #e2e4e9;border-radius:8px;font-family:inherit;font-size:14px;color:#36393e;margin-bottom:14px;transition:border-color .2s;box-sizing:border-box;}',
			'.oc-input:focus{outline:none;border-color:#4a4f59;}',
			'.oc-input.is-error{border-color:#c82a20;}',
			'.oc-error{font-size:12px;color:#c82a20;margin-top:-10px;margin-bottom:10px;display:none;}',
			'.oc-btn{width:100%;height:48px;background:#36393e;color:#fff;border:none;border-radius:8px;font-family:inherit;font-size:15px;font-weight:600;cursor:pointer;transition:background .2s;}',
			'.oc-btn:hover{background:#1f2226;}',
			'.oc-btn:disabled{opacity:.6;cursor:not-allowed;}',
			'.oc-close{position:absolute;top:16px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#8a8f9a;line-height:1;}',
		].join('');
		document.head.appendChild(s);
	}

	const overlay = document.createElement('div');
	overlay.id = 'one-click-overlay';

	const productJson = JSON.stringify(product).replace(/</g, '\\u003c').replace(/"/g, '&quot;');

	overlay.innerHTML =
		'<div id="one-click-modal">' +
		'<button class="oc-close" onclick="oneClickClose()">\xd7</button>' +
		'<div class="oc-title">\u041a\u0443\u043f\u0438\u0442\u044c \u0432 \u043e\u0434\u0438\u043d \u043a\u043b\u0438\u043a</div>' +
		'<div class="oc-sub">' + _escHtml(product.name) + '</div>' +
		'<label class="oc-label">\u0412\u0430\u0448\u0435 \u0438\u043c\u044f *</label>' +
		'<input class="oc-input" type="text" id="oc-name" placeholder="\u0418\u0432\u0430\u043d \u0418\u0432\u0430\u043d\u043e\u0432" autocomplete="name">' +
		'<div class="oc-error" id="oc-name-err"></div>' +
		'<label class="oc-label">\u0422\u0435\u043b\u0435\u0444\u043e\u043d *</label>' +
		'<input class="oc-input" type="tel" id="oc-phone" placeholder="+7 (___) ___-__-__" autocomplete="tel">' +
		'<div class="oc-error" id="oc-phone-err"></div>' +
		'<button class="oc-btn" id="oc-submit" onclick="oneClickSubmit(' + productJson + ')">' +
		'\u041e\u0444\u043e\u0440\u043c\u0438\u0442\u044c \u0437\u0430\u043a\u0430\u0437' +
		'</button>' +
		'</div>';

	document.body.appendChild(overlay);
	overlay.addEventListener('click', e => { if (e.target === overlay) oneClickClose(); });

	applyPhoneMask(document.getElementById('oc-phone'));
	applyNameMask(document.getElementById('oc-name'));
	setTimeout(() => document.getElementById('oc-name').focus(), 50);
}

function oneClickClose() {
	document.getElementById('one-click-overlay')?.remove();
}

function oneClickSubmit(product) {
	const nameEl  = document.getElementById('oc-name');
	const phoneEl = document.getElementById('oc-phone');
	let valid = true;

	if (!validateName(nameEl.value)) {
		showFieldError('oc-name', 'oc-name-err', '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u0438\u043c\u044f (\u0442\u043e\u043b\u044c\u043a\u043e \u0431\u0443\u043a\u0432\u044b \u0438 \u043f\u0440\u043e\u0431\u0435\u043b)');
		valid = false;
	} else {
		clearFieldError('oc-name', 'oc-name-err');
	}

	if (!validatePhone(phoneEl.value)) {
		showFieldError('oc-phone', 'oc-phone-err', '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043a\u043e\u0440\u0440\u0435\u043a\u0442\u043d\u044b\u0439 \u043d\u043e\u043c\u0435\u0440 \u0442\u0435\u043b\u0435\u0444\u043e\u043d\u0430');
		valid = false;
	} else {
		clearFieldError('oc-phone', 'oc-phone-err');
	}

	if (!valid) return;

	const btn = document.getElementById('oc-submit');
	btn.disabled = true;
	btn.textContent = '\u041e\u0444\u043e\u0440\u043c\u043b\u044f\u0435\u043c\u2026';

	const cartItems = cartLoad().filter(i => i.article === product.article);

	fetch('/cart/order_create.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({
			name:   nameEl.value.trim(),
			phone:  phoneEl.value.trim(),
			email:  '',
			items:  cartItems,
			source: 'one_click',
		}),
	})
	.then(r => r.json())
	.then(data => {
		if (data.ok) {
			oneClickClose();
			window.location.href = '/order/?id=' + data.order_id;
		} else {
			btn.disabled = false;
			btn.textContent = '\u041e\u0444\u043e\u0440\u043c\u0438\u0442\u044c \u0437\u0430\u043a\u0430\u0437';
			alert(data.error || '\u041e\u0448\u0438\u0431\u043a\u0430. \u041f\u043e\u043f\u0440\u043e\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.');
		}
	})
	.catch(() => {
		btn.disabled = false;
		btn.textContent = '\u041e\u0444\u043e\u0440\u043c\u0438\u0442\u044c \u0437\u0430\u043a\u0430\u0437';
		alert('\u041e\u0448\u0438\u0431\u043a\u0430 \u0441\u043e\u0435\u0434\u0438\u043d\u0435\u043d\u0438\u044f. \u041f\u043e\u043f\u0440\u043e\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.');
	});
}

/* ===== MASKS & VALIDATION ===== */

function applyPhoneMask(input) {
	input.addEventListener('input', function() {
		let v = this.value.replace(/\D/g, '');
		if (v.startsWith('8')) v = '7' + v.slice(1);
		if (!v.startsWith('7') && v.length > 0) v = '7' + v;
		v = v.slice(0, 11);
		let out = '';
		if (v.length > 0)  out = '+7';
		if (v.length > 1)  out += ' (' + v.slice(1, 4);
		if (v.length >= 4) out += ') ' + v.slice(4, 7);
		if (v.length >= 7) out += '-' + v.slice(7, 9);
		if (v.length >= 9) out += '-' + v.slice(9, 11);
		this.value = out;
	});
	input.addEventListener('keydown', function(e) {
		if (e.key === 'Backspace' && this.value === '+7') {
			this.value = '';
			e.preventDefault();
		}
	});
}

function applyNameMask(input) {
	input.addEventListener('input', function() {
		this.value = this.value.replace(/[^\u0430-\u044f\u0451\u0410-\u042f\u0401a-zA-Z ]/g, '');
	});
}

function validateName(v) {
	v = v.trim();
	return v.length >= 2 && /^[\u0430-\u044f\u0451\u0410-\u042f\u0401a-zA-Z ]+$/.test(v);
}

function validatePhone(v) {
	const digits = v.replace(/\D/g, '');
	return digits.length === 11 && digits.startsWith('7');
}

function validateEmail(v) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
}

function showFieldError(inputId, errId, msg) {
	document.getElementById(inputId)?.classList.add('is-error');
	const err = document.getElementById(errId);
	if (err) { err.textContent = msg; err.style.display = 'block'; }
}

function clearFieldError(inputId, errId) {
	document.getElementById(inputId)?.classList.remove('is-error');
	const err = document.getElementById(errId);
	if (err) { err.style.display = 'none'; }
}

/* ===== CATALOG ===== */

function catalogAddToCart(product, e) {
	e.preventDefault();
	e.stopPropagation();
	cartAdd(product);
}

/* ===== INIT ===== */
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', cartUpdateBadge);
} else {
	cartUpdateBadge();
}
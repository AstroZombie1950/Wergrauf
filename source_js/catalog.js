'use strict';
/* catalog.js — универсальный каталог
   Использует PRODUCTS_DATA, SECTION_URL, ACTIVE_FILTERS из шаблона */

const filtersState = {
	modes:       new Set(),
	design:      new Set(),
	price:       [0, 100000],
	models:      new Set(),
	colors:      new Set(),
	collections: new Set(),
	heaterType:  new Set(),
	heaterShape: new Set(),
	embedded:    false,
	thermostat:  false,
	builtIn:     false,
};

let allProducts      = [];
let filteredProducts = [];

const grid      = document.getElementById('products-grid');
const counter   = document.getElementById('product-count');
const minSlider = document.querySelector('.price-min');
const maxSlider = document.querySelector('.price-max');
const minValue  = document.querySelector('.price-value-min');
const maxValue  = document.querySelector('.price-value-max');
const sliderWrap= document.querySelector('.price-slider-wrapper');

function init() {
	if (!Array.isArray(PRODUCTS_DATA) || !PRODUCTS_DATA.length) {
		grid.innerHTML = '<div class="products-empty">Товары не найдены</div>';
		return;
	}

	allProducts = PRODUCTS_DATA.map(p => ({
		...p,
		_model:       (p.model              || '').toLowerCase(),
		_color:       (p.spec_color         || '').toLowerCase(),
		_collection:  (p.spec_collection    || '').toLowerCase(),
		_heaterType:  (p.spec_heater_type   || '').toLowerCase(),
		_heaterShape: (p.spec_heater_shape  || '').toLowerCase(),
		_embedded:    (p.spec_built_in      || '').toLowerCase() === 'да',
		_thermostat:  (p.spec_thermostat    || '').toLowerCase() === 'да',
		_builtIn:     (p.spec_built_in      || '').toLowerCase() === 'да',
	}));

	// Реальный диапазон цен
	const prices   = allProducts.map(p => p.price);
	const priceMin = Math.floor(Math.min(...prices) / 500) * 500;
	const priceMax = Math.ceil(Math.max(...prices)  / 500) * 500;

	minSlider.min = maxSlider.min = priceMin;
	minSlider.max = maxSlider.max = priceMax;
	minSlider.value = priceMin;
	maxSlider.value = priceMax;
	filtersState.price = [priceMin, priceMax];
	updatePriceUI();

	buildFilters();
	bindEvents();
	applyFilters();
}

// --- Динамические фильтры ---
function buildFilters() {
	if (ACTIVE_FILTERS.includes('model')) {
		const models = uniq(allProducts.map(p => p._model)).sort();
		renderList('filter-model-list', models, 'model', v => v.toUpperCase());
	}
	if (ACTIVE_FILTERS.includes('color')) {
		const colors = uniq(allProducts.map(p => p._color)).sort();
		renderList('filter-color-list', colors, 'color', capitalize);
	}
	if (ACTIVE_FILTERS.includes('collection')) {
		const cols = uniq(allProducts.map(p => p._collection)).sort();
		renderList('filter-collection-list', cols, 'collection', capitalize);
	}
	if (ACTIVE_FILTERS.includes('modes')) {
		const modes = uniq(allProducts.map(p => p._modes)).sort((a,b) => a-b);
		renderList('filter-modes-list', modes, 'modes', v => v + ' режима');
	}
	if (ACTIVE_FILTERS.includes('design')) {
		const designs = uniq(allProducts.map(p => p._design)).sort();
		renderList('filter-design-list', designs, 'design', capitalize);
	}
	if (ACTIVE_FILTERS.includes('heater_type')) {
		const types = uniq(allProducts.map(p => p._heaterType)).sort();
		renderList('filter-heater-type-list', types, 'heater_type', capitalize);
	}
	if (ACTIVE_FILTERS.includes('heater_shape')) {
		const shapes = uniq(allProducts.map(p => p._heaterShape)).sort();
		renderList('filter-heater-shape-list', shapes, 'heater_shape', capitalize);
	}
}

function renderList(id, values, type, labelFn) {
	const el = document.getElementById(id);
	if (!el) return;
	const filtered = values.filter(Boolean);
	if (!filtered.length) { el.closest('.filter')?.style.setProperty('display', 'none'); return; }
	el.innerHTML = filtered.map(v => `
		<label class="filter-checkbox">
			<input type="checkbox" data-filter="${type}" value="${esc(v)}">
			<span class="checkbox-ui"></span>
			<span class="checkbox-label">${esc(labelFn(String(v)))}</span>
		</label>
	`).join('');
}

// --- Фильтрация ---
function applyFilters() {
	filteredProducts = allProducts.filter(p => {
		if (p.price < filtersState.price[0] || p.price > filtersState.price[1]) return false;
		if (filtersState.models.size      && !filtersState.models.has(p._model))        return false;
		if (filtersState.colors.size      && !filtersState.colors.has(p._color))        return false;
		if (filtersState.collections.size && !filtersState.collections.has(p._collection)) return false;
		if (filtersState.modes.size       && !filtersState.modes.has(p._modes))        return false;
		if (filtersState.design.size       && !filtersState.design.has(p._design))       return false;
		if (filtersState.heaterType.size  && !filtersState.heaterType.has(p._heaterType))  return false;
		if (filtersState.heaterShape.size && !filtersState.heaterShape.has(p._heaterShape)) return false;
		if (filtersState.embedded   && !p._embedded)   return false;
		if (filtersState.thermostat && !p._thermostat) return false;
		if (filtersState.builtIn    && !p._builtIn)    return false;
		return true;
	});
	renderProducts(filteredProducts);
	if (counter) counter.textContent = filteredProducts.length;
}

// --- Рендер карточек ---
function renderProducts(list) {
	if (!list.length) {
		grid.innerHTML = '<div class="products-empty">Товары не найдены. Попробуйте изменить фильтры.</div>';
		return;
	}
	grid.innerHTML = list.map(p => {
		const url      = `${SECTION_URL}${p.slug}/`;
		const discount = p.old_price > p.price
			? Math.round((1 - p.price / p.old_price) * 100) + '%' : null;
		return `
			<article class="product-card">
				<a href="${url}" class="product-link" aria-label="${esc(p.name)}"></a>
				${discount ? `<span class="product-badge-discount">${discount}</span>` : ''}
				<div class="product-image">
					<img src="${esc(p.image || '')}" alt="${esc(p.name)}" loading="lazy" width="600" height="800">
				</div>
				<h3 class="product-title">${esc(p.name)}</h3>
				<div class="product-article">арт. ${esc(String(p.article))}</div>
				<div class="product-price-wrap">
					<span class="product-price">${fmt(p.price)}</span>
					${p.old_price > p.price ? `<span class="product-price-old">${fmt(p.old_price)}</span>` : ''}
				</div>
				<button class="product-buy" type="button" onclick="catalogAddToCart(${JSON.stringify({
						article: String(p.article),
						name: p.name,
						price: p.price,
						old_price: p.old_price || 0,
						image: p.image || '',
						slug: p.slug || '',
						section_url: SECTION_URL,
						promo_code: p.promo_code || '',
						discount_percent: p.discount_percent || '',
					}).replace(/"/g,'&quot;')},event)">Купить</button>
			</article>
		`;
	}).join('');
}

// --- Слайдер цены ---
function updatePriceUI() {
	const min = Number(minSlider.value);
	const max = Number(maxSlider.value);
	if (max <= min) { (document.activeElement === minSlider ? minSlider : maxSlider).value = min === max ? min : (document.activeElement === minSlider ? max : min); }
	minValue.textContent = fmt(Number(minSlider.value));
	maxValue.textContent = fmt(Number(maxSlider.value));
	const minPct = ((minSlider.value - minSlider.min) / (minSlider.max - minSlider.min)) * 100;
	const maxPct = ((maxSlider.value - maxSlider.min) / (maxSlider.max - maxSlider.min)) * 100;
	sliderWrap.style.setProperty('--left',  `${minPct}%`);
	sliderWrap.style.setProperty('--right', `${maxPct}%`);
	filtersState.price = [Number(minSlider.value), Number(maxSlider.value)];
	applyFilters();
}

// --- События ---
function bindEvents() {
	minSlider.addEventListener('input', updatePriceUI);
	maxSlider.addEventListener('input', updatePriceUI);

	document.querySelector('.catalog-filters').addEventListener('change', e => {
		const input  = e.target;
		if (input.type !== 'checkbox') return;
		const filter = input.dataset.filter;
		const value  = input.value;
		const add    = input.checked;

		if (filter === 'model')       toggleSet(filtersState.models,      value, add);
		if (filter === 'color')       toggleSet(filtersState.colors,      value, add);
		if (filter === 'collection')  toggleSet(filtersState.collections, value, add);
		if (filter === 'modes')        toggleSet(filtersState.modes,       value, add);
		if (filter === 'design')       toggleSet(filtersState.design,      value, add);
		if (filter === 'heater_type') toggleSet(filtersState.heaterType,  value, add);
		if (filter === 'heater_shape')toggleSet(filtersState.heaterShape, value, add);
		if (value === 'embedded')     filtersState.embedded   = add;
		if (value === 'thermostat')   filtersState.thermostat = add;
		if (value === 'built_in')     filtersState.builtIn    = add;
		applyFilters();
	});

	document.querySelectorAll('.filter-title').forEach(btn =>
		btn.addEventListener('click', () => btn.closest('.filter').classList.toggle('is-open'))
	);

	document.querySelectorAll('[data-sort]').forEach(btn =>
		btn.addEventListener('click', () => {
			const dir = btn.dataset.sort;
			filteredProducts.sort((a, b) => dir === 'asc' ? a.price - b.price : b.price - a.price);
			renderProducts(filteredProducts);
		})
	);

	document.querySelector('.filters-reset').addEventListener('click', () => {
		filtersState.models.clear();
		filtersState.colors.clear();
		filtersState.collections.clear();
		filtersState.modes.clear();
		filtersState.design.clear();
		filtersState.heaterType.clear();
		filtersState.heaterShape.clear();
		filtersState.embedded = filtersState.thermostat = filtersState.builtIn = false;
		document.querySelectorAll('.catalog-filters input[type="checkbox"]').forEach(i => i.checked = false);
		minSlider.value = minSlider.min;
		maxSlider.value = maxSlider.max;
		updatePriceUI();
	});

	// Мобильные фильтры
	const panel    = document.querySelector('.catalog-filters');
	const openBtn  = document.querySelector('.filters-toggle');
	const closeBtn = document.querySelector('.filters-close');
	openBtn?.addEventListener('click',  () => { panel.classList.add('is-mobile-open');    document.body.style.overflow = 'hidden'; });
	closeBtn?.addEventListener('click', () => { panel.classList.remove('is-mobile-open'); document.body.style.overflow = ''; });
}

// --- Корзина ---
function catalogAddToCart(product, e) {
	e.preventDefault();
	e.stopPropagation();
	if (typeof cartAdd === 'function') {
		cartAdd(product);
	}
}

// --- Утилиты ---
function toggleSet(set, v, add) { add ? set.add(v) : set.delete(v); }
function uniq(arr) { return [...new Set(arr.filter(Boolean))]; }
function fmt(v) { return Number(v).toLocaleString('ru-RU') + ' ₽'; }
function capitalize(s) { return s ? s[0].toUpperCase() + s.slice(1) : s; }
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

document.addEventListener('DOMContentLoaded', init);
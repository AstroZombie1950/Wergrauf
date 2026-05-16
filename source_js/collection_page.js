/* collection_page.js — каталог душевых систем
   Данные берём из PRODUCTS_DATA (серверная вставка в index.php) */

'use strict';

// --- Состояние фильтров ---
const filtersState = {
	price:      [0, 100000],
	models:     new Set(),
	colors:     new Set(),
	modes:      new Set(),
	design:     new Set(),
	collection: new Set(),
	embedded:   false,
	thermostat: false,
};

let allProducts     = [];
let filteredProducts = [];

// --- DOM ---
const grid       = document.getElementById('products-grid');
const counter    = document.getElementById('product-count');
const minSlider  = document.querySelector('.price-min');
const maxSlider  = document.querySelector('.price-max');
const minValue   = document.querySelector('.price-value-min');
const maxValue   = document.querySelector('.price-value-max');
const sliderWrap = document.querySelector('.price-slider-wrapper');

// --- Инициализация ---
function init() {
	if (!Array.isArray(PRODUCTS_DATA) || !PRODUCTS_DATA.length) {
		grid.innerHTML = '<div class="products-empty">Товары не найдены</div>';
		return;
	}

	allProducts = PRODUCTS_DATA.map(p => ({
		...p,
		// нормализуем для фильтров
		_model:      (p.model      || '').toLowerCase(),
		_color:      (p.spec_color || '').toLowerCase(),
		_modes:      parseInt(p.spec_modes_count) || 0,
		_design:     (p.spec_design      || '').toLowerCase(),
		_collection: (p.spec_collection  || '').toLowerCase(),
		_embedded:   (p.spec_built_in    || '').toLowerCase() === 'да',
		_thermostat: (p.spec_thermostat  || '').toLowerCase() === 'да',
	}));

	// Устанавливаем реальный диапазон цен
	const prices = allProducts.map(p => p.price);
	const priceMin = Math.floor(Math.min(...prices) / 500) * 500;
	const priceMax = Math.ceil(Math.max(...prices)  / 500) * 500;

	minSlider.min   = priceMin;
	minSlider.max   = priceMax;
	minSlider.value = priceMin;
	maxSlider.min   = priceMin;
	maxSlider.max   = priceMax;
	maxSlider.value = priceMax;

	filtersState.price = [priceMin, priceMax];
	updatePriceUI();

	buildDynamicFilters();
	bindEvents();
	applyFilters();
}

// --- Динамические фильтры из данных ---
function buildDynamicFilters() {
	const models     = [...new Set(allProducts.map(p => p._model).filter(Boolean))].sort();
	const colors     = [...new Set(allProducts.map(p => p._color).filter(Boolean))].sort();
	const modes      = [...new Set(allProducts.map(p => p._modes).filter(Boolean))].sort((a,b) => a - b);
	const designs    = [...new Set(allProducts.map(p => p._design).filter(Boolean))].sort();
	const collections= [...new Set(allProducts.map(p => p._collection).filter(Boolean))].sort();

	renderCheckboxList('filter-model-list',      models,      'model',      v => v.toUpperCase());
	renderCheckboxList('filter-color-list',      colors,      'color',      v => capitalize(v));
	renderCheckboxList('filter-modes-list',      modes,       'modes',      v => v + ' режима');
	renderCheckboxList('filter-design-list',     designs,     'design',     v => capitalize(v));
	renderCheckboxList('filter-collection-list', collections, 'collection', v => capitalize(v));
}

function renderCheckboxList(containerId, values, type, labelFn) {
	const container = document.getElementById(containerId);
	if (!container) return;

	if (!values.length) {
		container.closest('.filter').style.display = 'none';
		return;
	}

	container.innerHTML = values.map(v => `
		<label class="filter-checkbox">
			<input type="checkbox" data-filter="${type}" value="${v}">
			<span class="checkbox-ui"></span>
			<span class="checkbox-label">${labelFn(String(v))}</span>
		</label>
	`).join('');
}

// --- Применить фильтры ---
function applyFilters() {
	filteredProducts = allProducts.filter(p => {
		if (p.price < filtersState.price[0] || p.price > filtersState.price[1]) return false;
		if (filtersState.models.size     && !filtersState.models.has(p._model))      return false;
		if (filtersState.colors.size     && !filtersState.colors.has(p._color))      return false;
		if (filtersState.modes.size      && !filtersState.modes.has(p._modes))       return false;
		if (filtersState.design.size     && !filtersState.design.has(p._design))     return false;
		if (filtersState.collection.size && !filtersState.collection.has(p._collection)) return false;
		if (filtersState.embedded   && !p._embedded)   return false;
		if (filtersState.thermostat && !p._thermostat) return false;
		return true;
	});

	renderProducts(filteredProducts);
	updateCounter(filteredProducts.length);
}

// --- Рендер карточек ---
function renderProducts(list) {
	if (!list.length) {
		grid.innerHTML = '<div class="products-empty">Товары не найдены. Попробуйте изменить фильтры.</div>';
		return;
	}

	grid.innerHTML = list.map(p => {
		const url      = `/shower_system/${p.slug}/`;
		const discount = p.old_price > p.price
			? Math.round((1 - p.price / p.old_price) * 100) + '%'
			: null;

		return `
			<article class="product-card">
				<a href="${url}" class="product-link" aria-label="${escHtml(p.name)}"></a>
				${discount ? `<span class="product-badge-discount">${discount}</span>` : ''}
				<div class="product-image">
					<img src="${escHtml(p.image || '')}" alt="${escHtml(p.name)}" loading="lazy" width="300" height="300">
				</div>
				<h3 class="product-title">${escHtml(p.name)}</h3>
				<div class="product-article">арт. ${escHtml(String(p.article))}</div>
				<div class="product-price-wrap">
					<span class="product-price">${fmtPrice(p.price)}</span>
					${p.old_price > p.price ? `<span class="product-price-old">${fmtPrice(p.old_price)}</span>` : ''}
				</div>
				<button class="product-buy" type="button" onclick="addToCart(${p.article}, event)">Купить</button>
			</article>
		`;
	}).join('');
}

// --- Счётчик ---
function updateCounter(count) {
	if (counter) counter.textContent = count;
}

// --- Слайдер цены ---
function updatePriceUI() {
	const min = Number(minSlider.value);
	const max = Number(maxSlider.value);

	if (max <= min) {
		if (document.activeElement === minSlider) {
			minSlider.value = max;
		} else {
			maxSlider.value = min;
		}
	}

	minValue.textContent = fmtPrice(Number(minSlider.value));
	maxValue.textContent = fmtPrice(Number(maxSlider.value));

	const minPct = ((minSlider.value - minSlider.min) / (minSlider.max - minSlider.min)) * 100;
	const maxPct = ((maxSlider.value - maxSlider.min) / (maxSlider.max - maxSlider.min)) * 100;

	sliderWrap.style.setProperty('--left',  `${minPct}%`);
	sliderWrap.style.setProperty('--right', `${maxPct}%`);

	filtersState.price = [Number(minSlider.value), Number(maxSlider.value)];
	applyFilters();
}

// --- Навешиваем события ---
function bindEvents() {
	// Слайдер цены
	minSlider.addEventListener('input', updatePriceUI);
	maxSlider.addEventListener('input', updatePriceUI);

	// Чекбоксы динамических фильтров
	document.querySelector('.catalog-filters').addEventListener('change', e => {
		const input = e.target;
		if (input.type !== 'checkbox') return;

		const filter = input.dataset.filter;
		const value  = input.value;

		if (filter === 'model')      toggleSet(filtersState.models,      value, input.checked);
		if (filter === 'color')      toggleSet(filtersState.colors,      value, input.checked);
		if (filter === 'design')     toggleSet(filtersState.design,      value, input.checked);
		if (filter === 'collection') toggleSet(filtersState.collection,  value, input.checked);
		if (filter === 'modes')      toggleSet(filtersState.modes, parseInt(value), input.checked);

		// Дополнительно
		if (value === 'embedded')   filtersState.embedded   = input.checked;
		if (value === 'thermostat') filtersState.thermostat = input.checked;

		applyFilters();
	});

	// Аккордеон фильтров
	document.querySelectorAll('.filter-title').forEach(btn => {
		btn.addEventListener('click', () => {
			btn.closest('.filter').classList.toggle('is-open');
		});
	});

	// Сортировка
	document.querySelectorAll('[data-sort]').forEach(btn => {
		btn.addEventListener('click', () => {
			const dir = btn.dataset.sort;
			filteredProducts.sort((a, b) => dir === 'asc' ? a.price - b.price : b.price - a.price);
			renderProducts(filteredProducts);
		});
	});

	// Сброс фильтров
	document.querySelector('.filters-reset').addEventListener('click', resetFilters);

	// Мобильный открыть/закрыть фильтры
	const filtersPanel = document.querySelector('.catalog-filters');
	const openBtn      = document.querySelector('.filters-toggle');
	const closeBtn     = document.querySelector('.filters-close');

	openBtn.addEventListener('click', () => {
		filtersPanel.classList.add('is-mobile-open');
		document.body.style.overflow = 'hidden';
	});

	closeBtn.addEventListener('click', () => {
		filtersPanel.classList.remove('is-mobile-open');
		document.body.style.overflow = '';
	});
}

// --- Сброс фильтров ---
function resetFilters() {
	filtersState.models.clear();
	filtersState.colors.clear();
	filtersState.modes.clear();
	filtersState.design.clear();
	filtersState.collection.clear();
	filtersState.embedded   = false;
	filtersState.thermostat = false;

	document.querySelectorAll('.catalog-filters input[type="checkbox"]')
		.forEach(i => i.checked = false);

	minSlider.value = minSlider.min;
	maxSlider.value = maxSlider.max;
	updatePriceUI();
}

// --- Добавить в корзину (заглушка, подключается позже) ---
function addToCart(article, e) {
	e.preventDefault();
	e.stopPropagation();
	// TODO: логика корзины
	console.log('addToCart:', article);
}

// --- Утилиты ---
function toggleSet(set, value, add) {
	add ? set.add(value) : set.delete(value);
}

function fmtPrice(v) {
	return Number(v).toLocaleString('ru-RU') + ' ₽';
}

function capitalize(s) {
	return s ? s[0].toUpperCase() + s.slice(1) : s;
}

function escHtml(s) {
	return String(s)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

// --- Старт ---
document.addEventListener('DOMContentLoaded', init);
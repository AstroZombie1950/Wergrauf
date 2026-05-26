<?php
/* catalog_template.php — общий шаблон каталога
   Ожидает: $section, $section_name, $section_url, $filters */

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/helpers.php';
$products = load_products($section);
/* Для каталога показываем только товары в наличии и не скрытые */
$catalog_products = array_values(array_filter($products, fn($p) => (int)($p['stock'] ?? 0) > 0 && empty($p['hidden'])));
$count    = count($catalog_products);

function ct_h(string $s): string {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | <?= ct_h($section_name) ?> купить — каталог, цены</title>
	<meta name="description" content="<?= ct_h($section_name) ?> WERGRAUF — каталог моделей с ценами. Современный дизайн, быстрая доставка.">
	<meta name="robots" content="index, follow">
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?= ct_h($section_name) ?> — каталог | WERGRAUF">
	<meta property="og:url" content="https://wergrauf.ru<?= $section_url ?>">
	<meta property="og:image" content="https://wergrauf.ru/images/logo_img.png">
	<link rel="canonical" href="https://wergrauf.ru<?= $section_url ?>">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type="text/css" href="/source_css/wg.css" media="all">
	<script type="application/ld+json">
	{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
		{"@type":"ListItem","position":1,"name":"Главная","item":"https://wergrauf.ru/"},
		{"@type":"ListItem","position":2,"name":"<?= ct_h($section_name) ?>","item":"https://wergrauf.ru<?= $section_url ?>"}
	]}
	</script>
	<style>
	.catalog { max-width: 1280px; margin: 0 auto; padding: 10px 24px 30px; color: #4a4f59e6; }
	.catalog a { color: inherit; text-decoration: none; }
	.catalog-title { font-size: 32px; font-weight: 600; color: #36393e; margin: 5px 0 8px; }
	.breadcrumbs { font-size: 14px; }
	.breadcrumbs span { margin: 0 6px; }
	.catalog-meta { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
	.catalog-count { background: #f3f4f6; padding: 8px 14px; border-radius: 8px; font-size: 14px; }
	.catalog-sort { display: flex; gap: 8px; }
	.catalog-sort button { background: none; border: none; font-size: 14px; cursor: pointer; margin-left: 16px; color: #4a4f59e6; }
	.catalog-sort button:hover { text-decoration: underline; }
	.catalog-layout { display: grid; grid-template-columns: 280px 1fr; gap: 32px; align-items: start; }
	.catalog-products { width: 100%; }
	.catalog-filters { background: #fff; border-radius: 16px; }
	.filter { border-bottom: 1px solid #e6e7eb; padding: 16px 0; }
	.filter-title { width: 100%; background: none; border: none; text-align: left; font-size: 16px; font-weight: 500; color: #36393e; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
	.filter-title::after { content: "▾"; font-size: 12px; transition: transform 0.2s; }
	.filter.is-open .filter-title::after { transform: rotate(180deg); }
	.filter-content { display: none; margin-top: 12px; padding-left: 10px; }
	.filter.is-open .filter-content { display: block; }
	.filters-reset { margin-top: 24px; background: none; border: none; color: #36393e; font-size: 14px; cursor: pointer; }
	.price-range { padding-top: 8px; }
	.price-slider-wrapper { position: relative; height: 16px; }
	.price-slider { position: absolute; left: 0; top: 50%; width: 100%; transform: translateY(-50%); pointer-events: none; -webkit-appearance: none; appearance: none; height: 2px; background: transparent; }
	.price-slider-wrapper::before { content: ""; position: absolute; left: 0; right: 0; top: 50%; height: 2px; background: #dcdfe4; transform: translateY(-50%); border-radius: 2px; }
	.price-slider-wrapper::after { content: ""; position: absolute; top: 50%; height: 2px; background: #36393e; transform: translateY(-50%); border-radius: 2px; left: var(--left); right: calc(100% - var(--right)); }
	.price-slider::-webkit-slider-thumb { -webkit-appearance: none; pointer-events: all; width: 16px; height: 16px; background: #36393e; box-shadow: 0 1px 4px rgba(0,0,0,.25); border-radius: 50%; cursor: pointer; }
	.price-slider::-moz-range-thumb { pointer-events: all; width: 16px; height: 16px; background: #36393e; border-radius: 50%; cursor: pointer; }
	.price-values { display: flex; justify-content: space-between; margin-top: 12px; font-size: 13px; }
	.catalog-products { width: 100%; }
	@media (max-width: 768px) { .catalog-products { margin-top: 0; } }
	.products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
	.product-card { background: #fff; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; height: 100%; position: relative; transition: box-shadow 0.2s; }
	.product-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
	.product-link { position: absolute; inset: 0; border-radius: 20px; z-index: 1; }
	.product-image { display: flex; justify-content: center; align-items: center; margin-bottom: 16px; aspect-ratio: 1; overflow: hidden; }
	.product-image img { max-width: 100%; max-height: 100%; object-fit: contain; width: 100%; height: 100%; }
	.product-badge-discount { position: absolute; top: 12px; right: 12px; background: #ff9800; color: #000; font-weight: 700; font-size: 12px; padding: 4px 8px; border-radius: 6px; z-index: 2; }
	.product-title { font-size: 15px; font-weight: 500; color: #36393e; margin-bottom: 3px; line-height: 1.3; }
	.product-article { font-size: 13px; margin-bottom: 5px; color: #8a8f9a; }
	.product-price-wrap { display: flex; align-items: baseline; gap: 8px; margin-bottom: 5px; }
	.product-price { font-size: 18px; font-weight: 600; color: #36393e; }
	.product-price-old { font-size: 13px; color: #9e9e9e; text-decoration: line-through; }
	.product-buy { margin-top: auto; background: #36393e; color: #fff; border: none; border-radius: 999px; padding: 12px; font-size: 14px; cursor: pointer; position: relative; z-index: 2; transition: opacity 0.2s; font-family: inherit; }
	.product-buy:hover { opacity: .85; }
	.products-empty { grid-column: 1 / -1; text-align: center; padding: 48px; color: #8a8f9a; font-size: 15px; }
	.filter-checkbox { cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px; color: #4a4f59e6; margin-bottom: 10px; user-select: none; }
	.filter-checkbox input { position: absolute; opacity: 0; pointer-events: none; }
	.checkbox-ui { width: 16px; height: 16px; border: 1.5px solid #b8bcc4; border-radius: 4px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s; flex-shrink: 0; position: relative; top: -1px; }
	.checkbox-ui::after { content: ""; width: 8px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg); opacity: 0; transition: opacity 0.15s; }
	.filter-checkbox input:checked + .checkbox-ui { background: #36393e; border-color: #36393e; }
	.filter-checkbox input:checked + .checkbox-ui::after { opacity: 1; }
	.filter-checkbox:hover .checkbox-ui { border-color: #36393e; }
	.filters-toggle { display: none; }
	.filters-mobile-header { display: none; }
	@media (max-width: 1200px) { .products-grid { grid-template-columns: repeat(2, 1fr); } }
	@media (max-width: 768px) {
		.catalog { padding: 0 16px 30px; margin-top: 0; }
		.catalog-layout { grid-template-columns: 1fr; }
		.catalog-filters { display: none; }
		.products-grid { grid-template-columns: 1fr; }
		.catalog-meta { flex-direction: column; align-items: flex-start; }
		.filters-toggle { display: flex; position: sticky; top: 12px; z-index: 900; margin-left: auto; width: 44px; height: 44px; border-radius: 50%; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.12); align-items: center; justify-content: center; border: none; cursor: pointer; }
		.catalog-filters.is-mobile-open { display: block; position: fixed; inset: 0; z-index: 1000; background: #fff; padding: 16px; overflow-y: auto; }
		.filters-mobile-header { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 500; margin-bottom: 16px; position: sticky; top: 0; background: #fff; padding-bottom: 12px; }
		.filters-close { background: none; border: none; font-size: 20px; cursor: pointer; }
	}
	</style>
</head>
<body class="cms-index-index cms-home chrome undefined header-fixed">
<div class="page-wrapper">
	<?php include($_SERVER['DOCUMENT_ROOT'].'/source_include/head.html'); ?>

	<main class="catalog">
		<nav class="breadcrumbs">
			<a href="/">Главная</a>
			<span>—</span>
			<span><?= ct_h($section_name) ?></span>
		</nav>
		<header class="catalog-header">
			<h1 class="catalog-title"><?= ct_h($section_name) ?></h1>
			<div class="catalog-meta">
				<span class="catalog-count">Доступно товаров <strong id="product-count">0</strong></span>
				<div class="catalog-sort">
					<button type="button" data-sort="asc">Сначала дешевые</button>
					<button type="button" data-sort="desc">Сначала дорогие</button>
				</div>
			</div>
		</header>

		<div class="catalog-layout">
			<aside class="catalog-filters">
				<div class="filters-mobile-header">
					<span>Фильтры</span>
					<button class="filters-close" type="button">✕</button>
				</div>

				<!-- Цена -->
				<section class="filter is-open">
					<button class="filter-title" type="button">Цена</button>
					<div class="filter-content">
						<div class="price-range">
							<div class="price-slider-wrapper">
								<input type="range" class="price-slider price-min" min="0" max="100000" step="500" value="0">
								<input type="range" class="price-slider price-max" min="0" max="100000" step="500" value="100000">
							</div>
							<div class="price-values">
								<span class="price-value-min">0 ₽</span>
								<span class="price-value-max">100 000 ₽</span>
							</div>
						</div>
					</div>
				</section>

				<!-- Модель -->
				<?php if (in_array('model', $filters)): ?>
				<section class="filter" id="filter-model">
					<button class="filter-title" type="button">Модель</button>
					<div class="filter-content" id="filter-model-list"></div>
				</section>
				<?php endif ?>

				<!-- Цвет -->
				<?php if (in_array('color', $filters)): ?>
				<section class="filter" id="filter-color">
					<button class="filter-title" type="button">Цвет</button>
					<div class="filter-content" id="filter-color-list"></div>
				</section>
				<?php endif ?>

				<!-- Коллекция -->
				<?php if (in_array('collection', $filters)): ?>
				<section class="filter" id="filter-collection">
					<button class="filter-title" type="button">Коллекция</button>
					<div class="filter-content" id="filter-collection-list"></div>
				</section>
				<?php endif ?>

				<!-- Режимы (душевые системы) -->
				<?php if (in_array('modes', $filters)): ?>
				<section class="filter" id="filter-modes">
					<button class="filter-title" type="button">Режимы</button>
					<div class="filter-content" id="filter-modes-list"></div>
				</section>
				<?php endif ?>

				<!-- Дизайн (душевые системы) -->
				<?php if (in_array('design', $filters)): ?>
				<section class="filter" id="filter-design">
					<button class="filter-title" type="button">Дизайн</button>
					<div class="filter-content" id="filter-design-list"></div>
				</section>
				<?php endif ?>

				<!-- Тип (полотенцесушители) -->
				<?php if (in_array('heater_type', $filters)): ?>
				<section class="filter" id="filter-heater-type">
					<button class="filter-title" type="button">Тип</button>
					<div class="filter-content" id="filter-heater-type-list"></div>
				</section>
				<?php endif ?>

				<!-- Форма (полотенцесушители) -->
				<?php if (in_array('heater_shape', $filters)): ?>
				<section class="filter" id="filter-heater-shape">
					<button class="filter-title" type="button">Форма</button>
					<div class="filter-content" id="filter-heater-shape-list"></div>
				</section>
				<?php endif ?>

				<!-- Встраиваемая / Термостат (душевые) -->
				<?php if (in_array('extra', $filters)): ?>
				<section class="filter" id="filter-extra">
					<button class="filter-title" type="button">Дополнительно</button>
					<div class="filter-content">
						<label class="filter-checkbox">
							<input type="checkbox" value="embedded">
							<span class="checkbox-ui"></span>
							<span class="checkbox-label">Встраиваемая</span>
						</label>
						<label class="filter-checkbox">
							<input type="checkbox" value="thermostat">
							<span class="checkbox-ui"></span>
							<span class="checkbox-label">С термостатом</span>
						</label>
					</div>
				</section>
				<?php endif ?>

				<button class="filters-reset" type="button">Очистить фильтры</button>
			</aside>

			<section class="catalog-products">
				<button class="filters-toggle" type="button" aria-label="Фильтры">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
						<path d="M2 5h16M5 10h10M8 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
				</button>
				<div class="products-grid" id="products-grid"></div>
			</section>
		</div>
	</main>

	<?php include($_SERVER['DOCUMENT_ROOT'].'/source_include/foot.html'); ?>

	<script>
	const PRODUCTS_DATA   = <?= json_encode($catalog_products, JSON_UNESCAPED_UNICODE) ?>;
	const SECTION_URL     = '<?= $section_url ?>';
	const ACTIVE_FILTERS  = <?= json_encode($filters) ?>;
	</script>

	<script src="/source_js/catalog.js"></script>
</div>
</body>
</html>
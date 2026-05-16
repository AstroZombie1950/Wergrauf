<?php
/* shower_system/product.php — карточка товара */

$slug = $_GET['slug'] ?? '';

// Загружаем данные
$json_file = $_SERVER['DOCUMENT_ROOT'] . '/data/shower_system.json';
$products  = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];

// Ищем товар по slug
$product = null;
foreach ($products as $p) {
	if (($p['slug'] ?? '') === $slug) {
		$product = $p;
		break;
	}
}

// Не нашли — 404
if (!$product) {
	http_response_code(404);
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	exit;
}

// --- Утилиты ---
function h(string $s): string {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function fmt_price(int $price): string {
	return number_format($price, 0, '.', ' ') . ' ₽';
}

// --- Парсинг slug для перелинковки ---
// Формат: [модель]-[размер][цвет], например 4s-30b, tki-2030ch, 4p-2030gr
function parse_slug(string $slug): array {
	// Маппинг суффиксов цвета (от длинных к коротким во избежание конфликтов)
	$color_suffixes = ['ch', 'gr', 'b', 'g', 's', 'w'];

	// Разбиваем на часть до первого дефиса (модель) и остаток (размер+цвет)
	$dash = strpos($slug, '-');
	if ($dash === false) return ['base' => $slug, 'size' => '', 'color' => ''];

	$base      = substr($slug, 0, $dash);       // напр. "4s"
	$size_color = substr($slug, $dash + 1);     // напр. "30b" или "2030ch"

	// Определяем цвет — ищем суффикс с конца
	$color = '';
	foreach ($color_suffixes as $suffix) {
		if (str_ends_with($size_color, $suffix)) {
			$color = $suffix;
			$size  = substr($size_color, 0, -strlen($suffix));
			return ['base' => $base, 'size' => $size, 'color' => $color];
		}
	}

	return ['base' => $base, 'size' => $size_color, 'color' => ''];
}

// Маппинг цветового кода → название и HEX
function color_info(string $code): array {
	$map = [
		'b'  => ['name' => 'Черный',        'hex' => '#1f1f1f'],
		'ch' => ['name' => 'Хром',          'hex' => '#cfcfcf'],
		'g'  => ['name' => 'Золото',        'hex' => '#c9a24d'],
		's'  => ['name' => 'Сатин',         'hex' => '#b8b0a0'],
		'gr' => ['name' => 'Графит',        'hex' => '#5a5a5a'],
		'w'  => ['name' => 'Белый',         'hex' => '#f0f0f0'],
	];
	return $map[$code] ?? ['name' => 'Комбинированный', 'hex' => 'linear-gradient(135deg,#cfcfcf,#c9a24d)'];
}

// Маппинг размера → отображение
function size_label(string $size): string {
	if (strlen($size) === 4 && is_numeric($size)) {
		// 2030 → 20×30, 2530 → 25×30
		return substr($size, 0, 2) . '×' . substr($size, 2);
	}
	return $size . '×' . $size; // 30 → 30×30
}

// --- Перелинковка ---
$parsed = parse_slug($slug);

// Все товары той же базовой модели
$same_model = array_filter($products, fn($p) =>
	parse_slug($p['slug'] ?? '')['base'] === $parsed['base']
);

// Уникальные размеры для текущего цвета
$sizes_for_color = [];
foreach ($same_model as $p) {
	$pp = parse_slug($p['slug']);
	if ($pp['color'] === $parsed['color'] && $pp['size']) {
		$sizes_for_color[$pp['size']] = $p['slug'];
	}
}
ksort($sizes_for_color);

// Уникальные цвета для текущего размера
$colors_for_size = [];
foreach ($same_model as $p) {
	$pp = parse_slug($p['slug']);
	if ($pp['size'] === $parsed['size'] && $pp['color']) {
		$colors_for_size[$pp['color']] = $p['slug'];
	}
}

// --- Скидка ---
$discount_pct = 0;
if (!empty($product['old_price']) && $product['old_price'] > $product['price']) {
	$discount_pct = round((1 - $product['price'] / $product['old_price']) * 100);
}

// --- Похожие товары (случайные из того же раздела, кроме текущего) ---
// TODO: заменить на логику рекомендаций
$similar_pool = array_values(array_filter($products, fn($p) => $p['slug'] !== $slug));
shuffle($similar_pool);
$similar = array_slice($similar_pool, 0, 6);

// --- Метатеги ---
$meta_title = !empty($product['meta_title'])
	? $product['meta_title']
	: ($product['name'] . ' купить | WERGRAUF');

$meta_desc = !empty($product['meta_description'])
	? $product['meta_description']
	: $product['description'] ?? '';

$og_image = !empty($product['image']) ? $product['image'] : 'https://wergrauf.ru/images/logo_img.png';
$canonical = 'https://wergrauf.ru/shower_system/' . h($slug) . '/';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | <?= h($meta_title) ?></title>
	<meta name="description" content="<?= h(mb_strimwidth($meta_desc, 0, 160, '…')) ?>">
	<meta name="robots" content="index, follow">
	<meta property="og:type" content="product">
	<meta property="og:title" content="<?= h($meta_title) ?>">
	<meta property="og:description" content="<?= h(mb_strimwidth($meta_desc, 0, 160, '…')) ?>">
	<meta property="og:url" content="<?= $canonical ?>">
	<meta property="og:image" content="<?= h($og_image) ?>">
	<link rel="canonical" href="<?= $canonical ?>">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type="text/css" href="/source_css/main.css" media="all">

	<!-- Schema.org Product -->
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "Product",
		"name": "<?= h($product['name']) ?>",
		"description": "<?= h(addslashes($product['description'] ?? '')) ?>",
		"image": "<?= h($og_image) ?>",
		"sku": "<?= h((string)$product['article']) ?>",
		"brand": {
			"@type": "Brand",
			"name": "WERGRAUF"
		},
		"offers": {
			"@type": "Offer",
			"price": "<?= $product['price'] ?>",
			"priceCurrency": "RUB",
			"availability": "https://schema.org/InStock",
			"url": "<?= $canonical ?>"
		}
	}
	</script>

	<!-- Schema.org BreadcrumbList -->
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "BreadcrumbList",
		"itemListElement": [
			{"@type": "ListItem", "position": 1, "name": "Главная", "item": "https://wergrauf.ru/"},
			{"@type": "ListItem", "position": 2, "name": "Душевые системы", "item": "https://wergrauf.ru/shower_system/"},
			{"@type": "ListItem", "position": 3, "name": "<?= h($product['name']) ?>", "item": "<?= $canonical ?>"}
		]
	}
	</script>

	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; font-family: "Roboto", "Arial", sans-serif; }

	/* --- Hero --- */
	.product-hero { padding: 32px 0; }
	.product-hero__wrapper { max-width: 1180px; margin: 0 auto; padding: 0 20px; }

	.product-hero__grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		grid-template-areas: "media info" "desc info";
		gap: 24px;
	}

	/* --- Карточки --- */
	.product-card {
		background: #fff;
		border-radius: 16px;
		padding: 20px;
		box-shadow: 0 8px 24px rgba(0,0,0,0.08);
	}

	.product-card--media { grid-area: media; padding: 16px; }
	.product-card--info  { grid-area: info; }

	/* --- Галерея --- */
	.product-image {
		position: relative;
		aspect-ratio: 1;
		overflow: hidden;
		border-radius: 12px;
	}

	.product-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: opacity 0.2s;
	}

	.product-discount {
		position: absolute;
		top: 12px;
		right: 12px;
		background: #ff9800;
		color: #000;
		font-weight: 700;
		font-size: 13px;
		padding: 6px 10px;
		border-radius: 6px;
		z-index: 2;
	}

	.product-thumbs {
		display: flex;
		gap: 8px;
		margin-top: 12px;
		overflow-x: auto;
	}

	.product-thumb {
		flex-shrink: 0;
		width: 64px;
		height: 64px;
		border-radius: 6px;
		border: 1px solid #e0e0e0;
		padding: 0;
		background: none;
		cursor: pointer;
		overflow: hidden;
	}

	.product-thumb.is-active { border-color: #000; border-width: 2px; }

	.product-thumb img { width: 100%; height: 100%; object-fit: cover; }

	/* --- Инфо --- */
	.product-title { font-size: 26px; margin: 0 0 8px; line-height: 1.2; }

	.product-subinfo {
		display: flex;
		gap: 12px;
		align-items: center;
		margin-bottom: 16px;
		flex-wrap: wrap;
	}

	.product-article-info { font-size: 13px; color: #6b6b6b; }

	/* --- Цена --- */
	.product-price-box {
		position: relative;
		background: #f5f5f5;
		border-radius: 10px;
		padding: 14px 16px 14px 20px;
		margin-bottom: 16px;
	}

	.product-price-box::before {
		content: "";
		position: absolute;
		left: 0; top: 8px; bottom: 8px;
		width: 4px;
		background: #9e9e9e;
		border-radius: 4px;
	}

	.price-main { display: flex; align-items: baseline; gap: 12px; margin-bottom: 4px; }
	.price-current { font-size: 24px; font-weight: 700; color: #000; }
	.price-old { font-size: 14px; color: #9e9e9e; text-decoration: line-through; }
	.price-economy { font-size: 13px; color: #2e7d32; }

	/* --- Промокод --- */
	.product-coupon {
		position: relative;
		background: #eaf6ec;
		border-radius: 10px;
		padding: 14px 16px 14px 20px;
		margin-bottom: 20px;
	}

	.product-coupon::before {
		content: "";
		position: absolute;
		left: 0; top: 8px; bottom: 8px;
		width: 4px;
		background: #4caf50;
		border-radius: 4px;
	}

	.coupon-title { font-size: 14px; font-weight: 600; color: #1b5e20; margin-bottom: 4px; }
	.coupon-code { font-size: 13px; color: #1b5e20; }

	/* --- Опции --- */
	.product-option { margin-bottom: 16px; }
	.option-title { font-size: 13px; font-weight: 600; color: #6b6b6b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .04em; }

	/* Цвета */
	.option-colors { display: flex; gap: 8px; flex-wrap: wrap; }

	.color-option { position: relative; cursor: pointer; }
	.color-option input { display: none; }

	.color-dot {
		display: block;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		border: 2px solid #e0e0e0;
		transition: border-color 0.15s;
	}

	.color-option input:checked + .color-dot,
	.color-dot:hover { border-color: #2b2f33; box-shadow: 0 0 0 2px #2b2f33 inset; }

	.color-option .color-name {
		position: absolute;
		bottom: -18px;
		left: 50%;
		transform: translateX(-50%);
		font-size: 10px;
		color: #6b6b6b;
		white-space: nowrap;
		pointer-events: none;
		opacity: 0;
		transition: opacity 0.15s;
	}

	.color-option:hover .color-name { opacity: 1; }

	/* Размеры */
	.option-sizes { display: flex; gap: 8px; flex-wrap: wrap; }

	.size-option { cursor: pointer; }
	.size-option input { display: none; }

	.size-option span {
		display: block;
		padding: 7px 12px;
		font-size: 13px;
		border-radius: 8px;
		border: 1px solid #dcdcdc;
		background: #fff;
		transition: all 0.15s;
	}

	.size-option input:checked + span,
	.size-option span:hover {
		background: #2b2f33;
		color: #fff;
		border-color: #2b2f33;
	}

	/* --- Кнопки --- */
	.product-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 20px 0 24px; }

	.product-btn {
		height: 52px;
		border-radius: 8px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		background: #2b2f33;
		color: #fff;
		border: none;
		transition: background 0.2s;
		font-family: inherit;
	}

	.product-btn:hover { background: #1f2226; }

	/* --- Features --- */
	.product-features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }

	.feature-card { background: #f7f7f7; border-radius: 10px; padding: 14px; }
	.feature-card strong { display: block; font-size: 14px; margin-bottom: 4px; }
	.feature-card span { font-size: 13px; color: #6b6b6b; }

	/* --- Trust --- */
	.product-trust { display: grid; grid-template-columns: repeat(3, 1fr); background: #f2f7fb; border-radius: 10px; padding: 16px; gap: 16px; }
	.trust-item strong { display: block; font-size: 14px; margin-bottom: 4px; }
	.trust-item span { font-size: 13px; color: #5f6f7f; }

	/* --- Описание --- */
	.product-description {
		grid-area: desc;
		padding: 20px;
		border-radius: 16px;
		box-shadow: 0 8px 24px rgba(0,0,0,0.08);
	}

	.product-description__title { font-size: 16px; font-weight: 600; text-transform: uppercase; margin: 5px 0; }

	.product-description__content {
		font-size: 14px;
		color: #4f4f4f;
		line-height: 1.6;
		overflow: hidden;
		transition: max-height 0.3s ease;
	}

	.product-description__content.is-collapsed { max-height: 48px; }
	.product-description__content:not(.is-collapsed) { max-height: 1000px; }

	.product-description__toggle {
		margin-top: 8px;
		padding: 0;
		background: none;
		border: none;
		font-size: 14px;
		color: #36393e;
		cursor: pointer;
		text-decoration: underline;
		font-family: inherit;
	}

	.product-description__toggle:hover { text-decoration: none; }

	/* --- Характеристики --- */
	.product-specs { margin-top: 10px; }
	.product-specs__wrapper { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
	.product-specs__title { font-size: 22px; font-weight: 600; margin-bottom: 24px; }
	.product-specs__table { border-top: 1px solid #e6e6e6; }

	.spec-row {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr 1fr;
		border-bottom: 1px solid #e6e6e6;
	}

	.spec-row:last-child { border-bottom: none; }
	.spec-name, .spec-value { padding: 16px 12px; font-size: 14px; line-height: 1.4; }
	.spec-name { color: #000; font-weight: 700; }
	.spec-value { color: #4f4f4f; }

	/* --- Похожие --- */
	.similar-products { margin-top: 10px; margin-bottom: 10px; }
	.similar-products__wrapper { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
	.similar-products__title { font-size: 22px; font-weight: 600; margin-bottom: 24px; }

	.similar-products__list { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 8px; }

	.similar-product-card { flex: 0 0 240px; text-decoration: none; color: inherit; }
	.similar-product-card__image { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; margin-bottom: 12px; }
	.similar-product-card__image img { width: 100%; height: 100%; object-fit: cover; }
	.similar-product-card__discount { position: absolute; top: 8px; right: 8px; background: #ff9800; color: #000; font-weight: 700; font-size: 12px; padding: 4px 8px; border-radius: 6px; }
	.similar-product-card__name { font-size: 14px; line-height: 1.4; margin-bottom: 6px; }
	.similar-product-card__price { font-size: 15px; font-weight: 600; }

	/* --- Хлебные крошки --- */
	.product-breadcrumbs { font-size: 13px; color: #6b6b6b; margin-bottom: 16px; }
	.product-breadcrumbs a { color: inherit; text-decoration: none; }
	.product-breadcrumbs a:hover { text-decoration: underline; }
	.product-breadcrumbs span { margin: 0 6px; }

	/* --- Сотрудничество --- */
	.cooperation { margin: 10px 0; }
	.cooperation__wrapper { max-width: 1180px; margin: 0 auto; padding: 48px 32px; background: #fff; border-radius: 16px; }
	.cooperation__title { text-align: center; font-size: 24px; font-weight: 600; margin-bottom: 12px; }
	.cooperation__subtitle { text-align: center; font-size: 14px; color: #6b6b6b; max-width: 720px; margin: 0 auto 32px; }
	.cooperation__features { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
	.cooperation-card { background: #f7f7f7; border-radius: 12px; padding: 20px 16px; text-align: center; }
	.cooperation-card strong { display: block; font-size: 14px; margin-bottom: 6px; }
	.cooperation-card span { font-size: 13px; color: #6b6b6b; }
	.cooperation__cta { width: 100%; height: 48px; background: #2b2f33; color: #fff; font-size: 14px; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; font-family: inherit; }
	.cooperation__cta:hover { background: #1f2226; }

	/* --- Отзывы --- */
	.product-reviews { max-width: 1180px; margin: 0 auto 30px; padding: 0 20px; }
	.product-reviews__title { font-size: 22px; font-weight: 600; margin-bottom: 24px; border-bottom: 1px solid #e6e6e6; padding-bottom: 12px; }
	.product-review { padding: 20px 0; border-bottom: 1px solid #e6e6e6; }
	.product-review:last-child { border-bottom: none; }
	.product-review__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
	.product-review__name { font-size: 14px; font-weight: 600; }
	.product-review__date { font-size: 12px; color: #9e9e9e; }
	.product-review__rating { display: flex; gap: 4px; margin-bottom: 8px; }
	.star { width: 14px; height: 14px; display: inline-block; background: #f5b301; clip-path: polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%); }
	.product-review__text { font-size: 14px; line-height: 1.6; color: #4f4f4f; }

	/* --- Mobile --- */
	@media (max-width: 768px) {
		.product-hero { padding: 0; }
		.product-hero__grid { grid-template-columns: 1fr; grid-template-areas: "media" "info" "desc"; }
		.product-title { font-size: 22px; }
		.product-actions { grid-template-columns: 1fr; }
		.product-features { grid-template-columns: 1fr; }
		.product-trust { grid-template-columns: 1fr; }
		.spec-row { grid-template-columns: 1fr; padding: 12px 0; }
		.spec-name { padding-bottom: 4px; }
		.spec-value { padding-top: 0; padding-bottom: 12px; }
		.cooperation__wrapper { padding: 20px; }
		.cooperation__features { grid-template-columns: 1fr; }
		.product-reviews { margin: 0 0 20px; }
		.product-review__header { flex-direction: column; align-items: flex-start; gap: 4px; }
		.similar-product-card { flex: 0 0 200px; }
	}
	</style>
</head>
<body class="cms-index-index cms-home chrome undefined header-fixed">
<div class="page-wrapper">
	<?php include($_SERVER['DOCUMENT_ROOT'].'/source_include/head.html'); ?>

	<section class="product-hero">
		<div class="product-hero__wrapper container">

			<!-- Хлебные крошки -->
			<nav class="product-breadcrumbs">
				<a href="/">Главная</a>
				<span>—</span>
				<a href="/shower_system/">Душевые системы</a>
				<span>—</span>
				<span><?= h($product['name']) ?></span>
			</nav>

			<div class="product-hero__grid">

				<!-- МЕДИА -->
				<div class="product-card product-card--media">
					<div class="product-image" id="main-image-wrap">
						<?php if ($discount_pct > 0): ?>
							<span class="product-discount">-<?= $discount_pct ?>%</span>
						<?php endif ?>
						<img
							id="main-image"
							src="<?= h($product['image'] ?? '') ?>"
							alt="<?= h($product['name']) ?>"
							width="600" height="600"
						>
					</div>

					<?php
					// Галерея: главное фото + массив gallery
					$gallery = $product['gallery'] ?? [];
					if (!empty($product['image'])) {
						array_unshift($gallery, $product['image']);
					}
					$gallery = array_unique($gallery);
					?>
					<?php if (count($gallery) > 1): ?>
					<div class="product-thumbs">
						<?php foreach ($gallery as $i => $img): ?>
							<button class="product-thumb <?= $i === 0 ? 'is-active' : '' ?>"
								type="button"
								onclick="switchImage(this, '<?= h($img) ?>')">
								<img src="<?= h($img) ?>" alt="" loading="lazy" width="64" height="64">
							</button>
						<?php endforeach ?>
					</div>
					<?php endif ?>
				</div>

				<!-- ИНФО -->
				<div class="product-card product-card--info">
					<h1 class="product-title"><?= h($product['name']) ?></h1>

					<div class="product-subinfo">
						<span class="product-article-info">Арт. <?= h((string)$product['article']) ?></span>
					</div>

					<!-- Цена -->
					<div class="product-price-box">
						<div class="price-main">
							<span class="price-current"><?= fmt_price($product['price']) ?></span>
							<?php if ($discount_pct > 0): ?>
								<span class="price-old"><?= fmt_price($product['old_price']) ?></span>
							<?php endif ?>
						</div>
						<?php if ($discount_pct > 0): ?>
							<div class="price-economy">
								Вы экономите <?= fmt_price($product['old_price'] - $product['price']) ?>
							</div>
						<?php endif ?>
					</div>

					<!-- Промокод -->
					<?php if (!empty($product['promo_code']) && !empty($product['discount_percent'])): ?>
					<div class="product-coupon">
						<div class="coupon-title">
							Скидка <?= h($product['discount_percent']) ?>% при заказе от 2-х товаров
						</div>
						<div class="coupon-code">
							По промокоду: <strong><?= h($product['promo_code']) ?></strong>
						</div>
					</div>
					<?php endif ?>

					<!-- Варианты цвета -->
					<?php if (count($colors_for_size) > 1): ?>
					<div class="product-option">
						<div class="option-title">Цвет</div>
						<div class="option-colors">
							<?php foreach ($colors_for_size as $color_code => $color_slug): ?>
								<?php $ci = color_info($color_code); ?>
								<label class="color-option">
									<input type="radio" name="color" value="<?= h($color_code) ?>"
										<?= $color_code === $parsed['color'] ? 'checked' : '' ?>
										onchange="window.location='/shower_system/<?= h($color_slug) ?>/'">
									<span class="color-dot" style="background: <?= h($ci['hex']) ?>"></span>
									<span class="color-name"><?= h($ci['name']) ?></span>
								</label>
							<?php endforeach ?>
						</div>
					</div>
					<?php endif ?>

					<!-- Варианты размера -->
					<?php if (count($sizes_for_color) > 1): ?>
					<div class="product-option">
						<div class="option-title">Размер душа</div>
						<div class="option-sizes">
							<?php foreach ($sizes_for_color as $size_code => $size_slug): ?>
								<label class="size-option">
									<input type="radio" name="size" value="<?= h($size_code) ?>"
										<?= $size_code === $parsed['size'] ? 'checked' : '' ?>
										onchange="window.location='/shower_system/<?= h($size_slug) ?>/'">
									<span><?= h(size_label($size_code)) ?></span>
								</label>
							<?php endforeach ?>
						</div>
					</div>
					<?php endif ?>

					<!-- Кнопки -->
					<?php $_cp = json_encode([
						'article'          => (string)$product['article'],
						'name'             => $product['name'],
						'price'            => (int)$product['price'],
						'old_price'        => (int)($product['old_price'] ?? 0),
						'image'            => $product['image'] ?? '',
						'slug'             => $slug,
						'section_url'      => '/shower_system/',
						'promo_code'       => $product['promo_code'] ?? '',
						'discount_percent' => $product['discount_percent'] ?? '',
					], JSON_UNESCAPED_UNICODE); ?>
					<div class="product-actions">
						<button class="product-btn" type="button"
							onclick='cartAdd(<?= h($_cp) ?>)'>
							Добавить в корзину
						</button>
						<button class="product-btn" type="button"
							onclick='oneClickOpen(<?= h($_cp) ?>)'>
							Купить в один клик
						</button>
					</div>

					<!-- Фичи -->
					<div class="product-features">
						<?php
						// Берём первые 4 характеристики для фич-блока
						$feat_specs = array_slice($product['specs'] ?? [], 0, 4);
						foreach ($feat_specs as $spec): ?>
							<div class="feature-card">
								<strong><?= h($spec['label']) ?></strong>
								<span><?= h($spec['value']) ?></span>
							</div>
						<?php endforeach ?>
					</div>

					<!-- Трастовый блок -->
					<div class="product-trust">
						<div class="trust-item">
							<strong>В наличии</strong>
							<span><?= (int)$product['stock'] ?> шт.</span>
						</div>
						<div class="trust-item">
							<strong>Быстрая доставка</strong>
							<span>По всей России</span>
						</div>
						<div class="trust-item">
							<strong>Официальная гарантия</strong>
							<span>от производителя</span>
						</div>
					</div>
				</div>

				<!-- ОПИСАНИЕ -->
				<div class="product-description">
					<h3 class="product-description__title">Описание</h3>
					<div class="product-description__content is-collapsed" id="desc-content">
						<?= nl2br(h($product['description'] ?? '')) ?>
					</div>
					<button class="product-description__toggle" id="desc-toggle">Показать полностью</button>
				</div>

			</div>
		</div>
	</section>

	<!-- Характеристики -->
	<?php if (!empty($product['specs'])): ?>
	<section class="product-specs">
		<div class="product-specs__wrapper container">
			<h2 class="product-specs__title">Технические характеристики</h2>
			<div class="product-specs__table">
				<?php
				// Выводим попарно: 2 характеристики в строку
				$specs = $product['specs'];
				$chunks = array_chunk($specs, 2);
				foreach ($chunks as $pair): ?>
					<div class="spec-row">
						<div class="spec-name"><?= h($pair[0]['label']) ?></div>
						<div class="spec-value"><?= h($pair[0]['value']) ?></div>
						<?php if (isset($pair[1])): ?>
							<div class="spec-name"><?= h($pair[1]['label']) ?></div>
							<div class="spec-value"><?= h($pair[1]['value']) ?></div>
						<?php else: ?>
							<div class="spec-name"></div>
							<div class="spec-value"></div>
						<?php endif ?>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	</section>
	<?php endif ?>

	<!-- Похожие товары -->
	<?php if (!empty($similar)): ?>
	<section class="similar-products">
		<div class="similar-products__wrapper container">
			<h2 class="similar-products__title">Похожие товары</h2>
			<div class="similar-products__list">
				<?php foreach ($similar as $sp):
					$sp_discount = (!empty($sp['old_price']) && $sp['old_price'] > $sp['price'])
						? round((1 - $sp['price'] / $sp['old_price']) * 100)
						: 0;
				?>
					<a href="/shower_system/<?= h($sp['slug']) ?>/" class="similar-product-card">
						<div class="similar-product-card__image">
							<?php if ($sp_discount > 0): ?>
								<span class="similar-product-card__discount">-<?= $sp_discount ?>%</span>
							<?php endif ?>
							<img src="<?= h($sp['image'] ?? '') ?>" alt="<?= h($sp['name']) ?>" loading="lazy" width="240" height="240">
						</div>
						<div class="similar-product-card__info">
							<div class="similar-product-card__name"><?= h($sp['name']) ?></div>
							<div class="similar-product-card__price"><?= fmt_price($sp['price']) ?></div>
						</div>
					</a>
				<?php endforeach ?>
			</div>
		</div>
	</section>
	<?php endif ?>

	<!-- Сотрудничество -->
	<section class="cooperation">
		<div class="cooperation__wrapper container">
			<h2 class="cooperation__title">Сотрудничество</h2>
			<p class="cooperation__subtitle">
				Мы сотрудничаем с компаниями, дизайнерами и мастерами.
				Узнайте о преимуществах работы наших корпоративных программ подробнее.
			</p>
			<div class="cooperation__features">
				<div class="cooperation-card">
					<strong>Широкий ассортимент</strong>
					<span>Все для ванной комнаты в одном месте</span>
				</div>
				<div class="cooperation-card">
					<strong>Скидки и бонусы</strong>
					<span>Специальные условия для партнеров</span>
				</div>
				<div class="cooperation-card">
					<strong>Прямые поставки</strong>
					<span>Без посредников, лучшие цены</span>
				</div>
				<div class="cooperation-card">
					<strong>Актуальное наличие</strong>
					<span>Точная информация о товарах на складе</span>
				</div>
			</div>
			<button class="cooperation__cta" onclick="window.location='/partners/'">Стать партнером</button>
		</div>
	</section>

	<!-- Отзывы (статика) -->
	<section class="product-reviews container">
		<h2 class="product-reviews__title">Отзывы покупателей</h2>
		<div class="product-review">
			<div class="product-review__header">
				<div class="product-review__name">Анна Смирнова</div>
				<div class="product-review__date">12 января 2024</div>
			</div>
			<div class="product-review__rating">
				<span class="star"></span><span class="star"></span><span class="star"></span>
				<span class="star"></span><span class="star"></span>
			</div>
			<p class="product-review__text">
				Прекрасная душевая система! Матовый чёрный цвет выглядит очень стильно и современно.
				Качество материалов на высоте, чувствуется, что сделано на совесть.
				Тропический душ — это невероятное удовольствие! Установка прошла без проблем.
			</p>
		</div>
		<div class="product-review">
			<div class="product-review__header">
				<div class="product-review__name">Дмитрий Ковалёв</div>
				<div class="product-review__date">5 января 2024</div>
			</div>
			<div class="product-review__rating">
				<span class="star"></span><span class="star"></span><span class="star"></span>
				<span class="star"></span><span class="star"></span>
			</div>
			<p class="product-review__text">
				Хорошая система за свои деньги. Качество сборки отличное, дизайн современный.
				Доставка была быстрой, упаковано всё надёжно.
			</p>
		</div>
	</section>

	<?php include($_SERVER['DOCUMENT_ROOT'].'/source_include/foot.html'); ?>

	<script src="/source_js/main.js" defer></script>
	<script>
	/* --- Галерея --- */
	function switchImage(btn, src) {
		document.getElementById('main-image').src = src;
		document.querySelectorAll('.product-thumb').forEach(b => b.classList.remove('is-active'));
		btn.classList.add('is-active');
	}

	/* --- Описание: развернуть/свернуть --- */
	document.getElementById('desc-toggle').addEventListener('click', function() {
		const content = document.getElementById('desc-content');
		content.classList.toggle('is-collapsed');
		this.textContent = content.classList.contains('is-collapsed')
			? 'Показать полностью'
			: 'Свернуть';
	});

	/* --- Корзина (заглушки, подключим позже) --- */
	// корзина: cart.js
	</script>
</div>
</body>
</html>
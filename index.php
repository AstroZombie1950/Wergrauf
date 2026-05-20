<?php
/* Главная страница WERGRAUF */

/* Загружаем хелперы */
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/helpers.php';

/* Данные главной из JSON */
$home_data_file = $_SERVER['DOCUMENT_ROOT'] . '/data/home.json';
$home = file_exists($home_data_file)
	? json_decode(file_get_contents($home_data_file), true)
	: [];

$leader   = $home['leader']           ?? [];
$promos   = $home['promos']           ?? [];
$popular_ids = $home['popular_products'] ?? [];

/* Подбираем популярные товары по артикулам из всех разделов */
$popular_products = [];
if (!empty($popular_ids)) {
	$sections = [
		'shower_system', 'kitchen_faucets', 'floor_faucets', 'bath_faucets',
		'sink_faucets', 'hygienic_shower', 'accessories', 'towel_warmers', 'components'
	];
	foreach ($sections as $section) {
		if (count($popular_products) >= count($popular_ids)) break;
		$products = load_products($section);
		foreach ($products as $p) {
			if (in_array($p['article'] ?? '', $popular_ids)) {
				$p['_section'] = $section;
				$popular_products[$p['article']] = $p;
			}
		}
	}
	/* Сохраняем порядок как в массиве popular_ids */
	$sorted = [];
	foreach ($popular_ids as $art) {
		if (isset($popular_products[$art])) {
			$sorted[] = $popular_products[$art];
		}
	}
	$popular_products = $sorted;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | Официальный интернет-магазин бренда</title>
	<meta name="description" content="Смесители, душевые системы и аксессуары для душа и кухни — купите в официальном интернет-магазине WERGRAUF. Цены, характеристики, отзывы. Доставка по России.">
	<meta name="keywords" content="WERGRAUF">
	<meta name="robots" content="index, follow">
	<meta property="og:type" content="website">
	<meta property="og:title" content="Официальный интернет-магазин | WERGRAUF">
	<meta property="og:description" content="Смесители, душевые системы и аксессуары для душа и кухни — купите в официальном интернет-магазине WERGRAUF.">
	<meta property="og:url" content="https://wergrauf.ru">
	<meta property="og:image" content="https://wergrauf.ru/images/logo_img.png">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600&display=swap">
	<link rel="stylesheet" href="/source_css/wg.css">
	<link rel="stylesheet" href="/source_css/home.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
	<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js" defer></script>
</head>

<body class="cms-index-index">
<div class="page-wrapper">

	<?php include $_SERVER['DOCUMENT_ROOT'] . '/source_include/head.html'; ?>

	<section class="main-container">
		<div class="main" role="main">

			<!-- ===== Лидер продаж ===== -->
			<div id="cooperation-block" class="section-leader">
				<div class="container">
					<div class="block-header">
						<h6 class="block-header__title">Лидер продаж</h6>
					</div>
					<div class="cooperation-main">
						<div class="cooperation-main__image-container">
							<picture>
								<source type="image/webp" media="(min-width: 1440px)" srcset="<?= h($leader['image'] ?? 'images/promo/promo-desktop.webp') ?>">
								<source type="image/webp" media="(min-width: 768px)"  srcset="<?= h($leader['image_tablet'] ?? 'images/promo/promo-tablet.webp') ?>">
								<img
									class="coop-image"
									src="<?= h($leader['image_mobile'] ?? 'images/promo/promo-mobile.webp') ?>"
									alt="Лидер продаж"
									width="940"
									height="1200"
									loading="eager"
								>
							</picture>
						</div>
						<div class="cooperation-main__content">
							<div class="cooperation-main__content__info">
								<h3 class="cooperation-main__content__info__caption">
									<?= h($leader['title'] ?? 'Встречайте душевую систему WERGRAUF 4S-30B') ?>
								</h3>
								<?php
								$text = $leader['text'] ?? '';
								$paragraphs = array_filter(explode("\n\n", $text));
								foreach ($paragraphs as $para):
								?>
									<p class="cooperation-main__content__info__text"><?= nl2br(h(trim($para))) ?></p>
								<?php endforeach; ?>
							</div>
							<div class="cooperation-main__content__tiles">
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M22.393 19.1v8.904c0 .229-.038.448-.108.653.517.143 1.07.243 1.588.243.36 0 .701-.05 1.012-.125a2.08 2.08 0 0 1-.176-.842V19.1h-2.316Zm-2.2-.1c0-1.16.94-2.1 2.1-2.1h2.516c1.16 0 2.1.94 2.1 2.1v1.1h3.093a1.1 1.1 0 1 1 0 2.2H26.91v5.537c.485.035.884.356 1.039.79a1.185 1.185 0 0 1-.46 1.385 6.713 6.713 0 0 1-3.615 1.088c-1.575 0-3.18-.608-4.024-1.01a1.183 1.183 0 0 1-.642-1.354c.111-.45.488-.82.986-.89v.158a.17.17 0 0 1 .17-.17c-.058 0-.115.003-.17.011V19ZM5.927 3.794C7.28 2.135 9.485.9 12.799.9s5.479 1.237 6.77 2.92c1.25 1.631 1.59 3.563 1.526 4.873a1.1 1.1 0 1 1-2.197-.108c.044-.902-.204-2.29-1.075-3.426-.83-1.083-2.319-2.059-5.024-2.059-2.706 0-4.264.978-5.168 2.085-.94 1.153-1.257 2.554-1.257 3.454v11.313H8.1v-2.653a1.1 1.1 0 0 1 2.2 0v2.653c.273 0 .6.052.918.223.873.467 1.7 1.376 1.7 2.897 0 1.522-.828 2.43-1.7 2.898-.319.17-.646.223-.92.223H6.374V30a1.1 1.1 0 1 1-2.2 0v-3.807h-.652c-.279 0-.593-.054-.897-.208-.385-.194-.841-.492-1.19-1.003-.356-.519-.533-1.156-.533-1.91s.177-1.39.532-1.91c.35-.51.806-.809 1.19-1.003a1.99 1.99 0 0 1 .898-.207h.652V8.639c0-1.313.436-3.231 1.753-4.845Z"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Современный<br>дизайн</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" d="M7.06 6.101a1.1 1.1 0 0 1 .942-.534h16a1.1 1.1 0 0 1 .944.534l4 6.667a1.1 1.1 0 0 1-.124 1.3L17.49 26.733a2.036 2.036 0 0 1-2.939.036 1.17 1.17 0 0 1-.034-.036L3.183 14.067a1.1 1.1 0 0 1-.124-1.3l4-6.666Zm1.565 1.666-3.262 5.438 10.64 11.89 10.639-11.89-3.262-5.438H8.624Z M12.035 10.79c.521.313.69.989.378 1.51l-.38.632 2.117 2.328a1.1 1.1 0 0 1-1.628 1.48l-2.667-2.933a1.1 1.1 0 0 1-.129-1.306l.8-1.333a1.1 1.1 0 0 1 1.51-.378Z" clip-rule="evenodd"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Надежная<br>технология</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<img src="images/wide_funk.png" alt="" aria-hidden="true" width="32" height="32">
									</div>
									<p class="cooperation-main__content__tile__text">Широкий<br>функционал</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" d="M20.78 12.555a1.1 1.1 0 0 1 0 1.556l-5.333 5.333a1.1 1.1 0 0 1-1.556 0l-2.666-2.666a1.1 1.1 0 0 1 1.555-1.556l1.889 1.889 4.555-4.556a1.1 1.1 0 0 1 1.556 0Z M7.18 7.178C5.73 8.628 5.102 11.192 5.102 16c0 4.808.628 7.372 2.078 8.822 1.45 1.45 4.014 2.078 8.822 2.078 4.808 0 7.372-.627 8.823-2.078 1.45-1.45 2.077-4.014 2.077-8.822 0-4.808-.627-7.372-2.078-8.822-1.45-1.45-4.014-2.078-8.822-2.078-4.807 0-7.372.628-8.822 2.078ZM5.625 5.622C7.775 3.472 11.21 2.9 16.002 2.9c4.793 0 8.228.573 10.378 2.722 2.15 2.15 2.722 5.586 2.722 10.378s-.572 8.228-2.722 10.378c-2.15 2.15-5.585 2.722-10.378 2.722-4.792 0-8.228-.572-10.377-2.722-2.15-2.15-2.723-5.586-2.723-10.378s.573-8.228 2.723-10.378Z" clip-rule="evenodd"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Актуальное<br>наличие</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" d="M23 27.34a4.34 4.34 0 1 1 3.06-1.27A4.311 4.311 0 0 1 23 27.34Zm0-6.67a2.312 2.312 0 0 0-1.65.68 2.32 2.32 0 0 0-.02 3.32 2.39 2.39 0 0 0 3.3 0 2.34 2.34 0 0 0 0-3.3 2.34 2.34 0 0 0-1.63-.7ZM9.66 27.34a4.34 4.34 0 1 1 .417-8.67 4.34 4.34 0 0 1-.417 8.67Zm0-6.67a2.33 2.33 0 0 0-1.65 4 2.39 2.39 0 0 0 3.32 0 2.32 2.32 0 0 0 0-3.3 2.35 2.35 0 0 0-1.67-.7Z M27.33 24h-1a1 1 0 0 1 0-2h1.43a1 1 0 0 0 .57-.33.94.94 0 0 0 .28-.58 2.762 2.762 0 0 0 0-.44V17a7.66 7.66 0 0 0-7.28-7.66v11a1 1 0 1 1-2 0V9c0-1.45 0-2.41-.29-2.7-.29-.29-1.26-.3-2.71-.3H7c-1.46 0-2.42 0-2.71.3C4 6.6 4 7.55 4 9v10.67a5.69 5.69 0 0 0 .13 1.83.94.94 0 0 0 .37.37 5.65 5.65 0 0 0 1.83.13 1 1 0 0 1 0 2 5.48 5.48 0 0 1-2.83-.4 2.91 2.91 0 0 1-1.1-1.1 5.48 5.48 0 0 1-.4-2.83V9c0-2.09 0-3.24.88-4.12C3.76 4 4.88 4 7 4h9.33c2.09 0 3.24 0 4.12.88a3.39 3.39 0 0 1 .88 2.46A9.67 9.67 0 0 1 30.69 17v3.67c.014.23.014.46 0 .69a3 3 0 0 1-2.61 2.6c-.249.03-.5.044-.75.04Zm-7.69 0H13a1 1 0 0 1 0-2h6.66a1 1 0 1 1 0 2h-.02Z"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Официальный<br>дистрибьютор</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<img src="images/full_set.png" alt="" aria-hidden="true" width="32" height="32">
									</div>
									<p class="cooperation-main__content__tile__text">Полный комплект<br>для установки</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- ===== Популярные товары ===== -->
			<div class="product-widget">
				<div class="product-widget__inner">
					<div class="product-widget__container">
						<div class="product-widget__header">
							<h2 class="product-widget__title">Популярные товары</h2>
						</div>
						<div class="product-widget__block">
							<?php if (!empty($popular_products)): ?>
							<div class="product-widget__swiper swiper js-slider">
								<div class="swiper-wrapper">
									<?php foreach ($popular_products as $p):
										$section  = $p['_section'] ?? 'shower_system';
										$slug     = $p['slug'] ?? $p['article'] ?? '';
										$photos   = $p['photos'] ?? [];
										$photo    = !empty($photos) ? $photos[0] : '';
										$price    = $p['price'] ?? 0;
										$old_price = $p['old_price'] ?? 0;
										$name     = $p['name'] ?? '';
										$article  = $p['article'] ?? '';
										$discount = ($old_price > 0 && $price > 0)
											? '-' . round((1 - $price / $old_price) * 100) . '%'
											: '';
									?>
									<div class="product-widget__swiper-item swiper-slide">
										<div class="product-widget__card">
											<div class="category-products__item">
												<div class="category-products__item-header">
													<div class="category-products__item-top"></div>
													<a class="category-products__item-link" href="/<?= h($section) ?>/product/?slug=<?= h($slug) ?>">
														<div class="category-products__item-image">
															<?php if ($photo): ?>
															<img
																src="<?= h($photo) ?>"
																alt="<?= h($name) ?>"
																width="300"
																height="300"
																loading="lazy"
															>
															<?php endif; ?>
														</div>
													</a>
												</div>
												<div class="category-products__item-footer">
													<div class="category-products__item-price-wrapper">
														<div class="price-container">
															<div class="price-wrap">
																<span class="special"><?= format_price($price) ?></span>
																<?php if ($old_price > 0): ?>
																<del class="old"><?= format_price($old_price) ?></del>
																<?php endif; ?>
															</div>
															<?php if ($discount): ?>
															<span class="savings"><span class="savings__label"><?= h($discount) ?></span></span>
															<?php endif; ?>
														</div>
													</div>
													<div class="category-products__item-title">
														<a href="/<?= h($section) ?>/product/?slug=<?= h($slug) ?>"><?= h($name) ?></a>
													</div>
													<div class="category-products__item-sku">Арт. <span><?= h($article) ?></span></div>
													<div class="category-products__item-buy">
														<button
															type="button"
															class="btn-add-to-cart"
															onclick="cartAdd('<?= h($article) ?>', '<?= h(addslashes($name)) ?>', <?= (int)$price ?>, '<?= h($photo) ?>', '<?= h($section) ?>', '<?= h($slug) ?>')"
														>В корзину</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="product-widget__navigation js-slider-navigation">
								<button class="product-widget__navigation-btn js-slider-btn js-slider-btn_prev" type="button" aria-label="Предыдущий слайд">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" aria-hidden="true">
										<path fill="currentColor" fill-rule="evenodd" d="M10.838 12.99a1.4 1.4 0 0 1 0-1.98l4.586-4.586a.6.6 0 0 0-.848-.848L9.99 10.162a2.6 2.6 0 0 0 0 3.676l4.586 4.586a.6.6 0 0 0 .848-.848L10.84 12.99Z" clip-rule="evenodd"/>
									</svg>
								</button>
								<button class="product-widget__navigation-btn js-slider-btn js-slider-btn_next" type="button" aria-label="Следующий слайд">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" aria-hidden="true">
										<path fill="currentColor" fill-rule="evenodd" d="M13.162 12.99a1.4 1.4 0 0 0 0-1.98L8.575 6.424a.6.6 0 1 1 .848-.848l4.586 4.586a2.6 2.6 0 0 1 0 3.676l-4.586 4.586a.6.6 0 0 1-.848-.848l4.585-4.586Z" clip-rule="evenodd"/>
									</svg>
								</button>
							</div>
							<?php else: ?>
							<!-- Популярные товары не настроены — добавьте в админке -->
							<p style="color:#9a9fa8; font-size:14px; padding:24px 0;">
								Популярные товары не настроены. <a href="/admin/home_edit.php" style="color:#385081;">Добавить в админке →</a>
							</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- ===== Готовые решения ===== -->
			<div id="solutions-block">
				<div class="container">
					<div class="solutions-description">
						<h6 class="solutions__title">ГОТОВЫЕ РЕШЕНИЯ</h6>
						<div class="solutions__info">
							<p class="solutions__info__caption">Выберите лучшее,<br>что предлагает <span class="nowrap">WERGRAUF</span><br>на сегодняшний день</p>
							<div class="solutions__info__slogan slogan">
								<p class="slogan__text">Современный дизайн и высочайшее качество для вашего дома</p>
							</div>
						</div>
					</div>
					<div class="solutions-variants">
						<a href="https://wergrauf.ru/shower_system" class="solutions-variant">
							<div class="solutions-variant__img-container">
								<img class="solutions-variant__img" alt="Душ" src="images/solutions/001-shower.png" loading="lazy" width="940" height="1510">
							</div>
							<button class="solutions-variant__button" type="button">Душ</button>
						</a>
						<a href="https://wergrauf.ru/bath_faucets" class="solutions-variant">
							<div class="solutions-variant__img-container">
								<img class="solutions-variant__img" alt="Ванная" src="images/solutions/002-bath.png" loading="lazy" width="940" height="1510">
							</div>
							<button class="solutions-variant__button" type="button">Ванная</button>
						</a>
						<a href="https://wergrauf.ru/towel_warmers" class="solutions-variant">
							<div class="solutions-variant__img-container">
								<img class="solutions-variant__img" alt="Полотенцесушители" src="images/solutions/003-heater.png" loading="lazy" width="940" height="1510">
							</div>
							<button class="solutions-variant__button" type="button">Полотенцесушители</button>
						</a>
						<a href="https://wergrauf.ru/kitchen_faucets" class="solutions-variant">
							<div class="solutions-variant__img-container">
								<img class="solutions-variant__img" alt="Кухня" src="images/solutions/004-kitchen.png" loading="lazy" width="940" height="1510">
							</div>
							<button class="solutions-variant__button" type="button">Кухня</button>
						</a>
					</div>
				</div>
			</div>

			<!-- ===== Акции ===== -->
			<?php if (!empty($promos)): ?>
			<div id="promotions-block">
				<div class="container">
					<div class="block-header">
						<h6 class="block-header__title">Акции</h6>
					</div>
					<div class="promotions-tiles">
						<?php foreach ($promos as $promo): ?>
						<a
							href="<?= h($promo['link'] ?? '/') ?>"
							class="promotions-tile <?= h($promo['theme'] ?? '') ?>"
							aria-label="<?= h($promo['title'] ?? '') ?>"
						>
							<?php if (!empty($promo['image_desktop'])): ?>
							<div class="promotions-tile__img-container">
								<picture>
									<source srcset="<?= h($promo['image_mobile'] ?? $promo['image_desktop']) ?>" media="(max-width: 480px)">
									<source srcset="<?= h($promo['image_tablet'] ?? $promo['image_desktop']) ?>" media="(max-width: 1439px)">
									<img src="<?= h($promo['image_desktop']) ?>" class="promotions-tile__img" alt="" aria-hidden="true" loading="lazy">
								</picture>
							</div>
							<?php endif; ?>
							<div class="promotions-tile__content">
								<?php
								$is_dark = ($promo['theme'] ?? '') === 'theme-dark-blue';
								$title_class = $is_dark ? 'promotions-tile__content__title_light' : 'promotions-tile__content__title_dark';
								$text_class  = $is_dark ? 'promotions-tile__content__text_light'  : 'promotions-tile__content__text_dark';
								$btn_class   = $is_dark ? 'promotions-tile__button_light'          : 'promotions-tile__button_dark';
								?>
								<div class="promotions-tile__content__title <?= $title_class ?>">
									<?= h($promo['title'] ?? '') ?>
								</div>
								<div class="promotions-tile__content__text <?= $text_class ?>">
									<?= h($promo['text'] ?? '') ?>
								</div>
							</div>
							<button class="promotions-tile__button <?= $btn_class ?>" type="button">
								Подробнее
								<span class="button-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
										<path fill="currentColor" fill-rule="evenodd" d="M13.162 12.99a1.4 1.4 0 0 0 0-1.98L8.575 6.424a.6.6 0 1 1 .848-.848l4.586 4.586a2.6 2.6 0 0 1 0 3.676l-4.586 4.586a.6.6 0 0 1-.848-.848l4.585-4.586Z" clip-rule="evenodd"/>
									</svg>
								</span>
							</button>
						</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- ===== Пункт выдачи ===== -->
			<div id="showroom-block">
				<div class="container">
					<div class="block-header">
						<h6 class="block-header__title">Пункт выдачи заказов <span class="nowrap">в Москве</span></h6>
						<a href="https://yandex.ru/maps/?um=constructor%3Afc2e623f23fd011579b421fde8a1b71561730592f9ebab57f7fd429163a3bc4f&source=constructorStati" target="_blank" rel="noopener" class="block-header__button">
							Показать на карте
							<span class="button-icon" aria-hidden="true">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M13.1615 12.99C13.7082 12.4432 13.7082 11.5568 13.1615 11.0101L8.57571 6.42429C8.34139 6.18997 8.34139 5.81007 8.57571 5.57576C8.81002 5.34145 9.18992 5.34145 9.42424 5.57576L14.01 10.1615C15.0254 11.1769 15.0254 12.8231 14.01 13.8385L9.42424 18.4243C9.18992 18.6586 8.81002 18.6586 8.57571 18.4243C8.34139 18.19 8.34139 17.8101 8.57571 17.5758L13.1615 12.99Z" fill="#36393E"/>
								</svg>
							</span>
						</a>
					</div>
					<div class="showroom-content">
						<div class="showroom-info">
							<div class="address-sign">
								<div class="address-sign__address">
									<a class="address-sign__address__icon" href="https://yandex.ru/maps/213/moscow/house/ulitsa_dmitriya_ulyanova_42s1/Z04Ycw9pS0UDQFtvfXp5cH5lYQ==/" target="_blank" rel="noopener" aria-label="Открыть на Яндекс.Картах">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" aria-hidden="true">
											<path fill="currentColor" d="M12 2c-4.4 0-8 3.6-8 8 0 5.4 7 11.5 7.3 11.8.2.1.5.2.7.2.2 0 .5-.1.7-.2.3-.3 7.3-6.4 7.3-11.8 0-4.4-3.6-8-8-8Zm0 17.7c-2.1-2-6-6.3-6-9.7 0-3.3 2.7-6 6-6s6 2.7 6 6-3.9 7.7-6 9.7ZM12 6c-2.2 0-4 1.8-4 4s1.8 4 4 4 4-1.8 4-4-1.8-4-4-4Zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2Z"/>
										</svg>
									</a>
									<p class="address-sign__address__value">г. Москва, ул. Новгородская, д. 22</p>
								</div>
							</div>
						</div>
						<div class="image-map">
							<picture>
								<source type="image/webp" media="(min-width: 1024px)" srcset="images/maps/pickup-map-desktop1x.webp, images/maps/pickup-map-desktop2x.webp 2x">
								<source type="image/webp" media="(min-width: 768px)"  srcset="images/maps/pickup-map-tablet1x.webp, images/maps/pickup-map-tablet2x.webp 2x">
								<source type="image/webp" srcset="images/maps/pickup-map-mobile1x.webp, images/maps/pickup-map-mobile2x.webp 2x">
								<source media="(min-width: 1024px)" srcset="images/maps/pickup-map-desktop1x.jpeg, images/maps/pickup-map-desktop2x.jpeg 2x">
								<source media="(min-width: 768px)"  srcset="images/maps/pickup-map-tablet1x.jpeg, images/maps/pickup-map-tablet2x.jpeg 2x">
								<img class="pickup-map-image" alt="Карта с пунктом самовывоза" src="images/maps/pickup-map-mobile1x.jpeg" width="800" height="400" loading="lazy">
							</picture>
						</div>
					</div>
				</div>
			</div>

			<!-- ===== Сотрудничество ===== -->
			<div id="cooperation-block" class="section-cooperation">
				<div class="container">
					<div class="block-header">
						<h6 class="block-header__title">Сотрудничество</h6>
					</div>
					<div class="cooperation-main">
						<div class="cooperation-main__image-container">
							<picture>
								<source type="image/webp" media="(min-width: 1440px)" srcset="images/cooperation/cooperation-desktop1x.webp, images/cooperation/cooperation-desktop2x.webp 2x">
								<source type="image/webp" media="(min-width: 1024px)" srcset="images/cooperation/cooperation-laptop1x.webp, images/cooperation/cooperation-laptop2x.webp 2x">
								<source type="image/webp" media="(min-width: 768px)"  srcset="images/cooperation/cooperation-tablet1x.webp, images/cooperation/cooperation-tablet2x.webp 2x">
								<source type="image/webp" srcset="images/cooperation/cooperation-mobile1x.webp, images/cooperation/cooperation-mobile2x.webp 2x">
								<img class="coop-image" src="images/cooperation/cooperation-mobile1x.webp" alt="Фото ванной" width="940" height="1200" loading="lazy">
							</picture>
						</div>
						<div class="cooperation-main__content">
							<div class="cooperation-main__content__info">
								<h3 class="cooperation-main__content__info__caption">Мы сотрудничаем с компаниями, дизайнерами и мастерами</h3>
								<p class="cooperation-main__content__info__text">Узнайте о преимуществах работы наших корпоративных программ подробнее</p>
								<div class="cooperation-main__content__info__btns">
									<a href="https://wergrauf.ru/partners" class="cooperation-main__content__info__btn">Дизайнерам и мастерам</a>
									<a href="https://wergrauf.ru/partners" class="cooperation-main__content__info__btn">У меня компания</a>
								</div>
							</div>
							<div class="cooperation-main__content__tiles">
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M22.393 19.1v8.904c0 .229-.038.448-.108.653.517.143 1.07.243 1.588.243.36 0 .701-.05 1.012-.125a2.08 2.08 0 0 1-.176-.842V19.1h-2.316Zm-2.2-.1c0-1.16.94-2.1 2.1-2.1h2.516c1.16 0 2.1.94 2.1 2.1v1.1h3.093a1.1 1.1 0 1 1 0 2.2H26.91v5.537c.485.035.884.356 1.039.79a1.185 1.185 0 0 1-.46 1.385 6.713 6.713 0 0 1-3.615 1.088c-1.575 0-3.18-.608-4.024-1.01a1.183 1.183 0 0 1-.642-1.354c.111-.45.488-.82.986-.89v.158a.17.17 0 0 1 .17-.17c-.058 0-.115.003-.17.011V19ZM5.927 3.794C7.28 2.135 9.485.9 12.799.9s5.479 1.237 6.77 2.92c1.25 1.631 1.59 3.563 1.526 4.873a1.1 1.1 0 1 1-2.197-.108c.044-.902-.204-2.29-1.075-3.426-.83-1.083-2.319-2.059-5.024-2.059-2.706 0-4.264.978-5.168 2.085-.94 1.153-1.257 2.554-1.257 3.454v11.313H8.1v-2.653a1.1 1.1 0 0 1 2.2 0v2.653c.273 0 .6.052.918.223.873.467 1.7 1.376 1.7 2.897 0 1.522-.828 2.43-1.7 2.898-.319.17-.646.223-.92.223H6.374V30a1.1 1.1 0 1 1-2.2 0v-3.807h-.652c-.279 0-.593-.054-.897-.208-.385-.194-.841-.492-1.19-1.003-.356-.519-.533-1.156-.533-1.91s.177-1.39.532-1.91c.35-.51.806-.809 1.19-1.003a1.99 1.99 0 0 1 .898-.207h.652V8.639c0-1.313.436-3.231 1.753-4.845Z"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Широкий<br>ассортимент</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" color="#36393E" aria-hidden="true">
											<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9ZM12 8v4M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7M7.5 8a2.5 2.5 0 1 1 0-5c.965-.017 1.91.451 2.713 1.343C11.015 5.235 11.638 6.51 12 8c.362-1.49.985-2.765 1.787-3.657.803-.892 1.748-1.36 2.713-1.343a2.5 2.5 0 0 1 0 5"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Скидки<br>и бонусы</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" d="M23 27.34a4.34 4.34 0 1 1 3.06-1.27A4.311 4.311 0 0 1 23 27.34Zm0-6.67a2.312 2.312 0 0 0-1.65.68 2.32 2.32 0 0 0-.02 3.32 2.39 2.39 0 0 0 3.3 0 2.34 2.34 0 0 0 0-3.3 2.34 2.34 0 0 0-1.63-.7ZM9.66 27.34a4.34 4.34 0 1 1 .417-8.67 4.34 4.34 0 0 1-.417 8.67Zm0-6.67a2.33 2.33 0 0 0-1.65 4 2.39 2.39 0 0 0 3.32 0 2.32 2.32 0 0 0 0-3.3 2.35 2.35 0 0 0-1.67-.7Z M27.33 24h-1a1 1 0 0 1 0-2h1.43a1 1 0 0 0 .57-.33.94.94 0 0 0 .28-.58 2.762 2.762 0 0 0 0-.44V17a7.66 7.66 0 0 0-7.28-7.66v11a1 1 0 1 1-2 0V9c0-1.45 0-2.41-.29-2.7-.29-.29-1.26-.3-2.71-.3H7c-1.46 0-2.42 0-2.71.3C4 6.6 4 7.55 4 9v10.67a5.69 5.69 0 0 0 .13 1.83.94.94 0 0 0 .37.37 5.65 5.65 0 0 0 1.83.13 1 1 0 0 1 0 2 5.48 5.48 0 0 1-2.83-.4 2.91 2.91 0 0 1-1.1-1.1 5.48 5.48 0 0 1-.4-2.83V9c0-2.09 0-3.24.88-4.12C3.76 4 4.88 4 7 4h9.33c2.09 0 3.24 0 4.12.88a3.39 3.39 0 0 1 .88 2.46A9.67 9.67 0 0 1 30.69 17v3.67c.014.23.014.46 0 .69a3 3 0 0 1-2.61 2.6c-.249.03-.5.044-.75.04Zm-7.69 0H13a1 1 0 0 1 0-2h6.66a1 1 0 1 1 0 2h-.02Z"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Прямые<br>поставки</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" d="M20.78 12.555a1.1 1.1 0 0 1 0 1.556l-5.333 5.333a1.1 1.1 0 0 1-1.556 0l-2.666-2.666a1.1 1.1 0 0 1 1.555-1.556l1.889 1.889 4.555-4.556a1.1 1.1 0 0 1 1.556 0Z M7.18 7.178C5.73 8.628 5.102 11.192 5.102 16c0 4.808.628 7.372 2.078 8.822 1.45 1.45 4.014 2.078 8.822 2.078 4.808 0 7.372-.627 8.823-2.078 1.45-1.45 2.077-4.014 2.077-8.822 0-4.808-.627-7.372-2.078-8.822-1.45-1.45-4.014-2.078-8.822-2.078-4.807 0-7.372.628-8.822 2.078ZM5.625 5.622C7.775 3.472 11.21 2.9 16.002 2.9c4.793 0 8.228.573 10.378 2.722 2.15 2.15 2.722 5.586 2.722 10.378s-.572 8.228-2.722 10.378c-2.15 2.15-5.585 2.722-10.378 2.722-4.792 0-8.228-.572-10.377-2.722-2.15-2.15-2.723-5.586-2.723-10.378s.573-8.228 2.723-10.378Z" clip-rule="evenodd"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Актуальное<br>наличие</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" d="M11.45 4.784a6.433 6.433 0 1 1 9.099 9.098 6.433 6.433 0 0 1-9.098-9.098ZM16 5.1a4.233 4.233 0 1 0 0 8.467A4.233 4.233 0 0 0 16 5.1ZM13.332 21.1a4.233 4.233 0 0 0-4.234 4.233V28a1.1 1.1 0 0 1-2.2 0v-2.667a6.433 6.433 0 0 1 6.434-6.433h.666a1.1 1.1 0 1 1 0 2.2h-.666Z" clip-rule="evenodd"/>
											<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m23.998 29.334 4.466-4.379a2.857 2.857 0 0 0 .007-4.095 2.99 2.99 0 0 0-4.172-.008l-.299.294-.297-.294a2.99 2.99 0 0 0-4.17-.008 2.857 2.857 0 0 0-.009 4.095l4.474 4.395Z"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Персональный<br>менеджер</p>
								</div>
								<div class="cooperation-main__content__tile">
									<div class="cooperation-main__content__tile__icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32" color="#36393E" aria-hidden="true">
											<path fill="currentColor" fill-rule="evenodd" d="M7.06 6.101a1.1 1.1 0 0 1 .942-.534h16a1.1 1.1 0 0 1 .944.534l4 6.667a1.1 1.1 0 0 1-.124 1.3L17.49 26.733a2.036 2.036 0 0 1-2.939.036 1.17 1.17 0 0 1-.034-.036L3.183 14.067a1.1 1.1 0 0 1-.124-1.3l4-6.666Zm1.565 1.666-3.262 5.438 10.64 11.89 10.639-11.89-3.262-5.438H8.624Z M12.035 10.79c.521.313.69.989.378 1.51l-.38.632 2.117 2.328a1.1 1.1 0 0 1-1.628 1.48l-2.667-2.933a1.1 1.1 0 0 1-.129-1.306l.8-1.333a1.1 1.1 0 0 1 1.51-.378Z" clip-rule="evenodd"/>
										</svg>
									</div>
									<p class="cooperation-main__content__tile__text">Предзаказ<br>эксклюзивов</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</section>

	<?php include $_SERVER['DOCUMENT_ROOT'] . '/source_include/foot.html'; ?>

</div>

<script>
/* Инициализация слайдера популярных товаров */
document.addEventListener('DOMContentLoaded', function () {
	var sliders = document.querySelectorAll('.js-slider');
	sliders.forEach(function (slider) {
		var block = slider.closest('.product-widget__block');
		new Swiper(slider, {
			slidesPerView: 'auto',
			spaceBetween: 12,
			speed: 900,
			navigation: {
				nextEl: block ? block.querySelector('.js-slider-btn_next') : null,
				prevEl: block ? block.querySelector('.js-slider-btn_prev') : null,
			},
			breakpoints: {
				0:    { slidesPerView: 'auto', spaceBetween: 12 },
				768:  { slidesPerView: 'auto', spaceBetween: 20 },
				1024: { slidesPerView: 4,      spaceBetween: 20 },
				1440: { slidesPerView: 6,      spaceBetween: 24 },
			}
		});
	});
});
</script>

</body>
</html>
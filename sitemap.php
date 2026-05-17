<?php
/* sitemap.php — генерация XML-карты сайта
   Статичные страницы — хардкод, товары — из /data/*.json */

$base = 'https://wergrauf.ru';
$data = $_SERVER['DOCUMENT_ROOT'] . '/data/';

/* Разделы каталога: ключ = имя JSON-файла = папка */
$sections = [
	'shower_system'    => '/shower_system/',
	'kitchen_faucets'  => '/kitchen_faucets/',
	'floor_faucets'    => '/floor_faucets/',
	'bath_faucets'     => '/bath_faucets/',
	'sink_faucets'     => '/sink_faucets/',
	'hygienic_shower'  => '/hygienic_shower/',
	'accessories'      => '/accessories/',
	'towel_warmers'    => '/towel_warmers/',
	'components'       => '/components/',
];

/* Статичные страницы */
$static = [
	['loc' => '/',                  'priority' => '1.0',  'changefreq' => 'weekly'],
	['loc' => '/about_company/',    'priority' => '0.5',  'changefreq' => 'monthly'],
	['loc' => '/contacts/',         'priority' => '0.6',  'changefreq' => 'monthly'],
	['loc' => '/delivery/',         'priority' => '0.6',  'changefreq' => 'monthly'],
	['loc' => '/payment/',          'priority' => '0.6',  'changefreq' => 'monthly'],
	['loc' => '/pickup/',           'priority' => '0.5',  'changefreq' => 'monthly'],
	['loc' => '/warranty/',         'priority' => '0.5',  'changefreq' => 'monthly'],
	['loc' => '/return/',           'priority' => '0.5',  'changefreq' => 'monthly'],
	['loc' => '/service/',          'priority' => '0.4',  'changefreq' => 'monthly'],
	['loc' => '/partners/',         'priority' => '0.4',  'changefreq' => 'monthly'],
	['loc' => '/instructions/',     'priority' => '0.4',  'changefreq' => 'monthly'],
];

/* Заголовок */
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$today = date('Y-m-d');

/* Статичные страницы */
foreach ($static as $p) {
	echo url($base . $p['loc'], $today, $p['changefreq'], $p['priority']);
}

/* Разделы и товары */
foreach ($sections as $key => $section_url) {
	$file = $data . $key . '.json';
	if (!file_exists($file)) continue;

	$products = json_decode(file_get_contents($file), true);
	if (!is_array($products) || !count($products)) continue;

	/* Страница раздела */
	echo url($base . $section_url, $today, 'weekly', '0.8');

	/* Карточки товаров */
	foreach ($products as $p) {
		if (empty($p['slug'])) continue;
		if (isset($p['hidden']) && $p['hidden']) continue;
		echo url($base . $section_url . $p['slug'] . '/', $today, 'weekly', '0.7');
	}
}

echo '</urlset>';

/* Вспомогательная функция */
function url(string $loc, string $lastmod, string $changefreq, string $priority): string {
	return "\t<url>\n"
		. "\t\t<loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n"
		. "\t\t<lastmod>{$lastmod}</lastmod>\n"
		. "\t\t<changefreq>{$changefreq}</changefreq>\n"
		. "\t\t<priority>{$priority}</priority>\n"
		. "\t</url>\n";
}
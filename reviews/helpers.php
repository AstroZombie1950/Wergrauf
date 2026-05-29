<?php
/* reviews/helpers.php — функции для работы с отзывами на фронте */

if (!defined('REVIEWS_FILE')) {
	define('REVIEWS_FILE', $_SERVER['DOCUMENT_ROOT'] . '/data/reviews.json');
}

/* Загрузить опубликованные отзывы по артикулу */
function reviews_get_published(string $article): array {
	if (!file_exists(REVIEWS_FILE)) return [];
	$all = json_decode(file_get_contents(REVIEWS_FILE), true) ?? [];
	$list = $all[$article] ?? [];
	return array_values(array_filter($list, fn($r) => !empty($r['published'])));
}

/* Средняя оценка */
function reviews_avg(array $reviews): float {
	if (empty($reviews)) return 0.0;
	return array_sum(array_column($reviews, 'rating')) / count($reviews);
}

/* HTML звёздочек */
function reviews_stars(int $rating, string $prefix = ''): string {
	$out = '<span class="rv-stars">';
	for ($i = 1; $i <= 5; $i++) {
		$cls = $i <= $rating ? 'rv-star rv-star--on' : 'rv-star';
		$out .= "<span class=\"$cls\">★</span>";
	}
	$out .= '</span>';
	return $out;
}
<?php
/* sync.php — синхронизация Google Sheets → JSON
   Подключается из admin/index.php, напрямую недоступен.
   Скачанные фото ужимаются и сохраняются в WebP. */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/image.php';

// Базовые поля — не попадают в specs
const BASE_FIELDS = [
	'id', 'slug', 'meta_title', 'meta_description',
	'image', 'gallery', 'name', 'model', 'article', 'category',
	'stock', 'price', 'old_price', 'description',
	'discount_percent', 'promo_code',
];

// --- Синхронизировать все разделы ---
function sync_all(): array {
	$results = [];
	@set_time_limit(0); // первая синхронизация может качать сотни изображений
	if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
	foreach (SHEET_MAP as $sheet_name => $section) {
		$results[$section] = sync_sheet($sheet_name, $section);
	}
	// Записываем в лог
	write_sync_log($results);
	return $results;
}

// --- Синхронизировать один раздел ---
function sync_sheet(string $sheet_name, string $section): array {
	$url = API_BASE . SPREADSHEET_ID . '/values/' . urlencode($sheet_name)
		. '?key=' . SHEETS_API_KEY;

	$response = sheets_request($url);

	if (isset($response['error'])) {
		return [
			'success' => false,
			'error'   => $response['error']['message'] ?? 'Неизвестная ошибка API',
			'count'   => 0,
			'skipped' => 0,
		];
	}

	$rows = $response['values'] ?? [];
	if (empty($rows)) {
		return ['success' => true, 'count' => 0, 'skipped' => 0];
	}

	$headers  = normalize_headers(array_shift($rows));
	$products = [];
	$skipped  = 0;

	foreach ($rows as $row) {
		$item = parse_row($row, $headers);

		if (empty($item['slug']) || mb_strtolower($item['slug']) === 'нет данных') {
			$skipped++;
			continue;
		}
		// Пропускаем только нулевую цену — нет смысла в товаре без цены
		// Нулевой остаток допустим: страница живёт для SEO, кнопка покупки скрыта
		if ($item['price'] === 0) {
			$skipped++;
			continue;
		}

		$products[] = $item;
	}

	// Оверрайды имеют приоритет над данными из таблицы
	$overrides = [];
	$override_file = OVERRIDE_DIR . $section . '.json';
	if (file_exists($override_file)) {
		$overrides = json_decode(file_get_contents($override_file), true) ?? [];
	}

	// Локализация фото: внешние ссылки скачиваем к себе на хост (в WebP)
	$img_stats = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

	foreach ($products as &$product) {
		$article = (string)$product['article'];
		$ov      = $overrides[$article] ?? [];

		// Фото из таблицы локализуем, кроме случаев, когда оно задано вручную в оверрайде
		if (!isset($ov['image'])) {
			$product['image'] = localize_image($product['image'] ?? '', $section, $product['slug'], '', $img_stats);
		}
		if (!isset($ov['gallery'])) {
			$gallery = [];
			foreach (($product['gallery'] ?? []) as $n => $g_url) {
				$gallery[] = localize_image($g_url, $section, $product['slug'], '-' . ($n + 1), $img_stats);
			}
			$product['gallery'] = $gallery;
		}

		// Оверрайды поверх данных таблицы
		if ($ov) {
			$product = array_merge($product, $ov);
		}
	}
	unset($product);

	file_put_contents(
		DATA_DIR . $section . '.json',
		json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);

	// Лог несработавших скачиваний — для ручной проверки/повтора
	if (!empty($img_stats['errors'])) {
		file_put_contents(
			DATA_DIR . 'sync_images.log',
			date('d.m.Y H:i:s') . " [$section]\n" . implode("\n", $img_stats['errors']) . "\n\n",
			FILE_APPEND
		);
	}

	return [
		'success' => true,
		'count'   => count($products),
		'skipped' => $skipped,
		'images'  => $img_stats,
	];
}

// --- Разбор строки ---
function parse_row(array $row, array $headers): array {
	$item = [];
	foreach ($headers as $i => $key) {
		$item[$key] = isset($row[$i]) ? trim($row[$i]) : '';
	}

	// Типизация
	$item['price']     = (int)str_replace([' ', ','], ['', '.'], $item['price']     ?? '');
	$item['old_price'] = (int)str_replace([' ', ','], ['', '.'], $item['old_price'] ?? '');
	$item['stock']     = (int)($item['stock'] ?? 0);

	// Галерея: строка с запятыми → массив
	if (!empty($item['gallery'])) {
		$item['gallery'] = array_values(array_filter(
			array_map('trim', explode(',', $item['gallery']))
		));
	} else {
		$item['gallery'] = [];
	}

	// Specs: все поля spec_* → отдельный массив
	$specs = [];
	foreach ($item as $key => $val) {
		if (str_starts_with($key, 'spec_') && $val !== '') {
			$specs[] = [
				'key'   => $key,
				'label' => SPEC_LABELS[$key] ?? $key,
				'value' => $val,
			];
		}
	}
	$item['specs'] = $specs;

	return $item;
}

// --- Скачать внешнее изображение → вернуть его байты ---
// Строка с содержимым при успехе, иначе false
function fetch_image_bytes(string $url): string|false {
	$bin = false;

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; WergraufBot/1.0)',
		]);
		$bin  = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($bin === false || $code >= 400) $bin = false;
	} else {
		$ctx = stream_context_create(['http' => [
			'method'  => 'GET',
			'timeout' => 20,
			'header'  => "User-Agent: Mozilla/5.0 (compatible; WergraufBot/1.0)\r\n",
		]]);
		$bin = @file_get_contents($url, false, $ctx);
	}

	if ($bin === false || strlen($bin) < 100) return false;

	// Проверяем, что это реально изображение
	$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bin);
	if (!str_starts_with((string)$mime, 'image/')) return false;

	return $bin;
}

// --- Локализовать одну ссылку на изображение ---
// Внешняя ссылка → скачать, ужать, сохранить в WebP, вернуть локальный URL.
// Уже локальная/пустая → вернуть как есть. При ошибке → вернуть исходную ссылку (запасную).
function localize_image(string $url, string $section, string $slug, string $suffix, array &$stats): string {
	$url = trim($url);
	if ($url === '') return '';

	// Уже на нашем хосте — ничего не делаем
	if (str_starts_with($url, '/images/products/')) return $url;

	// Не http(s) — оставляем как есть
	if (!preg_match('~^https?://~i', $url)) return $url;

	// Имя файла по слагу: только латиница, цифры, дефис. Расширение всегда .webp
	$safe_slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
	if ($safe_slug === '') $safe_slug = 'img';

	$filename  = $safe_slug . $suffix . '.webp';
	$dir       = $_SERVER['DOCUMENT_ROOT'] . '/images/products/' . $section . '/';
	$local_url = '/images/products/' . $section . '/' . $filename;
	$dest      = $dir . $filename;

	// Уже скачано — пропускаем (идемпотентность)
	if (file_exists($dest)) {
		$stats['skipped']++;
		img_make_variants($dest); // догенерим card/thumb, если их ещё нет
		return $local_url;
	}

	if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
		$stats['failed']++;
		$stats['errors'][] = $slug . ': не удалось создать папку ' . $dir;
		return $url; // запасная внешняя ссылка
	}

	$bin = fetch_image_bytes($url);
	if ($bin !== false && img_bin_to_webp($bin, $dest)) {
		$stats['downloaded']++;
		img_make_variants($dest); // card/thumb рядом с оригиналом
		return $local_url;
	}

	// Не скачалось/не обработалось — оставляем внешнюю ссылку, пишем в лог
	$stats['failed']++;
	$stats['errors'][] = $slug . ': ' . $url;
	return $url;
}

// --- Нормализация заголовков → snake_case ---
function normalize_headers(array $headers): array {
	$map = [
		'ID'                                     => 'id',
		'Slug'                                   => 'slug',
		'META-title'                             => 'meta_title',
		'META-description'                       => 'meta_description',
		'Изображение'                            => 'image',
		'Галерея'                                => 'gallery',
		'Название'                               => 'name',
		'Модель'                                 => 'model',
		'Артикул'                                => 'article',
		'Категория'                              => 'category',
		'Остаток'                                => 'stock',
		'Цена'                                   => 'price',
		'Цена до скидки'                         => 'old_price',
		'Описание'                               => 'description',
		'Скидка по промокоду'                    => 'discount_percent',
		'Промокод'                               => 'promo_code',
		'Комплектация'                           => 'spec_kit',
		'Материал'                               => 'spec_material',
		'Гарантия'                               => 'spec_warranty',
		'Гарантийный срок (лет)'                 => 'spec_warranty',
		'Коллекция'                              => 'spec_collection',
		'Цвет'                                   => 'spec_color',
		'Покрытие'                               => 'spec_coating',
		'Встраиваемая'                           => 'spec_built_in',
		'Термостат'                              => 'spec_thermostat',
		'Количество режимов'                     => 'spec_modes_count',
		'Режимы'                                 => 'spec_modes',
		'Дизайн'                                 => 'spec_design',
		'Размер душа'                            => 'spec_shower_size',
		'Высота стойки'                          => 'spec_stand_height',
		'Вид излива'                             => 'spec_spout_type',
		'Вращение излива'                        => 'spec_spout_rotation',
		'Запорный клапан'                        => 'spec_valve',
		'Механизм смесителя'                     => 'spec_mechanism',
		'Назначение смесителя'                   => 'spec_purpose',
		'Наличие душевого гарнитура в комплекте' => 'spec_shower_set',
		'Отверстия для монтажа'                  => 'spec_mount_holes',
		'Тип подводки'                           => 'spec_pipe_type',
		'Вес с упаковкой (кг)'                   => 'spec_weight',
		'Высота упаковки'                        => 'spec_pkg_height',
		'Длина упаковки'                         => 'spec_pkg_length',
		'Ширина упаковки'                        => 'spec_pkg_width',
		'Подключение'                            => 'spec_connection',
		'Полочка'                                => 'spec_shelf',
		'Дисплей'                                => 'spec_display',
		'Подключение полотенцесушителя'          => 'spec_heater_connection',
		'Тип полотенцесушителя'                  => 'spec_heater_type',
		'Форма полотенцесушителя'                => 'spec_heater_shape',
	];

	$result = [];
	foreach ($headers as $h) {
		$result[] = $map[$h] ?? strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $h));
	}
	return $result;
}

// --- HTTP запрос ---
function sheets_request(string $url): array {
	$ctx = stream_context_create([
		'http' => [
			'method'  => 'GET',
			'timeout' => 15,
			'header'  => 'Accept: application/json',
		],
	]);

	$body = @file_get_contents($url, false, $ctx);

	if ($body === false) {
		return ['error' => ['message' => 'Не удалось выполнить запрос к API. Проверьте ключ и доступность таблицы.']];
	}

	$decoded = json_decode($body, true);
	return $decoded ?? ['error' => ['message' => 'Ошибка парсинга ответа от API']];
}

// Прямой доступ запрещён
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
	http_response_code(403);
	exit('Forbidden');
}
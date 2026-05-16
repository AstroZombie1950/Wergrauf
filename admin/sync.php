<?php
/* sync.php — синхронизация Google Sheets → JSON
   Подключается из admin/index.php, напрямую недоступен */

require_once __DIR__ . '/config.php';

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
		if ($item['price'] === 0 || $item['stock'] === 0) {
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

	foreach ($products as &$product) {
		$article = (string)$product['article'];
		if (isset($overrides[$article])) {
			$product = array_merge($product, $overrides[$article]);
		}
	}
	unset($product);

	file_put_contents(
		DATA_DIR . $section . '.json',
		json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);

	return ['success' => true, 'count' => count($products), 'skipped' => $skipped];
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

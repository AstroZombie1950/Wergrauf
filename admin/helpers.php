<?php
/* helpers.php — общие функции админки */

// --- Авторизация ---

function admin_check_auth(): void {
	session_start();
	if (empty($_SESSION['admin_logged_in'])) {
		header('Location: /admin/');
		exit;
	}
}

// --- Работа с данными ---

// Загрузить все товары раздела (основные данные + оверрайды + ручные товары)
function load_products(string $section): array {
	$file = DATA_DIR . $section . '.json';
	$products  = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
	$overrides = load_overrides($section);

	// Мержим оверрайды поверх данных из таблицы
	foreach ($products as &$product) {
		$article = (string)$product['article'];
		if (isset($overrides[$article])) {
			$product = array_merge($product, $overrides[$article]);
			$product['_has_overrides'] = true;
		}
		// Убираем удалённые характеристики
		if (!empty($product['_deleted_specs']) && !empty($product['specs'])) {
			$deleted = $product['_deleted_specs'];
			$product['specs'] = array_values(
				array_filter($product['specs'], fn($s) => !in_array($s['key'], $deleted, true))
			);
		}
		// Добавляем доп. характеристики из оверрайда в конец списка
		if (!empty($product['_extra_specs'])) {
			foreach ($product['_extra_specs'] as $es) {
				$product['specs'][] = [
					'key'   => '_extra_' . md5($es['label']),
					'label' => $es['label'],
					'value' => $es['value'],
				];
			}
		}
	}
	unset($product);

	// Добавляем ручные товары (которых нет в основном JSON)
	$existing_articles = array_column($products, 'article');
	foreach ($overrides as $article => $override) {
		if (!empty($override['_manual']) && !in_array($article, $existing_articles, true)) {
			$override['article']       = $article;
			$override['_has_overrides'] = true;
			$products[]                = $override;
		}
	}

	// Применяем сохранённый порядок
	$order = load_order($section);
	if (!empty($order)) {
		$indexed = [];
		foreach ($products as $p) {
			$indexed[(string)$p['article']] = $p;
		}
		$sorted = [];
		// Сначала товары из order-файла в нужном порядке
		foreach ($order as $article) {
			if (isset($indexed[$article])) {
				$sorted[] = $indexed[$article];
				unset($indexed[$article]);
			}
		}
		// Новые товары, которых нет в order-файле — в конец
		foreach ($indexed as $p) {
			$sorted[] = $p;
		}
		$products = $sorted;
	}

	return $products;
}

// Найти товар по slug
function find_product(string $section, string $slug): ?array {
	foreach (load_products($section) as $product) {
		if ($product['slug'] === $slug) return $product;
	}
	return null;
}

// Найти товар по артикулу
function find_product_by_article(string $section, string $article): ?array {
	foreach (load_products($section) as $product) {
		if ((string)$product['article'] === $article) return $product;
	}
	return null;
}

// --- Оверрайды ---

function load_overrides(string $section): array {
	$file = OVERRIDE_DIR . $section . '.json';
	return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
}

function save_override(string $section, string $article, array $fields): bool {
	if (!is_dir(OVERRIDE_DIR)) mkdir(OVERRIDE_DIR, 0755, true);
	$overrides           = load_overrides($section);
	$overrides[$article] = $fields;
	return file_put_contents(
		OVERRIDE_DIR . $section . '.json',
		json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	) !== false;
}

function delete_override(string $section, string $article): bool {
	$overrides = load_overrides($section);
	if (!isset($overrides[$article])) return true;
	unset($overrides[$article]);
	return file_put_contents(
		OVERRIDE_DIR . $section . '.json',
		json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	) !== false;
}

// --- Удаление товара из основного JSON ---
// При следующей синхронизации товар вернётся если он есть в таблице

function delete_product_from_json(string $section, string $article): bool {
	$file = DATA_DIR . $section . '.json';
	if (!file_exists($file)) return true;

	$products = json_decode(file_get_contents($file), true) ?? [];
	$filtered = array_values(array_filter($products, fn($p) => (string)$p['article'] !== $article));

	return file_put_contents(
		$file,
		json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	) !== false;
}

// --- Порядок товаров ---

function load_order(string $section): array {
	$file = DATA_DIR . 'order_' . $section . '.json';
	return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
}

function save_order(string $section, array $articles): bool {
	if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
	return file_put_contents(
		DATA_DIR . 'order_' . $section . '.json',
		json_encode(array_values($articles), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	) !== false;
}

// --- Флаг скрытия ---

function set_product_hidden(string $section, string $article, bool $hidden): bool {
	$existing = load_overrides($section)[$article] ?? [];
	if ($hidden) {
		$existing['hidden'] = true;
	} else {
		unset($existing['hidden']);
	}
	// Если оверрайд стал пустым — удаляем
	if (empty($existing)) return delete_override($section, $article);
	return save_override($section, $article, $existing);
}

// --- Статистика раздела ---

function section_stats(string $section): array {
	$file = DATA_DIR . $section . '.json';
	if (!file_exists($file)) {
		return ['total' => 0, 'in_stock' => 0, 'hidden' => 0, 'synced_at' => null, 'overrides' => 0];
	}
	$products  = load_products($section);
	$overrides = load_overrides($section);

	return [
		'total'     => count($products),
		'in_stock'  => count(array_filter($products, fn($p) => (int)$p['stock'] > 0 && empty($p['hidden']))),
		'hidden'    => count(array_filter($products, fn($p) => !empty($p['hidden']))),
		'synced_at' => filemtime($file),
		'overrides' => count($overrides),
	];
}

// --- Лог синхронизаций ---

function write_sync_log(array $results): void {
	if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

	$log_file = DATA_DIR . 'sync_log.json';
	$log      = file_exists($log_file) ? (json_decode(file_get_contents($log_file), true) ?? []) : [];

	// Новая запись
	$entry = [
		'ts'      => time(),
		'date'    => date('d.m.Y H:i:s'),
		'results' => [],
	];

	foreach ($results as $section => $r) {
		$entry['results'][] = [
			'section' => $section,
			'name'    => SECTION_NAMES[$section] ?? $section,
			'success' => $r['success'],
			'count'   => $r['count']   ?? 0,
			'skipped' => $r['skipped'] ?? 0,
			'error'   => $r['error']   ?? null,
			'images'  => $r['images']  ?? null,
		];
	}

	array_unshift($log, $entry);

	// Ротация: храним записи не старше 30 дней
	$cutoff = time() - 30 * 86400;
	$log    = array_values(array_filter($log, fn($e) => $e['ts'] >= $cutoff));

	file_put_contents($log_file, json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function read_sync_log(): array {
	$log_file = DATA_DIR . 'sync_log.json';
	return file_exists($log_file) ? (json_decode(file_get_contents($log_file), true) ?? []) : [];
}

// --- Смена пароля ---
// Пароль хранится в /data/admin_credentials.json

function load_credentials(): array {
	$file = DATA_DIR . 'admin_credentials.json';
	if (file_exists($file)) {
		return json_decode(file_get_contents($file), true) ?? [];
	}
	// Фолбек на config.php
	return ['login' => ADMIN_LOGIN, 'password' => ADMIN_PASSWORD];
}

function save_credentials(string $login, string $password): bool {
	if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
	return file_put_contents(
		DATA_DIR . 'admin_credentials.json',
		json_encode(['login' => $login, 'password' => $password], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	) !== false;
}

function check_credentials(string $login, string $password): bool {
	$creds = load_credentials();
	return $creds['login'] === $login && $creds['password'] === $password;
}

// --- Названия характеристик ---
// Используется в sync.php (при парсинге) и product_new.php (подписи полей формы)

const SPEC_LABELS = [
	'spec_color'             => 'Цвет',
	'spec_coating'           => 'Покрытие',
	'spec_material'          => 'Материал',
	'spec_kit'               => 'Комплектация',
	'spec_warranty'          => 'Гарантия',
	'spec_collection'        => 'Коллекция',
	'spec_built_in'          => 'Встраиваемая',
	'spec_thermostat'        => 'Термостат',
	'spec_modes_count'       => 'Количество режимов',
	'spec_modes'             => 'Режимы',
	'spec_design'            => 'Дизайн',
	'spec_shower_size'       => 'Размер душа',
	'spec_stand_height'      => 'Высота стойки',
	'spec_spout_type'        => 'Вид излива',
	'spec_spout_rotation'    => 'Вращение излива',
	'spec_valve'             => 'Запорный клапан',
	'spec_mechanism'         => 'Механизм смесителя',
	'spec_purpose'           => 'Назначение',
	'spec_shower_set'        => 'Душевой гарнитур в комплекте',
	'spec_mount_holes'       => 'Отверстия для монтажа',
	'spec_pipe_type'         => 'Тип подводки',
	'spec_weight'            => 'Вес с упаковкой (кг)',
	'spec_pkg_height'        => 'Высота упаковки',
	'spec_pkg_length'        => 'Длина упаковки',
	'spec_pkg_width'         => 'Ширина упаковки',
	'spec_connection'        => 'Подключение',
	'spec_shelf'             => 'Полочка',
	'spec_display'           => 'Дисплей',
	'spec_heater_connection' => 'Подключение полотенцесушителя',
	'spec_heater_type'       => 'Тип полотенцесушителя',
	'spec_heater_shape'      => 'Форма полотенцесушителя',
];

// --- Утилиты ---

function h($str): string {
	return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function format_price($price): string {
	return number_format((int)$price, 0, '.', ' ') . ' ₽';
}

function time_ago(int $timestamp): string {
	$diff = time() - $timestamp;
	if ($diff < 60)    return 'только что';
	if ($diff < 3600)  return (int)($diff / 60) . ' мин. назад';
	if ($diff < 86400) return (int)($diff / 3600) . ' ч. назад';
	return date('d.m.Y H:i', $timestamp);
}
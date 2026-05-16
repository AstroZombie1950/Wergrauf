<?php
/* admin/upload.php — загрузка и удаление изображений товаров
   Принимает POST-запросы из product_edit и product_new, возвращает JSON */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

header('Content-Type: application/json; charset=utf-8');

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$section = $_POST['section'] ?? $_GET['section'] ?? '';

// Базовая валидация раздела
if (!array_key_exists($section, SECTION_NAMES)) {
	echo json_encode(['ok' => false, 'error' => 'Неизвестный раздел']);
	exit;
}

// Папка для загрузок раздела
$upload_dir  = $_SERVER['DOCUMENT_ROOT'] . '/images/products/' . $section . '/';
$upload_url  = '/images/products/' . $section . '/';
$allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$max_size    = 8 * 1024 * 1024; // 8 МБ

// --- Загрузка файла ---
if ($action === 'upload') {
	if (empty($_FILES['file'])) {
		echo json_encode(['ok' => false, 'error' => 'Файл не передан']);
		exit;
	}

	$file = $_FILES['file'];

	if ($file['error'] !== UPLOAD_ERR_OK) {
		echo json_encode(['ok' => false, 'error' => 'Ошибка загрузки: код ' . $file['error']]);
		exit;
	}

	if ($file['size'] > $max_size) {
		echo json_encode(['ok' => false, 'error' => 'Файл слишком большой (максимум 8 МБ)']);
		exit;
	}

	// Проверяем расширение
	$original_name = $file['name'];
	$ext           = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
	if (!in_array($ext, $allowed_ext, true)) {
		echo json_encode(['ok' => false, 'error' => 'Недопустимый формат. Разрешены: ' . implode(', ', $allowed_ext)]);
		exit;
	}

	// Проверяем что это реально изображение (magic bytes)
	$mime = mime_content_type($file['tmp_name']);
	if (!str_starts_with($mime, 'image/')) {
		echo json_encode(['ok' => false, 'error' => 'Файл не является изображением']);
		exit;
	}

	// Создаём папку если нет
	if (!is_dir($upload_dir)) {
		if (!mkdir($upload_dir, 0755, true)) {
			echo json_encode(['ok' => false, 'error' => 'Не удалось создать папку ' . $upload_dir]);
			exit;
		}
	}

	// Генерируем безопасное имя файла
	$safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
	$safe_name = trim($safe_name, '_');
	$filename  = $safe_name . '.' . $ext;
	$dest      = $upload_dir . $filename;

	// Добавляем суффикс если файл уже существует
	if (file_exists($dest)) {
		$i = 1;
		do {
			$filename = $safe_name . '_' . $i . '.' . $ext;
			$dest     = $upload_dir . $filename;
			$i++;
		} while (file_exists($dest));
	}

	if (!move_uploaded_file($file['tmp_name'], $dest)) {
		echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить файл на сервер']);
		exit;
	}

	echo json_encode([
		'ok'  => true,
		'url' => $upload_url . $filename,
		'name' => $filename,
	]);
	exit;
}

// --- Удаление файла ---
if ($action === 'delete') {
	$url = $_POST['url'] ?? '';

	// Защита: файл должен быть внутри нашей папки
	$expected_prefix = $upload_url;
	if (!str_starts_with($url, $expected_prefix)) {
		echo json_encode(['ok' => false, 'error' => 'Недопустимый путь к файлу']);
		exit;
	}

	$filename = basename($url);
	// Ещё раз проверяем расширение — на случай path traversal
	$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	if (!in_array($ext, $allowed_ext, true)) {
		echo json_encode(['ok' => false, 'error' => 'Недопустимый формат файла']);
		exit;
	}

	$path = $upload_dir . $filename;
	if (!file_exists($path)) {
		// Файла нет — считаем успехом (может уже удалён)
		echo json_encode(['ok' => true]);
		exit;
	}

	if (!unlink($path)) {
		echo json_encode(['ok' => false, 'error' => 'Не удалось удалить файл. Проверьте права доступа.']);
		exit;
	}

	echo json_encode(['ok' => true]);
	exit;
}

echo json_encode(['ok' => false, 'error' => 'Неизвестное действие']);

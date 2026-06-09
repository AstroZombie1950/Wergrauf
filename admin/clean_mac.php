<?php
/* admin/clean_mac.php — чистка мусора Mac/Windows (.DS_Store, ._*, __MACOSX и т.п.)
   По умолчанию — предпросмотр (ничего не удаляет).
   Реальное удаление: открыть с ?delete=1
   Можно оставить на сервере как обслуживающий инструмент (под логином админки). */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

@set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');

$do_delete = isset($_GET['delete']) && $_GET['delete'] === '1';
$root      = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__);

// Точные имена файлов-мусора
$junk_files = ['.DS_Store', '.apdisk', 'Thumbs.db', 'desktop.ini', 'Icon\r'];
// Директории-мусор (удаляются целиком)
$junk_dirs  = ['__MACOSX', '.Spotlight-V100', '.Trashes', '.fseventsd',
               '.DocumentRevisions-V100', '.TemporaryItems'];

// --- Рекурсивное удаление папки ---
function rrmdir(string $dir): int {
	$freed = 0;
	foreach (scandir($dir) as $f) {
		if ($f === '.' || $f === '..') continue;
		$path = $dir . '/' . $f;
		if (is_dir($path)) {
			$freed += rrmdir($path);
		} else {
			$freed += filesize($path);
			@unlink($path);
		}
	}
	@rmdir($dir);
	return $freed;
}

$found = 0;
$freed = 0;
$mode  = $do_delete ? 'УДАЛЕНИЕ' : 'ПРЕДПРОСМОТР (ничего не удалено)';
echo "Режим: $mode\n";
echo "Корень: $root\n\n";

$it = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::CHILD_FIRST // сначала содержимое, потом папка
);

foreach ($it as $info) {
	$name = $info->getFilename();
	$path = $info->getPathname();
	$rel  = str_replace($root, '', $path);

	$is_junk = false;

	if ($info->isDir() && in_array($name, $junk_dirs, true)) {
		$is_junk = true;
		$size    = 0;
		if ($do_delete) { $size = rrmdir($path); }
		$freed  += $size;
	} elseif ($info->isFile()) {
		// AppleDouble (._*) или точное имя из списка
		if (str_starts_with($name, '._') || in_array($name, $junk_files, true)) {
			$is_junk = true;
			$freed  += $info->getSize();
			if ($do_delete) @unlink($path);
		}
	}

	if ($is_junk) {
		$found++;
		echo ($do_delete ? 'УДАЛЕНО ' : 'НАЙДЕНО ') . $rel . "\n";
	}
}

echo "\n========== ИТОГ ==========\n";
echo ($do_delete ? "Удалено объектов: " : "Найдено объектов: ") . $found . "\n";
echo "Объём: " . number_format($freed / 1024, 1) . " КБ\n";
if (!$do_delete && $found > 0) {
	echo "\nЧтобы удалить — открой этот же адрес с ?delete=1 на конце.\n";
}
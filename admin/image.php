<?php
/* admin/image.php — обработка изображений товаров
   Ресайз по длинной стороне + конвертация в WebP.
   Используется в upload.php (ручная загрузка) и sync.php (синхронизация),
   а также в optimize_images.php (разовый батч). */

const IMG_MAX_SIDE = 1000; // максимум по длинной стороне, px
const IMG_QUALITY  = 82;   // качество WebP, 0–100

// --- Путь с любым расширением → тот же путь с .webp ---
function img_webp_path(string $path): string {
	return preg_replace('/\.(jpe?g|png|gif|webp)$/i', '', $path) . '.webp';
}

// --- Обработать файл-источник → сохранить WebP ---
// $src — путь к исходнику (jpg/png/webp/gif), $dest_webp — путь назначения .webp.
// Только уменьшает (не растягивает), сохраняет прозрачность. true при успехе.
function img_to_webp(string $src, string $dest_webp, int $max = IMG_MAX_SIDE, int $quality = IMG_QUALITY): bool {
	if (!is_file($src)) return false;

	$info = @getimagesize($src);
	if ($info === false) return false;

	[$w, $h] = $info;
	$type = $info[2];
	if ($w < 1 || $h < 1) return false;

	// Загружаем исходник по типу
	$srcImg = match ($type) {
		IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
		IMAGETYPE_PNG  => @imagecreatefrompng($src),
		IMAGETYPE_WEBP => @imagecreatefromwebp($src),
		IMAGETYPE_GIF  => @imagecreatefromgif($src),
		default        => false,
	};
	if (!$srcImg) return false;

	// Новые размеры — только вниз
	$scale = min(1.0, $max / max($w, $h));
	$nw = max(1, (int)round($w * $scale));
	$nh = max(1, (int)round($h * $scale));

	$dst = imagecreatetruecolor($nw, $nh);

	// Сохраняем прозрачность (фото товаров часто на прозрачном фоне)
	imagealphablending($dst, false);
	imagesavealpha($dst, true);
	$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
	imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);

	imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);

	// Папку создаём при необходимости
	$dir = dirname($dest_webp);
	if (!is_dir($dir)) @mkdir($dir, 0755, true);

	$ok = imagewebp($dst, $dest_webp, $quality);

	imagedestroy($srcImg);
	imagedestroy($dst);

	return $ok;
}

// --- Сохранить бинарную строку как WebP ---
// Для sync: скачали байты → обработали. Пишем во временный файл и прогоняем через img_to_webp.
function img_bin_to_webp(string $bin, string $dest_webp, int $max = IMG_MAX_SIDE, int $quality = IMG_QUALITY): bool {
	if ($bin === '') return false;
	$tmp = tempnam(sys_get_temp_dir(), 'wg_img_');
	if ($tmp === false) return false;
	if (file_put_contents($tmp, $bin) === false) {
		@unlink($tmp);
		return false;
	}
	$ok = img_to_webp($tmp, $dest_webp, $max, $quality);
	@unlink($tmp);
	return $ok;
}
<?php
/* reviews/submit.php — приём и сохранение отзыва */

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
	exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/config.php';

define('REVIEWS_FILE',   DATA_DIR . 'reviews.json');
define('REVIEWS_UPLOAD', $_SERVER['DOCUMENT_ROOT'] . '/order/reviews/uploads/');

// --- Антибот: honeypot ---
if (!empty($_POST['website'])) {
	echo json_encode(['ok' => false, 'error' => 'Bot detected']);
	exit;
}

// --- Антибот: время заполнения ---
$form_time = (int)($_POST['_ft'] ?? 0);
if ($form_time === 0 || (time() - $form_time) < 4) {
	echo json_encode(['ok' => false, 'error' => 'Слишком быстро. Попробуйте ещё раз.']);
	exit;
}

// --- Капча ---
$captcha_answer  = trim($_POST['captcha'] ?? '');
$captcha_correct = $_SESSION['captcha_answer'] ?? null;
unset($_SESSION['captcha_answer']);

if ($captcha_correct === null || (int)$captcha_answer !== (int)$captcha_correct) {
	echo json_encode(['ok' => false, 'error' => 'Неверный ответ на вопрос проверки.']);
	exit;
}

// --- Валидация полей ---
$article = trim($_POST['article'] ?? '');
$name    = trim($_POST['name']    ?? '');
$rating  = (int)($_POST['rating'] ?? 0);
$text    = trim($_POST['text']    ?? '');

if ($article === '') {
	echo json_encode(['ok' => false, 'error' => 'Не указан товар.']);
	exit;
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
	echo json_encode(['ok' => false, 'error' => 'Имя должно быть от 2 до 60 символов.']);
	exit;
}

if ($rating < 1 || $rating > 5) {
	echo json_encode(['ok' => false, 'error' => 'Укажите оценку от 1 до 5.']);
	exit;
}

if (mb_strlen($text) < 10 || mb_strlen($text) > 2000) {
	echo json_encode(['ok' => false, 'error' => 'Текст отзыва — от 10 до 2000 символов.']);
	exit;
}

// --- Фотографии (до 4 штук) ---
$photos    = [];
$allowed   = ['image/jpeg', 'image/png', 'image/webp'];
$max_size  = 5 * 1024 * 1024; // 5 МБ

if (!empty($_FILES['photos']['name'][0])) {
	if (!is_dir(REVIEWS_UPLOAD)) {
		mkdir(REVIEWS_UPLOAD, 0755, true);
	}

	$review_id = 'rv_' . bin2hex(random_bytes(5));

	foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
		if (count($photos) >= 4) break;
		if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
		if ($_FILES['photos']['size'][$i]  >  $max_size)      continue;

		// Проверяем mime через finfo — не доверяем $_FILES['type']
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime  = $finfo->file($tmp);
		if (!in_array($mime, $allowed, true)) continue;

		$ext      = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
		$filename = $review_id . '_' . ($i + 1) . '.' . $ext;
		$dest     = REVIEWS_UPLOAD . $filename;

		if (move_uploaded_file($tmp, $dest)) {
			$photos[] = '/order/reviews/uploads/' . $filename;
		}
	}
} else {
	// ID генерируем в любом случае
	$review_id = 'rv_' . bin2hex(random_bytes(5));
}

// --- Формируем отзыв ---
$review = [
	'id'         => $review_id,
	'article'    => $article,
	'name'       => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
	'rating'     => $rating,
	'text'       => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
	'photos'     => $photos,
	'published'  => false,
	'created_at' => time(),
	'date'       => date('d.m.Y'),
];

// --- Сохраняем ---
$all = file_exists(REVIEWS_FILE)
	? (json_decode(file_get_contents(REVIEWS_FILE), true) ?? [])
	: [];

if (!isset($all[$article])) $all[$article] = [];
array_unshift($all[$article], $review);

file_put_contents(
	REVIEWS_FILE,
	json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode(['ok' => true]);
exit;
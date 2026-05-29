<?php
/* reviews/captcha.php — генерация и выдача капчи
   Возвращает JSON: { "question": "Сколько будет 4 + 7?" }
   Ответ сохраняется в $_SESSION['captcha_answer'] */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$a = random_int(2, 15);
$b = random_int(2, 15);

// Случайно: сложение или вычитание (вычитание только если результат > 0)
if ($a >= $b && random_int(0, 1)) {
	$question = "Сколько будет $a − $b?";
	$answer   = $a - $b;
} else {
	$question = "Сколько будет $a + $b?";
	$answer   = $a + $b;
}

$_SESSION['captcha_answer'] = $answer;

echo json_encode(['question' => $question]);
exit;
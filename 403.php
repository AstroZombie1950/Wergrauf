<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
	<title>WERGRAUF | Доступ запрещён</title>
	<meta name="robots" content="noindex">
	<link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/source_css/wg.css" media="all">
	<style>
	*, *::before, *::after { box-sizing: border-box; }
	body { margin: 0; font-family: "Roboto", Arial, sans-serif; color: #36393e; background: #f7f8fa; }
	.error-page { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; text-align: center; }
	.error-code { font-size: 120px; font-weight: 700; color: #e8eaed; line-height: 1; margin-bottom: 8px; }
	.error-title { font-size: 26px; font-weight: 600; color: #36393e; margin-bottom: 12px; }
	.error-text { font-size: 15px; color: #8a8f9a; max-width: 400px; line-height: 1.6; margin-bottom: 32px; }
	.error-btn { display: inline-block; background: #36393e; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; transition: background .2s; }
	.error-btn:hover { background: #1f2226; }
	.error-home { display: inline-block; margin-top: 16px; color: #8a8f9a; font-size: 14px; text-decoration: none; }
	.error-home:hover { color: #36393e; }
	</style>
</head>
<body>
<div class="error-page">
	<div class="error-code">403</div>
	<div class="error-title">Доступ запрещён</div>
	<div class="error-text">У вас нет прав для просмотра этой страницы.</div>
	<a href="/shower_system/" class="error-btn">Перейти в каталог</a>
	<br>
	<a href="/" class="error-home">На главную</a>
</div>
</body>
</html>
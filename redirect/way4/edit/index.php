<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="HandheldFriendly" content="True">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wergrauf</title>
    <meta name="description" content="Интернет-магазин Верграуф.">
    <link rel="icon" href="https://wergrauf.ru/favicon.ico" type="image/svg+xml">
    <link rel="canonical" href="https://wergrauf.ru/">
    <style>
        .form_field{
            margin: 5px;
            font-family: "Open Sans Light",sans-serif;
        }
    </style>
</head>
<body style="margin: 0;">
	<div id="wrapper" style="margin: 5px;">
        <p>QR: </p>
        <img src="../qr.png" width="20%">
        <p>Актуальная ссылка: <?php include("../link.html"); ?></p>
		<form style="margin-right: 5px;" method="post">
            <input type="submit" name="test_button" value="Тест"/>
            <?php
                if (isset($_POST['test_button'])) {
                    $actual_link = file_get_contents('../link.html');
                    header("Refresh: 0; URL=".$actual_link);
                }
            ?>
		</form>
		<form style="margin-right: 5px;" method="post">
            <div class="form_field"><input name="new_link" placeholder="Новая ссылка"></div>
            <div class="form_field"><input name="password_field" placeholder="Пароль"></div>
			<input type="submit" name="rewrite_link" value="Заменить"/>
            <?php
                $password = htmlspecialchars($_POST["password_field"]);
                if (isset($_POST['rewrite_link']) && $password == "leha9") {
                    $new_link = htmlspecialchars($_POST["new_link"]);
                    $link_rewrite = fopen("../link.html", "w");
                    fwrite($link_rewrite, $new_link);
                    fclose($link_rewrite);
                    header("Refresh: 0");
                }
            ?>
		</form>
	</div>
</body>
</html>
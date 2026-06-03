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
    <style>
        .list {
            margin: auto;
            padding: 0;
            width: 100%;
            text-align: center;
            position: fixed;
            bottom: 100px;
            text-decoration: none;
            color: #ffffff;
            font-family: "Open Sans Light",sans-serif;
        }
    </style>
</head>
<body style="background-color: #060606; margin: 0;">
    <img src="https://wergrauf.ru/images/site_logo/wergrauf_logo_small.png" alt="Wergrauf logo" width="100%">
    <div class="main">
        <ul class="list">страница сейчас откроется...</ul>
    </div>
    <?php
        $alink = file_get_contents('link.html');
        header("Refresh: 0; URL=".$alink);
    ?>
</body>
</html>
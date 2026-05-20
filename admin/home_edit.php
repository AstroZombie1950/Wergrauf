<?php
/* admin/home_edit.php — редактирование блоков главной страницы */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/* Проверка авторизации */
if (empty($_SESSION['admin_logged_in'])) {
	header('Location: /admin/');
	exit;
}

$home_file = DATA_DIR . '/home.json';
$sections_map = SECTIONS_MAP ?? [];

/* Загружаем все товары для выпадающего списка */
$all_products = [];
foreach (array_keys($sections_map) as $section) {
	$products = load_products($section);
	foreach ($products as $p) {
		if (empty($p['article'])) continue;
		$all_products[] = [
			'article' => $p['article'],
			'name'    => $p['name'] ?? '',
			'section' => $section,
		];
	}
}

/* Загружаем текущие данные главной */
$home = [];
if (file_exists($home_file)) {
	$home = json_decode(file_get_contents($home_file), true) ?? [];
}

$leader   = $home['leader']           ?? [];
$popular  = $home['popular_products'] ?? [];
$promos   = $home['promos']           ?? [];

$success = '';
$error   = '';

/* ===== Обработка POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	/* Лидер продаж */
	if ($action === 'save_leader') {
		$home['leader']['title'] = trim($_POST['leader_title'] ?? '');
		$home['leader']['text']  = trim($_POST['leader_text']  ?? '');

		/* Загрузка картинки */
		if (!empty($_FILES['leader_image']['tmp_name'])) {
			$file  = $_FILES['leader_image'];
			$ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed = ['jpg', 'jpeg', 'png', 'webp'];
			if (!in_array($ext, $allowed)) {
				$error = 'Недопустимый формат файла.';
			} else {
				$dest_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/promo/';
				if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
				$fname = 'promo-desktop.' . $ext;
				if (move_uploaded_file($file['tmp_name'], $dest_dir . $fname)) {
					$home['leader']['image']        = 'images/promo/' . $fname;
					$home['leader']['image_tablet'] = 'images/promo/' . $fname;
					$home['leader']['image_mobile'] = 'images/promo/' . $fname;
				} else {
					$error = 'Ошибка загрузки файла.';
				}
			}
		}

		if (!$error) $success = 'Блок «Лидер продаж» сохранён.';
	}

	/* Популярные товары */
	if ($action === 'save_popular') {
		$articles = $_POST['popular_articles'] ?? [];
		/* Чистим пустые */
		$articles = array_values(array_filter(array_map('trim', $articles)));
		$home['popular_products'] = $articles;
		$success = 'Популярные товары сохранены.';
	}

	/* Акции */
	if ($action === 'save_promos') {
		$ids     = $_POST['promo_id']    ?? [];
		$titles  = $_POST['promo_title'] ?? [];
		$texts   = $_POST['promo_text']  ?? [];
		$links   = $_POST['promo_link']  ?? [];
		$themes  = $promos; /* Тема не меняется — берём из существующего */

		$new_promos = [];
		foreach ($ids as $i => $id) {
			/* Ищем исходную запись чтобы сохранить тему и картинки */
			$orig = null;
			foreach ($promos as $pr) {
				if ((string)($pr['id'] ?? '') === (string)$id) {
					$orig = $pr;
					break;
				}
			}
			if (!$orig) continue;

			$orig['title'] = trim($titles[$i] ?? '');
			$orig['text']  = trim($texts[$i]  ?? '');
			$orig['link']  = trim($links[$i]  ?? '/');
			$new_promos[]  = $orig;
		}

		$home['promos'] = $new_promos;
		$success = 'Акции сохранены.';
	}

	/* Записываем JSON */
	if (!$error) {
		$ok = file_put_contents(
			$home_file,
			json_encode($home, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
		);
		if (!$ok) $error = 'Не удалось записать файл. Проверьте права на /data/.';

		/* Перезагружаем данные */
		$leader  = $home['leader']           ?? [];
		$popular = $home['popular_products'] ?? [];
		$promos  = $home['promos']           ?? [];
	}
}

/* Метки разделов */
$section_labels = [
	'shower_system'   => 'Душевые системы',
	'kitchen_faucets' => 'Кухонные смесители',
	'floor_faucets'   => 'Напольные смесители',
	'bath_faucets'    => 'Смесители для ванны',
	'sink_faucets'    => 'Смесители для раковины',
	'hygienic_shower' => 'Гигиенические души',
	'accessories'     => 'Аксессуары',
	'towel_warmers'   => 'Полотенцесушители',
	'components'      => 'Комплектующие',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Редактирование главной — WERGRAUF Admin</title>
	<link rel="stylesheet" href="/source_css/wg.css">
	<style>
		/* ===== Базовые стили админки ===== */
		body { font-family: 'Roboto', sans-serif; background: #f5f5f5; color: #232528; margin: 0; }
		.admin-wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
		h1 { font-size: 24px; font-weight: 600; margin: 0 0 24px; }
		h2 { font-size: 17px; font-weight: 600; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #e5e5e5; }

		/* Навигация */
		.admin-nav { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
		.admin-nav a { font-size: 14px; font-weight: 500; color: #385081; text-decoration: none; }
		.admin-nav a:hover { text-decoration: underline; }

		/* Карточки блоков */
		.admin-card { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.07); }

		/* Поля */
		label { display: block; font-size: 13px; font-weight: 500; color: #555; margin-bottom: 6px; }
		input[type=text], input[type=url], textarea, select {
			width: 100%; padding: 10px 14px; font-size: 14px; font-family: inherit;
			border: 1px solid #d1d5db; border-radius: 10px; outline: none;
			transition: border-color .2s ease; background: #fafafa; box-sizing: border-box;
		}
		input[type=text]:focus, input[type=url]:focus, textarea:focus, select:focus {
			border-color: #385081; background: #fff;
		}
		textarea { resize: vertical; min-height: 120px; line-height: 1.6; }

		/* Кнопки */
		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 10px; border: none; font-size: 14px; font-weight: 500; font-family: inherit; cursor: pointer; transition: background .2s ease; }
		.btn-primary { background: #232528; color: #fff; }
		.btn-primary:hover { background: #36393e; }
		.btn-danger  { background: #fee2e2; color: #b91c1c; }
		.btn-danger:hover  { background: #fecaca; }
		.btn-secondary { background: #f3f4f6; color: #232528; }
		.btn-secondary:hover { background: #e5e7eb; }

		/* Сообщения */
		.msg-ok  { background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
		.msg-err { background: #fee2e2; color: #b91c1c; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }

		/* Строка популярного товара */
		.popular-row { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; margin-bottom: 8px; }
		.popular-row select { margin: 0; }

		/* Акции */
		.promo-row { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
		.promo-row__head { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 12px; }
		.field-row { margin-bottom: 12px; }

		/* Загрузка файла */
		.file-preview { margin-top: 8px; max-height: 80px; border-radius: 8px; }
	</style>
</head>
<body>
<div class="admin-wrap">

	<div class="admin-nav">
		<a href="/admin/">← Дашборд</a>
		<a href="/">Сайт ↗</a>
	</div>

	<h1>Редактирование главной страницы</h1>

	<?php if ($success): ?>
	<div class="msg-ok"><?= h($success) ?></div>
	<?php endif; ?>

	<?php if ($error): ?>
	<div class="msg-err"><?= h($error) ?></div>
	<?php endif; ?>

	<!-- ===== Лидер продаж ===== -->
	<div class="admin-card">
		<h2>Лидер продаж</h2>
		<form method="POST" enctype="multipart/form-data">
			<input type="hidden" name="action" value="save_leader">

			<div class="field-row">
				<label for="leader_title">Заголовок</label>
				<input type="text" id="leader_title" name="leader_title" value="<?= h($leader['title'] ?? '') ?>">
			</div>

			<div class="field-row">
				<label for="leader_text">Текст (разделяйте абзацы пустой строкой)</label>
				<textarea id="leader_text" name="leader_text"><?= h($leader['text'] ?? '') ?></textarea>
			</div>

			<div class="field-row">
				<label for="leader_image">Изображение (jpg/png/webp, загружается как promo-desktop)</label>
				<?php if (!empty($leader['image'])): ?>
				<img src="/<?= h($leader['image']) ?>" alt="" class="file-preview">
				<?php endif; ?>
				<input type="file" id="leader_image" name="leader_image" accept="image/*" style="margin-top:8px;">
			</div>

			<button type="submit" class="btn btn-primary">Сохранить</button>
		</form>
	</div>

	<!-- ===== Популярные товары ===== -->
	<div class="admin-card">
		<h2>Популярные товары</h2>
		<form method="POST" id="form-popular">
			<input type="hidden" name="action" value="save_popular">

			<div id="popular-list">
				<?php foreach ($popular as $i => $art): ?>
				<div class="popular-row" id="popular-row-<?= $i ?>">
					<select name="popular_articles[]">
						<option value="">— не выбрано —</option>
						<?php foreach ($all_products as $prod): ?>
						<option value="<?= h($prod['article']) ?>" <?= $prod['article'] === $art ? 'selected' : '' ?>>
							<?= h($prod['article']) ?> — <?= h(mb_substr($prod['name'], 0, 60)) ?> (<?= h($section_labels[$prod['section']] ?? $prod['section']) ?>)
						</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="btn btn-danger" onclick="removePopularRow(this)">✕</button>
				</div>
				<?php endforeach; ?>
			</div>

			<div style="margin: 12px 0; display:flex; gap:12px; align-items:center;">
				<button type="button" class="btn btn-secondary" onclick="addPopularRow()">+ Добавить товар</button>
				<span style="font-size:13px; color:#6b7280;">Максимум 10 товаров. Порядок — сверху вниз.</span>
			</div>

			<button type="submit" class="btn btn-primary">Сохранить</button>
		</form>
	</div>

	<!-- ===== Акции ===== -->
	<div class="admin-card">
		<h2>Акции</h2>
		<p style="font-size:13px; color:#6b7280; margin-top:0;">Картинки не меняются здесь — загружайте их по FTP в <code>/images/offers/</code>.</p>
		<form method="POST">
			<input type="hidden" name="action" value="save_promos">

			<?php foreach ($promos as $i => $promo): ?>
			<div class="promo-row">
				<div class="promo-row__head">Карточка <?= $i + 1 ?></div>
				<input type="hidden" name="promo_id[]" value="<?= h($promo['id'] ?? $i + 1) ?>">

				<div class="field-row">
					<label>Заголовок</label>
					<input type="text" name="promo_title[]" value="<?= h($promo['title'] ?? '') ?>">
				</div>
				<div class="field-row">
					<label>Текст</label>
					<input type="text" name="promo_text[]" value="<?= h($promo['text'] ?? '') ?>">
				</div>
				<div class="field-row">
					<label>Ссылка</label>
					<input type="url" name="promo_link[]" value="<?= h($promo['link'] ?? '/') ?>">
				</div>
			</div>
			<?php endforeach; ?>

			<button type="submit" class="btn btn-primary">Сохранить акции</button>
		</form>
	</div>

</div>

<script>
/* Шаблон строки для нового товара */
var allProducts = <?= json_encode(array_map(function($p) use ($section_labels) {
	return [
		'article' => $p['article'],
		'label'   => $p['article'] . ' — ' . mb_substr($p['name'], 0, 60) . ' (' . ($section_labels[$p['section']] ?? $p['section']) . ')',
	];
}, $all_products), JSON_UNESCAPED_UNICODE) ?>;

function addPopularRow() {
	var list = document.getElementById('popular-list');
	var idx  = list.children.length;
	if (idx >= 10) { alert('Максимум 10 товаров'); return; }

	var div = document.createElement('div');
	div.className = 'popular-row';
	div.id = 'popular-row-' + idx;

	var sel = document.createElement('select');
	sel.name = 'popular_articles[]';

	var opt0 = document.createElement('option');
	opt0.value = '';
	opt0.textContent = '— не выбрано —';
	sel.appendChild(opt0);

	allProducts.forEach(function(p) {
		var opt = document.createElement('option');
		opt.value = p.article;
		opt.textContent = p.label;
		sel.appendChild(opt);
	});

	var btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'btn btn-danger';
	btn.textContent = '✕';
	btn.onclick = function() { removePopularRow(btn); };

	div.appendChild(sel);
	div.appendChild(btn);
	list.appendChild(div);
}

function removePopularRow(btn) {
	btn.closest('.popular-row').remove();
}
</script>
</body>
</html>
<?php
/* admin/product_edit.php — редактирование карточки товара */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

// --- Параметры ---
$section = $_GET['section'] ?? '';
$article = $_GET['article'] ?? '';

if (!array_key_exists($section, SECTION_NAMES) || $article === '') {
	header('Location: /admin/');
	exit;
}

$section_name = SECTION_NAMES[$section];
$product      = find_product_by_article($section, $article);

if (!$product) {
	header('Location: /admin/catalog.php?section=' . urlencode($section));
	exit;
}

$is_manual = !empty($product['_manual']);

// --- Сохранение ---
$save_success = false;
$save_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

	$fields = [
		'name'             => trim($_POST['name']             ?? ''),
		'description'      => trim($_POST['description']      ?? ''),
		'price'            => (int)($_POST['price']           ?? 0),
		'old_price'        => (int)($_POST['old_price']       ?? 0),
		'stock'            => (int)($_POST['stock']           ?? 0),
		'meta_title'       => trim($_POST['meta_title']       ?? ''),
		'meta_description' => trim($_POST['meta_description'] ?? ''),
		'promo_code'       => trim($_POST['promo_code']       ?? ''),
		'discount_percent' => trim($_POST['discount_percent'] ?? ''),
		'image'            => trim($_POST['image']            ?? ''),
	];

	// Галерея: textarea → массив
	$gallery_raw = trim($_POST['gallery'] ?? '');
	$fields['gallery'] = $gallery_raw !== ''
		? array_values(array_filter(array_map('trim', explode("\n", $gallery_raw))))
		: [];

	// Slug (только с явным подтверждением)
	if (!empty($_POST['slug_confirm']) && trim($_POST['new_slug'] ?? '') !== '') {
		$new_slug = trim($_POST['new_slug']);
		// Базовая валидация: только a-z, 0-9, дефис
		if (preg_match('/^[a-z0-9\-]+$/', $new_slug)) {
			$fields['slug'] = $new_slug;
		} else {
			$save_error = 'Slug может содержать только латинские буквы, цифры и дефис';
		}
	}

	if ($save_error === '') {
		// Характеристики из POST
		$specs_override = [];
		foreach ($_POST as $key => $val) {
			if (str_starts_with($key, 'spec_')) {
				$specs_override[$key] = trim($val);
			}
		}

		// Для ручных товаров сохраняем все поля целиком
		if ($is_manual) {
			$existing = load_overrides($section)[$article] ?? [];
			$merged   = array_merge($existing, $fields, $specs_override);
			if (save_override($section, $article, $merged)) {
				$save_success = true;
				$product      = find_product_by_article($section, $article);
			} else {
				$save_error = 'Не удалось сохранить файл оверрайдов';
			}
		} else {
			// Для обычных товаров — только изменённые поля
			$override = [];
			foreach ($fields as $k => $v) {
				if ($v !== '' && $v != ($product[$k] ?? '')) {
					$override[$k] = $v;
				}
			}
			// Обновляем specs
			foreach ($specs_override as $k => $v) {
				$original = '';
				foreach ($product['specs'] ?? [] as $s) {
					if ($s['key'] === $k) { $original = $s['value']; break; }
				}
				if ($v !== $original) $override[$k] = $v;
			}

			if (!empty($override)) {
				$existing = load_overrides($section)[$article] ?? [];
				$merged   = array_merge($existing, $override);
				if (save_override($section, $article, $merged)) {
					$save_success = true;
					$product      = find_product_by_article($section, $article);
				} else {
					$save_error = 'Не удалось сохранить файл оверрайдов. Проверьте права на запись в /data/overrides/';
				}
			} else {
				$save_success = true;
			}
		}
	}
}

// --- Сброс оверрайдов ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_overrides'])) {
	delete_override($section, $article);
	header('Location: /admin/product_edit.php?section=' . urlencode($section) . '&article=' . urlencode($article) . '&reset=1');
	exit;
}

$just_reset        = isset($_GET['reset']);
$current_overrides = load_overrides($section)[$article] ?? [];
$has_overrides     = !empty($current_overrides);

// Галерея для textarea
$gallery_text = implode("\n", $product['gallery'] ?? []);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Редактирование — <?= h($product['name'] ?? '') ?></title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

		:root {
			--bg:       #f4f5f7;
			--surface:  #ffffff;
			--border:   #e2e4e9;
			--text:     #36393e;
			--muted:    #8a8f9a;
			--accent:   #4a4f59;
			--accent-h: #385081;
			--danger:   #c82a20;
			--radius:   10px;
			--shadow:   0 1px 4px rgba(0,0,0,.08);
		}

		body { font-family: "Roboto", Arial, sans-serif; font-size: 14px; background: var(--bg); color: var(--text); }

		.topbar { background: var(--accent); color: #fff; padding: 0 24px; height: 52px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
		.topbar__left { display: flex; align-items: center; gap: 10px; font-size: 14px; }
		.topbar__brand { font-weight: 700; font-size: 15px; text-decoration: none; color: #fff; }
		.topbar__sep { opacity: .4; }
		.topbar__crumb a { color: rgba(255,255,255,.8); text-decoration: none; }
		.topbar__crumb a:hover { color: #fff; }
		.topbar__logout { color: rgba(255,255,255,.7); text-decoration: none; font-size: 13px; }
		.topbar__logout:hover { color: #fff; }

		.page { max-width: 960px; margin: 0 auto; padding: 24px; }

		.page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
		.page-head__title { font-size: 18px; font-weight: 600; line-height: 1.3; }
		.page-head__meta { font-size: 12px; color: var(--muted); margin-top: 4px; font-family: monospace; }

		/* Карточки секций формы */
		.form-section { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow); }
		.form-section__title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }

		.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		.form-row--3 { grid-template-columns: 1fr 1fr 1fr; }
		.form-group { margin-bottom: 16px; }
		.form-group:last-child { margin-bottom: 0; }
		.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
		.form-label small { font-weight: 400; color: var(--muted); margin-left: 6px; }

		.form-input, .form-textarea, .form-select {
			width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
			font-family: inherit; font-size: 14px; color: var(--text); background: var(--surface);
			transition: border-color .2s;
		}
		.form-input:focus, .form-textarea:focus { outline: none; border-color: var(--accent); }
		.form-textarea { resize: vertical; min-height: 80px; }

		/* Превью фото */
		.image-preview { display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap; margin-top: 8px; }
		.image-preview__img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }
		.image-preview__placeholder { width: 80px; height: 80px; border-radius: 8px; background: var(--bg); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 28px; }

		/* Slug — опасная зона */
		.slug-danger-zone { background: #fff3f3; border: 1px solid #ffcdd2; border-radius: var(--radius); padding: 16px; margin-top: 8px; }
		.slug-danger-zone__title { font-size: 13px; font-weight: 600; color: var(--danger); margin-bottom: 8px; }
		.slug-danger-zone__warning { font-size: 12px; color: #b71c1c; line-height: 1.5; margin-bottom: 12px; }
		.slug-confirm-label { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 12px; cursor: pointer; }

		/* Notices */
		.notice { border-radius: var(--radius); padding: 12px 16px; font-size: 13px; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; }
		.notice--warning { background: #fff8e1; border: 1px solid #f0c030; color: #5a4000; }
		.notice--success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; }
		.notice--error { background: #fde0df; border: 1px solid #ef9a9a; color: var(--danger); }
		.notice__icon { flex-shrink: 0; font-size: 16px; line-height: 1; margin-top: 1px; }

		/* Кнопки */
		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: none; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .2s; white-space: nowrap; }
		.btn--primary { background: var(--accent); color: #fff; }
		.btn--primary:hover { background: var(--accent-h); }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }
		.btn--danger { background: var(--danger); color: #fff; }
		.btn--danger:hover { background: #a32219; }
		.btn--danger-outline { background: transparent; color: var(--danger); border: 1px solid #ef9a9a; }
		.btn--danger-outline:hover { background: #fde0df; }

		.actions-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px 0; border-top: 1px solid var(--border); margin-top: 8px; }

		/* Specs */
		.specs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
	</style>
</head>
<body>

<div class="topbar">
	<div class="topbar__left">
		<a class="topbar__brand" href="/admin/">WERGRAUF</a>
		<span class="topbar__sep">/</span>
		<div class="topbar__crumb">
			<a href="/admin/catalog.php?section=<?= h($section) ?>"><?= h($section_name) ?></a>
		</div>
		<span class="topbar__sep">/</span>
		<span><?= h($product['name'] ?? '') ?></span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<div class="page">

	<div class="page-head">
		<div>
			<div class="page-head__title"><?= h($product['name'] ?? 'Без названия') ?></div>
			<div class="page-head__meta">
				Арт. <?= h((string)($product['article'] ?? '')) ?> &nbsp;·&nbsp;
				/<?= h($product['slug'] ?? '') ?>/
				<?php if ($is_manual): ?> &nbsp;·&nbsp; <span style="color:#1565c0">ручной товар</span><?php endif ?>
			</div>
		</div>
		<a class="btn btn--outline"
			href="<?= h(SECTION_URLS[$section]) ?><?= h($product['slug'] ?? '') ?>/"
			target="_blank">
			👁 На сайте
		</a>
	</div>

	<!-- Уведомления -->
	<?php if ($save_success): ?>
		<div class="notice notice--success"><span class="notice__icon">✅</span> Сохранено</div>
	<?php endif ?>
	<?php if ($save_error): ?>
		<div class="notice notice--error"><span class="notice__icon">❌</span> <?= h($save_error) ?></div>
	<?php endif ?>
	<?php if ($just_reset): ?>
		<div class="notice notice--success"><span class="notice__icon">✅</span> Ручные правки сброшены. Данные из Google Sheets восстановлены.</div>
	<?php endif ?>
	<?php if ($has_overrides && !$is_manual): ?>
		<div class="notice notice--warning">
			<span class="notice__icon">⚠️</span>
			<div>
				У этого товара есть ручные правки (они имеют приоритет над данными из таблицы).
				<form method="POST" style="display:inline; margin-left:8px"
					onsubmit="return confirm('Сбросить все ручные правки? Данные вернутся к значениям из Google Sheets.')">
					<button class="btn btn--danger-outline" type="submit" name="reset_overrides" value="1"
						style="padding:4px 10px; font-size:12px">
						↺ Сбросить правки
					</button>
				</form>
			</div>
		</div>
	<?php endif ?>

	<form method="POST">

		<!-- Основное -->
		<div class="form-section">
			<div class="form-section__title">Основные данные</div>

			<div class="form-group">
				<label class="form-label" for="name">Название</label>
				<input class="form-input" type="text" id="name" name="name"
					value="<?= h($product['name'] ?? '') ?>" required>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label class="form-label" for="price">Цена, ₽</label>
					<input class="form-input" type="number" id="price" name="price"
						value="<?= (int)($product['price'] ?? 0) ?>" min="0" required>
				</div>
				<div class="form-group">
					<label class="form-label" for="old_price">Цена до скидки, ₽ <small>необязательно</small></label>
					<input class="form-input" type="number" id="old_price" name="old_price"
						value="<?= (int)($product['old_price'] ?? 0) ?>" min="0">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label class="form-label" for="stock">Остаток, шт.</label>
					<input class="form-input" type="number" id="stock" name="stock"
						value="<?= (int)($product['stock'] ?? 0) ?>" min="0" required>
				</div>
				<div class="form-group">
					<label class="form-label" for="promo_code">Промокод <small>необязательно</small></label>
					<input class="form-input" type="text" id="promo_code" name="promo_code"
						value="<?= h($product['promo_code'] ?? '') ?>">
				</div>
			</div>

			<div class="form-group">
				<label class="form-label" for="discount_percent">Скидка по промокоду, % <small>необязательно</small></label>
				<input class="form-input" type="text" id="discount_percent" name="discount_percent"
					value="<?= h($product['discount_percent'] ?? '') ?>" placeholder="например: 10" style="max-width:180px">
			</div>

			<div class="form-group">
				<label class="form-label" for="description">Описание</label>
				<textarea class="form-textarea" id="description" name="description" rows="6"><?= h($product['description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Фото -->
		<div class="form-section">
			<div class="form-section__title">Фотографии</div>

			<div class="form-group">
				<label class="form-label" for="image">Главное фото (URL)</label>
				<input class="form-input" type="url" id="image" name="image"
					value="<?= h($product['image'] ?? '') ?>"
					placeholder="https://…"
					oninput="updateMainPreview(this.value)">
				<div class="image-preview" id="main-preview">
					<?php if (!empty($product['image'])): ?>
						<img class="image-preview__img" src="<?= h($product['image']) ?>" alt="" id="main-preview-img">
					<?php else: ?>
						<div class="image-preview__placeholder" id="main-preview-img">📷</div>
					<?php endif ?>
				</div>
			</div>

			<div class="form-group">
				<label class="form-label" for="gallery">
					Галерея (каждая ссылка с новой строки)
					<small><?= count($product['gallery'] ?? []) ?> фото</small>
				</label>
				<textarea class="form-textarea" id="gallery" name="gallery" rows="5"
					placeholder="https://…&#10;https://…"><?= h($gallery_text) ?></textarea>
			</div>
		</div>

		<!-- Метатеги -->
		<div class="form-section">
			<div class="form-section__title">SEO / Метатеги</div>

			<div class="form-group">
				<label class="form-label" for="meta_title">Meta title <small>необязательно, по умолчанию — название товара</small></label>
				<input class="form-input" type="text" id="meta_title" name="meta_title"
					value="<?= h($product['meta_title'] ?? '') ?>"
					placeholder="<?= h($product['name'] ?? '') ?> купить | WERGRAUF">
			</div>

			<div class="form-group">
				<label class="form-label" for="meta_description">Meta description <small>до 160 символов</small></label>
				<textarea class="form-textarea" id="meta_description" name="meta_description" rows="3"
					maxlength="160"><?= h($product['meta_description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Характеристики -->
		<?php if (!empty($product['specs'])): ?>
		<div class="form-section">
			<div class="form-section__title">Характеристики</div>
			<div class="specs-grid">
				<?php foreach ($product['specs'] as $spec): ?>
					<div class="form-group">
						<label class="form-label"><?= h($spec['label']) ?></label>
						<input class="form-input" type="text" name="<?= h($spec['key']) ?>"
							value="<?= h($spec['value']) ?>">
					</div>
				<?php endforeach ?>
			</div>
		</div>
		<?php endif ?>

		<!-- Slug — опасная зона -->
		<div class="form-section">
			<div class="form-section__title">Slug</div>

			<div class="form-group">
				<label class="form-label">Текущий slug</label>
				<input class="form-input" type="text" value="<?= h($product['slug'] ?? '') ?>" disabled
					style="font-family:monospace; background:#f4f5f7;">
			</div>

			<div class="slug-danger-zone">
				<div class="slug-danger-zone__title">⚠️ Изменение slug — только в крайних случаях</div>
				<div class="slug-danger-zone__warning">
					Slug — это URL товара. Его изменение <strong>сломает все ссылки</strong> на карточку товара,
					обнулит позиции в поиске и потребует ручного обновления таблицы Google Sheets.
					Трогайте только если есть явная опечатка или критическая ошибка.
				</div>

				<label class="slug-confirm-label">
					<input type="checkbox" id="slug-confirm-check" onchange="toggleSlugField(this.checked)">
					<span>Я понимаю последствия и хочу изменить slug</span>
				</label>

				<div id="slug-field" style="display:none">
					<input class="form-input" type="text" id="new_slug" name="new_slug"
						value="<?= h($product['slug'] ?? '') ?>"
						pattern="[a-z0-9\-]+" title="Только латинские буквы в нижнем регистре, цифры и дефис"
						style="font-family:monospace; margin-bottom:8px">
					<input type="hidden" name="slug_confirm" id="slug-confirm-input" value="">
				</div>
			</div>
		</div>

		<!-- Кнопки -->
		<div class="actions-bar">
			<button class="btn btn--primary" type="submit" name="save" value="1">💾 Сохранить</button>
			<a class="btn btn--outline" href="/admin/catalog.php?section=<?= h($section) ?>">← Назад к разделу</a>
		</div>

	</form>
</div>

<script>
/* Превью главного фото */
function updateMainPreview(url) {
	const wrap = document.getElementById('main-preview');
	const old  = document.getElementById('main-preview-img');
	if (!url) {
		old.outerHTML = '<div class="image-preview__placeholder" id="main-preview-img">📷</div>';
		return;
	}
	const img = document.createElement('img');
	img.className = 'image-preview__img';
	img.id = 'main-preview-img';
	img.src = url;
	img.alt = '';
	img.onerror = () => { img.outerHTML = '<div class="image-preview__placeholder" id="main-preview-img">❌</div>'; };
	old.replaceWith(img);
}

/* Открыть поле slug */
function toggleSlugField(checked) {
	document.getElementById('slug-field').style.display = checked ? 'block' : 'none';
	document.getElementById('slug-confirm-input').value = checked ? '1' : '';
}
</script>

</body>
</html>

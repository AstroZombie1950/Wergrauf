<?php
/* admin/product_new.php — создание нового товара */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

$section = $_GET['section'] ?? '';
if (!array_key_exists($section, SECTION_NAMES)) {
	header('Location: /admin/');
	exit;
}

$section_name = SECTION_NAMES[$section];
$errors       = [];
$form         = [];

// --- Создание ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {

	$form = [
		'name'             => trim($_POST['name']             ?? ''),
		'slug'             => trim($_POST['slug']             ?? ''),
		'article'          => trim($_POST['article']          ?? ''),
		'price'            => (int)($_POST['price']           ?? 0),
		'old_price'        => (int)($_POST['old_price']       ?? 0),
		'stock'            => (int)($_POST['stock']           ?? 0),
		'description'      => trim($_POST['description']      ?? ''),
		'image'            => trim($_POST['image']            ?? ''),
		'meta_title'       => trim($_POST['meta_title']       ?? ''),
		'meta_description' => trim($_POST['meta_description'] ?? ''),
		'promo_code'       => trim($_POST['promo_code']       ?? ''),
		'discount_percent' => trim($_POST['discount_percent'] ?? ''),
	];

	// Галерея
	$gallery_raw   = trim($_POST['gallery'] ?? '');
	$form['gallery'] = $gallery_raw !== ''
		? array_values(array_filter(array_map('trim', explode("\n", $gallery_raw))))
		: [];

	// Валидация
	if ($form['name'] === '')    $errors[] = 'Название обязательно';
	if ($form['slug'] === '')    $errors[] = 'Slug обязателен';
	if ($form['article'] === '') $errors[] = 'Артикул обязателен';
	if ($form['price'] <= 0)     $errors[] = 'Цена должна быть больше 0';

	if ($form['slug'] !== '' && !preg_match('/^[a-z0-9\-]+$/', $form['slug'])) {
		$errors[] = 'Slug может содержать только латинские буквы, цифры и дефис';
	}

	// Проверяем уникальность артикула и slug в разделе
	if (empty($errors)) {
		$existing_products = load_products($section);
		foreach ($existing_products as $p) {
			if ((string)$p['article'] === $form['article']) {
				$errors[] = 'Товар с артикулом «' . h($form['article']) . '» уже существует в этом разделе';
				break;
			}
			if (($p['slug'] ?? '') === $form['slug']) {
				$errors[] = 'Товар со slug «' . h($form['slug']) . '» уже существует в этом разделе';
				break;
			}
		}
	}

	if (empty($errors)) {
		// Specs из POST
		$specs = [];
		foreach ($_POST as $key => $val) {
			if (str_starts_with($key, 'spec_') && trim($val) !== '') {
				$specs[] = [
					'key'   => $key,
					'label' => SPEC_LABELS[$key] ?? $key,
					'value' => trim($val),
				];
			}
		}

		$new_product = array_merge($form, [
			'specs'   => $specs,
			'_manual' => true,  // флаг ручного товара — не удаляется при синхронизации
		]);

		if (save_override($section, $form['article'], $new_product)) {
			header('Location: /admin/product_edit.php?section=' . urlencode($section) . '&article=' . urlencode($form['article']) . '&created=1');
			exit;
		} else {
			$errors[] = 'Не удалось сохранить. Проверьте права на запись в /data/overrides/';
		}
	}
}

// Список spec-полей для формы
$spec_fields = array_keys(SPEC_LABELS);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Новый товар — <?= h($section_name) ?></title>
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

		.page { max-width: 960px; margin: 0 auto; padding: 24px; }
		.page-head { margin-bottom: 24px; }
		.page-head__title { font-size: 18px; font-weight: 600; }
		.page-head__sub { font-size: 13px; color: var(--muted); margin-top: 4px; }

		.form-section { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow); }
		.form-section__title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }

		.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		.form-group { margin-bottom: 16px; }
		.form-group:last-child { margin-bottom: 0; }
		.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
		.form-label small { font-weight: 400; color: var(--muted); margin-left: 6px; }
		.form-hint { font-size: 12px; color: var(--muted); margin-top: 4px; }

		.form-input, .form-textarea {
			width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
			font-family: inherit; font-size: 14px; color: var(--text); background: var(--surface);
			transition: border-color .2s;
		}
		.form-input:focus, .form-textarea:focus { outline: none; border-color: var(--accent); }
		.form-textarea { resize: vertical; min-height: 80px; }

		.notice { border-radius: var(--radius); padding: 12px 16px; font-size: 13px; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; }
		.notice--warning { background: #fff8e1; border: 1px solid #f0c030; color: #5a4000; }
		.notice--error { background: #fde0df; border: 1px solid #ef9a9a; color: var(--danger); }
		.notice ul { margin: 6px 0 0 16px; }
		.notice__icon { flex-shrink: 0; font-size: 16px; line-height: 1; margin-top: 1px; }

		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: none; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .2s; }
		.btn--primary { background: var(--accent); color: #fff; }
		.btn--primary:hover { background: var(--accent-h); }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }

		.actions-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px 0; border-top: 1px solid var(--border); }

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
		<span>Новый товар</span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<div class="page">

	<div class="page-head">
		<div class="page-head__title">Новый товар — <?= h($section_name) ?></div>
		<div class="page-head__sub">Товар будет создан вручную и <strong>не будет перезаписан при синхронизации</strong> с Google Sheets.</div>
	</div>

	<?php if (!empty($errors)): ?>
		<div class="notice notice--error">
			<span class="notice__icon">❌</span>
			<div>
				Исправьте ошибки:
				<ul><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach ?></ul>
			</div>
		</div>
	<?php endif ?>

	<form method="POST">

		<!-- Основное -->
		<div class="form-section">
			<div class="form-section__title">Основные данные</div>

			<div class="form-group">
				<label class="form-label" for="name">Название *</label>
				<input class="form-input" type="text" id="name" name="name"
					value="<?= h($form['name'] ?? '') ?>" required autofocus>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label class="form-label" for="article">Артикул *</label>
					<input class="form-input" type="text" id="article" name="article"
						value="<?= h($form['article'] ?? '') ?>" required>
				</div>
				<div class="form-group">
					<label class="form-label" for="slug">Slug * <small>URL товара</small></label>
					<input class="form-input" type="text" id="slug" name="slug"
						value="<?= h($form['slug'] ?? '') ?>"
						pattern="[a-z0-9\-]+"
						placeholder="ff-01b"
						style="font-family:monospace" required
						oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9\-]/g,'')">
					<div class="form-hint">Только латиница, цифры и дефис. Формат: модель-цвет, например ff-01b</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label class="form-label" for="price">Цена, ₽ *</label>
					<input class="form-input" type="number" id="price" name="price"
						value="<?= (int)($form['price'] ?? 0) ?>" min="1" required>
				</div>
				<div class="form-group">
					<label class="form-label" for="old_price">Цена до скидки, ₽ <small>необязательно</small></label>
					<input class="form-input" type="number" id="old_price" name="old_price"
						value="<?= (int)($form['old_price'] ?? 0) ?>" min="0">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label class="form-label" for="stock">Остаток, шт. *</label>
					<input class="form-input" type="number" id="stock" name="stock"
						value="<?= (int)($form['stock'] ?? 1) ?>" min="0" required>
				</div>
				<div class="form-group">
					<label class="form-label" for="promo_code">Промокод <small>необязательно</small></label>
					<input class="form-input" type="text" id="promo_code" name="promo_code"
						value="<?= h($form['promo_code'] ?? '') ?>">
				</div>
			</div>

			<div class="form-group">
				<label class="form-label" for="description">Описание</label>
				<textarea class="form-textarea" id="description" name="description" rows="5"><?= h($form['description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Фото -->
		<div class="form-section">
			<div class="form-section__title">Фотографии</div>

			<div class="form-group">
				<label class="form-label" for="image">Главное фото (URL)</label>
				<input class="form-input" type="url" id="image" name="image"
					value="<?= h($form['image'] ?? '') ?>" placeholder="https://…">
			</div>

			<div class="form-group">
				<label class="form-label" for="gallery">Галерея (каждая ссылка с новой строки)</label>
				<textarea class="form-textarea" id="gallery" name="gallery" rows="4"
					placeholder="https://…&#10;https://…"><?= h(implode("\n", $form['gallery'] ?? [])) ?></textarea>
			</div>
		</div>

		<!-- SEO -->
		<div class="form-section">
			<div class="form-section__title">SEO / Метатеги</div>

			<div class="form-group">
				<label class="form-label" for="meta_title">Meta title <small>необязательно</small></label>
				<input class="form-input" type="text" id="meta_title" name="meta_title"
					value="<?= h($form['meta_title'] ?? '') ?>">
			</div>

			<div class="form-group">
				<label class="form-label" for="meta_description">Meta description <small>до 160 символов</small></label>
				<textarea class="form-textarea" id="meta_description" name="meta_description" rows="3"
					maxlength="160"><?= h($form['meta_description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Характеристики -->
		<div class="form-section">
			<div class="form-section__title">Характеристики <small style="font-size:12px; font-weight:400; color:var(--muted)">— заполните нужные</small></div>
			<div class="specs-grid">
				<?php foreach ($spec_fields as $key): ?>
					<div class="form-group">
						<label class="form-label"><?= h(SPEC_LABELS[$key]) ?></label>
						<input class="form-input" type="text" name="<?= h($key) ?>"
							value="<?= h($_POST[$key] ?? '') ?>">
					</div>
				<?php endforeach ?>
			</div>
		</div>

		<div class="actions-bar">
			<button class="btn btn--primary" type="submit" name="create" value="1">＋ Создать товар</button>
			<a class="btn btn--outline" href="/admin/catalog.php?section=<?= h($section) ?>">Отмена</a>
		</div>

	</form>
</div>

</body>
</html>

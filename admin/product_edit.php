<?php
/* admin/product_edit.php — редактирование карточки товара */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

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

	// Галерея
	$gallery_raw       = trim($_POST['gallery'] ?? '');
	$fields['gallery'] = $gallery_raw !== ''
		? array_values(array_filter(array_map('trim', explode("\n", $gallery_raw))))
		: [];

	// Slug
	if (!empty($_POST['slug_confirm']) && trim($_POST['new_slug'] ?? '') !== '') {
		$new_slug = trim($_POST['new_slug']);
		if (preg_match('/^[a-z0-9\-]+$/', $new_slug)) {
			$fields['slug'] = $new_slug;
		} else {
			$save_error = 'Slug может содержать только латинские буквы, цифры и дефис';
		}
	}

	// Характеристики из таблицы
	$specs_override = [];
	foreach ($_POST as $key => $val) {
		if (str_starts_with($key, 'spec_')) {
			$specs_override[$key] = trim($val);
		}
	}

	// Дополнительные характеристики (ручные)
	$extra_specs  = [];
	$extra_labels = $_POST['extra_spec_label'] ?? [];
	$extra_values = $_POST['extra_spec_value'] ?? [];
	foreach ($extra_labels as $i => $label) {
		$label = trim($label);
		$value = trim($extra_values[$i] ?? '');
		if ($label !== '' && $value !== '') {
			$extra_specs[] = ['label' => $label, 'value' => $value];
		}
	}

	// Удалённые характеристики
	$deleted_specs = array_values(array_filter($_POST['deleted_specs'] ?? []));

	if ($save_error === '') {
		$existing = load_overrides($section)[$article] ?? [];

		if ($is_manual) {
			$merged = array_merge($existing, $fields, $specs_override);
		} else {
			$override = [];
			foreach ($fields as $k => $v) {
				if ($v !== '' && $v != ($product[$k] ?? '')) $override[$k] = $v;
			}
			foreach ($specs_override as $k => $v) {
				$original = '';
				foreach ($product['specs'] ?? [] as $s) {
					if ($s['key'] === $k) { $original = $s['value']; break; }
				}
				if ($v !== $original) $override[$k] = $v;
			}
			$merged = array_merge($existing, $override);
		}

		// Дополнительные характеристики
		$merged['_extra_specs'] = $extra_specs;

		// Удалённые характеристики
		if (!empty($deleted_specs)) {
			$merged['_deleted_specs'] = array_unique($deleted_specs);
		} else {
			unset($merged['_deleted_specs']);
		}

		if (save_override($section, $article, $merged)) {
			$save_success = true;
			$product      = find_product_by_article($section, $article);
		} else {
			$save_error = 'Не удалось сохранить файл оверрайдов. Проверьте права на запись в /data/overrides/';
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

// Все URL фото (главное + галерея)
$all_images = [];
if (!empty($product['image'])) $all_images[] = $product['image'];
foreach ($product['gallery'] ?? [] as $img) {
	if (!in_array($img, $all_images, true)) $all_images[] = $img;
}

// Доп. характеристики и удалённые
$extra_specs   = $current_overrides['_extra_specs']   ?? [];
$deleted_specs = $current_overrides['_deleted_specs'] ?? [];

// Сырые характеристики из основного JSON (без применения _deleted_specs)
$raw_specs = [];
if ($is_manual) {
	$raw_specs = $product['specs'] ?? [];
} else {
	$json_file = $_SERVER['DOCUMENT_ROOT'] . '/data/' . $section . '.json';
	if (file_exists($json_file)) {
		$all_products = json_decode(file_get_contents($json_file), true) ?? [];
		foreach ($all_products as $p) {
			if ((string)$p['article'] === $article) { $raw_specs = $p['specs'] ?? []; break; }
		}
	}
}
// Применяем оверрайды значений к raw_specs
foreach ($raw_specs as &$spec) {
	if (isset($current_overrides[$spec['key']])) {
		$spec['value'] = $current_overrides[$spec['key']];
	}
}
unset($spec);
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

		.page { max-width: 960px; margin: 0 auto; padding: 24px; }

		.page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
		.page-head__title { font-size: 18px; font-weight: 600; line-height: 1.3; }
		.page-head__meta { font-size: 12px; color: var(--muted); margin-top: 4px; font-family: monospace; }

		.form-section { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow); }
		.form-section__title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }

		.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		.form-group { margin-bottom: 16px; }
		.form-group:last-child { margin-bottom: 0; }
		.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
		.form-label small { font-weight: 400; color: var(--muted); margin-left: 6px; }

		.form-input, .form-textarea {
			width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
			font-family: inherit; font-size: 14px; color: var(--text); background: var(--surface);
			transition: border-color .2s;
		}
		.form-input:focus, .form-textarea:focus { outline: none; border-color: var(--accent); }
		.form-textarea { resize: vertical; min-height: 80px; }

		/* Фото */
		.photo-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; min-height: 40px; }
		.photo-item { position: relative; width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); flex-shrink: 0; }
		.photo-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
		.photo-item__badge { position: absolute; top: 4px; left: 4px; background: var(--accent); color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 4px; }
		.photo-item__del { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; background: rgba(200,42,32,.85); color: #fff; border: none; border-radius: 50%; cursor: pointer; font-size: 14px; line-height: 1; display: flex; align-items: center; justify-content: center; padding: 0; }
		.photo-item__del:hover { background: var(--danger); }

		.photo-upload-zone { border: 2px dashed var(--border); border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; }
		.photo-upload-zone:hover, .photo-upload-zone.is-over { border-color: var(--accent); background: #f0f2ff; }
		.photo-upload-zone input { display: none; }
		.photo-upload-zone__text { font-size: 13px; color: var(--muted); line-height: 1.6; }
		.photo-upload-zone__text strong { color: var(--accent); }
		.upload-progress { display: none; margin-top: 10px; font-size: 13px; color: var(--accent); }
		.upload-error { display: none; margin-top: 8px; font-size: 13px; color: var(--danger); background: #fde0df; padding: 8px 12px; border-radius: 6px; }

		/* Характеристики */
		.spec-row { display: grid; grid-template-columns: 1fr 1fr 34px; gap: 8px; align-items: center; margin-bottom: 10px; }
		.spec-row--deleted .form-input { opacity: .45; }
		.spec-row--deleted .form-input:not(:first-child) { text-decoration: line-through; }
		.spec-extra-row { display: grid; grid-template-columns: 1fr 1fr 34px; gap: 8px; align-items: center; margin-bottom: 10px; }
		.spec-extra-row .form-input { border-style: dashed; }

		.spec-btn { width: 32px; height: 32px; border: none; background: none; cursor: pointer; border-radius: 6px; font-size: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--muted); }
		.spec-btn--del:hover { background: #fde0df; color: var(--danger); }
		.spec-btn--restore:hover { background: #e8f5e9; color: #2e7d32; }

		/* Slug */
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
		.btn--danger-outline { background: transparent; color: var(--danger); border: 1px solid #ef9a9a; }
		.btn--danger-outline:hover { background: #fde0df; }
		.btn--sm { padding: 5px 10px; font-size: 12px; }

		.actions-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px 0; border-top: 1px solid var(--border); margin-top: 8px; }
	</style>
	<script src="/admin/admin_ui.js"></script>
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
				<?php if ($is_manual): ?>&nbsp;·&nbsp;<span style="color:#1565c0">ручной товар</span><?php endif ?>
			</div>
		</div>
		<a class="btn btn--outline"
			href="<?= h(SECTION_URLS[$section]) ?><?= h($product['slug'] ?? '') ?>/"
			target="_blank">👁 На сайте</a>
	</div>

	<?php if ($save_success): ?>
		<div class="notice notice--success"><span class="notice__icon">✅</span> Сохранено</div>
	<?php endif ?>
	<?php if ($save_error): ?>
		<div class="notice notice--error"><span class="notice__icon">❌</span> <?= h($save_error) ?></div>
	<?php endif ?>
	<?php if ($just_reset): ?>
		<div class="notice notice--success"><span class="notice__icon">✅</span> Правки сброшены. Данные восстановлены из Google Sheets.</div>
	<?php endif ?>
	<?php if ($has_overrides && !$is_manual): ?>
		<div class="notice notice--warning">
			<span class="notice__icon">⚠️</span>
			<div>
				У этого товара есть ручные правки.
				<form method="POST" style="display:inline; margin-left:8px">
					<input type="hidden" name="reset_overrides" value="1">
					<button class="btn btn--danger-outline btn--sm" type="button"
						onclick="(function(btn){ adminConfirm('Сбросить правки?', 'Данные вернутся к значениям из Google Sheets. Загруженные вручную фото останутся на сервере.', () => btn.closest('form').submit(), {confirmText:'Сбросить',danger:false}); })(this)">↺ Сбросить правки</button>
				</form>
			</div>
		</div>
	<?php endif ?>

	<form method="POST" id="main-form">

		<!-- Основные данные -->
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
				<label class="form-label" for="discount_percent">Скидка по промокоду, %</label>
				<input class="form-input" type="text" id="discount_percent" name="discount_percent"
					value="<?= h($product['discount_percent'] ?? '') ?>" placeholder="например: 10" style="max-width:180px">
			</div>

			<div class="form-group">
				<label class="form-label" for="description">Описание</label>
				<textarea class="form-textarea" id="description" name="description" rows="6"><?= h($product['description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Фотографии -->
		<div class="form-section">
			<div class="form-section__title">Фотографии</div>

			<!-- Текущие фото — рендерятся через JS -->
			<div class="photo-grid" id="photo-grid"></div>

			<!-- Скрытые поля синхронизируются через JS -->
			<div id="photo-fields">
				<input type="hidden" name="image" value="<?= h($all_images[0] ?? '') ?>">
				<textarea name="gallery" style="display:none"><?= h(implode("\n", array_slice($all_images, 1))) ?></textarea>
			</div>

			<!-- Зона загрузки -->
			<div class="photo-upload-zone" id="upload-zone">
				<input type="file" id="file-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
				<div class="photo-upload-zone__text">
					<strong>Выберите файлы</strong> или перетащите сюда<br>
					JPG, PNG, WebP, GIF · до 8 МБ каждый
				</div>
			</div>
			<div class="upload-progress" id="upload-progress">⏳ Загрузка…</div>
			<div class="upload-error" id="upload-error"></div>
		</div>

		<!-- SEO -->
		<div class="form-section">
			<div class="form-section__title">SEO / Метатеги</div>

			<div class="form-group">
				<label class="form-label" for="meta_title">Meta title <small>необязательно</small></label>
				<input class="form-input" type="text" id="meta_title" name="meta_title"
					value="<?= h($product['meta_title'] ?? '') ?>">
			</div>

			<div class="form-group">
				<label class="form-label" for="meta_description">Meta description <small>до 160 символов</small></label>
				<textarea class="form-textarea" id="meta_description" name="meta_description" rows="3"
					maxlength="160"><?= h($product['meta_description'] ?? '') ?></textarea>
			</div>
		</div>

		<!-- Характеристики -->
		<div class="form-section">
			<div class="form-section__title">
				<span>Характеристики</span>
				<button type="button" class="btn btn--outline btn--sm" onclick="addSpecRow()">＋ Добавить</button>
			</div>

			<div id="specs-list">

				<!-- Характеристики из таблицы -->
				<?php foreach ($raw_specs as $spec):
					$is_deleted = in_array($spec['key'], $deleted_specs, true);
				?>
					<div class="spec-row <?= $is_deleted ? 'spec-row--deleted' : '' ?>" id="spec-row-<?= h($spec['key']) ?>">
						<!-- Название (нередактируемое) -->
						<input class="form-input" type="text" value="<?= h($spec['label']) ?>" disabled
							style="background:var(--bg); color:var(--muted);">
						<!-- Значение -->
						<input class="form-input" type="text"
							name="<?= h($spec['key']) ?>"
							value="<?= h($spec['value']) ?>"
							<?= $is_deleted ? 'disabled' : '' ?>>
						<?php if ($is_deleted): ?>
							<input type="hidden" name="deleted_specs[]" value="<?= h($spec['key']) ?>"
								id="del-field-<?= h($spec['key']) ?>">
							<button type="button" class="spec-btn spec-btn--restore" title="Восстановить"
								onclick="restoreSpec('<?= h($spec['key']) ?>')">↩</button>
						<?php else: ?>
							<button type="button" class="spec-btn spec-btn--del" title="Скрыть характеристику"
								onclick="deleteSpec('<?= h($spec['key']) ?>', '<?= h(addslashes($spec['label'])) ?>')">×</button>
						<?php endif ?>
					</div>
				<?php endforeach ?>

				<!-- Дополнительные характеристики (ручные) -->
				<?php foreach ($extra_specs as $es): ?>
					<div class="spec-extra-row">
						<input class="form-input" type="text" name="extra_spec_label[]"
							value="<?= h($es['label']) ?>" placeholder="Название">
						<input class="form-input" type="text" name="extra_spec_value[]"
							value="<?= h($es['value']) ?>" placeholder="Значение">
						<button type="button" class="spec-btn spec-btn--del"
							onclick="this.closest('.spec-extra-row').remove()">×</button>
					</div>
				<?php endforeach ?>

			</div>

			<!-- Шаблон новой строки -->
			<template id="spec-row-tpl">
				<div class="spec-extra-row">
					<input class="form-input" type="text" name="extra_spec_label[]" placeholder="Название характеристики">
					<input class="form-input" type="text" name="extra_spec_value[]" placeholder="Значение">
					<button type="button" class="spec-btn spec-btn--del"
						onclick="this.closest('.spec-extra-row').remove()">×</button>
				</div>
			</template>
		</div>

		<!-- Slug -->
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
					Slug — это URL товара. Его изменение <strong>сломает все ссылки</strong> на карточку,
					обнулит позиции в поиске и потребует обновления Google Sheets.
					Трогайте только при явной опечатке.
				</div>
				<label class="slug-confirm-label">
					<input type="checkbox" id="slug-confirm-check" onchange="toggleSlugField(this.checked)">
					<span>Я понимаю последствия и хочу изменить slug</span>
				</label>
				<div id="slug-field" style="display:none">
					<input class="form-input" type="text" id="new_slug" name="new_slug"
						value="<?= h($product['slug'] ?? '') ?>"
						pattern="[a-z0-9\-]+"
						style="font-family:monospace; margin-bottom:8px">
					<input type="hidden" name="slug_confirm" id="slug-confirm-input" value="">
				</div>
			</div>
		</div>

		<div class="actions-bar">
			<button class="btn btn--primary" type="submit" name="save" value="1">💾 Сохранить</button>
			<a class="btn btn--outline" href="/admin/catalog.php?section=<?= h($section) ?>">← Назад</a>
		</div>

	</form>
</div>

<script>
const SECTION    = '<?= h($section) ?>';
const UPLOAD_URL = '/admin/upload.php';

/* ===== ФОТО ===== */

let photos = <?= json_encode(array_values($all_images), JSON_UNESCAPED_UNICODE) ?>;

function syncPhotoFields() {
	const wrap  = document.getElementById('photo-fields');
	wrap.innerHTML = '';

	// Главное фото
	const imgEl  = document.createElement('input');
	imgEl.type   = 'hidden';
	imgEl.name   = 'image';
	imgEl.value  = photos[0] ?? '';
	wrap.appendChild(imgEl);

	// Галерея
	const galEl  = document.createElement('textarea');
	galEl.name   = 'gallery';
	galEl.style.display = 'none';
	galEl.value  = photos.slice(1).join('\n');
	wrap.appendChild(galEl);
}

function renderPhotoGrid() {
	const grid = document.getElementById('photo-grid');
	grid.innerHTML = '';

	photos.forEach((url, i) => {
		const div = document.createElement('div');
		div.className   = 'photo-item';
		div.dataset.url = url;

		const img = document.createElement('img');
		img.src     = url;
		img.alt     = '';
		img.loading = 'lazy';
		div.appendChild(img);

		if (i === 0) {
			const badge = document.createElement('span');
			badge.className   = 'photo-item__badge';
			badge.textContent = 'Главное';
			div.appendChild(badge);
		}

		const del = document.createElement('button');
		del.className = 'photo-item__del';
		del.type      = 'button';
		del.title     = 'Удалить';
		del.textContent = '×';
		del.addEventListener('click', () => removePhoto(url));
		div.appendChild(del);

		grid.appendChild(div);
	});
}

function isLocal(url) {
	return url.startsWith('/images/products/');
}

function removePhoto(url) {
	const fromServer = isLocal(url);
	const msg = fromServer
		? 'Файл будет удалён с сервера — это необратимо.'
		: 'Ссылка на фото будет убрана из списка.';
	adminConfirm('Удалить фото?', msg, () => _doRemovePhoto(url, fromServer));
}

function _doRemovePhoto(url, fromServer) {
	if (fromServer) {
		const body = new URLSearchParams({ action: 'delete', section: SECTION, url });
		fetch(UPLOAD_URL, { method: 'POST', body })
			.then(r => r.json())
			.then(data => {
				if (data.ok) { doRemove(url); }
				else { showError('Ошибка удаления: ' + data.error); }
			})
			.catch(() => showError('Ошибка соединения'));
	} else {
		doRemove(url);
	}
}

function doRemove(url) {
	photos = photos.filter(u => u !== url);
	renderPhotoGrid();
	syncPhotoFields();
}

function uploadFiles(files) {
	if (!files.length) return;
	showProgress(true);
	hideError();

	const promises = [...files].map(file => {
		const fd = new FormData();
		fd.append('action', 'upload');
		fd.append('section', SECTION);
		fd.append('file', file);
		return fetch(UPLOAD_URL, { method: 'POST', body: fd }).then(r => r.json());
	});

	Promise.all(promises).then(results => {
		showProgress(false);
		const errors = results.filter(r => !r.ok).map(r => r.error);
		if (errors.length) showError(errors.join('; '));
		results.filter(r => r.ok).forEach(r => {
			if (!photos.includes(r.url)) photos.push(r.url);
		});
		renderPhotoGrid();
		syncPhotoFields();
	}).catch(() => { showProgress(false); showError('Ошибка загрузки'); });
}

function showProgress(v) { document.getElementById('upload-progress').style.display = v ? 'block' : 'none'; }
function showError(msg) { const el = document.getElementById('upload-error'); el.textContent = msg; el.style.display = 'block'; }
function hideError() { document.getElementById('upload-error').style.display = 'none'; }

// Инициализация — рендерим фото через JS чтобы обработчики работали
renderPhotoGrid();
syncPhotoFields();

// Клик по зоне загрузки
const zone      = document.getElementById('upload-zone');
const fileInput = document.getElementById('file-input');
zone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => { uploadFiles(fileInput.files); fileInput.value = ''; });

// Drag & drop
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('is-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('is-over'));
zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('is-over'); uploadFiles(e.dataTransfer.files); });

/* ===== ХАРАКТЕРИСТИКИ ===== */

function addSpecRow() {
	const tpl   = document.getElementById('spec-row-tpl');
	const clone = tpl.content.cloneNode(true);
	document.getElementById('specs-list').appendChild(clone);
	const rows  = document.querySelectorAll('#specs-list .spec-extra-row');
	rows[rows.length - 1].querySelector('input').focus();
}

function deleteSpec(key, label) {
	adminConfirm('Скрыть характеристику «' + label + '»?', 'Её можно будет восстановить кнопкой ↩', () => _deleteSpecDo(key, label));
}

function _deleteSpecDo(key, label) {
	const row = document.getElementById('spec-row-' + key);
	if (!row) return;

	row.classList.add('spec-row--deleted');
	row.querySelectorAll('input[type=text]').forEach(i => i.disabled = true);

	// Скрытое поле для передачи ключа
	const hidden = document.createElement('input');
	hidden.type  = 'hidden';
	hidden.name  = 'deleted_specs[]';
	hidden.value = key;
	hidden.id    = 'del-field-' + key;
	row.appendChild(hidden);

	// Меняем кнопку × → ↩
	const delBtn = row.querySelector('.spec-btn--del');
	const restoreBtn = document.createElement('button');
	restoreBtn.type      = 'button';
	restoreBtn.className = 'spec-btn spec-btn--restore';
	restoreBtn.title     = 'Восстановить';
	restoreBtn.textContent = '↩';
	restoreBtn.onclick   = () => restoreSpec(key);
	delBtn.replaceWith(restoreBtn);
}

function restoreSpec(key) {
	const row = document.getElementById('spec-row-' + key);
	if (!row) return;

	row.classList.remove('spec-row--deleted');
	row.querySelectorAll('input[type=text]').forEach(i => i.disabled = false);

	document.getElementById('del-field-' + key)?.remove();

	// Меняем кнопку ↩ → ×
	const restoreBtn = row.querySelector('.spec-btn--restore');
	const label = row.querySelector('input[type=text]').value;
	const delBtn = document.createElement('button');
	delBtn.type      = 'button';
	delBtn.className = 'spec-btn spec-btn--del';
	delBtn.title     = 'Скрыть характеристику';
	delBtn.textContent = '×';
	delBtn.onclick   = () => deleteSpec(key, label);
	restoreBtn.replaceWith(delBtn);
}

/* ===== SLUG ===== */

function toggleSlugField(checked) {
	document.getElementById('slug-field').style.display = checked ? 'block' : 'none';
	document.getElementById('slug-confirm-input').value  = checked ? '1' : '';
}
</script>

</body>
</html>
<?php
/* admin/catalog.php — список товаров раздела */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

// --- Параметры ---
$section = $_GET['section'] ?? '';
if (!array_key_exists($section, SECTION_NAMES)) {
	header('Location: /admin/');
	exit;
}

$section_name = SECTION_NAMES[$section];

// --- Одиночные POST-действия ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Сохранить порядок
	if (isset($_POST['save_order'])) {
		$articles = array_filter(array_map('trim', (array)$_POST['order_articles']));
		save_order($section, array_values($articles));
		header('Location: /admin/catalog.php?section=' . urlencode($section) . '&sorted=1');
		exit;
	}

	// Сброс оверрайдов
	if (isset($_POST['delete_override'])) {
		$article = $_POST['article'] ?? '';
		if ($article) delete_override($section, $article);
		header('Location: /admin/catalog.php?section=' . urlencode($section) . '&q=' . urlencode($_GET['q'] ?? ''));
		exit;
	}

	// Скрыть/показать товар
	if (isset($_POST['toggle_hidden'])) {
		$article = $_POST['article'] ?? '';
		$hidden  = (bool)($_POST['hidden'] ?? false);
		if ($article) set_product_hidden($section, $article, $hidden);
		header('Location: /admin/catalog.php?section=' . urlencode($section) . '&q=' . urlencode($_GET['q'] ?? ''));
		exit;
	}

	// Удалить товар (необратимо)
	if (isset($_POST['delete_product'])) {
		$article = $_POST['article'] ?? '';
		if ($article) {
			delete_product_from_json($section, $article);
			delete_override($section, $article);
		}
		header('Location: /admin/catalog.php?section=' . urlencode($section));
		exit;
	}

	// Bulk-действия
	if (isset($_POST['bulk_action'], $_POST['bulk_articles'])) {
		$action   = $_POST['bulk_action'];
		$articles = array_filter((array)$_POST['bulk_articles']);

		foreach ($articles as $article) {
			switch ($action) {
				case 'hide':
					set_product_hidden($section, $article, true);
					break;
				case 'show':
					set_product_hidden($section, $article, false);
					break;
				case 'delete':
					delete_product_from_json($section, $article);
					delete_override($section, $article);
					break;
			}
		}
		header('Location: /admin/catalog.php?section=' . urlencode($section));
		exit;
	}
}

// --- Режим сортировки ---
$sort_mode   = isset($_GET['sort_mode']);
$sort_saved  = isset($_GET['sorted']);

// --- Загрузка и фильтрация ---
$products = load_products($section);

$search = $sort_mode ? '' : trim($_GET['q'] ?? '');
if ($search !== '') {
	$q        = mb_strtolower($search);
	$products = array_filter($products, function($p) use ($q) {
		return str_contains(mb_strtolower($p['name'] ?? ''), $q)
			|| str_contains((string)($p['article'] ?? ''), $q)
			|| str_contains(mb_strtolower($p['slug'] ?? ''), $q);
	});
}

// --- Сортировка столбцов (не в режиме drag-and-drop) ---
$sort = $_GET['sort'] ?? '';
$dir  = $_GET['dir']  ?? 'asc';

if (!$sort_mode && $sort !== '') {
	// Явная сортировка по столбцу — применяем usort
	usort($products, function($a, $b) use ($sort, $dir) {
		$va = $a[$sort] ?? '';
		$vb = $b[$sort] ?? '';
		$cmp = is_numeric($va) && is_numeric($vb)
			? $va <=> $vb
			: mb_strtolower((string)$va) <=> mb_strtolower((string)$vb);
		return $dir === 'desc' ? -$cmp : $cmp;
	});
}
// Если sort не задан — товары уже в порядке из load_products (с учётом order-файла)

$total = count($products);

function sort_url(string $col, string $cur_sort, string $cur_dir): string {
	$new_dir = ($cur_sort === $col && $cur_dir === 'asc') ? 'desc' : 'asc';
	return '?section=' . urlencode($_GET['section'] ?? '')
		. '&sort=' . $col . '&dir=' . $new_dir
		. (isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '');
}

function sort_arrow(string $col, string $cur_sort, string $cur_dir): string {
	if ($cur_sort !== $col) return '';
	return $cur_dir === 'asc' ? ' ↑' : ' ↓';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= h($section_name) ?> — Админка WERGRAUF</title>
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
		.topbar__left { display: flex; align-items: center; gap: 16px; }
		.topbar__brand { font-weight: 700; font-size: 15px; text-decoration: none; color: #fff; }
		.topbar__sep { opacity: .4; }
		.topbar__section { font-size: 14px; opacity: .85; }
		.topbar__logout { color: rgba(255,255,255,.7); text-decoration: none; font-size: 13px; }
		.topbar__logout:hover { color: #fff; }

		.page { max-width: 1300px; margin: 0 auto; padding: 24px; }

		/* Тулбар */
		.toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
		.toolbar__back { color: var(--accent); text-decoration: none; font-size: 13px; }
		.toolbar__back:hover { text-decoration: underline; }
		.toolbar__title { font-size: 18px; font-weight: 600; flex: 1; }
		.toolbar__count { font-size: 13px; color: var(--muted); background: var(--bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border); }

		/* Поиск и bulk */
		.search-bulk { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
		.search-input { flex: 1; min-width: 200px; max-width: 340px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; color: var(--text); background: var(--surface); }
		.search-input:focus { outline: none; border-color: var(--accent); }

		/* Bulk-панель */
		.bulk-bar { display: none; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; align-items: center; gap: 12px; flex-wrap: wrap; }
		.bulk-bar.is-visible { display: flex; }
		.bulk-bar__count { font-size: 13px; font-weight: 500; }
		.bulk-bar__actions { display: flex; gap: 8px; }

		/* Кнопки */
		.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; border: none; font-family: inherit; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .2s; white-space: nowrap; }
		.btn--primary { background: var(--accent); color: #fff; }
		.btn--primary:hover { background: var(--accent-h); }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }
		.btn--danger { background: var(--danger); color: #fff; }
		.btn--danger:hover { background: #a32219; }
		.btn--danger-outline { background: transparent; color: var(--danger); border: 1px solid #ef9a9a; }
		.btn--danger-outline:hover { background: #fde0df; }
		.btn--warning { background: #f57c00; color: #fff; }
		.btn--warning:hover { background: #e65100; }
		.btn--success { background: #2e7d32; color: #fff; }
		.btn--success:hover { background: #1b5e20; }

		/* Таблица */
		.table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
		table { width: 100%; border-collapse: collapse; }
		thead { background: var(--bg); }
		th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; border-bottom: 1px solid var(--border); }
		th a { color: inherit; text-decoration: none; }
		th a:hover { color: var(--text); }
		td { padding: 9px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
		tr:last-child td { border-bottom: none; }
		tr:hover td { background: #fafbfc; }
		tr.is-hidden td { opacity: .55; }

		/* Ячейки */
		.td-check { width: 32px; }
		.td-img { width: 52px; }
		.td-img img { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); display: block; }
		.td-img .no-img { width: 44px; height: 44px; border-radius: 6px; background: var(--bg); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 18px; }
		.td-name { max-width: 240px; }
		.td-name__title { font-weight: 500; font-size: 13px; line-height: 1.4; }
		.td-name__slug { font-size: 11px; color: var(--muted); margin-top: 2px; font-family: monospace; }
		.td-price { white-space: nowrap; }
		.td-price__current { font-weight: 600; }
		.td-price__old { font-size: 12px; color: var(--muted); text-decoration: line-through; }
		.td-actions { white-space: nowrap; }

		.badge { display: inline-block; padding: 2px 7px; border-radius: 5px; font-size: 11px; font-weight: 600; }
		.badge--stock { background: #e8f5e9; color: #2e7d32; }
		.badge--override { background: #fff3e0; color: #e65100; }
		.badge--hidden { background: #fce4ec; color: #c62828; }
		.badge--manual { background: #e3f2fd; color: #1565c0; }

		/* Пусто */
		.empty { text-align: center; padding: 48px 24px; color: var(--muted); font-size: 14px; }
		.empty p { margin-top: 8px; font-size: 13px; }

		/* Drag-and-drop сортировка */
		.drag-handle { cursor: grab; color: var(--muted); font-size: 16px; padding: 0 4px; user-select: none; }
		.drag-handle:active { cursor: grabbing; }
		tr.is-dragging { opacity: .4; }
		tr.drag-over td { border-top: 2px solid var(--accent-h); }
		.sort-mode-bar { background: #e8f0fe; border: 1px solid #b3c8f5; border-radius: 8px; padding: 10px 16px; margin-bottom: 14px; display: flex; align-items: center; gap: 12px; font-size: 13px; color: #1a3a6e; }
		.sort-mode-bar strong { font-weight: 600; }
	</style>
	<script src="/admin/admin_ui.js"></script>
</head>
<body>

<div class="topbar">
	<div class="topbar__left">
		<a class="topbar__brand" href="/admin/">WERGRAUF</a>
		<span class="topbar__sep">/</span>
		<span class="topbar__section"><?= h($section_name) ?></span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<!-- Навигация -->
<nav class="admin-nav" style="background:#fff;border-bottom:1px solid #e2e4e9;padding:0 24px;display:flex;gap:4px;position:sticky;top:52px;z-index:99;">
	<a style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;font-size:13px;font-weight:500;color:#8a8f9a;text-decoration:none;border-bottom:2px solid transparent;" href="/admin/">🗂 Дашборд</a>
	<a style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;font-size:13px;font-weight:500;color:#8a8f9a;text-decoration:none;border-bottom:2px solid transparent;" href="/admin/home_edit.php">🏠 Главная страница</a>
	<a style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;font-size:13px;font-weight:500;color:#8a8f9a;text-decoration:none;border-bottom:2px solid transparent;" href="/admin/log.php">📋 Лог синхронизаций</a>
</nav>

<div class="page">

	<div class="toolbar">
		<a class="toolbar__back" href="/admin/">← Назад</a>
		<div class="toolbar__title"><?= h($section_name) ?></div>
		<div class="toolbar__count"><?= $total ?> товаров</div>
		<a class="btn btn--primary" href="/admin/product_new.php?section=<?= h($section) ?>">＋ Новый товар</a>
		<?php if (!$sort_mode): ?>
			<a class="btn btn--outline" href="?section=<?= h($section) ?>&sort_mode">⇅ Сортировка</a>
		<?php else: ?>
			<a class="btn btn--outline" href="?section=<?= h($section) ?>">✕ Выйти из сортировки</a>
		<?php endif ?>
	</div>

	<?php if ($sort_saved): ?>
	<div class="sort-mode-bar" style="background:#e8f5e9;border-color:#a5d6a7;color:#1b5e20;">
		✅ <strong>Порядок сохранён.</strong>
	</div>
	<?php endif ?>

	<?php if ($sort_mode): ?>
	<div class="sort-mode-bar">
		⇅ <strong>Режим сортировки.</strong> Перетащите строки в нужном порядке, затем нажмите «Сохранить порядок».
	</div>
	<?php endif ?>

	<!-- Поиск (скрыт в режиме сортировки) -->
	<?php if (!$sort_mode): ?>
	<form class="search-bulk" method="GET">
		<input type="hidden" name="section" value="<?= h($section) ?>">
		<input class="search-input" type="text" name="q"
			placeholder="Поиск по названию, артикулу, slug…"
			value="<?= h($search) ?>" autofocus>
		<button class="btn btn--primary" type="submit">Найти</button>
		<?php if ($search): ?>
			<a class="btn btn--outline" href="?section=<?= h($section) ?>">Сбросить</a>
		<?php endif ?>
	</form>
	<?php endif /* !sort_mode */ ?>

	<?php if ($sort_mode): ?>
	<!-- Форма сохранения порядка -->
	<form method="POST" id="sort-form">
		<input type="hidden" name="section" value="<?= h($section) ?>">
		<input type="hidden" name="save_order" value="1">
		<div id="sort-articles"></div>
	<?php else: ?>
	<!-- Bulk-панель (показывается при выборе чекбоксов) -->
	<form method="POST" id="bulk-form">
		<input type="hidden" name="section" value="<?= h($section) ?>">
		<input type="hidden" name="bulk_action" id="bulk-action-input" value="">

		<div class="bulk-bar" id="bulk-bar">
			<div class="bulk-bar__count">Выбрано: <strong id="bulk-count">0</strong></div>
			<div class="bulk-bar__actions">
				<button class="btn btn--warning" type="button" onclick="bulkSubmit('hide')">🚫 Скрыть</button>
				<button class="btn btn--success" type="button" onclick="bulkSubmit('show')">✅ Показать</button>
				<button class="btn btn--danger" type="button" onclick="bulkSubmit('delete')">🗑 Удалить</button>
			</div>
		</div>
	</form><!-- /bulk-form -->
	<?php endif ?>

	<?php if (empty($products)): ?>
		<div class="empty">
			<?php if ($search): ?>
				🔍 Ничего не найдено по запросу «<?= h($search) ?>»
				<p><a href="?section=<?= h($section) ?>">Сбросить поиск</a></p>
			<?php else: ?>
				📭 В этом разделе нет товаров
				<p>Запустите синхронизацию из <a href="/admin/">дашборда</a> или <a href="/admin/product_new.php?section=<?= h($section) ?>">добавьте вручную</a></p>
			<?php endif ?>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table>
				<thead>
					<tr>
						<?php if ($sort_mode): ?>
						<th style="width:32px;"></th>
						<?php else: ?>
						<th class="td-check">
							<input type="checkbox" id="check-all" title="Выбрать все">
						</th>
						<?php endif ?>
						<th class="td-img"></th>
						<th><a href="<?= sort_url('name', $sort, $dir) ?>">Название<?= sort_arrow('name', $sort, $dir) ?></a></th>
						<th><a href="<?= sort_url('article', $sort, $dir) ?>">Артикул<?= sort_arrow('article', $sort, $dir) ?></a></th>
						<th><a href="<?= sort_url('price', $sort, $dir) ?>">Цена<?= sort_arrow('price', $sort, $dir) ?></a></th>
						<th><a href="<?= sort_url('stock', $sort, $dir) ?>">Остаток<?= sort_arrow('stock', $sort, $dir) ?></a></th>
						<th>Статус</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($products as $p):
					$art    = (string)($p['article'] ?? '');
					$hidden = !empty($p['hidden']);
					$manual = !empty($p['_manual']);
				?>
					<tr <?= $hidden ? 'class="is-hidden"' : '' ?> data-article="<?= h($art) ?>">
						<!-- Handle или чекбокс -->
						<?php if ($sort_mode): ?>
						<td class="drag-handle" title="Перетащить">⠿</td>
						<?php else: ?>
						<td class="td-check">
							<input type="checkbox" name="bulk_articles[]" value="<?= h($art) ?>" class="row-check">
						</td>
						<?php endif ?>

						<!-- Фото -->
						<td class="td-img">
							<?php if (!empty($p['image'])): ?>
								<img src="<?= h($p['image']) ?>" alt="" loading="lazy">
							<?php else: ?>
								<div class="no-img">📷</div>
							<?php endif ?>
						</td>

						<!-- Название -->
						<td class="td-name">
							<div class="td-name__title"><?= h($p['name'] ?? '') ?></div>
							<div class="td-name__slug">/<?= h($p['slug'] ?? '') ?>/</div>
						</td>

						<!-- Артикул -->
						<td><?= h($art) ?></td>

						<!-- Цена -->
						<td class="td-price">
							<div class="td-price__current"><?= format_price($p['price']) ?></div>
							<?php if (!empty($p['old_price'])): ?>
								<div class="td-price__old"><?= format_price($p['old_price']) ?></div>
							<?php endif ?>
						</td>

						<!-- Остаток -->
						<td><span class="badge badge--stock"><?= (int)($p['stock'] ?? 0) ?> шт.</span></td>

						<!-- Статус -->
						<td>
							<?php if ($hidden): ?>
								<span class="badge badge--hidden">Скрыт</span>
							<?php endif ?>
							<?php if ($manual): ?>
								<span class="badge badge--manual">Ручной</span>
							<?php endif ?>
							<?php if (!empty($p['_has_overrides']) && !$manual): ?>
								<span class="badge badge--override">Правки</span>
							<?php endif ?>
						</td>

						<!-- Действия -->
						<td class="td-actions">
							<a class="btn btn--primary" href="/admin/product_edit.php?section=<?= h($section) ?>&article=<?= h($art) ?>">
								✏️ Ред.
							</a>

							<a class="btn btn--outline" href="<?= h(SECTION_URLS[$section]) ?><?= h($p['slug'] ?? '') ?>/" target="_blank">
								👁
							</a>

							<!-- Скрыть / показать -->
							<?php if ($hidden): ?>
								<form method="POST" style="display:inline">
									<input type="hidden" name="article" value="<?= h($art) ?>">
									<input type="hidden" name="hidden" value="0">
									<button class="btn btn--success" type="submit" name="toggle_hidden" value="1" title="Показать товар">✅</button>
								</form>
							<?php else: ?>
								<form method="POST" style="display:inline">
									<input type="hidden" name="article" value="<?= h($art) ?>">
									<input type="hidden" name="hidden" value="1">
									<button class="btn btn--outline" type="submit" name="toggle_hidden" value="1" title="Скрыть товар">🚫</button>
								</form>
							<?php endif ?>

							<!-- Сброс оверрайдов -->
							<?php if (!empty($p['_has_overrides']) && !$manual): ?>
								<form method="POST" style="display:inline">
									<input type="hidden" name="article" value="<?= h($art) ?>">
									<input type="hidden" name="delete_override" value="1">
									<button class="btn btn--danger-outline" type="button"
								onclick="(function(btn){ adminConfirm('Сбросить правки?', 'Ручные изменения этого товара будут удалены, данные вернутся из Google Sheets.', () => btn.closest('form').submit(), {confirmText:'Сбросить',danger:false}); })(this)">↺ Сброс</button>
								</form>
							<?php endif ?>

							<!-- Удалить -->
							<form method="POST" style="display:inline">
								<input type="hidden" name="article" value="<?= h($art) ?>">
								<input type="hidden" name="delete_product" value="1">
								<button class="btn btn--danger" type="button"
								onclick="(function(btn){ adminConfirm('Удалить товар?', 'Необратимое действие. Если товар есть в Google Sheets — вернётся при следующей синхронизации.', () => btn.closest('form').submit(), {confirmText:'Удалить',danger:true}); })(this)">🗑</button>
							</form>
						</td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>

	<?php if ($sort_mode): ?>
		<div style="margin-top:16px;">
			<button type="submit" form="sort-form" class="btn btn--primary">💾 Сохранить порядок</button>
		</div>
	</form><!-- /sort-form -->
	<?php endif ?>
</div>

<script>
/* --- Выбор чекбоксов и bulk-панель --- */
const checkAll  = document.getElementById('check-all');
const bulkBar   = document.getElementById('bulk-bar');
const bulkCount = document.getElementById('bulk-count');
const rowChecks = () => document.querySelectorAll('.row-check');

function updateBulkBar() {
	const checked = [...rowChecks()].filter(c => c.checked).length;
	bulkCount.textContent = checked;
	bulkBar.classList.toggle('is-visible', checked > 0);
	if (checkAll) checkAll.indeterminate = checked > 0 && checked < rowChecks().length;
}

if (checkAll) {
	checkAll.addEventListener('change', () => {
		rowChecks().forEach(c => c.checked = checkAll.checked);
		updateBulkBar();
	});
}

document.addEventListener('change', e => {
	if (e.target.classList.contains('row-check')) updateBulkBar();
});

function bulkSubmit(action) {
	const checked = [...rowChecks()].filter(c => c.checked);
	if (!checked.length) return;

	const labels   = { hide: 'Скрыть', show: 'Показать', delete: 'Удалить' };
	const isDanger = action === 'delete';
	const text     = `Действие будет применено к ${checked.length} товарам.`
		+ (isDanger ? ' Удалённые товары вернутся при синхронизации с Google Sheets.' : '');

	adminConfirm(labels[action] + ' выбранные товары?', text, () => {
		document.getElementById('bulk-action-input').value = action;
		document.getElementById('bulk-form').submit();
	}, { confirmText: labels[action], danger: isDanger });
}

/* --- Drag-and-drop сортировка --- */
var SORT_MODE = <?= $sort_mode ? 'true' : 'false' ?>;

if (SORT_MODE) (function() {
	const tbody  = document.querySelector('tbody');
	const form   = document.getElementById('sort-form');
	const holder = document.getElementById('sort-articles');
	let dragged  = null;

	/* Делаем строки перетаскиваемыми */
	tbody.querySelectorAll('tr').forEach(tr => { tr.draggable = true; });

	tbody.addEventListener('dragstart', e => {
		dragged = e.target.closest('tr');
		dragged.classList.add('is-dragging');
	});

	tbody.addEventListener('dragend', () => {
		if (dragged) dragged.classList.remove('is-dragging');
		tbody.querySelectorAll('tr.drag-over').forEach(r => r.classList.remove('drag-over'));
		dragged = null;
	});

	tbody.addEventListener('dragover', e => {
		e.preventDefault();
		const target = e.target.closest('tr');
		if (!target || target === dragged) return;
		tbody.querySelectorAll('tr.drag-over').forEach(r => r.classList.remove('drag-over'));
		target.classList.add('drag-over');
	});

	tbody.addEventListener('drop', e => {
		e.preventDefault();
		const target = e.target.closest('tr');
		if (!target || target === dragged) return;
		target.classList.remove('drag-over');
		tbody.insertBefore(dragged, target);
	});

	/* Перед отправкой формы собираем порядок артикулов */
	form.addEventListener('submit', () => {
		holder.innerHTML = '';
		tbody.querySelectorAll('tr').forEach(tr => {
			const art = tr.dataset.article;
			if (!art) return;
			const inp = document.createElement('input');
			inp.type  = 'hidden';
			inp.name  = 'order_articles[]';
			inp.value = art;
			holder.appendChild(inp);
		});
	});
})();
</script>

</body>
</html>
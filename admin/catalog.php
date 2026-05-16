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

// --- Загрузка и фильтрация ---
$products = load_products($section);

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
	$q        = mb_strtolower($search);
	$products = array_filter($products, function($p) use ($q) {
		return str_contains(mb_strtolower($p['name'] ?? ''), $q)
			|| str_contains((string)($p['article'] ?? ''), $q)
			|| str_contains(mb_strtolower($p['slug'] ?? ''), $q);
	});
}

// --- Сортировка ---
$sort = $_GET['sort'] ?? 'name';
$dir  = $_GET['dir']  ?? 'asc';

usort($products, function($a, $b) use ($sort, $dir) {
	$va = $a[$sort] ?? '';
	$vb = $b[$sort] ?? '';
	$cmp = is_numeric($va) && is_numeric($vb)
		? $va <=> $vb
		: mb_strtolower((string)$va) <=> mb_strtolower((string)$vb);
	return $dir === 'desc' ? -$cmp : $cmp;
});

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

<div class="page">

	<div class="toolbar">
		<a class="toolbar__back" href="/admin/">← Назад</a>
		<div class="toolbar__title"><?= h($section_name) ?></div>
		<div class="toolbar__count"><?= $total ?> товаров</div>
		<a class="btn btn--primary" href="/admin/product_new.php?section=<?= h($section) ?>">＋ Новый товар</a>
	</div>

	<!-- Поиск -->
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
						<th class="td-check">
							<input type="checkbox" id="check-all" title="Выбрать все">
						</th>
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
					<tr <?= $hidden ? 'class="is-hidden"' : '' ?>>
						<!-- Чекбокс -->
						<td class="td-check">
							<input type="checkbox" name="bulk_articles[]" value="<?= h($art) ?>" class="row-check">
						</td>

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

	</form><!-- /bulk-form -->
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
</script>

</body>
</html>
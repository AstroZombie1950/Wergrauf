<?php
/* admin/reviews.php — модерация и редактирование отзывов */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

define('REVIEWS_FILE',   DATA_DIR . 'reviews.json');
define('REVIEWS_UPLOAD', $_SERVER['DOCUMENT_ROOT'] . '/order/reviews/uploads/');

/* --- Загрузка --- */
function rv_load(): array {
	if (!file_exists(REVIEWS_FILE)) return [];
	return json_decode(file_get_contents(REVIEWS_FILE), true) ?? [];
}

function rv_save(array $data): void {
	file_put_contents(REVIEWS_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/* Плоский список всех отзывов (с полем _article) */
function rv_flat(array $all): array {
	$out = [];
	foreach ($all as $article => $list) {
		foreach ($list as $r) {
			$r['_article'] = $article;
			$out[] = $r;
		}
	}
	// Сортируем по дате — новые сверху
	usort($out, fn($a, $b) => ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0));
	return $out;
}

/* Найти отзыв по id */
function rv_find(array $all, string $id): ?array {
	foreach ($all as $list) {
		foreach ($list as $r) {
			if ($r['id'] === $id) return $r;
		}
	}
	return null;
}

/* Обновить поля отзыва */
function rv_update(array &$all, string $id, array $fields): bool {
	foreach ($all as $article => &$list) {
		foreach ($list as &$r) {
			if ($r['id'] === $id) {
				$r = array_merge($r, $fields);
				return true;
			}
		}
	}
	return false;
}

/* Удалить отзыв */
function rv_delete(array &$all, string $id): void {
	foreach ($all as $article => &$list) {
		foreach ($list as $i => $r) {
			if ($r['id'] === $id) {
				// Удаляем файлы фото
				foreach ($r['photos'] ?? [] as $photo) {
					$path = $_SERVER['DOCUMENT_ROOT'] . $photo;
					if (file_exists($path)) @unlink($path);
				}
				array_splice($list, $i, 1);
				return;
			}
		}
	}
}

/* Удалить одно фото из отзыва */
function rv_delete_photo(array &$all, string $id, int $photo_idx): void {
	foreach ($all as $article => &$list) {
		foreach ($list as &$r) {
			if ($r['id'] === $id && isset($r['photos'][$photo_idx])) {
				$path = $_SERVER['DOCUMENT_ROOT'] . $r['photos'][$photo_idx];
				if (file_exists($path)) @unlink($path);
				array_splice($r['photos'], $photo_idx, 1);
				return;
			}
		}
	}
}

/* --- Обработка действий --- */
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$rv_id   = trim($_POST['id'] ?? $_GET['id'] ?? '');
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rv_id !== '') {
	$all = rv_load();

	if ($action === 'publish') {
		rv_update($all, $rv_id, ['published' => true]);
		rv_save($all);
		$message = 'Отзыв опубликован.';

	} elseif ($action === 'unpublish') {
		rv_update($all, $rv_id, ['published' => false]);
		rv_save($all);
		$message = 'Отзыв скрыт.';

	} elseif ($action === 'delete') {
		rv_delete($all, $rv_id);
		rv_save($all);
		$message = 'Отзыв удалён.';
		$rv_id   = '';

	} elseif ($action === 'delete_photo') {
		$photo_idx = (int)($_POST['photo_idx'] ?? -1);
		if ($photo_idx >= 0) {
			rv_delete_photo($all, $rv_id, $photo_idx);
			rv_save($all);
			$message = 'Фото удалено.';
		}

	} elseif ($action === 'save') {
		$fields = [
			'name'   => htmlspecialchars(trim($_POST['name']   ?? ''), ENT_QUOTES, 'UTF-8'),
			'rating' => max(1, min(5, (int)($_POST['rating'] ?? 5))),
			'text'   => htmlspecialchars(trim($_POST['text']   ?? ''), ENT_QUOTES, 'UTF-8'),
			'date'   => trim($_POST['date'] ?? ''),
		];
		if ($fields['name'] === '' || $fields['text'] === '') {
			$error = 'Имя и текст не могут быть пустыми.';
		} else {
			rv_update($all, $rv_id, $fields);
			rv_save($all);
			// Редирект закрывает панель редактирования и показывает flash
			header('Location: ?tab=' . urlencode($_GET['tab'] ?? 'pending') . '&saved=1');
			exit;
		}
	}
}

/* --- Данные для отображения --- */
if (!isset($all)) $all = rv_load();
$flat     = rv_flat($all);
$pending  = array_filter($flat, fn($r) => empty($r['published']));
$approved = array_filter($flat, fn($r) => !empty($r['published']));

/* Текущий редактируемый отзыв */
$edit_review = $rv_id !== '' ? rv_find($all, $rv_id) : null;

/* Фильтр */
$tab = $_GET['tab'] ?? 'pending'; // pending | approved | all
$view_list = match($tab) {
	'approved' => array_values($approved),
	'all'      => $flat,
	default    => array_values($pending),
};

/* --- Секция: количество ожидающих (для log.php) --- */
define('RV_PENDING_COUNT', count($pending));

/* Индекс article → name/model по всем разделам */
function rv_build_product_index(): array {
	$index = [];
	foreach (array_keys(SECTION_NAMES) as $sec) {
		$file = DATA_DIR . $sec . '.json';
		if (!file_exists($file)) continue;
		$items = json_decode(file_get_contents($file), true) ?? [];
		foreach ($items as $p) {
			$art = (string)($p['article'] ?? '');
			if ($art !== '') $index[$art] = [
				'name'  => $p['name']  ?? '',
				'model' => $p['model'] ?? '',
			];
		}
	}
	return $index;
}
$product_index = rv_build_product_index();

function rv_stars_html(int $r): string {
	$out = '';
	for ($i = 1; $i <= 5; $i++) {
		$out .= $i <= $r ? '<span style="color:#f5b301">★</span>' : '<span style="color:#ddd">★</span>';
	}
	return $out;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Отзывы — WERGRAUF</title>
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
			--success:  #2e7d32;
			--warning:  #b45309;
			--radius:   10px;
			--shadow:   0 1px 4px rgba(0,0,0,.08);
		}

		body { font-family: "Roboto", Arial, sans-serif; font-size: 14px; background: var(--bg); color: var(--text); }

		/* Topbar */
		.topbar { background: var(--accent); color: #fff; padding: 0 24px; height: 52px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
		.topbar__left { display: flex; align-items: center; gap: 16px; }
		.topbar__brand { font-weight: 700; font-size: 15px; text-decoration: none; color: #fff; }
		.topbar__sep { opacity: .4; }
		.topbar__logout { color: rgba(255,255,255,.7); text-decoration: none; font-size: 13px; }
		.topbar__logout:hover { color: #fff; }

		/* Layout */
		.page { max-width: 1200px; margin: 0 auto; padding: 24px; }
		.layout { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }

		/* Toolbar */
		.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
		.toolbar__back { color: var(--accent); text-decoration: none; font-size: 13px; }
		.toolbar__back:hover { text-decoration: underline; }
		.toolbar__title { font-size: 20px; font-weight: 600; flex: 1; }

		/* Tabs */
		.tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 4px; margin-bottom: 20px; width: fit-content; }
		.tab { padding: 7px 16px; border-radius: 7px; font-size: 13px; font-weight: 500; text-decoration: none; color: var(--muted); transition: all .15s; }
		.tab.is-active { background: var(--accent); color: #fff; }
		.tab:hover:not(.is-active) { background: var(--bg); color: var(--text); }
		.tab__badge { display: inline-flex; align-items: center; justify-content: center; background: #e53935; color: #fff; border-radius: 99px; font-size: 11px; font-weight: 700; min-width: 18px; height: 18px; padding: 0 5px; margin-left: 6px; }

		/* Сообщения */
		.flash { padding: 12px 16px; border-radius: var(--radius); font-size: 14px; margin-bottom: 16px; }
		.flash--ok  { background: #e8f5e9; color: var(--success); border: 1px solid #c8e6c9; }
		.flash--err { background: #fdecea; color: var(--danger);  border: 1px solid #ffcdd2; }

		/* Карточки отзывов */
		.rv-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; margin-bottom: 12px; box-shadow: var(--shadow); transition: border-color .15s; cursor: pointer; }
		.rv-card:hover { border-color: var(--accent); }
		.rv-card.is-selected { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(74,79,89,.15); }
		.rv-card.is-pending { border-left: 3px solid #f59e0b; }
		.rv-card.is-approved { border-left: 3px solid var(--success); }

		.rv-card__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
		.rv-card__meta { flex: 1; min-width: 0; }
		.rv-card__name { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.rv-card__sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
		.rv-card__status { flex-shrink: 0; }
		.badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
		.badge--pending  { background: #fff8e1; color: #b45309; }
		.badge--approved { background: #e8f5e9; color: var(--success); }

		.rv-card__stars { font-size: 16px; margin-bottom: 6px; }
		.rv-card__text { font-size: 13px; color: #4f4f4f; line-height: 1.5; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
		.rv-card__photos { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
		.rv-card__product { font-size: 12px; color: var(--text); margin-top: 4px; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
		.rv-card__model { color: var(--muted); margin-left: 6px; }
		.rv-card__photo { width: 48px; height: 48px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border); }

		.rv-card__actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }

		/* Кнопки */
		.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; border: none; font-family: inherit; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; transition: all .15s; }
		.btn--primary { background: var(--accent); color: #fff; }
		.btn--primary:hover { background: var(--accent-h); }
		.btn--success { background: #e8f5e9; color: var(--success); border: 1px solid #c8e6c9; }
		.btn--success:hover { background: #c8e6c9; }
		.btn--warning { background: #fff8e1; color: var(--warning); border: 1px solid #f0c030; }
		.btn--warning:hover { background: #f0c030; color: #000; }
		.btn--danger  { background: #fdecea; color: var(--danger); border: 1px solid #ffcdd2; }
		.btn--danger:hover  { background: #ffcdd2; }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }

		/* Панель редактирования */
		.edit-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); position: sticky; top: 72px; }
		.edit-panel__head { padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 15px; }
		.edit-panel__body { padding: 20px; }
		.edit-panel__empty { padding: 40px 20px; text-align: center; color: var(--muted); font-size: 13px; }

		/* Форма редактирования */
		.field { margin-bottom: 16px; }
		.field label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
		.field input, .field textarea, .field select {
			width: 100%; border: 1px solid var(--border); border-radius: 7px;
			padding: 8px 10px; font-size: 13px; font-family: inherit;
			background: var(--bg); color: var(--text); transition: border-color .15s;
		}
		.field input:focus, .field textarea:focus, .field select:focus { outline: none; border-color: var(--accent); background: #fff; }
		.field textarea { resize: vertical; min-height: 80px; }

		/* Фото в редакторе */
		.edit-photos { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
		.edit-photo { position: relative; }
		.edit-photo img { width: 64px; height: 64px; object-fit: cover; border-radius: 7px; border: 1px solid var(--border); display: block; }
		.edit-photo__rm { position: absolute; top: -6px; right: -6px; background: var(--danger); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

		/* Пустой список */
		.empty { text-align: center; padding: 48px 24px; color: var(--muted); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
		.empty strong { display: block; font-size: 15px; margin-bottom: 6px; color: var(--text); }

		/* Счётчики сверху */
		.stats-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
		.stat-chip { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 10px 16px; font-size: 13px; }
		.stat-chip strong { font-size: 20px; display: block; color: var(--text); }
		.stat-chip span { color: var(--muted); }

		@media (max-width: 900px) {
			.layout { grid-template-columns: 1fr; }
			.edit-panel { position: static; }
		}
	</style>
</head>
<body>

<div class="topbar">
	<div class="topbar__left">
		<a class="topbar__brand" href="/admin/">WERGRAUF</a>
		<span class="topbar__sep">/</span>
		<span>Отзывы</span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<div class="page">

	<div class="toolbar">
		<a class="toolbar__back" href="/admin/">← Дашборд</a>
		<div class="toolbar__title">Отзывы покупателей</div>
	</div>

	<?php if ($message): ?>
		<div class="flash flash--ok"><?= h($message) ?></div>
	<?php endif ?>
	<?php if (!empty($_GET['saved'])): ?>
		<div class="flash flash--ok">Отзыв сохранён.</div>
	<?php endif ?>
	<?php if ($error): ?>
		<div class="flash flash--err"><?= h($error) ?></div>
	<?php endif ?>

	<!-- Счётчики -->
	<div class="stats-row">
		<div class="stat-chip">
			<strong><?= count($pending) ?></strong>
			<span>Ожидают модерации</span>
		</div>
		<div class="stat-chip">
			<strong><?= count($approved) ?></strong>
			<span>Опубликованы</span>
		</div>
		<div class="stat-chip">
			<strong><?= count($flat) ?></strong>
			<span>Всего</span>
		</div>
	</div>

	<!-- Табы -->
	<div class="tabs">
		<a class="tab <?= $tab === 'pending'  ? 'is-active' : '' ?>" href="?tab=pending">
			На модерации
			<?php if (count($pending) > 0): ?>
				<span class="tab__badge"><?= count($pending) ?></span>
			<?php endif ?>
		</a>
		<a class="tab <?= $tab === 'approved' ? 'is-active' : '' ?>" href="?tab=approved">Опубликованные</a>
		<a class="tab <?= $tab === 'all'      ? 'is-active' : '' ?>" href="?tab=all">Все</a>
	</div>

	<div class="layout">

		<!-- Список -->
		<div class="rv-list">
			<?php if (empty($view_list)): ?>
				<div class="empty">
					<strong>Отзывов нет</strong>
					<?= $tab === 'pending' ? 'Все отзывы проверены' : 'Нет отзывов в этой категории' ?>
				</div>
			<?php else: ?>
				<?php foreach ($view_list as $r):
					$is_sel = $edit_review && $edit_review['id'] === $r['id'];
				?>
				<div class="rv-card <?= empty($r['published']) ? 'is-pending' : 'is-approved' ?> <?= $is_sel ? 'is-selected' : '' ?>"
				     onclick="window.location='?tab=<?= h($tab) ?>&id=<?= h($r['id']) ?>#edit-panel'">

					<div class="rv-card__head">
						<div class="rv-card__meta">
							<div class="rv-card__name"><?= h($r['name']) ?></div>
							<div class="rv-card__sub">
								Арт. <?= h($r['_article']) ?> · <?= h($r['date']) ?>
							</div>
							<?php if (!empty($product_index[$r['_article']])): $pi = $product_index[$r['_article']]; ?>
							<div class="rv-card__product">
								<?= h($pi['name']) ?>
								<?php if (!empty($pi['model'])): ?>
									<span class="rv-card__model"><?= h($pi['model']) ?></span>
								<?php endif ?>
							</div>
							<?php endif ?>
						</div>
						<div class="rv-card__status">
							<?php if (empty($r['published'])): ?>
								<span class="badge badge--pending">Ожидает</span>
							<?php else: ?>
								<span class="badge badge--approved">Опубликован</span>
							<?php endif ?>
						</div>
					</div>

					<div class="rv-card__stars"><?= rv_stars_html((int)$r['rating']) ?></div>
					<div class="rv-card__text"><?= h($r['text']) ?></div>

					<?php if (!empty($r['photos'])): ?>
					<div class="rv-card__photos">
						<?php foreach ($r['photos'] as $ph): ?>
							<img class="rv-card__photo" src="<?= h($ph) ?>" alt="" loading="lazy">
						<?php endforeach ?>
					</div>
					<?php endif ?>

					<!-- Быстрые действия -->
					<div class="rv-card__actions" onclick="event.stopPropagation()">
						<?php if (empty($r['published'])): ?>
							<form method="post">
								<input type="hidden" name="id" value="<?= h($r['id']) ?>">
								<input type="hidden" name="action" value="publish">
								<button class="btn btn--success" type="submit">✓ Опубликовать</button>
							</form>
						<?php else: ?>
							<form method="post">
								<input type="hidden" name="id" value="<?= h($r['id']) ?>">
								<input type="hidden" name="action" value="unpublish">
								<button class="btn btn--warning" type="submit">⊘ Скрыть</button>
							</form>
						<?php endif ?>
						<form method="post" onsubmit="return confirm('Удалить отзыв безвозвратно?')">
							<input type="hidden" name="id" value="<?= h($r['id']) ?>">
							<input type="hidden" name="action" value="delete">
							<button class="btn btn--danger" type="submit">✕ Удалить</button>
						</form>
						<a class="btn btn--outline" href="?tab=<?= h($tab) ?>&id=<?= h($r['id']) ?>#edit-panel">✎ Редактировать</a>
					</div>
				</div>
				<?php endforeach ?>
			<?php endif ?>
		</div>

		<!-- Панель редактирования -->
		<aside class="edit-panel" id="edit-panel">
			<div class="edit-panel__head">Редактирование</div>
			<?php if ($edit_review): ?>
			<div class="edit-panel__body">
				<form method="post">
					<input type="hidden" name="id" value="<?= h($edit_review['id']) ?>">
					<input type="hidden" name="action" value="save">

					<div class="field">
						<label>Имя</label>
						<input type="text" name="name" value="<?= h($edit_review['name']) ?>" maxlength="60" required>
					</div>

					<div class="field">
						<label>Оценка</label>
						<select name="rating">
							<?php for ($i = 5; $i >= 1; $i--): ?>
								<option value="<?= $i ?>" <?= (int)$edit_review['rating'] === $i ? 'selected' : '' ?>>
									<?= $i ?> ★
								</option>
							<?php endfor ?>
						</select>
					</div>

					<div class="field">
						<label>Дата</label>
						<input type="text" name="date" value="<?= h($edit_review['date']) ?>" placeholder="дд.мм.гггг">
					</div>

					<div class="field">
						<label>Текст отзыва</label>
						<textarea name="text" rows="5" required><?= h($edit_review['text']) ?></textarea>
					</div>

					<!-- Фотографии -->
					<?php if (!empty($edit_review['photos'])): ?>
					<div class="field">
						<label>Фотографии</label>
						<div class="edit-photos">
							<?php foreach ($edit_review['photos'] as $pi => $ph): ?>
							<div class="edit-photo">
								<img src="<?= h($ph) ?>" alt="">
								<button class="edit-photo__rm" type="button"
									onclick="rvDeletePhoto('<?= h($edit_review['id']) ?>', <?= $pi ?>)">✕</button>
							</div>
							<?php endforeach ?>
						</div>
					</div>
					<?php endif ?>

					<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
						<button class="btn btn--primary" type="submit">Сохранить</button>
					</div>
				</form>
			</div>
			<?php else: ?>
			<div class="edit-panel__empty">Выберите отзыв из списка для редактирования</div>
			<?php endif ?>
		</aside>

	</div>
</div>

<script>
function rvDeletePhoto(id, idx) {
	if (!confirm('Удалить фото?')) return;
	const f = document.createElement('form');
	f.method = 'post';
	f.innerHTML =
		'<input name="id" value="' + id + '">' +
		'<input name="action" value="delete_photo">' +
		'<input name="photo_idx" value="' + idx + '">';
	document.body.appendChild(f);
	f.submit();
}
</script>
</body>
</html>
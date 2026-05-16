<?php
/* admin/log.php — просмотр лога синхронизаций */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

$log = read_sync_log();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Лог синхронизаций — WERGRAUF</title>
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
			--radius:   10px;
			--shadow:   0 1px 4px rgba(0,0,0,.08);
		}

		body { font-family: "Roboto", Arial, sans-serif; font-size: 14px; background: var(--bg); color: var(--text); }

		.topbar { background: var(--accent); color: #fff; padding: 0 24px; height: 52px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
		.topbar__left { display: flex; align-items: center; gap: 16px; }
		.topbar__brand { font-weight: 700; font-size: 15px; text-decoration: none; color: #fff; }
		.topbar__sep { opacity: .4; }
		.topbar__logout { color: rgba(255,255,255,.7); text-decoration: none; font-size: 13px; }
		.topbar__logout:hover { color: #fff; }

		.page { max-width: 1000px; margin: 0 auto; padding: 24px; }

		.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
		.toolbar__back { color: var(--accent); text-decoration: none; font-size: 13px; }
		.toolbar__back:hover { text-decoration: underline; }
		.toolbar__title { font-size: 20px; font-weight: 600; flex: 1; }
		.toolbar__sub { font-size: 13px; color: var(--muted); }

		/* Запись лога */
		.log-entry { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 16px; box-shadow: var(--shadow); overflow: hidden; }

		.log-entry__head { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--bg); border-bottom: 1px solid var(--border); cursor: pointer; user-select: none; }
		.log-entry__head:hover { background: #eceef2; }

		.log-entry__date { font-weight: 600; font-size: 14px; }
		.log-entry__summary { font-size: 13px; color: var(--muted); flex: 1; }
		.log-entry__toggle { font-size: 12px; color: var(--accent); }

		.log-entry__body { display: none; padding: 0; }
		.log-entry__body.is-open { display: block; }

		/* Таблица результатов */
		table { width: 100%; border-collapse: collapse; }
		td { padding: 9px 16px; font-size: 13px; border-bottom: 1px solid var(--border); }
		tr:last-child td { border-bottom: none; }

		.td-icon { width: 28px; text-align: center; }
		.td-name { font-weight: 500; }
		.td-count { color: var(--muted); }
		.td-error { color: var(--danger); }

		/* Пусто */
		.empty { text-align: center; padding: 64px 24px; color: var(--muted); }
		.empty strong { font-size: 16px; display: block; margin-bottom: 8px; }

		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; border: none; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .2s; }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }
	</style>
</head>
<body>

<div class="topbar">
	<div class="topbar__left">
		<a class="topbar__brand" href="/admin/">WERGRAUF</a>
		<span class="topbar__sep">/</span>
		<span>Лог синхронизаций</span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<div class="page">

	<div class="toolbar">
		<a class="toolbar__back" href="/admin/">← Дашборд</a>
		<div class="toolbar__title">Лог синхронизаций</div>
		<div class="toolbar__sub">Хранится 30 дней · <?= count($log) ?> записей</div>
	</div>

	<?php if (empty($log)): ?>
		<div class="empty">
			<strong>Лог пуст</strong>
			Записи появятся после первой синхронизации с Google Sheets
		</div>
	<?php else: ?>
		<?php foreach ($log as $i => $entry):
			// Считаем сводку по записи
			$total_ok  = array_sum(array_column(array_filter($entry['results'], fn($r) => $r['success']), 'count'));
			$has_error = count(array_filter($entry['results'], fn($r) => !$r['success'])) > 0;
		?>
			<div class="log-entry">
				<div class="log-entry__head" onclick="toggleEntry(<?= $i ?>)">
					<span><?= $has_error ? '❌' : '✅' ?></span>
					<span class="log-entry__date"><?= h($entry['date']) ?></span>
					<span class="log-entry__summary">
						<?php if ($has_error): ?>
							Есть ошибки
						<?php else: ?>
							Загружено товаров: <?= $total_ok ?>
						<?php endif ?>
					</span>
					<span class="log-entry__toggle" id="toggle-<?= $i ?>">▼ Подробнее</span>
				</div>

				<div class="log-entry__body" id="body-<?= $i ?>">
					<table>
						<?php foreach ($entry['results'] as $r): ?>
							<tr>
								<td class="td-icon"><?= $r['success'] ? '✅' : '❌' ?></td>
								<td class="td-name"><?= h($r['name']) ?></td>
								<?php if ($r['success']): ?>
									<td class="td-count">загружено: <strong><?= $r['count'] ?></strong>, пропущено: <?= $r['skipped'] ?></td>
									<td></td>
								<?php else: ?>
									<td></td>
									<td class="td-error"><?= h($r['error'] ?? 'Ошибка') ?></td>
								<?php endif ?>
							</tr>
						<?php endforeach ?>
					</table>
				</div>
			</div>
		<?php endforeach ?>
	<?php endif ?>

</div>

<script>
function toggleEntry(i) {
	const body   = document.getElementById('body-' + i);
	const toggle = document.getElementById('toggle-' + i);
	const open   = body.classList.toggle('is-open');
	toggle.textContent = open ? '▲ Свернуть' : '▼ Подробнее';
}
</script>

</body>
</html>

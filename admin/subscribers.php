<?php
/* admin/subscribers.php — просмотр подписчиков на рассылку */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

admin_check_auth();

// Читаем подписчиков из JSON
$file = DATA_DIR . 'subscribers.json';
$subscribers = [];
if (file_exists($file)) {
	$subscribers = json_decode(file_get_contents($file), true) ?? [];
}

// Новые сверху
$subscribers = array_reverse($subscribers);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Подписчики — WERGRAUF</title>
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

		.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }

		table { width: 100%; border-collapse: collapse; }
		th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; background: var(--bg); border-bottom: 1px solid var(--border); }
		td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid var(--border); }
		tr:last-child td { border-bottom: none; }
		.td-num { width: 48px; color: var(--muted); }
		.td-email { font-weight: 500; }
		.td-email a { color: var(--accent-h); text-decoration: none; }
		.td-email a:hover { text-decoration: underline; }
		.td-date, .td-ip { color: var(--muted); white-space: nowrap; }

		.empty { text-align: center; padding: 64px 24px; color: var(--muted); }
		.empty strong { font-size: 16px; display: block; margin-bottom: 8px; }
	</style>
</head>
<body>

<div class="topbar">
	<div class="topbar__left">
		<a class="topbar__brand" href="/admin/">WERGRAUF</a>
		<span class="topbar__sep">/</span>
		<span>Подписчики</span>
	</div>
	<a class="topbar__logout" href="/admin/?logout">Выйти</a>
</div>

<div class="page">

	<div class="toolbar">
		<a class="toolbar__back" href="/admin/">← Дашборд</a>
		<div class="toolbar__title">Подписчики</div>
		<div class="toolbar__sub">Всего: <?= count($subscribers) ?></div>
	</div>

	<?php if (empty($subscribers)): ?>
		<div class="empty">
			<strong>Подписчиков пока нет</strong>
			Записи появятся, когда кто-нибудь подпишется через форму в подвале сайта
		</div>
	<?php else: ?>
		<div class="card">
			<table>
				<thead>
					<tr>
						<th class="td-num">#</th>
						<th>Email</th>
						<th>Дата</th>
						<th>IP</th>
					</tr>
				</thead>
				<tbody>
					<?php $n = count($subscribers); foreach ($subscribers as $s): ?>
						<tr>
							<td class="td-num"><?= $n-- ?></td>
							<td class="td-email"><a href="mailto:<?= h($s['email'] ?? '') ?>"><?= h($s['email'] ?? '') ?></a></td>
							<td class="td-date"><?= h($s['date'] ?? '') ?></td>
							<td class="td-ip"><?= h($s['ip'] ?? '') ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>

</div>

</body>
</html>
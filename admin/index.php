<?php
/* admin/index.php — авторизация и дашборд */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/sync.php';

session_start();

// --- Выход ---
if (isset($_GET['logout'])) {
	$_SESSION = [];
	session_destroy();
	header('Location: /admin/');
	exit;
}

// --- Авторизация ---
$auth_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
	if (check_credentials($_POST['login'], $_POST['password'])) {
		$_SESSION['admin_logged_in'] = true;
		header('Location: /admin/');
		exit;
	}
	$auth_error = 'Неверный логин или пароль';
}

$is_logged_in = !empty($_SESSION['admin_logged_in']);

// --- Смена пароля ---
$pw_success = false;
$pw_error   = '';
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
	$creds    = load_credentials();
	$cur      = $_POST['current_password'] ?? '';
	$new      = $_POST['new_password']     ?? '';
	$confirm  = $_POST['confirm_password'] ?? '';

	if ($creds['password'] !== $cur) {
		$pw_error = 'Текущий пароль неверный';
	} elseif (strlen($new) < 6) {
		$pw_error = 'Новый пароль должен быть не короче 6 символов';
	} elseif ($new !== $confirm) {
		$pw_error = 'Пароли не совпадают';
	} else {
		if (save_credentials($creds['login'], $new)) {
			$pw_success = true;
		} else {
			$pw_error = 'Не удалось сохранить пароль. Проверьте права на запись в /data/';
		}
	}
}

// --- Синхронизация ---
$sync_results = null;
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['sync_all'])) {
		$sync_results = sync_all();
	} elseif (isset($_POST['sync_section'], $_POST['section'])) {
		$section    = $_POST['section'];
		$sheet_name = array_search($section, SHEET_MAP);
		if ($sheet_name !== false) {
			$r = sync_sheet($sheet_name, $section);
			write_sync_log([$section => $r]);
			$sync_results = [$section => $r];
		}
	}
}

// --- Статистика ---
$stats = [];
if ($is_logged_in) {
	foreach (SHEET_MAP as $section) {
		$stats[$section] = section_stats($section);
	}
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Админка — WERGRAUF</title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

		:root {
			--bg:        #f4f5f7;
			--surface:   #ffffff;
			--border:    #e2e4e9;
			--text:      #36393e;
			--muted:     #8a8f9a;
			--accent:    #4a4f59;
			--accent-h:  #385081;
			--danger:    #c82a20;
			--success:   #2e7d32;
			--warning-bg:#fff8e1;
			--warning-bd:#f0c030;
			--radius:    10px;
			--shadow:    0 1px 4px rgba(0,0,0,.08);
		}

		body { font-family: "Roboto", Arial, sans-serif; font-size: 14px; background: var(--bg); color: var(--text); min-height: 100vh; }

		/* --- Логин --- */
		.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
		.login-box { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px; width: 100%; max-width: 360px; box-shadow: var(--shadow); }
		.login-logo { font-size: 18px; font-weight: 700; letter-spacing: .04em; color: var(--accent); margin-bottom: 24px; text-align: center; }
		.login-logo span { color: var(--muted); font-weight: 400; font-size: 12px; display: block; margin-top: 2px; }

		/* --- Топбар --- */
		.topbar { background: var(--accent); color: #fff; padding: 0 24px; height: 52px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
		.topbar__brand { font-weight: 700; font-size: 15px; letter-spacing: .04em; }
		.topbar__brand span { font-weight: 400; opacity: .6; font-size: 12px; margin-left: 8px; }
		.topbar__right { display: flex; align-items: center; gap: 16px; }
		.topbar__link { color: rgba(255,255,255,.7); text-decoration: none; font-size: 13px; }
		.topbar__link:hover { color: #fff; }

		/* --- Навигация --- */
		.admin-nav { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 24px; display: flex; gap: 4px; position: sticky; top: 52px; z-index: 99; }
		.admin-nav__item { display: inline-flex; align-items: center; gap: 6px; padding: 10px 14px; font-size: 13px; font-weight: 500; color: var(--muted); text-decoration: none; border-bottom: 2px solid transparent; transition: color .2s, border-color .2s; white-space: nowrap; }
		.admin-nav__item:hover { color: var(--text); }
		.admin-nav__item--active { color: var(--accent-h); border-bottom-color: var(--accent-h); }

		/* --- Контент --- */
		.page { max-width: 1100px; margin: 0 auto; padding: 28px 24px; }
		.page-title { font-size: 20px; font-weight: 600; margin-bottom: 24px; }

		/* --- Грид разделов --- */
		.sections-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px; }

		.section-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
		.section-card__head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
		.section-card__name { font-weight: 600; font-size: 15px; line-height: 1.3; }
		.section-card__sync-time { font-size: 11px; color: var(--muted); text-align: right; flex-shrink: 0; margin-left: 8px; }
		.section-card__counts { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
		.count-chip { font-size: 12px; padding: 3px 8px; border-radius: 6px; background: var(--bg); color: var(--text); }
		.count-chip--overrides { background: #fff3e0; color: #e65100; }
		.count-chip--hidden { background: #fce4ec; color: #c62828; }
		.section-card__actions { display: flex; gap: 8px; flex-wrap: wrap; }

		/* --- Кнопки --- */
		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; border: none; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .2s, color .2s; white-space: nowrap; }
		.btn--primary { background: var(--accent); color: #fff; }
		.btn--primary:hover { background: var(--accent-h); }
		.btn--outline { background: transparent; color: var(--accent); border: 1px solid var(--border); }
		.btn--outline:hover { background: var(--bg); }
		.btn--danger { background: var(--danger); color: #fff; }
		.btn--danger:hover { background: #a32219; }
		.btn--sm { padding: 5px 10px; font-size: 12px; }
		.btn--full { width: 100%; justify-content: center; }

		/* --- Sync bar --- */
		.sync-all-bar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; box-shadow: var(--shadow); }
		.sync-all-bar__text strong { font-size: 15px; }
		.sync-all-bar__text p { color: var(--muted); font-size: 13px; margin-top: 3px; }

		/* --- Смена пароля --- */
		.pw-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); margin-bottom: 32px; max-width: 420px; }
		.pw-card__title { font-size: 15px; font-weight: 600; margin-bottom: 16px; }

		/* --- Notices --- */
		.notice { border-radius: var(--radius); padding: 12px 16px; font-size: 13px; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; }
		.notice--warning { background: var(--warning-bg); border: 1px solid var(--warning-bd); color: #5a4000; }
		.notice--success { background: #e8f5e9; border: 1px solid #a5d6a7; color: var(--success); }
		.notice--error { background: #fde0df; border: 1px solid #ef9a9a; color: var(--danger); }
		.notice__icon { flex-shrink: 0; font-size: 16px; line-height: 1; margin-top: 1px; }

		/* --- Результаты синхронизации --- */
		.sync-results { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 28px; }
		.sync-results__title { padding: 12px 18px; background: var(--bg); border-bottom: 1px solid var(--border); font-weight: 600; font-size: 13px; }
		.sync-results__list { padding: 8px 0; }
		.sync-result-row { display: flex; align-items: center; gap: 10px; padding: 7px 18px; font-size: 13px; }
		.sync-result-row:not(:last-child) { border-bottom: 1px solid var(--border); }
		.sync-result-row__icon { font-size: 15px; flex-shrink: 0; }
		.sync-result-row__name { flex: 1; font-weight: 500; }
		.sync-result-row__meta { color: var(--muted); font-size: 12px; }
		.sync-result-row__error { color: var(--danger); font-size: 12px; }

		/* --- Формы --- */
		.form-group { margin-bottom: 14px; }
		.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
		.form-input { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; color: var(--text); background: var(--surface); transition: border-color .2s; }
		.form-input:focus { outline: none; border-color: var(--accent); }
		.form-error { background: #fde0df; border: 1px solid #ef9a9a; color: var(--danger); padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
	</style>
</head>
<body>

<?php if (!$is_logged_in): ?>

<!-- ФОРМА ВХОДА -->
<div class="login-wrap">
	<div class="login-box">
		<div class="login-logo">
			WERGRAUF
			<span>Панель управления</span>
		</div>

		<?php if ($auth_error): ?>
			<div class="form-error"><?= h($auth_error) ?></div>
		<?php endif ?>

		<form method="POST">
			<div class="form-group">
				<label class="form-label" for="login">Логин</label>
				<input class="form-input" type="text" id="login" name="login" autocomplete="username" autofocus required>
			</div>
			<div class="form-group">
				<label class="form-label" for="password">Пароль</label>
				<input class="form-input" type="password" id="password" name="password" autocomplete="current-password" required>
			</div>
			<button class="btn btn--primary btn--full" type="submit">Войти</button>
		</form>
	</div>
</div>

<?php else: ?>

<!-- ДАШБОРД -->
<div class="topbar">
	<div class="topbar__brand">
		WERGRAUF <span>Панель управления</span>
	</div>
	<div class="topbar__right">
		<a class="topbar__link" href="?logout">Выйти</a>
	</div>
</div>

<!-- Навигация -->
<nav class="admin-nav">
	<a class="admin-nav__item admin-nav__item--active" href="/admin/">🗂 Дашборд</a>
	<a class="admin-nav__item" href="/admin/home_edit.php">🏠 Главная страница</a>
	<a class="admin-nav__item" href="/admin/log.php">📋 Лог синхронизаций</a>
</nav>

<div class="page">
	<div class="page-title">Дашборд</div>

	<div class="notice notice--warning">
		<span class="notice__icon">⚠️</span>
		<div>
			<strong>Локальные правки имеют приоритет.</strong>
			При синхронизации данные из Google Sheets не перезапишут поля, отредактированные вручную в админке.
			Чтобы вернуть данные из таблицы — сбросьте правки на странице товара.
		</div>
	</div>

	<!-- Результаты синхронизации -->
	<?php if ($sync_results !== null): ?>
		<div class="sync-results">
			<div class="sync-results__title">
				Результат синхронизации — <?= date('d.m.Y H:i:s') ?>
			</div>
			<div class="sync-results__list">
				<?php foreach ($sync_results as $section => $result): ?>
					<div class="sync-result-row">
						<span class="sync-result-row__icon"><?= $result['success'] ? '✅' : '❌' ?></span>
						<span class="sync-result-row__name"><?= h(SECTION_NAMES[$section] ?? $section) ?></span>
						<?php if ($result['success']): ?>
							<span class="sync-result-row__meta">загружено: <?= $result['count'] ?>, пропущено: <?= $result['skipped'] ?></span>
						<?php else: ?>
							<span class="sync-result-row__error"><?= h($result['error']) ?></span>
						<?php endif ?>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>

	<!-- Синхронизация всех -->
	<div class="sync-all-bar">
		<div class="sync-all-bar__text">
			<strong>Синхронизация с Google Sheets</strong>
			<p>Загрузит все разделы из таблицы. Товары без slug или с ценой 0 — пропускаются.</p>
		</div>
		<form method="POST">
			<button class="btn btn--primary" type="submit" name="sync_all" value="1">
				🔄 Синхронизировать все
			</button>
		</form>
	</div>

	<!-- Разделы -->
	<div class="page-title">Разделы каталога</div>
	<div class="sections-grid">
		<?php foreach (SECTION_NAMES as $section => $name):
			$s      = $stats[$section];
			$synced = $s['synced_at'] ? time_ago($s['synced_at']) : 'нет данных';
		?>
		<div class="section-card">
			<div class="section-card__head">
				<div class="section-card__name"><?= h($name) ?></div>
				<div class="section-card__sync-time">Синхр.:<br><?= h($synced) ?></div>
			</div>

			<div class="section-card__counts">
				<span class="count-chip">Товаров: <?= $s['total'] ?></span>
				<span class="count-chip">В наличии: <?= $s['in_stock'] ?></span>
				<?php if ($s['hidden'] > 0): ?>
					<span class="count-chip count-chip--hidden">Скрыто: <?= $s['hidden'] ?></span>
				<?php endif ?>
				<?php if ($s['overrides'] > 0): ?>
					<span class="count-chip count-chip--overrides">Правок: <?= $s['overrides'] ?></span>
				<?php endif ?>
			</div>

			<div class="section-card__actions">
				<?php if ($s['total'] > 0): ?>
					<a class="btn btn--outline btn--sm" href="/admin/catalog.php?section=<?= h($section) ?>">📋 Товары</a>
					<a class="btn btn--outline btn--sm" href="/admin/product_new.php?section=<?= h($section) ?>">＋ Добавить</a>
				<?php else: ?>
					<a class="btn btn--outline btn--sm" href="/admin/product_new.php?section=<?= h($section) ?>">＋ Добавить</a>
				<?php endif ?>

				<form method="POST" style="margin:0">
					<input type="hidden" name="section" value="<?= h($section) ?>">
					<button class="btn btn--outline btn--sm" type="submit" name="sync_section" value="1">🔄 Синхр.</button>
				</form>

				<?php if ($s['total'] > 0): ?>
					<a class="btn btn--outline btn--sm" href="<?= h(SECTION_URLS[$section]) ?>" target="_blank">🔗</a>
				<?php endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>

	<!-- Смена пароля -->
	<div class="page-title">Безопасность</div>
	<div class="pw-card">
		<div class="pw-card__title">Сменить пароль</div>

		<?php if ($pw_success): ?>
			<div class="notice notice--success"><span class="notice__icon">✅</span> Пароль успешно изменён</div>
		<?php elseif ($pw_error): ?>
			<div class="notice notice--error"><span class="notice__icon">❌</span> <?= h($pw_error) ?></div>
		<?php endif ?>

		<form method="POST">
			<div class="form-group">
				<label class="form-label">Текущий пароль</label>
				<input class="form-input" type="password" name="current_password" required>
			</div>
			<div class="form-group">
				<label class="form-label">Новый пароль</label>
				<input class="form-input" type="password" name="new_password" minlength="6" required>
			</div>
			<div class="form-group">
				<label class="form-label">Подтвердите новый пароль</label>
				<input class="form-input" type="password" name="confirm_password" required>
			</div>
			<button class="btn btn--primary" type="submit" name="change_password" value="1">Сохранить пароль</button>
		</form>
	</div>
</div>

<?php endif ?>

</body>
</html>
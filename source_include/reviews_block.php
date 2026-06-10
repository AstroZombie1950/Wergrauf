<?php
/* source_include/reviews_block.php
   Ожидает: $product['article'] (строка)
   Подключается в конце product.php и product_template.php вместо статики */

require_once $_SERVER['DOCUMENT_ROOT'] . '/reviews/helpers.php';

$rv_article  = (string)($product['article'] ?? '');
$rv_list     = reviews_get_published($rv_article);
$rv_count    = count($rv_list);
$rv_avg      = reviews_avg($rv_list);
?>

<style>
/* --- Блок отзывов --- */
.rv { max-width: 1180px; margin: 0 auto 40px; padding: 0 20px; font-family: "Roboto", Arial, sans-serif; }
.rv__head { display: flex; align-items: center; gap: 20px; margin-bottom: 28px; border-bottom: 1px solid #e6e6e6; padding-bottom: 16px; flex-wrap: wrap; }
.rv__title { font-size: 22px; font-weight: 600; }
.rv__summary { display: flex; align-items: center; gap: 10px; }
.rv__avg { font-size: 28px; font-weight: 700; color: #36393e; line-height: 1; }
.rv-stars { display: inline-flex; gap: 2px; }
.rv-star { font-size: 18px; color: #ddd; }
.rv-star--on { color: #f5b301; }
.rv__count { font-size: 14px; color: #8a8f9a; }
.rv__write-btn { margin-left: auto; background: #36393e; color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 500; cursor: pointer; font-family: inherit; transition: background .2s; white-space: nowrap; }
.rv__write-btn:hover { background: #1f2226; }

/* Список отзывов */
.rv__list { margin-bottom: 32px; }
.rv-item { padding: 20px 0; border-bottom: 1px solid #e6e6e6; }
.rv-item:last-child { border-bottom: none; }
.rv-item__head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; gap: 12px; flex-wrap: wrap; }
.rv-item__name { font-size: 14px; font-weight: 600; }
.rv-item__date { font-size: 12px; color: #9e9e9e; }
.rv-item__stars { margin-bottom: 10px; }
.rv-item__text { font-size: 14px; line-height: 1.6; color: #4f4f4f; white-space: pre-line; }
.rv-item__photos { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.rv-item__photo { width: 80px; height: 80px; border-radius: 8px; overflow: hidden; cursor: pointer; border: 1px solid #e0e0e0; }
.rv-item__photo img { width: 100%; height: 100%; object-fit: cover; }

/* Пусто */
.rv__empty { padding: 32px 0; color: #6a6f7a; font-size: 14px; }

/* Лайтбокс */
.rv-lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 9999; align-items: center; justify-content: center; }
.rv-lightbox.is-open { display: flex; }
.rv-lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 8px; object-fit: contain; }
.rv-lightbox__close { position: absolute; top: 20px; right: 24px; color: #fff; font-size: 28px; cursor: pointer; line-height: 1; background: none; border: none; }

/* Форма */
.rv-form-wrap { background: #f7f7f7; border-radius: 16px; padding: 28px; }
.rv-form-wrap.is-hidden { display: none; }
.rv-form__title { font-size: 18px; font-weight: 600; margin-bottom: 20px; }
.rv-form__row { margin-bottom: 16px; }
.rv-form__label { display: block; font-size: 13px; font-weight: 500; color: #6b6b6b; margin-bottom: 6px; }
.rv-form__input, .rv-form__textarea { width: 100%; border: 1px solid #dcdcdc; border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; background: #fff; color: #36393e; transition: border-color .15s; }
.rv-form__input:focus, .rv-form__textarea:focus { outline: none; border-color: #36393e; }
.rv-form__textarea { resize: vertical; min-height: 100px; }

/* Звёзды-выбор */
.rv-rating-pick { display: flex; gap: 6px; flex-direction: row-reverse; justify-content: flex-end; }
.rv-rating-pick input { display: none; }
.rv-rating-pick label { font-size: 28px; color: #ddd; cursor: pointer; transition: color .1s; line-height: 1; }
.rv-rating-pick input:checked ~ label,
.rv-rating-pick label:hover,
.rv-rating-pick label:hover ~ label { color: #f5b301; }

/* Фото */
.rv-photo-drop { border: 2px dashed #dcdcdc; border-radius: 8px; padding: 20px; text-align: center; font-size: 13px; color: #8a8f9a; cursor: pointer; transition: border-color .15s; }
.rv-photo-drop:hover { border-color: #36393e; }
.rv-photo-previews { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.rv-photo-preview { position: relative; width: 72px; height: 72px; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; }
.rv-photo-preview img { width: 100%; height: 100%; object-fit: cover; }
.rv-photo-preview__rm { position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,.55); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 12px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; }

/* Капча */
.rv-captcha { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.rv-captcha__q { font-size: 14px; font-weight: 500; color: #36393e; }
.rv-captcha__input { width: 80px; }
.rv-captcha__reload { background: none; border: none; color: #8a8f9a; font-size: 18px; cursor: pointer; line-height: 1; padding: 0 4px; }
.rv-captcha__reload:hover { color: #36393e; }

/* Кнопка отправки */
.rv-form__submit { background: #36393e; color: #fff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: background .2s; }
.rv-form__submit:hover { background: #1f2226; }
.rv-form__submit:disabled { background: #b0b3b8; cursor: default; }

/* Honeypot — скрыт для людей */
.rv-hp { position: absolute; left: -9999px; }

/* Сообщения */
.rv-msg { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; display: none; }
.rv-msg--ok  { background: #e8f5e9; color: #2e7d32; }
.rv-msg--err { background: #fdecea; color: #c82a20; }
</style>

<section class="rv container" id="rv-section">

	<div class="rv__head">
		<h2 class="rv__title">Отзывы</h2>
		<?php if ($rv_count > 0): ?>
		<div class="rv__summary">
			<span class="rv__avg"><?= number_format($rv_avg, 1) ?></span>
			<?= reviews_stars((int)round($rv_avg)) ?>
			<span class="rv__count"><?= $rv_count ?> <?= rv_plural($rv_count, 'отзыв', 'отзыва', 'отзывов') ?></span>
		</div>
		<?php endif ?>
		<button class="rv__write-btn" type="button" id="rv-open-form">Написать отзыв</button>
	</div>

	<!-- Список опубликованных отзывов -->
	<?php if ($rv_count > 0): ?>
	<div class="rv__list">
		<?php foreach ($rv_list as $rv): ?>
		<div class="rv-item">
			<div class="rv-item__head">
				<span class="rv-item__name"><?= htmlspecialchars($rv['name'], ENT_QUOTES, 'UTF-8') ?></span>
				<span class="rv-item__date"><?= htmlspecialchars($rv['date'], ENT_QUOTES, 'UTF-8') ?></span>
			</div>
			<div class="rv-item__stars"><?= reviews_stars((int)$rv['rating']) ?></div>
			<div class="rv-item__text"><?= htmlspecialchars($rv['text'], ENT_QUOTES, 'UTF-8') ?></div>
			<?php if (!empty($rv['photos'])): ?>
			<div class="rv-item__photos">
				<?php foreach ($rv['photos'] as $photo): ?>
				<div class="rv-item__photo" onclick="rvLightbox(<?= htmlspecialchars(json_encode($photo), ENT_QUOTES, 'UTF-8') ?>)">
					<img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
				</div>
				<?php endforeach ?>
			</div>
			<?php endif ?>
		</div>
		<?php endforeach ?>
	</div>
	<?php else: ?>
	<div class="rv__empty">Пока нет отзывов. Будьте первым!</div>
	<?php endif ?>

	<!-- Форма добавления отзыва -->
	<div class="rv-form-wrap is-hidden" id="rv-form-wrap">
		<div class="rv-msg rv-msg--ok" id="rv-msg-ok">Спасибо! Ваш отзыв отправлен на проверку и появится после модерации.</div>
		<div class="rv-msg rv-msg--err" id="rv-msg-err"></div>

		<h3 class="rv-form__title">Ваш отзыв</h3>
		<form id="rv-form" enctype="multipart/form-data" novalidate>
			<input type="hidden" name="article" value="<?= htmlspecialchars($rv_article, ENT_QUOTES, 'UTF-8') ?>">
			<input type="hidden" name="_ft" id="rv-ft" value="">

			<!-- Honeypot -->
			<div class="rv-hp" aria-hidden="true">
				<input type="text" name="website" tabindex="-1" autocomplete="off">
			</div>

			<!-- Имя -->
			<div class="rv-form__row">
				<label class="rv-form__label" for="rv-name">Ваше имя *</label>
				<input class="rv-form__input" type="text" id="rv-name" name="name" maxlength="60" required>
			</div>

			<!-- Оценка -->
			<div class="rv-form__row">
				<label class="rv-form__label">Оценка *</label>
				<div class="rv-rating-pick">
					<input type="radio" name="rating" id="rv-r5" value="5"><label for="rv-r5">★</label>
					<input type="radio" name="rating" id="rv-r4" value="4"><label for="rv-r4">★</label>
					<input type="radio" name="rating" id="rv-r3" value="3"><label for="rv-r3">★</label>
					<input type="radio" name="rating" id="rv-r2" value="2"><label for="rv-r2">★</label>
					<input type="radio" name="rating" id="rv-r1" value="1"><label for="rv-r1">★</label>
				</div>
			</div>

			<!-- Текст -->
			<div class="rv-form__row">
				<label class="rv-form__label" for="rv-text">Текст отзыва * (от 10 символов)</label>
				<textarea class="rv-form__textarea" id="rv-text" name="text" maxlength="2000" required></textarea>
			</div>

			<!-- Фото -->
			<div class="rv-form__row">
				<label class="rv-form__label">Фотографии (до 4 штук, JPG/PNG/WebP, до 5 МБ каждая)</label>
				<div class="rv-photo-drop" id="rv-drop" onclick="document.getElementById('rv-photos').click()">
					Нажмите или перетащите фото сюда
				</div>
				<input type="file" id="rv-photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple style="display:none">
				<div class="rv-photo-previews" id="rv-previews"></div>
			</div>

			<!-- Капча -->
			<div class="rv-form__row">
				<label class="rv-form__label">Проверка *</label>
				<div class="rv-captcha">
					<span class="rv-captcha__q" id="rv-captcha-q">Загрузка…</span>
					<input class="rv-form__input rv-captcha__input" type="text" name="captcha" id="rv-captcha" inputmode="numeric" autocomplete="off" required>
					<button type="button" class="rv-captcha__reload" id="rv-captcha-reload" title="Обновить вопрос">↻</button>
				</div>
			</div>

			<button class="rv-form__submit" type="submit" id="rv-submit">Отправить отзыв</button>
		</form>
	</div>
</section>

<!-- Лайтбокс -->
<div class="rv-lightbox" id="rv-lightbox" onclick="rvLightboxClose()">
	<button class="rv-lightbox__close" type="button">✕</button>
	<img src="" alt="" id="rv-lightbox-img">
</div>

<script>
(function() {
	/* --- Открытие формы --- */
	document.getElementById('rv-open-form').addEventListener('click', function() {
		const wrap = document.getElementById('rv-form-wrap');
		wrap.classList.remove('is-hidden');
		this.style.display = 'none';
		wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
		rvLoadCaptcha();
		// Фиксируем время открытия формы
		document.getElementById('rv-ft').value = Math.floor(Date.now() / 1000);
	});

	/* --- Капча --- */
	function rvLoadCaptcha() {
		fetch('/reviews/captcha.php')
			.then(r => r.json())
			.then(d => { document.getElementById('rv-captcha-q').textContent = d.question; })
			.catch(() => { document.getElementById('rv-captcha-q').textContent = 'Ошибка загрузки'; });
	}
	document.getElementById('rv-captcha-reload').addEventListener('click', rvLoadCaptcha);

	/* --- Превью фото --- */
	const inputFile  = document.getElementById('rv-photos');
	const previews   = document.getElementById('rv-previews');
	let selectedFiles = [];

	inputFile.addEventListener('change', handleFiles);

	const drop = document.getElementById('rv-drop');
	drop.addEventListener('dragover', e => { e.preventDefault(); drop.style.borderColor = '#36393e'; });
	drop.addEventListener('dragleave', () => { drop.style.borderColor = ''; });
	drop.addEventListener('drop', e => {
		e.preventDefault();
		drop.style.borderColor = '';
		handleFilesRaw(e.dataTransfer.files);
	});

	function handleFiles() { handleFilesRaw(inputFile.files); }

	function handleFilesRaw(files) {
		for (const f of files) {
			if (selectedFiles.length >= 4) break;
			if (!f.type.match(/^image\/(jpeg|png|webp)$/)) continue;
			if (f.size > 5 * 1024 * 1024) continue;
			selectedFiles.push(f);
		}
		renderPreviews();
		syncInput();
	}

	function renderPreviews() {
		previews.innerHTML = '';
		selectedFiles.forEach((f, i) => {
			const url  = URL.createObjectURL(f);
			const wrap = document.createElement('div');
			wrap.className = 'rv-photo-preview';
			wrap.innerHTML = `<img src="${url}" alt=""><button class="rv-photo-preview__rm" type="button" data-i="${i}">✕</button>`;
			previews.appendChild(wrap);
		});
		previews.querySelectorAll('.rv-photo-preview__rm').forEach(btn => {
			btn.addEventListener('click', () => {
				selectedFiles.splice(+btn.dataset.i, 1);
				renderPreviews();
				syncInput();
			});
		});
	}

	function syncInput() {
		// Собираем новый FileList через DataTransfer
		const dt = new DataTransfer();
		selectedFiles.forEach(f => dt.items.add(f));
		inputFile.files = dt.files;
	}

	/* --- Отправка формы --- */
	document.getElementById('rv-form').addEventListener('submit', function(e) {
		e.preventDefault();

		const msgOk  = document.getElementById('rv-msg-ok');
		const msgErr = document.getElementById('rv-msg-err');
		msgOk.style.display  = 'none';
		msgErr.style.display = 'none';

		const btn = document.getElementById('rv-submit');
		btn.disabled = true;
		btn.textContent = 'Отправка…';

		const fd = new FormData(this);

		fetch('/reviews/submit.php', { method: 'POST', body: fd })
			.then(r => r.json())
			.then(d => {
				if (d.ok) {
					msgOk.style.display = 'block';
					this.style.display  = 'none';
				} else {
					msgErr.textContent   = d.error || 'Ошибка. Попробуйте ещё раз.';
					msgErr.style.display = 'block';
					btn.disabled    = false;
					btn.textContent = 'Отправить отзыв';
					// Обновляем капчу при ошибке
					rvLoadCaptcha();
					document.getElementById('rv-captcha').value = '';
				}
			})
			.catch(() => {
				msgErr.textContent   = 'Ошибка сети. Попробуйте ещё раз.';
				msgErr.style.display = 'block';
				btn.disabled    = false;
				btn.textContent = 'Отправить отзыв';
			});
	});
})();

/* --- Лайтбокс --- */
function rvLightbox(src) {
	document.getElementById('rv-lightbox-img').src = src;
	document.getElementById('rv-lightbox').classList.add('is-open');
	document.body.style.overflow = 'hidden';
}
function rvLightboxClose() {
	document.getElementById('rv-lightbox').classList.remove('is-open');
	document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') rvLightboxClose(); });
</script>

<?php
/* Склонение числительных */
function rv_plural(int $n, string $one, string $few, string $many): string {
	$n = abs($n) % 100;
	$n1 = $n % 10;
	if ($n >= 11 && $n <= 19) return $many;
	if ($n1 === 1) return $one;
	if ($n1 >= 2 && $n1 <= 4) return $few;
	return $many;
}
?>
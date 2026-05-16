'use strict';
/* admin_ui.js — модальные диалоги и toast-уведомления
   Подключается на всех страницах админки */

/* ===== СТИЛИ ===== */
(function injectStyles() {
	const css = `
/* --- Toast --- */
#admin-toast-wrap {
	position: fixed;
	bottom: 24px;
	right: 24px;
	z-index: 9999;
	display: flex;
	flex-direction: column;
	gap: 10px;
	pointer-events: none;
}

.admin-toast {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	padding: 12px 16px;
	border-radius: 10px;
	font-family: "Roboto", Arial, sans-serif;
	font-size: 14px;
	line-height: 1.4;
	box-shadow: 0 4px 16px rgba(0,0,0,.18);
	pointer-events: all;
	max-width: 340px;
	animation: toast-in .25s ease;
}

.admin-toast--success { background: #1b5e20; color: #fff; }
.admin-toast--error   { background: #b71c1c; color: #fff; }
.admin-toast--info    { background: #1a237e; color: #fff; }
.admin-toast--warning { background: #e65100; color: #fff; }

.admin-toast__icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.admin-toast__body { flex: 1; }
.admin-toast__title { font-weight: 600; }
.admin-toast__text  { font-size: 13px; opacity: .85; margin-top: 2px; }
.admin-toast__close {
	background: none; border: none; color: inherit; opacity: .7;
	cursor: pointer; font-size: 16px; line-height: 1; padding: 0; flex-shrink: 0;
}
.admin-toast__close:hover { opacity: 1; }
.admin-toast.is-hiding { animation: toast-out .2s ease forwards; }

@keyframes toast-in  { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes toast-out { from { opacity: 1; transform: none; } to { opacity: 0; transform: translateY(12px); } }

/* --- Модальное окно --- */
#admin-modal-overlay {
	position: fixed;
	inset: 0;
	background: rgba(0,0,0,.45);
	z-index: 10000;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
	animation: modal-bg-in .2s ease;
}

@keyframes modal-bg-in { from { opacity: 0; } to { opacity: 1; } }

#admin-modal {
	background: #fff;
	border-radius: 14px;
	padding: 28px;
	max-width: 420px;
	width: 100%;
	box-shadow: 0 12px 40px rgba(0,0,0,.25);
	animation: modal-in .2s ease;
	font-family: "Roboto", Arial, sans-serif;
}

@keyframes modal-in { from { opacity: 0; transform: scale(.95) translateY(-8px); } to { opacity: 1; transform: none; } }

.admin-modal__icon   { font-size: 28px; margin-bottom: 12px; }
.admin-modal__title  { font-size: 17px; font-weight: 700; color: #36393e; margin-bottom: 8px; }
.admin-modal__text   { font-size: 14px; color: #6b6b6b; line-height: 1.5; margin-bottom: 24px; }
.admin-modal__btns   { display: flex; gap: 10px; justify-content: flex-end; }

.admin-modal__btn {
	padding: 9px 18px; border-radius: 8px; border: none;
	font-family: inherit; font-size: 14px; font-weight: 500;
	cursor: pointer; transition: background .15s;
}
.admin-modal__btn--cancel  { background: #f4f5f7; color: #36393e; }
.admin-modal__btn--cancel:hover  { background: #e2e4e9; }
.admin-modal__btn--confirm { background: #c82a20; color: #fff; }
.admin-modal__btn--confirm:hover { background: #a32219; }
.admin-modal__btn--confirm.is-safe { background: #4a4f59; }
.admin-modal__btn--confirm.is-safe:hover { background: #385081; }
`;
	const el = document.createElement('style');
	el.textContent = css;
	document.head.appendChild(el);
})();

/* ===== TOAST ===== */

function _getToastWrap() {
	let wrap = document.getElementById('admin-toast-wrap');
	if (!wrap) {
		wrap = document.createElement('div');
		wrap.id = 'admin-toast-wrap';
		document.body.appendChild(wrap);
	}
	return wrap;
}

const TOAST_ICONS = {
	success: '✅',
	error:   '❌',
	warning: '⚠️',
	info:    'ℹ️',
};

/**
 * adminToast(type, title, text?, duration?)
 * type: 'success' | 'error' | 'warning' | 'info'
 */
function adminToast(type, title, text = '', duration = 4000) {
	const wrap  = _getToastWrap();
	const toast = document.createElement('div');
	toast.className = 'admin-toast admin-toast--' + type;

	toast.innerHTML = `
		<span class="admin-toast__icon">${TOAST_ICONS[type] ?? 'ℹ️'}</span>
		<div class="admin-toast__body">
			<div class="admin-toast__title">${_esc(title)}</div>
			${text ? `<div class="admin-toast__text">${_esc(text)}</div>` : ''}
		</div>
		<button class="admin-toast__close" type="button">×</button>
	`;

	toast.querySelector('.admin-toast__close').addEventListener('click', () => _hideToast(toast));
	wrap.appendChild(toast);

	if (duration > 0) {
		setTimeout(() => _hideToast(toast), duration);
	}
}

function _hideToast(el) {
	el.classList.add('is-hiding');
	el.addEventListener('animationend', () => el.remove(), { once: true });
}

/* ===== CONFIRM MODAL ===== */

let _modalCallback = null;

/**
 * adminConfirm(title, text, onConfirm, options?)
 * options: { confirmText, cancelText, danger }
 */
function adminConfirm(title, text, onConfirm, options = {}) {
	// Удаляем предыдущий если был
	document.getElementById('admin-modal-overlay')?.remove();

	const {
		confirmText = 'Подтвердить',
		cancelText  = 'Отмена',
		danger      = true,
	} = options;

	_modalCallback = onConfirm;

	const overlay = document.createElement('div');
	overlay.id = 'admin-modal-overlay';

	overlay.innerHTML = `
		<div id="admin-modal" role="dialog" aria-modal="true">
			<div class="admin-modal__icon">${danger ? '⚠️' : '❓'}</div>
			<div class="admin-modal__title">${_esc(title)}</div>
			<div class="admin-modal__text">${_esc(text)}</div>
			<div class="admin-modal__btns">
				<button class="admin-modal__btn admin-modal__btn--cancel" id="modal-cancel">${_esc(cancelText)}</button>
				<button class="admin-modal__btn admin-modal__btn--confirm ${danger ? '' : 'is-safe'}" id="modal-confirm">${_esc(confirmText)}</button>
			</div>
		</div>
	`;

	document.body.appendChild(overlay);

	document.getElementById('modal-cancel').addEventListener('click', _closeModal);
	document.getElementById('modal-confirm').addEventListener('click', () => {
		_closeModal();
		if (_modalCallback) _modalCallback();
	});

	// Закрытие по клику на фон
	overlay.addEventListener('click', e => { if (e.target === overlay) _closeModal(); });

	// Закрытие по Escape
	document.addEventListener('keydown', _modalEscHandler);

	// Фокус на кнопку отмены (безопаснее)
	setTimeout(() => document.getElementById('modal-cancel')?.focus(), 50);
}

function _closeModal() {
	document.getElementById('admin-modal-overlay')?.remove();
	document.removeEventListener('keydown', _modalEscHandler);
}

function _modalEscHandler(e) {
	if (e.key === 'Escape') _closeModal();
}

/* ===== УТИЛИТЫ ===== */

function _esc(str) {
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}
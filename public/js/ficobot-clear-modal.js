/**
 * ficoBot “clear conversation” confirmation — same interaction pattern as admin logout modal.
 * Focus is moved out of the dialog before aria-hidden=true to satisfy WAI-ARIA.
 */
(function () {
	'use strict';

	if (window.__growficoFicobotClearModalBound) {
		return;
	}
	window.__growficoFicobotClearModalBound = true;

	var focusReturnEl = null;

	function getModal() {
		return document.getElementById('ficobot-clear-modal');
	}

	function getChatModal() {
		return document.getElementById('chat-modal');
	}

	function getChatBox() {
		return document.getElementById('chat-box');
	}

	function tryFocus(el) {
		if (!el || el.nodeType !== 1 || typeof el.focus !== 'function' || el.disabled) {
			return false;
		}
		if (!document.contains(el)) {
			return false;
		}
		el.focus({ preventScroll: true });
		return true;
	}

	function openModal() {
		var modal = getModal();
		if (!modal) {
			return;
		}

		var ae = document.activeElement;
		if (ae && ae.nodeType === 1 && !modal.contains(ae)) {
			focusReturnEl = ae;
		} else {
			focusReturnEl = document.querySelector('[data-ficobot-clear-open]');
		}

		var chatModal = getChatModal();
		if (chatModal && typeof chatModal.setAttribute === 'function') {
			chatModal.setAttribute('inert', '');
		}

		modal.style.display = 'flex';
		modal.classList.remove('hidden');
		modal.setAttribute('aria-hidden', 'false');

		window.requestAnimationFrame(function () {
			var cancelBtn = document.getElementById('ficobot-clear-cancel-btn');
			if (cancelBtn && typeof cancelBtn.focus === 'function') {
				cancelBtn.focus();
			}
		});
	}

	function closeModal() {
		var modal = getModal();
		if (!modal || modal.style.display !== 'flex') {
			return;
		}

		var chatModal = getChatModal();
		if (chatModal) {
			chatModal.removeAttribute('inert');
		}

		var returnTo = focusReturnEl;
		focusReturnEl = null;

		if (!tryFocus(returnTo)) {
			if (!tryFocus(document.getElementById('chat-input'))) {
				tryFocus(document.querySelector('[data-ficobot-clear-open]'));
			}
		}

		modal.style.display = 'none';
		modal.setAttribute('aria-hidden', 'true');
	}

	function performClear() {
		var box = getChatBox();
		if (!box) return;

		box.innerHTML =
			'<div class="text-center my-4" id="start-label">' +
			'<span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest">Start Conversation</span>' +
			'</div>';

		var suggestions = document.getElementById('chat-suggestions');
		if (suggestions) suggestions.style.display = 'flex';
	}

	window.openFicobotClearModal = openModal;

	document.addEventListener('click', function (e) {
		var openBtn = e.target.closest && e.target.closest('[data-ficobot-clear-open]');
		if (openBtn) {
			e.preventDefault();
			openModal();
			return;
		}

		if (e.target.closest && e.target.closest('#ficobot-clear-cancel-btn')) {
			closeModal();
			return;
		}

		if (e.target.closest && e.target.closest('#ficobot-clear-confirm-btn')) {
			performClear();
			closeModal();
			return;
		}

		if (e.target.id === 'ficobot-clear-backdrop') {
			closeModal();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') {
			return;
		}
		var modal = getModal();
		if (modal && modal.style.display === 'flex') {
			closeModal();
		}
	});
})();

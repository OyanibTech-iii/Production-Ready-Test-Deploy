/**
 * Admin logout modal: delegated handlers so it works after Turbo Drive body swaps.
 */
(function () {
    'use strict';

    if (window.__growficoAdminLogoutModalBound) {
        return;
    }
    window.__growficoAdminLogoutModalBound = true;

    function getModal() {
        return document.getElementById('logout-modal');
    }

    document.addEventListener('click', function (e) {
        var openBtn = e.target.closest && e.target.closest('#logout-btn');
        if (openBtn) {
            var modal = getModal();
            if (modal) {
                e.preventDefault();
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
            return;
        }

        if (e.target.closest && e.target.closest('#logout-cancel-btn')) {
            var modal = getModal();
            if (modal) {
                modal.style.display = 'none';
            }
            return;
        }

        var modal = getModal();
        if (modal && modal.style.display === 'flex') {
            var onBackdrop =
                e.target === modal ||
                (e.target.classList && e.target.classList.contains('backdrop-blur-md'));
            if (onBackdrop) {
                modal.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }
        var modal = getModal();
        if (modal && modal.style.display === 'flex') {
            modal.style.display = 'none';
        }
    });
})();

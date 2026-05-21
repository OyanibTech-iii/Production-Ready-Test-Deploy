/**
 * User mobile sidebar: same behavior as admin (delegated clicks, FAB/header hide when open, Turbo-safe).
 */
(function () {
    'use strict';

    if (window.__growficoUserSidebarBound) {
        return;
    }
    window.__growficoUserSidebarBound = true;

    function getSidebar() {
        return document.getElementById('sidebar');
    }

    function getOverlay() {
        return document.getElementById('mobile-overlay');
    }

    function getMainHeader() {
        return document.getElementById('main-header');
    }

    function getFab() {
        return document.querySelector('.js-user-mobile-menu-fab');
    }

    function isMobileViewport() {
        return window.innerWidth < 1024;
    }

    function closeSidebar() {
        var sidebar = getSidebar();
        var overlay = getOverlay();
        var mainHeader = getMainHeader();
        var fab = getFab();
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        overlay.classList.add('hidden');
        if (fab) {
            fab.classList.remove('hidden');
        }
        if (mainHeader && isMobileViewport()) {
            mainHeader.classList.remove('hidden');
        }
    }

    function openSidebar() {
        var sidebar = getSidebar();
        var overlay = getOverlay();
        var mainHeader = getMainHeader();
        var fab = getFab();
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
        if (fab) {
            fab.classList.add('hidden');
        }
        if (mainHeader && isMobileViewport()) {
            mainHeader.classList.add('hidden');
        }
    }

    function toggleSidebar() {
        var sidebar = getSidebar();
        if (!sidebar) {
            return;
        }
        var isOpen = sidebar.classList.contains('translate-x-0');
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    window.growficoCloseUserSidebar = closeSidebar;

    document.addEventListener('click', function (e) {
        var openMenu = e.target.closest && e.target.closest('.js-user-mobile-menu');
        if (openMenu) {
            e.preventDefault();
            toggleSidebar();
            return;
        }

        var navLink = e.target.closest && e.target.closest('#sidebar a');
        if (navLink && isMobileViewport()) {
            closeSidebar();
            return;
        }

        if (!isMobileViewport()) {
            return;
        }

        var sidebar = getSidebar();
        var overlay = getOverlay();
        if (!sidebar || !overlay || overlay.classList.contains('hidden')) {
            return;
        }

        if (sidebar.contains(e.target)) {
            return;
        }

        var fab = getFab();
        if (fab && fab.contains(e.target)) {
            return;
        }

        var headerBtn = document.querySelector('.js-user-mobile-menu-header');
        if (headerBtn && headerBtn.contains(e.target)) {
            return;
        }

        closeSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            closeSidebar();
            var mainHeader = getMainHeader();
            if (mainHeader) {
                mainHeader.classList.remove('hidden');
            }
        }
    });

    document.addEventListener('turbo:load', function () {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const CART_KEY = 'growfico_cart';
    const badge = document.getElementById('header-cart-count');

    function setBadgeCount(total) {
        if (!badge) {
            return;
        }
        const n = typeof total === 'number' && !Number.isNaN(total) ? total : 0;
        if (n > 0) {
            badge.textContent = n > 9 ? '9+' : String(n);
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function refreshHeaderCartFromStorage() {
        try {
            const raw = sessionStorage.getItem(CART_KEY);
            const items = raw ? JSON.parse(raw) : [];
            const total = items.reduce(function (n, i) {
                return n + (i.quantity || 1);
            }, 0);
            setBadgeCount(total);
        } catch (e) {
            console.error('Cart sync error', e);
        }
    }

    function refreshHeaderCartFromServer() {
        const url = window.growficoCartSummaryUrl;
        if (typeof url !== 'string' || !url.length) {
            refreshHeaderCartFromStorage();
            return;
        }
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                setBadgeCount(typeof d.count === 'number' ? d.count : 0);
            })
            .catch(function () {
                refreshHeaderCartFromStorage();
            });
    }

    refreshHeaderCartFromServer();

    window.addEventListener('storage', refreshHeaderCartFromStorage);

    window.addEventListener('cartUpdated', function (e) {
        if (e.detail && typeof e.detail.count === 'number') {
            setBadgeCount(e.detail.count);
            return;
        }
        refreshHeaderCartFromServer();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const profileLink = document.querySelector('a[data-action="profile"]');
    const profileSection = document.getElementById('profile-section');

    if (profileLink && profileSection) {
        profileLink.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            profileSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (window.innerWidth < 1024 && typeof window.growficoCloseUserSidebar === 'function') {
                window.growficoCloseUserSidebar();
            }
        });
    }
});

const logoutBtn = document.getElementById('user-logout-btn-dropdown');
const logoutModal = document.getElementById('user-logout-modal');
const logoutCancel = document.getElementById('user-logout-cancel');

logoutBtn?.addEventListener('click', function (e) {
    e.preventDefault();
    if (logoutModal) {
        logoutModal.style.display = 'flex';
    }
});

logoutCancel?.addEventListener('click', function () {
    if (logoutModal) {
        logoutModal.style.display = 'none';
    }
});

logoutModal?.addEventListener('click', function (e) {
    if (e.target === logoutModal) {
        logoutModal.style.display = 'none';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && logoutModal && logoutModal.style.display !== 'none') {
        logoutModal.style.display = 'none';
    }
});

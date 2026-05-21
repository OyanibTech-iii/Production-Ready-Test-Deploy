/**
 / Admin mobile sidebar: delegated clicks + live DOM lookups so Turbo navigations keep working.
 */
(function () {
    'use strict';

    if (window.__growficoAdminSidebarBound) {
        return;
    }
    window.__growficoAdminSidebarBound = true;

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
        return document.querySelector('.js-admin-mobile-menu-fab');
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

    document.addEventListener('click', function (e) {
        var openMenu = e.target.closest && e.target.closest('.js-admin-mobile-menu');
        if (openMenu) {
            e.preventDefault();
            toggleSidebar();
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

        var headerBtn = document.querySelector('.js-admin-mobile-menu-header');
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

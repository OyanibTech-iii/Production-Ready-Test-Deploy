(function () {
    'use strict';

    if (window.__growficoThemeBound) {
        return;
    }
    window.__growficoThemeBound = true;

    var STORAGE_KEY = 'theme';
    var HTML = document.documentElement;

    function isDark() {
        return HTML.classList.contains('dark');
    }

    function persistTheme(dark) {
        try {
            localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
        } catch (e) {
        }
    }

    function syncThemeControls() {
        var dark = isDark();
        window.dispatchEvent(new CustomEvent('dark-mode-changed', {
            detail: { dark: dark }
        }));
        document.querySelectorAll('.js-theme-toggle, #theme-toggle').forEach(function (toggle) {
            var label = dark ? 'Switch to light mode' : 'Switch to dark mode';
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
            toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
        });

        document.querySelectorAll('.js-theme-icon, #theme-icon').forEach(function (icon) {
            icon.setAttribute('name', dark ? 'sunny-outline' : 'moon-outline');
        });
    }

    function toggleTheme() {
        var next = !isDark();
        HTML.classList.toggle('dark', next);
        persistTheme(next);
        syncThemeControls();
    }

    function findThemeToggle(e) {
        var el = e.target;
        if (el && typeof el.closest === 'function') {
            var btn = el.closest('.js-theme-toggle') || el.closest('#theme-toggle');
            if (btn) {
                return btn;
            }
        }
        if (typeof e.composedPath === 'function') {
            var path = e.composedPath();
            for (var i = 0; i < path.length; i++) {
                var n = path[i];
                if (n && n.nodeType === 1) {
                    if (n.id === 'theme-toggle' || (n.classList && n.classList.contains('js-theme-toggle'))) {
                        return n;
                    }
                }
            }
        }
        return null;
    }

    function initThemeUi() {
        syncThemeControls();
    }

    document.addEventListener('DOMContentLoaded', initThemeUi);
    document.addEventListener('turbo:load', initThemeUi);
    document.addEventListener('turbo:render', initThemeUi);

    document.addEventListener(
        'click',
        function (e) {
            var toggle = findThemeToggle(e);
            if (!toggle) {
                return;
            }
            e.preventDefault();
            toggleTheme();
        },
        true
    );

    window.addEventListener('storage', function (e) {
        if (e.key !== STORAGE_KEY || e.newValue == null) {
            return;
        }
        var dark = e.newValue === 'dark';
        HTML.classList.toggle('dark', dark);
        syncThemeControls();
    });
})();

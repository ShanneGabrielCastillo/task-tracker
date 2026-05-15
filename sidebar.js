/**
 * sidebar.js — Mobile hamburger menu for the Task Tracker sidebar.
 * Include on every app page (dashboard, all_tasks, calendar, etc.)
 *
 * The overlay is controlled entirely by JS:
 *   open  → display:block + .visible (opacity 1, pointer-events auto)
 *   close → remove .visible, then display:none after transition
 *
 * This prevents the overlay from ever blocking interactions when hidden.
 */
(function () {
    'use strict';

    var BREAKPOINT = 768;

    // ── Inject hamburger button into .app-header ──────────────────────────
    function injectHamburger() {
        if (document.getElementById('hamburger-btn')) return;
        var header = document.querySelector('.app-header');
        if (!header) return;

        var btn = document.createElement('button');
        btn.id        = 'hamburger-btn';
        btn.type      = 'button';
        btn.setAttribute('aria-label', 'Open navigation menu');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML =
            '<span class="ham-line"></span>' +
            '<span class="ham-line"></span>' +
            '<span class="ham-line"></span>';

        header.insertBefore(btn, header.firstChild);
    }

    // ── Inject overlay backdrop ───────────────────────────────────────────
    function injectOverlay() {
        if (document.getElementById('sidebar-overlay')) return;
        var overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        // Start fully hidden — display:none + pointer-events:none
        overlay.style.display      = 'none';
        overlay.style.pointerEvents = 'none';
        document.body.appendChild(overlay);
    }

    // ── Open sidebar ──────────────────────────────────────────────────────
    function openSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var btn     = document.getElementById('hamburger-btn');
        if (!sidebar) return;

        sidebar.classList.add('open');

        if (overlay) {
            overlay.style.display       = 'block';   // make it exist in layout
            overlay.style.pointerEvents = 'auto';     // allow clicks to close
            // Force reflow so the transition fires
            void overlay.offsetWidth;
            overlay.classList.add('visible');         // fade in via CSS transition
        }

        if (btn) btn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('sidebar-open');
    }

    // ── Close sidebar ─────────────────────────────────────────────────────
    function closeSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var btn     = document.getElementById('hamburger-btn');
        if (!sidebar) return;

        sidebar.classList.remove('open');

        if (overlay) {
            overlay.classList.remove('visible');      // fade out via CSS transition
            overlay.style.pointerEvents = 'none';     // immediately stop intercepting

            // After the fade-out transition, hide from layout entirely
            var onEnd = function () {
                overlay.style.display = 'none';
                overlay.removeEventListener('transitionend', onEnd);
            };
            overlay.addEventListener('transitionend', onEnd);

            // Fallback: if transition doesn't fire (e.g. reduced-motion), hide after 300ms
            setTimeout(function () {
                if (!overlay.classList.contains('visible')) {
                    overlay.style.display = 'none';
                }
            }, 350);
        }

        if (btn) btn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('sidebar-open');
    }

    // ── Wire up events ────────────────────────────────────────────────────
    function init() {
        injectHamburger();
        injectOverlay();

        // Hamburger toggle
        var btn = document.getElementById('hamburger-btn');
        if (btn) {
            btn.addEventListener('click', function () {
                var sidebar = document.querySelector('.sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        // Overlay click closes sidebar
        var overlay = document.getElementById('sidebar-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Nav link tap closes sidebar on mobile
        var navLinks = document.querySelectorAll('.sidebar .nav-item');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= BREAKPOINT) {
                    closeSidebar();
                }
            });
        });

        // Escape key closes sidebar
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        // Resize above breakpoint closes sidebar
        window.addEventListener('resize', function () {
            if (window.innerWidth > BREAKPOINT) {
                closeSidebar();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

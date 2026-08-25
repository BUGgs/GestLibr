(function () {
    'use strict';

    var modal = null;
    var messageEl = null;
    var pendingUrl = '';

    function appendJsConfirmed(url) {
        var value = String(url || '');
        if (value === '') {
            return value;
        }
        if (/[?&]is_js_confirmed=1(?:&|$)/.test(value)) {
            return value;
        }
        return value + (value.indexOf('?') === -1 ? '?' : '&') + 'is_js_confirmed=1';
    }

    function hideModal() {
        pendingUrl = '';
        if (modal) {
            modal.setAttribute('hidden', 'hidden');
        }
    }

    function confirmAndNavigate() {
        var url = pendingUrl;
        hideModal();
        if (url !== '') {
            window.location.href = appendJsConfirmed(url);
        }
    }

    function showModal(message, url) {
        pendingUrl = String(url || '');
        if (pendingUrl === '') {
            return;
        }

        if (!modal) {
            if (window.confirm(String(message || 'Confirmer la suppression ?'))) {
                window.location.href = appendJsConfirmed(pendingUrl);
            }
            return;
        }

        if (modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }

        modal.style.position = 'fixed';
        modal.style.left = '0';
        modal.style.top = '0';
        modal.style.right = '0';
        modal.style.bottom = '0';
        modal.style.zIndex = '2600';

        if (messageEl) {
            messageEl.textContent = String(message || 'Confirmer la suppression ?');
        }

        modal.removeAttribute('hidden');
    }

    function init() {
        modal = document.querySelector('[data-book-search-confirm-modal]');
        messageEl = modal ? modal.querySelector('[data-book-search-confirm-message]') : null;

        document.addEventListener('click', function (event) {
            var deleteLink = event.target ? event.target.closest('.book-search-delete-link') : null;
            if (deleteLink) {
                event.preventDefault();
                event.stopPropagation();
                showModal(deleteLink.getAttribute('data-confirm-text'), deleteLink.getAttribute('href'));
                return;
            }

            if (!modal || modal.hasAttribute('hidden')) {
                return;
            }

            if (event.target && event.target.closest('[data-book-search-confirm-ok]')) {
                event.preventDefault();
                confirmAndNavigate();
                return;
            }

            if (event.target && event.target.closest('[data-book-search-confirm-cancel]')) {
                event.preventDefault();
                hideModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!modal || modal.hasAttribute('hidden')) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                hideModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

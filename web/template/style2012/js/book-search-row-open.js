(function () {
    'use strict';

    function shouldIgnoreClick(target) {
        if (!target) {
            return true;
        }

        if (target.closest('.book-row-no-open')) {
            return true;
        }

        if (target.closest('.table-action--delete')) {
            return true;
        }

        if (target.closest('input[type="checkbox"], input[type="text"], input[type="number"], input[type="email"], button, select, textarea')) {
            return true;
        }

        return false;
    }

    function initRow(row) {
        var url = String(row.getAttribute('data-edit-url') || '');
        if (!url) {
            return;
        }

        row.addEventListener('click', function (event) {
            if (event.defaultPrevented) {
                return;
            }

            if (event.button !== 0) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            if (shouldIgnoreClick(event.target)) {
                return;
            }

            var selection = window.getSelection ? window.getSelection() : null;
            if (selection && String(selection.toString() || '').length > 0) {
                return;
            }

            window.location.href = url;
        });
    }

    function initAll() {
        var rows = document.querySelectorAll('tr.book-result-row-click[data-edit-url]');
        var i = 0;
        while (i < rows.length) {
            initRow(rows[i]);
            i += 1;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();

(function () {
    'use strict';

    function trim(value) {
        return String(value == null ? '' : value).replace(/^\s+|\s+$/g, '');
    }

    function normalizeLocation(value) {
        return trim(value).toUpperCase();
    }

    function setDisplayValue(button, locationValue) {
        var label = locationValue === '' ? '\u2014' : locationValue;
        button.textContent = label;
        button.title = locationValue === '' ? 'Cliquer pour modifier le rayon' : ('Rayon: ' + locationValue + ' (cliquer pour modifier)');
    }

    function setState(wrapper, statusEl, state, message) {
        var symbol = '';
        if (state === 'saving') {
            symbol = '\u2026';
        } else if (state === 'saved') {
            symbol = '\u2713';
        } else if (state === 'error') {
            symbol = '!';
        }

        wrapper.setAttribute('data-state', state || '');
        if (statusEl) {
            statusEl.textContent = symbol;
            statusEl.title = message || '';
        }
    }

    function initLocationEditor(wrapper) {
        var button = wrapper.querySelector('.book-location-inline__value');
        var input = wrapper.querySelector('.book-location-inline__input');
        var statusEl = wrapper.querySelector('.book-location-inline__status');
        var apiUrl = String(wrapper.getAttribute('data-api-url') || '');
        var bookId = parseInt(wrapper.getAttribute('data-book-id') || '0', 10);

        if (!button || !input || !apiUrl || !bookId) {
            return;
        }

        var state = {
            timer: null,
            requestSeq: 0,
            hideSavedTimer: null,
            controller: null,
            lastSaved: normalizeLocation(input.value)
        };

        input.value = state.lastSaved;
        setDisplayValue(button, state.lastSaved);

        function beginEdit() {
            wrapper.classList.add('is-editing');
            input.focus();
            input.select();
        }

        function endEdit() {
            wrapper.classList.remove('is-editing');
        }

        function scheduleSave(delayMs) {
            if (state.timer) {
                window.clearTimeout(state.timer);
            }
            state.timer = window.setTimeout(function () {
                state.timer = null;
                saveNow();
            }, delayMs);
        }

        function saveNow() {
            var normalized = normalizeLocation(input.value);
            input.value = normalized;

            if (normalized === state.lastSaved) {
                setState(wrapper, statusEl, 'saved', 'Sauvegard\u00e9');
                if (state.hideSavedTimer) {
                    window.clearTimeout(state.hideSavedTimer);
                }
                state.hideSavedTimer = window.setTimeout(function () {
                    if (!wrapper.classList.contains('is-editing')) {
                        setState(wrapper, statusEl, '', '');
                    }
                }, 1200);
                return;
            }

            if (state.controller && typeof state.controller.abort === 'function') {
                state.controller.abort();
            }

            state.requestSeq += 1;
            var requestSeq = state.requestSeq;

            var params = new URLSearchParams();
            params.append('m', 'bo');
            params.append('a', 'vi');
            params.append('sa', 're');
            params.append('book_action', 'quick_save_location');
            params.append('idBook', String(bookId));
            params.append('location', normalized);

            var fetchOptions = {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params.toString()
            };

            if (typeof AbortController !== 'undefined') {
                state.controller = new AbortController();
                fetchOptions.signal = state.controller.signal;
            } else {
                state.controller = null;
            }

            setState(wrapper, statusEl, 'saving', 'Enregistrement...');

            fetch(apiUrl, fetchOptions)
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    if (requestSeq !== state.requestSeq) {
                        return;
                    }

                    if (!payload || payload.ok !== true) {
                        var message = 'Sauvegarde impossible.';
                        if (payload && payload.message) {
                            message = payload.message;
                        }
                        throw new Error(message);
                    }

                    var serverLocation = normalized;
                    if (payload.book && typeof payload.book.location !== 'undefined') {
                        serverLocation = normalizeLocation(payload.book.location);
                    }

                    state.lastSaved = serverLocation;
                    input.value = serverLocation;
                    setDisplayValue(button, serverLocation);
                    setState(wrapper, statusEl, 'saved', 'Sauvegard\u00e9');

                    if (state.hideSavedTimer) {
                        window.clearTimeout(state.hideSavedTimer);
                    }
                    state.hideSavedTimer = window.setTimeout(function () {
                        if (!wrapper.classList.contains('is-editing')) {
                            setState(wrapper, statusEl, '', '');
                        }
                    }, 1200);
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    if (requestSeq !== state.requestSeq) {
                        return;
                    }
                    setState(wrapper, statusEl, 'error', (err && err.message) ? err.message : 'Erreur de sauvegarde');
                })
                .finally(function () {
                    if (requestSeq === state.requestSeq) {
                        state.controller = null;
                    }
                });
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            beginEdit();
        });

        input.addEventListener('input', function () {
            var normalized = normalizeLocation(input.value);
            if (normalized !== input.value) {
                input.value = normalized;
            }
            setDisplayValue(button, normalized);
            setState(wrapper, statusEl, 'saving', 'Modification en attente...');
            scheduleSave(700);
        });

        input.addEventListener('blur', function () {
            scheduleSave(0);
            window.setTimeout(function () {
                endEdit();
            }, 120);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                scheduleSave(0);
                input.blur();
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                if (state.timer) {
                    window.clearTimeout(state.timer);
                    state.timer = null;
                }
                input.value = state.lastSaved;
                setDisplayValue(button, state.lastSaved);
                setState(wrapper, statusEl, '', '');
                endEdit();
                button.focus();
            }
        });
    }

    function initAllEditors() {
        var items = document.querySelectorAll('.book-location-inline');
        var i = 0;
        while (i < items.length) {
            initLocationEditor(items[i]);
            i += 1;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllEditors);
    } else {
        initAllEditors();
    }
})();

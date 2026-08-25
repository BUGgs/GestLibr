(function (window, document) {
    'use strict';

    function addClass(el, className) {
        if (!el) {
            return;
        }
        if (el.classList) {
            el.classList.add(className);
            return;
        }
        if ((' ' + el.className + ' ').indexOf(' ' + className + ' ') === -1) {
            el.className += (el.className ? ' ' : '') + className;
        }
    }

    function removeClass(el, className) {
        if (!el) {
            return;
        }
        if (el.classList) {
            el.classList.remove(className);
            return;
        }
        el.className = (' ' + el.className + ' ').replace(' ' + className + ' ', ' ').replace(/^\s+|\s+$/g, '');
    }

    function parseJson(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function normalizeIsbn(value) {
        return String(value || '')
            .replace(/[\s-]+/g, '')
            .toUpperCase();
    }

    function isLookupReady(isbn) {
        var len = isbn.length;
        if (len !== 10 && len !== 13) {
            return false;
        }
        return /^[0-9X]+$/.test(isbn);
    }

    function dispatchInputLikeEvent(field) {
        var ev;
        if (!field) {
            return;
        }
        if (typeof window.Event === 'function') {
            ev = new window.Event('input', { bubbles: true });
        } else {
            ev = document.createEvent('Event');
            ev.initEvent('input', true, true);
        }
        field.dispatchEvent(ev);
    }

    function initCreateIsbn() {
        var form = document.querySelector('form.book-form[data-book-form-mode="create"]');
        var isbnInput;
        var statusEl;
        var duplicateBox;
        var duplicateText;
        var duplicateLink;
        var lookupUrl;
        var debounceTimer = null;
        var activeXhr = null;
        var activeRequestId = 0;
        var lastRequestedIsbn = '';

        var fieldMap = {
            isbn: 'book-isbn',
            author: 'book-author',
            title: 'book-title',
            subtitle: 'book-subtitle',
            description: 'book-description',
            editor: 'book-editor',
            publishDate: 'book-publish-date',
            pageNumber: 'book-page-number',
            publishLocation: 'book-publish-location',
            collection: 'book-asin'
        };

        if (!form) {
            return;
        }

        isbnInput = form.querySelector('#book-isbn');
        statusEl = form.querySelector('[data-book-isbn-status]');
        duplicateBox = form.querySelector('[data-book-isbn-duplicate]');
        duplicateText = form.querySelector('[data-book-isbn-duplicate-text]');
        duplicateLink = form.querySelector('[data-book-isbn-duplicate-link]');
        lookupUrl = String(form.getAttribute('data-book-isbn-lookup-url') || '');

        if (!isbnInput || !statusEl || !lookupUrl) {
            return;
        }

        function clearStatusClass() {
            removeClass(statusEl, 'book-isbn-status--info');
            removeClass(statusEl, 'book-isbn-status--success');
            removeClass(statusEl, 'book-isbn-status--error');
        }

        function setStatus(type, message) {
            clearStatusClass();
            if (!message) {
                statusEl.textContent = '';
                return;
            }
            if (type === 'success') {
                addClass(statusEl, 'book-isbn-status--success');
            } else if (type === 'error') {
                addClass(statusEl, 'book-isbn-status--error');
            } else {
                addClass(statusEl, 'book-isbn-status--info');
            }
            statusEl.textContent = String(message);
        }

        function hideDuplicate() {
            if (!duplicateBox) {
                return;
            }
            duplicateBox.setAttribute('hidden', 'hidden');
            if (duplicateText) {
                duplicateText.textContent = '';
            }
            if (duplicateLink) {
                duplicateLink.setAttribute('href', '#');
            }
        }

        function showDuplicate(payload) {
            var title = payload && payload.title ? String(payload.title) : '';
            var author = payload && payload.author ? String(payload.author) : '';
            var idBook = payload && payload.idBook ? String(payload.idBook) : '';
            var openUrl = payload && payload.openUrl ? String(payload.openUrl) : '';
            var label = 'ISBN deja present dans GestLibr';

            if (idBook) {
                label += ' (ID ' + idBook + ')';
            }
            if (title || author) {
                label += ': ' + [author, title].filter(function (v) { return String(v).trim() !== ''; }).join(' - ');
            }

            if (duplicateText) {
                duplicateText.textContent = label;
            }
            if (duplicateLink && openUrl) {
                duplicateLink.setAttribute('href', openUrl);
            }
            if (duplicateBox) {
                duplicateBox.removeAttribute('hidden');
            }
        }

        function flashField(field) {
            if (!field) {
                return;
            }
            removeClass(field, 'book-prefill-flash');
            addClass(field, 'book-prefill-flash');
            window.setTimeout(function () {
                removeClass(field, 'book-prefill-flash');
            }, 1500);
        }

        function applyPrefill(prefill) {
            var key;
            var fieldId;
            var field;
            var value;
            var filled = 0;

            if (!prefill || typeof prefill !== 'object') {
                return filled;
            }

            for (key in prefill) {
                if (!Object.prototype.hasOwnProperty.call(prefill, key)) {
                    continue;
                }
                fieldId = fieldMap[key];
                if (!fieldId) {
                    continue;
                }
                field = form.querySelector('#' + fieldId);
                if (!field) {
                    continue;
                }
                value = String(prefill[key] || '').trim();
                if (!value) {
                    continue;
                }
                if (String(field.value || '').trim() !== '') {
                    continue;
                }
                field.value = value;
                flashField(field);
                dispatchInputLikeEvent(field);
                filled += 1;
            }
            return filled;
        }

        function runLookup(normalizedIsbn) {
            var xhr;
            var requestId;
            var body;

            if (!isLookupReady(normalizedIsbn)) {
                return;
            }
            if (normalizedIsbn === lastRequestedIsbn) {
                return;
            }

            if (activeXhr && activeXhr.readyState !== 4) {
                activeXhr.abort();
            }

            activeRequestId += 1;
            requestId = activeRequestId;
            lastRequestedIsbn = normalizedIsbn;
            setStatus('info', 'Recherche ISBN en cours...');
            hideDuplicate();

            xhr = new XMLHttpRequest();
            activeXhr = xhr;
            xhr.open('POST', lookupUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = function () {
                var payload;
                var filledCount;
                if (xhr.readyState !== 4) {
                    return;
                }
                if (requestId !== activeRequestId) {
                    return;
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    setStatus('error', 'Lookup ISBN impossible (HTTP ' + xhr.status + ').');
                    return;
                }

                payload = parseJson(xhr.responseText);
                if (!payload) {
                    setStatus('error', 'Reponse ISBN invalide.');
                    return;
                }

                if (payload.code === 'duplicate') {
                    showDuplicate((payload.data && payload.data.duplicate) ? payload.data.duplicate : null);
                    setStatus('info', payload.message || 'Cet ISBN existe deja dans la base.');
                    return;
                }

                if (payload.ok === true && payload.code === 'ok') {
                    filledCount = applyPrefill((payload.data && payload.data.prefill) ? payload.data.prefill : null);
                    if (filledCount > 0) {
                        setStatus('success', 'ISBN trouve. ' + filledCount + ' champ(s) pre-rempli(s).');
                    } else {
                        setStatus('info', 'ISBN trouve. Aucun champ vide a completer.');
                    }
                    return;
                }

                if (payload.code === 'not_found') {
                    setStatus('error', payload.message || 'Aucune information trouvee pour cet ISBN.');
                    return;
                }

                setStatus('error', payload.message || 'Erreur lors de la recherche ISBN.');
            };

            body = 'isbn=' + encodeURIComponent(normalizedIsbn);
            xhr.send(body);
        }

        function scheduleLookup() {
            var normalized = normalizeIsbn(isbnInput.value);

            hideDuplicate();

            if (!normalized) {
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = null;
                }
                setStatus('', '');
                return;
            }

            if (!isLookupReady(normalized)) {
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = null;
                }
                setStatus('info', 'Saisissez un ISBN complet (10 ou 13 caracteres).');
                return;
            }

            if (normalized === lastRequestedIsbn) {
                return;
            }

            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(function () {
                debounceTimer = null;
                runLookup(normalized);
            }, 450);
        }

        isbnInput.addEventListener('input', scheduleLookup);

        isbnInput.addEventListener('blur', function () {
            var normalized = normalizeIsbn(isbnInput.value);
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
                debounceTimer = null;
            }
            if (isLookupReady(normalized) && normalized !== lastRequestedIsbn) {
                runLookup(normalized);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCreateIsbn);
    } else {
        initCreateIsbn();
    }
})(window, document);

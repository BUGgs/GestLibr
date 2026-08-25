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

    function hasClass(el, className) {
        if (!el) {
            return false;
        }
        if (el.classList) {
            return el.classList.contains(className);
        }
        return (' ' + el.className + ' ').indexOf(' ' + className + ' ') !== -1;
    }

    function escapeSelectorValue(value) {
        var str = String(value || '');
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(str);
        }
        return str.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function parseJson(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            var excerpt = String(raw || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
            if (excerpt.length > 180) {
                excerpt = excerpt.substring(0, 180) + '...';
            }
            var message = 'Reponse JSON invalide.';
            if (excerpt !== '') {
                message = 'Reponse JSON invalide: ' + excerpt;
            }
            return {
                ok: false,
                code: 'server_error',
                message: message
            };
        }
    }

    function findAncestorWithAttr(el, attrName, root) {
        var current = el;
        while (current && current !== root) {
            if (current.getAttribute && current.getAttribute(attrName) !== null) {
                return current;
            }
            current = current.parentNode;
        }
        return null;
    }

    function buildPayload(action, payload) {
        var parts = [];
        var key;
        var value;
        parts.push('book_action=' + encodeURIComponent(action));
        payload = payload || {};

        for (key in payload) {
            if (!Object.prototype.hasOwnProperty.call(payload, key)) {
                continue;
            }
            value = payload[key];
            if (value === null || typeof value === 'undefined') {
                continue;
            }

            if (key === 'changes' && typeof value === 'object') {
                var field;
                for (field in value) {
                    if (!Object.prototype.hasOwnProperty.call(value, field)) {
                        continue;
                    }
                    parts.push('changes[' + encodeURIComponent(field) + ']=' + encodeURIComponent(String(value[field])));
                }
                continue;
            }

            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(String(value)));
        }

        return parts.join('&');
    }

    function request(apiUrl, action, payload, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        options = options || {};
        var xhr = new XMLHttpRequest();
        var done = false;
        var timeoutMs = parseInt(options.timeoutMs || '0', 10);

        function finalize(response, error) {
            if (done) {
                return;
            }
            done = true;
            callback(response, error);
        }

        xhr.open('POST', apiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        if (timeoutMs > 0) {
            xhr.timeout = timeoutMs;
        }
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                finalize(parseJson(xhr.responseText), null);
                return;
            }
            finalize(null, {
                ok: false,
                code: 'server_error',
                message: 'HTTP ' + xhr.status + ' - sauvegarde impossible.'
            });
        };
        xhr.onerror = function () {
            finalize(null, {
                ok: false,
                code: 'server_error',
                message: 'Erreur réseau - sauvegarde impossible.'
            });
        };
        xhr.ontimeout = function () {
            finalize(null, {
                ok: false,
                code: 'timeout',
                message: 'Délai dépassé (' + String(Math.round(timeoutMs / 1000)) + 's) - action interrompue.'
            });
        };
        xhr.send(buildPayload(action, payload));
        return xhr;
    }

    function initBookAutosave(form) {
        var enabled = String(form.getAttribute('data-book-autosave-enabled') || '0') === '1';
        var apiUrl = String(form.getAttribute('data-book-api-url') || form.getAttribute('action') || '');
        var idField = form.querySelector("input[name='id']");
        var statusEl = document.querySelector('[data-book-save-status]');
        var manualSubmit = form.querySelector('[data-book-manual-submit]');
        var clientModifyDateField = form.querySelector("input[name='clientModifyDate']");
        var quantityInput = form.querySelector("input[name='quantity']");
        var confirmModal = (form.parentNode && form.parentNode.querySelector) ? form.parentNode.querySelector('[data-book-confirm-modal]') : null;
        var confirmMessageEl = confirmModal ? confirmModal.querySelector('[data-book-confirm-message]') : null;
        var ebayItemLinkEl = form.querySelector('[data-book-ebay-item-link]');
        var ebayLastSyncEl = form.querySelector('[data-book-ebay-last-sync]');
        var ebaySyncNeededEl = form.querySelector('[data-book-ebay-sync-needed]');
        var onEbayInput = form.querySelector("input[name='onEbay']");
        var modifyDateEl = form.querySelector('[data-book-modify-date]');
        var ebayInlineStatusEl = form.querySelector('[data-book-ebay-inline-status]');
        var requestTimeoutMs = parseInt(form.getAttribute('data-book-request-timeout-ms') || '70000', 10);
        var slowLoader = document.getElementById('slow-page-loader');
        var slowLoaderTitle = slowLoader ? slowLoader.querySelector('.slow-page-loader__title') : null;
        var slowLoaderText = slowLoader ? slowLoader.querySelector('.slow-page-loader__text') : null;
        var slowLoaderTimer = null;
        var slowLoaderDefaultTitle = slowLoaderTitle ? slowLoaderTitle.innerHTML : '';
        var slowLoaderDefaultText = slowLoaderText ? slowLoaderText.innerHTML : '';

        var pendingChanges = {};
        var debounceTimer = null;
        var retryTimer = null;
        var retryDelay = 1200;
        var retryMax = 12000;
        var conflictRetryStreak = 0;
        var maxImmediateConflictRetries = 2;
        var inFlight = false;
        var activeRequestKind = '';
        var actionQueue = [];
        var applyingServer = false;
        var clientModifyDate = clientModifyDateField ? parseInt(clientModifyDateField.value || '0', 10) : 0;
        var confirmCallback = null;

        if (!requestTimeoutMs || requestTimeoutMs < 10000) {
            requestTimeoutMs = 70000;
        }

        function getCharCounterNode(input) {
            var inputId;
            if (!input || !input.id) {
                return null;
            }
            inputId = escapeSelectorValue(input.id);
            return form.querySelector('[data-char-count-for="' + inputId + '"]');
        }

        function isHeadlineCounterField(input) {
            if (!input || !input.id) {
                return false;
            }
            return input.id === 'book-author' || input.id === 'book-title' || input.id === 'book-subtitle';
        }

        function getFieldCharLength(input) {
            if (!input || typeof input.value === 'undefined' || input.value === null) {
                return 0;
            }
            return String(input.value).length;
        }

        function getHeadlineTotalCharLength() {
            var ids = ['book-author', 'book-title', 'book-subtitle'];
            var total = 0;
            var i;
            var field;
            for (i = 0; i < ids.length; i++) {
                field = form.querySelector('#' + ids[i]);
                total += getFieldCharLength(field);
            }
            return total;
        }

        function updateCharCounter(input) {
            var counter = getCharCounterNode(input);
            var fieldLength;
            var totalLength;
            if (!counter) {
                return;
            }
            fieldLength = getFieldCharLength(input);
            if (isHeadlineCounterField(input)) {
                totalLength = getHeadlineTotalCharLength();
                if (totalLength > 78) {
                    addClass(counter, 'book-char-count--danger');
                } else {
                    removeClass(counter, 'book-char-count--danger');
                }
                counter.innerHTML = String(fieldLength) + ' car. (' + String(totalLength) + ' car. totaux)';
                return;
            }
            removeClass(counter, 'book-char-count--danger');
            counter.innerHTML = String(fieldLength) + ' car.';
        }

        function showCharCounter(input) {
            var counter = getCharCounterNode(input);
            if (!counter) {
                return;
            }
            updateCharCounter(input);
            addClass(counter, 'book-char-count--visible');
        }

        function hideCharCounter(input) {
            var counter = getCharCounterNode(input);
            if (!counter) {
                return;
            }
            removeClass(counter, 'book-char-count--visible');
        }

        function initCharCounters() {
            var counterInputs = form.querySelectorAll('#book-author, #book-title, #book-subtitle, #book-description');
            var i;
            for (i = 0; i < counterInputs.length; i++) {
                (function (field) {
                    field.addEventListener('focus', function () {
                        showCharCounter(field);
                    });
                    field.addEventListener('input', function () {
                        updateCharCounter(field);
                    });
                    field.addEventListener('blur', function () {
                        hideCharCounter(field);
                    });
                    if (document.activeElement === field) {
                        showCharCounter(field);
                    }
                })(counterInputs[i]);
            }
        }

        function isEbayAction(action) {
            return action === 'ebay_add' || action === 'ebay_update' || action === 'ebay_remove';
        }

        function clearEbayInlineStatus() {
            if (!ebayInlineStatusEl) {
                return;
            }
            removeClass(ebayInlineStatusEl, 'book-ebay-inline-status--saving');
            removeClass(ebayInlineStatusEl, 'book-ebay-inline-status--saved');
            removeClass(ebayInlineStatusEl, 'book-ebay-inline-status--error');
            ebayInlineStatusEl.innerHTML = '';
        }

        function setEbayInlineStatus(type, message) {
            if (!ebayInlineStatusEl) {
                return;
            }
            clearEbayInlineStatus();
            if (!message) {
                return;
            }
            if (type === 'saving') {
                addClass(ebayInlineStatusEl, 'book-ebay-inline-status--saving');
            } else if (type === 'saved') {
                addClass(ebayInlineStatusEl, 'book-ebay-inline-status--saved');
            } else if (type === 'error') {
                addClass(ebayInlineStatusEl, 'book-ebay-inline-status--error');
            }
            ebayInlineStatusEl.innerHTML = String(message);
        }

        function setEbayActionsDisabled(disabled) {
            var nodes = form.querySelectorAll("[data-book-action='ebay_add'], [data-book-action='ebay_update'], [data-book-action='ebay_remove']");
            var i;
            for (i = 0; i < nodes.length; i++) {
                if (disabled) {
                    addClass(nodes[i], 'action-chip--disabled');
                    nodes[i].setAttribute('aria-disabled', 'true');
                } else {
                    removeClass(nodes[i], 'action-chip--disabled');
                    nodes[i].removeAttribute('aria-disabled');
                }
            }
        }

        function formatTimestamp(timestamp) {
            var ts = parseInt(timestamp || '0', 10);
            var date;
            var pad = function (v) {
                return v < 10 ? ('0' + String(v)) : String(v);
            };
            if (!ts || ts <= 0) {
                return '—';
            }
            date = new Date(ts * 1000);
            if (isNaN(date.getTime())) {
                return '—';
            }
            return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + String(date.getFullYear()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        }

        function normalizeEbayItemId(value) {
            var itemId = String(value || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
            if (itemId === '' || itemId === '-' || itemId === '—') {
                return '';
            }
            return itemId;
        }

        function updateEbaySyncNeededIndicator(bookData) {
            var itemId = '';
            var onEbay = false;
            var modifyTs = clientModifyDate;
            var ebayLastSyncTs = 0;
            var show = false;

            if (!ebaySyncNeededEl) {
                return;
            }

            itemId = normalizeEbayItemId(ebayItemLinkEl ? ebayItemLinkEl.textContent : '');
            onEbay = onEbayInput ? onEbayInput.checked : false;
            if (ebayLastSyncEl) {
                ebayLastSyncTs = parseInt(ebayLastSyncEl.getAttribute('data-book-ebay-last-sync-ts') || '0', 10);
                if (!ebayLastSyncTs || ebayLastSyncTs < 0) {
                    ebayLastSyncTs = 0;
                }
            }

            if (bookData && typeof bookData === 'object') {
                if (typeof bookData.ebayItemId !== 'undefined') {
                    itemId = normalizeEbayItemId(bookData.ebayItemId);
                }
                if (typeof bookData.onEbay !== 'undefined') {
                    onEbay = (String(bookData.onEbay) === '1');
                }
                if (typeof bookData.modifyDate !== 'undefined') {
                    modifyTs = parseInt(bookData.modifyDate || '0', 10);
                    if (!modifyTs || modifyTs < 0) {
                        modifyTs = 0;
                    }
                }
                if (typeof bookData.ebayLastSync !== 'undefined') {
                    ebayLastSyncTs = parseInt(bookData.ebayLastSync || '0', 10);
                    if (!ebayLastSyncTs || ebayLastSyncTs < 0) {
                        ebayLastSyncTs = 0;
                    }
                }
            }

            show = (itemId !== '' && onEbay && modifyTs > 0 && (ebayLastSyncTs <= 0 || modifyTs > ebayLastSyncTs));
            if (show) {
                removeClass(ebaySyncNeededEl, 'book-ebay-sync-needed--hidden');
                ebaySyncNeededEl.removeAttribute('hidden');
                return;
            }
            addClass(ebaySyncNeededEl, 'book-ebay-sync-needed--hidden');
            ebaySyncNeededEl.setAttribute('hidden', 'hidden');
        }

        function refreshEbayUi(bookData) {
            var itemId = '';
            var addBtn = form.querySelector("[data-book-action='ebay_add']");
            var delBtn = form.querySelector("[data-book-action='ebay_remove']");
            var updateBtn = form.querySelector("[data-book-action='ebay_update']");
            var ebayLastSyncTs = 0;
            if (!bookData || typeof bookData !== 'object') {
                updateEbaySyncNeededIndicator();
                return;
            }
            itemId = normalizeEbayItemId(bookData.ebayItemId);
            ebayLastSyncTs = parseInt(bookData.ebayLastSync || '0', 10);
            if (!ebayLastSyncTs || ebayLastSyncTs < 0) {
                ebayLastSyncTs = 0;
            }

            if (ebayItemLinkEl) {
                if (itemId !== '') {
                    ebayItemLinkEl.innerHTML = itemId;
                    ebayItemLinkEl.href = 'https://www.ebay.fr/itm/' + encodeURIComponent(itemId);
                    ebayItemLinkEl.target = '_blank';
                    removeClass(ebayItemLinkEl, 'book-ebay-item-link--empty');
                } else {
                    ebayItemLinkEl.innerHTML = '—';
                    ebayItemLinkEl.href = '#';
                    ebayItemLinkEl.target = '_self';
                    addClass(ebayItemLinkEl, 'book-ebay-item-link--empty');
                }
            }

            if (ebayLastSyncEl) {
                ebayLastSyncEl.innerHTML = formatTimestamp(ebayLastSyncTs);
                ebayLastSyncEl.setAttribute('data-book-ebay-last-sync-ts', String(ebayLastSyncTs));
            }

            if (addBtn) {
                addBtn.hidden = (itemId !== '');
            }
            if (delBtn) {
                delBtn.hidden = (itemId === '');
            }
            if (updateBtn) {
                updateBtn.hidden = (itemId === '');
            }
            updateEbaySyncNeededIndicator(bookData);
        }

        function refreshModifyDateUi(timestamp) {
            if (!modifyDateEl) {
                return;
            }
            modifyDateEl.innerHTML = formatTimestamp(timestamp);
            updateEbaySyncNeededIndicator();
        }

        function hideSlowLoader() {
            if (slowLoaderTimer !== null) {
                window.clearTimeout(slowLoaderTimer);
                slowLoaderTimer = null;
            }
            if (!slowLoader) {
                return;
            }
            slowLoader.classList.remove('slow-page-loader--visible');
            slowLoader.setAttribute('aria-hidden', 'true');
            removeClass(document.body, 'slow-page-loader-open');
            if (slowLoaderTitle) {
                slowLoaderTitle.innerHTML = slowLoaderDefaultTitle;
            }
            if (slowLoaderText) {
                slowLoaderText.innerHTML = slowLoaderDefaultText;
            }
        }

        function showSlowLoaderDelayed(title, text, delayMs) {
            if (!slowLoader) {
                return;
            }
            hideSlowLoader();
            slowLoaderTimer = window.setTimeout(function () {
                slowLoaderTimer = null;
                if (slowLoaderTitle && title) {
                    slowLoaderTitle.innerHTML = String(title);
                }
                if (slowLoaderText && text) {
                    slowLoaderText.innerHTML = String(text);
                }
                slowLoader.classList.add('slow-page-loader--visible');
                slowLoader.setAttribute('aria-hidden', 'false');
                addClass(document.body, 'slow-page-loader-open');
            }, delayMs > 0 ? delayMs : 700);
        }

        function getActionSavingMessage(action) {
            if (action === 'ebay_add') {
                return 'Ajout eBay en cours...';
            }
            if (action === 'ebay_update') {
                return 'Mise à jour eBay en cours...';
            }
            if (action === 'ebay_remove') {
                return 'Suppression eBay en cours...';
            }
            return 'Enregistrement...';
        }

        function initEbayPageReloadIndicators() {
            form.addEventListener('click', function (event) {
                var actionEl = findAncestorWithAttr(event.target, 'data-book-action', form);
                var action = '';
                if (!actionEl) {
                    return;
                }
                action = String(actionEl.getAttribute('data-book-action') || '');
                if (!isEbayAction(action)) {
                    return;
                }
                if (hasClass(actionEl, 'action-chip--disabled') || actionEl.getAttribute('aria-disabled') === 'true') {
                    event.preventDefault();
                    return;
                }
                setEbayActionsDisabled(true);
                setEbayInlineStatus('saving', getActionSavingMessage(action) + ' Demande envoyee...');
                showSlowLoaderDelayed('Action eBay en cours', 'Veuillez patienter, la page va se recharger automatiquement.', 500);
            });
        }

        function closeConfirmModal(confirmed) {
            var callback = confirmCallback;
            confirmCallback = null;
            if (confirmModal) {
                confirmModal.setAttribute('hidden', 'hidden');
            }
            if (typeof callback === 'function') {
                callback(confirmed === true);
            }
        }

        function openConfirmModal(message, callback) {
            if (!confirmModal) {
                callback(window.confirm(message));
                return;
            }
            // Ensure modal is attached to <body> so parent layout/overflow cannot clip it.
            if (confirmModal.parentNode !== document.body) {
                document.body.appendChild(confirmModal);
            }
            // Force viewport-relative positioning even if stylesheet cache is stale.
            confirmModal.style.position = 'fixed';
            confirmModal.style.left = '0';
            confirmModal.style.top = '0';
            confirmModal.style.right = '0';
            confirmModal.style.bottom = '0';
            confirmModal.style.zIndex = '2600';

            if (confirmMessageEl) {
                confirmMessageEl.innerHTML = String(message || '');
            }
            confirmCallback = callback;
            confirmModal.removeAttribute('hidden');
        }

        function clearStatusClasses() {
            removeClass(statusEl, 'book-save-status--idle');
            removeClass(statusEl, 'book-save-status--saving');
            removeClass(statusEl, 'book-save-status--saved');
            removeClass(statusEl, 'book-save-status--error');
        }

        function setStatus(type, message) {
            if (!statusEl) {
                return;
            }
            clearStatusClasses();
            if (type === 'saving') {
                addClass(statusEl, 'book-save-status--saving');
                statusEl.innerHTML = message || 'Enregistrement...';
                return;
            }
            if (type === 'saved') {
                addClass(statusEl, 'book-save-status--saved');
                statusEl.innerHTML = message || 'Sauvegarde';
                return;
            }
            if (type === 'error') {
                addClass(statusEl, 'book-save-status--error');
                statusEl.innerHTML = message || 'Non sauvegarde (retry...)';
                return;
            }
            addClass(statusEl, 'book-save-status--idle');
            statusEl.innerHTML = message || '';
        }

        function getEditableFieldName(el) {
            if (!el || !el.getAttribute) {
                return '';
            }
            return String(el.getAttribute('data-autosave-field') || '').trim();
        }

        function getFieldValue(el) {
            if (!el) {
                return '';
            }
            if (el.type === 'checkbox') {
                return el.checked ? '1' : '0';
            }
            return String(el.value || '');
        }

        function markFieldState(field, state) {
            var nodes = form.querySelectorAll('[data-autosave-field="' + escapeSelectorValue(field) + '"]');
            var i;
            for (i = 0; i < nodes.length; i++) {
                removeClass(nodes[i], 'book-field--dirty');
                removeClass(nodes[i], 'book-field--saving');
                removeClass(nodes[i], 'book-field--error');
                if (state === 'dirty') {
                    addClass(nodes[i], 'book-field--dirty');
                } else if (state === 'saving') {
                    addClass(nodes[i], 'book-field--saving');
                } else if (state === 'error') {
                    addClass(nodes[i], 'book-field--error');
                }
            }
        }

        function updateClientModifyDate(value) {
            var parsed = parseInt(value || '0', 10);
            if (!parsed || parsed < 0) {
                return;
            }
            clientModifyDate = parsed;
            if (clientModifyDateField) {
                clientModifyDateField.value = String(parsed);
            }
        }

        function notifyDescriptionUpdated(node, value) {
            var ev;
            if (!node) {
                return;
            }
            if (typeof window.CustomEvent === 'function') {
                ev = new window.CustomEvent('book:description-updated', {
                    bubbles: true,
                    detail: { value: String(value || '') }
                });
            } else {
                ev = document.createEvent('Event');
                ev.initEvent('book:description-updated', true, true);
                ev.detail = { value: String(value || '') };
            }
            node.dispatchEvent(ev);
        }

        function applyServerBook(bookData) {
            var field;
            var nodes;
            var i;
            var previousValue;
            var nextValue;
            if (!bookData || typeof bookData !== 'object') {
                return;
            }

            applyingServer = true;
            for (field in bookData) {
                if (!Object.prototype.hasOwnProperty.call(bookData, field)) {
                    continue;
                }
                nodes = form.querySelectorAll('[data-autosave-field="' + escapeSelectorValue(field) + '"]');
                for (i = 0; i < nodes.length; i++) {
                    if (nodes[i].type === 'checkbox') {
                        nodes[i].checked = (String(bookData[field]) === '1');
                    } else {
                        previousValue = String(nodes[i].value || '');
                        nextValue = String(bookData[field] === null ? '' : bookData[field]);
                        nodes[i].value = nextValue;
                        // Avoid forcing Quill refresh/focus after regular autosave.
                        if (
                            field === 'description' &&
                            previousValue !== nextValue &&
                            activeRequestKind !== 'save_fields'
                        ) {
                            notifyDescriptionUpdated(nodes[i], nodes[i].value);
                        }
                    }
                    markFieldState(field, '');
                }
            }
            applyingServer = false;

            if (quantityInput && typeof bookData.quantity !== 'undefined') {
                quantityInput.value = String(bookData.quantity);
            }
            if (typeof bookData.modifyDate !== 'undefined') {
                updateClientModifyDate(bookData.modifyDate);
                refreshModifyDateUi(bookData.modifyDate);
            }
            refreshEbayUi(bookData);
        }

        function applyUiBlocks(uiData) {
            var categoryBlock = document.getElementById('book-category-block');
            var catalogBlock = document.getElementById('book-catalog-block');
            if (!uiData || typeof uiData !== 'object') {
                return;
            }
            if (categoryBlock && typeof uiData.categoryHtml !== 'undefined') {
                categoryBlock.innerHTML = uiData.categoryHtml;
            }
            if (catalogBlock && typeof uiData.catalogHtml !== 'undefined') {
                catalogBlock.innerHTML = uiData.catalogHtml;
            }
        }

        function hasPendingChanges() {
            var field;
            for (field in pendingChanges) {
                if (Object.prototype.hasOwnProperty.call(pendingChanges, field)) {
                    return true;
                }
            }
            return false;
        }

        function mergeBackChanges(changes) {
            var field;
            for (field in changes) {
                if (!Object.prototype.hasOwnProperty.call(changes, field)) {
                    continue;
                }
                if (!Object.prototype.hasOwnProperty.call(pendingChanges, field)) {
                    pendingChanges[field] = changes[field];
                    markFieldState(field, 'dirty');
                }
            }
        }

        function processActionQueue() {
            var next;
            if (inFlight) {
                return;
            }
            if (actionQueue.length > 0) {
                next = actionQueue.shift();
                next();
                return;
            }
            if (hasPendingChanges()) {
                flushSaveNow();
            }
        }

        function scheduleRetry() {
            if (retryTimer !== null) {
                return;
            }
            retryTimer = window.setTimeout(function () {
                retryTimer = null;
                if (!inFlight) {
                    flushSaveNow();
                }
            }, retryDelay);
            retryDelay = Math.min(retryDelay * 2, retryMax);
        }

        function clearRetry() {
            if (retryTimer !== null) {
                window.clearTimeout(retryTimer);
                retryTimer = null;
            }
            retryDelay = 1200;
        }

        function enqueueChange(field, value, immediate) {
            if (!field) {
                return;
            }
            pendingChanges[field] = value;
            markFieldState(field, 'dirty');

            if (immediate) {
                if (debounceTimer !== null) {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = null;
                }
                flushSaveNow();
                return;
            }

            if (debounceTimer !== null) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(function () {
                debounceTimer = null;
                flushSaveNow();
            }, 700);
        }

        function flushSaveNow() {
            var payload;
            var changedFields;
            var i;

            if (!enabled || !apiUrl || inFlight || !hasPendingChanges()) {
                return;
            }

            payload = {};
            for (var field in pendingChanges) {
                if (Object.prototype.hasOwnProperty.call(pendingChanges, field)) {
                    payload[field] = pendingChanges[field];
                }
            }
            pendingChanges = {};
            changedFields = Object.keys(payload);
            if (!changedFields.length) {
                return;
            }

            for (i = 0; i < changedFields.length; i++) {
                markFieldState(changedFields[i], 'saving');
            }
            clearRetry();
            inFlight = true;
            activeRequestKind = 'save_fields';
            setStatus('saving', 'Enregistrement...');

            request(apiUrl, 'save_fields', {
                changes: payload,
                clientModifyDate: clientModifyDate
            }, function (resp, err) {
                var j;
                var shouldRetry;
                var errorMessage;
                inFlight = false;
                activeRequestKind = '';

                if (err) {
                    mergeBackChanges(payload);
                    setStatus('error', 'Non sauvegarde (retry...)');
                    for (j = 0; j < changedFields.length; j++) {
                        markFieldState(changedFields[j], 'error');
                    }
                    scheduleRetry();
                    processActionQueue();
                    return;
                }

                if (resp && resp.ok) {
                    conflictRetryStreak = 0;
                    applyServerBook(resp.book || {});
                    setStatus('saved', 'Sauvegarde');
                    clearRetry();
                    for (j = 0; j < changedFields.length; j++) {
                        markFieldState(changedFields[j], '');
                    }
                    processActionQueue();
                    return;
                }

                if (resp && resp.code === 'conflict') {
                    if (resp.book && typeof resp.book.modifyDate !== 'undefined') {
                        updateClientModifyDate(resp.book.modifyDate);
                        refreshModifyDateUi(resp.book.modifyDate);
                    }

                    // A conflict often happens when another in-page action (photo/category/catalog)
                    // updated modifyDate just before this autosave request reached the server.
                    // Keep user edits and retry after local re-sync.
                    mergeBackChanges(payload);
                    for (j = 0; j < changedFields.length; j++) {
                        markFieldState(changedFields[j], 'dirty');
                    }

                    if (conflictRetryStreak < maxImmediateConflictRetries) {
                        conflictRetryStreak += 1;
                        setStatus('saving', 'Resynchronisation en cours...');
                        processActionQueue();
                        return;
                    }

                    conflictRetryStreak = 0;
                    applyServerBook(resp.book || {});
                    applyUiBlocks(resp.ui || {});
                    setStatus('error', 'Conflit detecte: fiche rechargee depuis le serveur.');
                    scheduleRetry();
                    processActionQueue();
                    return;
                }

                conflictRetryStreak = 0;

                shouldRetry = !resp || String(resp.code || '') === 'server_error';
                errorMessage = (resp && resp.message) ? String(resp.message) : 'Sauvegarde impossible.';
                if (resp && resp.errors && resp.errors.length > 0) {
                    errorMessage = String(resp.errors[0]);
                }
                if (shouldRetry) {
                    mergeBackChanges(payload);
                    setStatus('error', 'Non sauvegarde (retry...)');
                    scheduleRetry();
                } else {
                    clearRetry();
                    setStatus('error', errorMessage);
                }
                for (j = 0; j < changedFields.length; j++) {
                    markFieldState(changedFields[j], 'error');
                }
                processActionQueue();
            });
        }

        function runAction(action, payload, successMessage) {
            var ebayAction = isEbayAction(action);
            var savingMessage = getActionSavingMessage(action);
            var execute = function () {
                inFlight = true;
                activeRequestKind = action;
                setStatus('saving', savingMessage);
                if (ebayAction) {
                    setEbayActionsDisabled(true);
                    setEbayInlineStatus('saving', savingMessage + ' Ne quittez pas la page.');
                    showSlowLoaderDelayed('Synchronisation eBay', 'Veuillez patienter, la publication peut prendre quelques secondes.', 900);
                }
                request(apiUrl, action, payload, {
                    timeoutMs: (ebayAction ? Math.max(requestTimeoutMs, 240000) : requestTimeoutMs)
                }, function (resp, err) {
                    inFlight = false;
                    activeRequestKind = '';
                    if (ebayAction) {
                        hideSlowLoader();
                        setEbayActionsDisabled(false);
                    }

                    if (err) {
                        setStatus('error', err.message || 'Action impossible.');
                        if (ebayAction) {
                            setEbayInlineStatus('error', err.message || 'Action eBay impossible.');
                        }
                        processActionQueue();
                        return;
                    }

                    if (resp && resp.ok) {
                        applyServerBook(resp.book || {});
                        applyUiBlocks(resp.ui || {});
                        setStatus('saved', resp.message || successMessage || 'Sauvegarde');
                        if (ebayAction) {
                            setEbayInlineStatus('saved', resp.message || successMessage || 'Action eBay terminée.');
                        }
                        clearRetry();
                        processActionQueue();
                        return;
                    }

                    setStatus('error', (resp && resp.message) ? resp.message : 'Action impossible.');
                    if (ebayAction) {
                        setEbayInlineStatus('error', (resp && resp.message) ? resp.message : 'Action eBay impossible.');
                    }
                    processActionQueue();
                });
            };

            if (inFlight) {
                actionQueue.push(execute);
            } else {
                execute();
            }
        }

        function initFieldListeners() {
            form.addEventListener('input', function (event) {
                var target = event.target;
                var field = getEditableFieldName(target);
                var tagName;

                if (applyingServer || !field) {
                    return;
                }
                tagName = String(target.tagName || '').toLowerCase();
                if (tagName !== 'input' && tagName !== 'textarea') {
                    return;
                }
                if (target.type === 'checkbox' || target.readOnly) {
                    return;
                }
                enqueueChange(field, getFieldValue(target), false);
            });

            form.addEventListener('blur', function (event) {
                var target = event.target;
                var field = getEditableFieldName(target);
                var tagName;

                if (applyingServer || !field) {
                    return;
                }
                tagName = String(target.tagName || '').toLowerCase();
                if ((tagName === 'input' && target.type !== 'checkbox') || tagName === 'textarea') {
                    enqueueChange(field, getFieldValue(target), true);
                }
            }, true);

            form.addEventListener('change', function (event) {
                var target = event.target;
                var field = getEditableFieldName(target);
                var tagName;

                if (applyingServer) {
                    return;
                }

                if (hasClass(target, 'book-category-add')) {
                    if (target.value) {
                        runAction('category_add', { categoryId: target.value }, 'Rubrique ajoutee.');
                        target.value = '';
                    }
                    return;
                }

                if (hasClass(target, 'book-catalog-add')) {
                    if (target.value) {
                        runAction('catalog_add', { addInCatalog: target.value }, 'Catalogue ajoute.');
                        target.value = '';
                    }
                    return;
                }

                if (!field) {
                    return;
                }
                tagName = String(target.tagName || '').toLowerCase();
                if (tagName === 'select' || target.type === 'checkbox') {
                    enqueueChange(field, getFieldValue(target), true);
                }
            });
        }

        function initActionListeners() {
            document.addEventListener('click', function (event) {
                var confirmOkEl = findAncestorWithAttr(event.target, 'data-book-confirm-ok', document.body);
                var confirmCancelEl = findAncestorWithAttr(event.target, 'data-book-confirm-cancel', document.body);

                if (confirmOkEl) {
                    event.preventDefault();
                    closeConfirmModal(true);
                    return;
                }

                if (confirmCancelEl) {
                    event.preventDefault();
                    closeConfirmModal(false);
                }
            });

            form.addEventListener('click', function (event) {
                var actionEl = findAncestorWithAttr(event.target, 'data-book-action', form);
                var action;
                var delta;
                var categoryId;
                var catalogId;

                if (!actionEl) {
                    return;
                }
                action = String(actionEl.getAttribute('data-book-action') || '');
                if (hasClass(actionEl, 'action-chip--disabled')) {
                    event.preventDefault();
                    return;
                }

                if (action === 'qty_delta') {
                    delta = parseInt(actionEl.getAttribute('data-delta') || '0', 10);
                    if (!delta) {
                        return;
                    }
                    event.preventDefault();
                    runAction('qty_delta', { delta: delta }, 'Quantite mise a jour.');
                    return;
                }

                if (action === 'category_del') {
                    categoryId = parseInt(actionEl.getAttribute('data-category-id') || '0', 10);
                    if (!categoryId) {
                        return;
                    }
                    event.preventDefault();
                    openConfirmModal('Supprimer cette rubrique de l ouvrage ?', function (confirmed) {
                        if (!confirmed) {
                            return;
                        }
                        runAction('category_del', { categoryId: categoryId }, 'Rubrique supprimee.');
                    });
                    return;
                }

                if (action === 'catalog_del') {
                    catalogId = parseInt(actionEl.getAttribute('data-catalog-id') || '0', 10);
                    if (!catalogId) {
                        return;
                    }
                    event.preventDefault();
                    if (!window.confirm('Supprimer cet ouvrage du catalogue ?')) {
                        return;
                    }
                    runAction('catalog_del', { catalogId: catalogId }, 'Catalogue mis a jour.');
                    return;
                }

                if (action === 'ebay_add') {
                    event.preventDefault();
                    runAction('ebay_add', {}, 'Ouvrage ajoute sur eBay.');
                    return;
                }

                if (action === 'ebay_update') {
                    event.preventDefault();
                    runAction('ebay_update', {}, 'Annonce eBay mise a jour.');
                    return;
                }

                if (action === 'ebay_remove') {
                    event.preventDefault();
                    openConfirmModal('Retirer cet ouvrage de eBay maintenant ?', function (confirmed) {
                        if (!confirmed) {
                            return;
                        }
                        runAction('ebay_remove', {}, 'Annonce eBay retiree.');
                    });
                }
            });

            if (confirmModal) {
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !confirmModal.hasAttribute('hidden')) {
                        closeConfirmModal(false);
                    }
                });
            }
        }

        initCharCounters();

        if (!enabled || !apiUrl || !idField || parseInt(idField.value || '0', 10) <= 0) {
            initEbayPageReloadIndicators();
            return;
        }

        if (manualSubmit) {
            addClass(manualSubmit, 'submit--secondary');
            manualSubmit.setAttribute('title', 'Sauvegarde manuelle (fallback)');
        }

        setStatus('saved', statusEl && statusEl.innerHTML ? statusEl.innerHTML : 'Sauvegarde automatique active.');
        if (clientModifyDate > 0) {
            refreshModifyDateUi(clientModifyDate);
        }
        updateEbaySyncNeededIndicator();
        initFieldListeners();
        initActionListeners();
        form.addEventListener('book:modify-date', function (event) {
            var detail = event && event.detail ? event.detail : {};
            if (typeof detail.modifyDate === 'undefined') {
                return;
            }
            updateClientModifyDate(detail.modifyDate);
            refreshModifyDateUi(detail.modifyDate);
        });

        form.addEventListener('submit', function () {
            if (activeRequestKind === 'save_fields' || hasPendingChanges()) {
                flushSaveNow();
            }
        });
    }

    function initAll() {
        var forms = document.querySelectorAll('form.book-form');
        var i;
        for (i = 0; i < forms.length; i++) {
            initBookAutosave(forms[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})(window, document);

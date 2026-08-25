(function (window, document) {
    'use strict';

    window.BookPhotoManagerActive = false;

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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseJson(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {
                ok: false,
                message: 'Reponse JSON invalide.',
                photos: [],
                errors: ['Impossible de parser la reponse du serveur.']
            };
        }
    }

    function toUrlEncoded(data) {
        var parts = [];
        var key;
        for (key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
        }
        return parts.join('&');
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

    function findAncestorWithClass(el, className, root) {
        var current = el;
        while (current && current !== root) {
            if ((' ' + current.className + ' ').indexOf(' ' + className + ' ') !== -1) {
                return current;
            }
            current = current.parentNode;
        }
        return null;
    }

    function initPhotoManager(manager) {
        var enabled = String(manager.getAttribute('data-photo-enabled') || '0') === '1';
        var mode = String(manager.getAttribute('data-photo-mode') || 'modify');
        var isCreateMode = (mode === 'create');
        var apiUrl = String(manager.getAttribute('data-photo-api-url') || '');
        var maxFiles = parseInt(manager.getAttribute('data-photo-max-files') || '20', 10);
        var maxSizeMb = parseInt(manager.getAttribute('data-photo-max-size-mb') || '10', 10);
        var maxSizeBytes = maxSizeMb * 1024 * 1024;

        var iconRotateLeft = String(manager.getAttribute('data-icon-rotate-left') || '');
        var iconRotateRight = String(manager.getAttribute('data-icon-rotate-right') || '');
        var iconTrash = String(manager.getAttribute('data-icon-trash') || '');
        var iconZoom = String(manager.getAttribute('data-icon-zoom') || '');
        var iconDrag = String(manager.getAttribute('data-icon-drag') || '');

        var status = manager.querySelector('.photo-status');
        var errors = manager.querySelector('.photo-errors');
        var gallery = manager.querySelector('.photo-gallery');
        var dropzone = manager.querySelector('.photo-uploader__dropzone');
        var browseBtn = manager.querySelector('.photo-uploader__browse');
        var input = manager.querySelector('.photo-uploader__input');
        var modal = manager.querySelector('.photo-modal');
        var modalImage = manager.querySelector('.photo-modal__image');
        var modalCaption = manager.querySelector('.photo-modal__caption');
        var bookForm = null;
        var modifyDateEl = null;
        var clientModifyDateField = null;
        var confirmModal = null;
        var confirmMessage = null;
        var confirmAcceptCallback = null;

        var sortable = null;
        var photosById = {};
        var localFiles = [];
        var localPreviewUrls = [];

        // Move modal to <body> to avoid clipping/positioning issues caused by
        // transformed or overflow-hidden ancestors in legacy layout containers.
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }

        function findBookFormRoot() {
            var current = manager;
            while (current && current !== document.body) {
                if (String(current.tagName || '').toLowerCase() === 'form' && hasClass(current, 'book-form')) {
                    return current;
                }
                current = current.parentNode;
            }
            return null;
        }

        bookForm = findBookFormRoot();
        modifyDateEl = bookForm ? bookForm.querySelector('[data-book-modify-date]') : null;
        clientModifyDateField = bookForm ? bookForm.querySelector("input[name='clientModifyDate']") : null;

        function hasClass(el, className) {
            if (!el) {
                return false;
            }
            if (el.classList) {
                return el.classList.contains(className);
            }
            return (' ' + el.className + ' ').indexOf(' ' + className + ' ') !== -1;
        }

        function syncBodyModalLock() {
            var viewerOpen = hasClass(modal, 'photo-modal--open');
            var confirmOpen = hasClass(confirmModal, 'photo-confirm-modal--open');
            if (viewerOpen || confirmOpen) {
                addClass(document.body, 'photo-modal-open');
                return;
            }
            removeClass(document.body, 'photo-modal-open');
        }

        function setStatus(type, message) {
            if (!status) {
                return;
            }
            removeClass(status, 'photo-status--success');
            removeClass(status, 'photo-status--error');
            removeClass(status, 'photo-status--info');

            if (!message) {
                status.innerHTML = '';
                return;
            }
            if (type === 'success') {
                addClass(status, 'photo-status--success');
            } else if (type === 'error') {
                addClass(status, 'photo-status--error');
            } else {
                addClass(status, 'photo-status--info');
            }
            status.innerHTML = escapeHtml(message);
        }

        function setErrors(list) {
            var html = '';
            var i;
            if (!errors) {
                return;
            }
            if (!list || !list.length) {
                errors.innerHTML = '';
                return;
            }
            html += '<ul class="photo-errors__list">';
            for (i = 0; i < list.length; i++) {
                html += '<li>' + escapeHtml(list[i]) + '</li>';
            }
            html += '</ul>';
            errors.innerHTML = html;
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

        function syncBookModifyDate(resp) {
            var modifyDate = 0;
            var ev;
            if (!resp || !resp.book || typeof resp.book !== 'object') {
                return;
            }
            modifyDate = parseInt(resp.book.modifyDate || '0', 10);
            if (!modifyDate || modifyDate <= 0) {
                return;
            }
            if (modifyDateEl) {
                modifyDateEl.innerHTML = formatTimestamp(modifyDate);
            }
            if (clientModifyDateField) {
                clientModifyDateField.value = String(modifyDate);
            }
            if (bookForm && typeof window.CustomEvent === 'function') {
                ev = new window.CustomEvent('book:modify-date', {
                    bubbles: true,
                    detail: { modifyDate: modifyDate }
                });
                bookForm.dispatchEvent(ev);
            }
        }

        function endpoint(action) {
            var glue = apiUrl.indexOf('?') === -1 ? '?' : '&';
            return apiUrl + glue + 'photo_action=' + encodeURIComponent(action);
        }

        function request(action, payload, callback) {
            var xhr = new XMLHttpRequest();
            var url = endpoint(action);
            var isFormData = (typeof FormData !== 'undefined') && payload instanceof FormData;
            var body = null;

            xhr.open('POST', url, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (xhr.status >= 200 && xhr.status < 300) {
                    callback(parseJson(xhr.responseText));
                    return;
                }
                callback({
                    ok: false,
                    message: 'HTTP ' + xhr.status + ' - action photo impossible.',
                    photos: [],
                    errors: ['HTTP ' + xhr.status + ' - action photo impossible.']
                });
            };

            if (isFormData) {
                body = payload;
            } else {
                body = toUrlEncoded(payload || {});
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            }
            xhr.send(body);
        }

        function clearLocalPreviewUrls() {
            var i;
            for (i = 0; i < localPreviewUrls.length; i++) {
                if (localPreviewUrls[i]) {
                    try {
                        window.URL.revokeObjectURL(localPreviewUrls[i]);
                    } catch (e) {
                        // no-op
                    }
                }
            }
            localPreviewUrls = [];
        }

        function syncInputWithLocalFiles() {
            var dt;
            var i;
            if (!input) {
                return;
            }
            if (typeof window.DataTransfer === 'undefined') {
                return;
            }
            dt = new window.DataTransfer();
            for (i = 0; i < localFiles.length; i++) {
                dt.items.add(localFiles[i]);
            }
            input.files = dt.files;
        }

        function renderLocalFiles() {
            var i;
            var file;
            var url;
            var html = '';
            if (!gallery) {
                return;
            }

            clearLocalPreviewUrls();
            photosById = {};

            if (!localFiles.length) {
                gallery.innerHTML = '<div class=\"photo-gallery__empty\">Aucune photo selectionnee pour cette nouvelle fiche.</div>';
                return;
            }

            for (i = 0; i < localFiles.length; i++) {
                file = localFiles[i];
                url = window.URL.createObjectURL(file);
                localPreviewUrls.push(url);
                html += '' +
                    '<article class=\"photo-card\" data-photo-local-index=\"' + i + '\">' +
                        '<div class=\"photo-card__header\">' +
                            '<span class=\"photo-card__primary\">' + (i + 1) + '</span>' +
                            '<span class=\"photo-card__meta\">Temporaire</span>' +
                        '</div>' +
                        '<button type=\"button\" class=\"photo-card__preview\" data-photo-local-open=\"' + i + '\">' +
                            '<img src=\"' + escapeHtml(url) + '\" alt=\"' + escapeHtml(file.name || 'Photo') + '\">' +
                        '</button>' +
                        '<div class=\"photo-card__toolbar\">' +
                            '<button type=\"button\" class=\"photo-btn\" data-photo-local-open=\"' + i + '\" title=\"Voir en grand\"><img class=\"photo-btn__icon\" src=\"' + escapeHtml(iconZoom) + '\" alt=\"Voir en grand\"></button>' +
                            '<span></span>' +
                            '<button type=\"button\" class=\"photo-btn photo-btn--danger\" data-photo-local-delete=\"' + i + '\" title=\"Retirer\"><img class=\"photo-btn__icon\" src=\"' + escapeHtml(iconTrash) + '\" alt=\"Retirer\"></button>' +
                            '<span></span>' +
                        '</div>' +
                    '</article>';
            }
            gallery.innerHTML = html;
        }

        function updateLocalFilesFromSelection(selectedFiles) {
            var i;
            var file;
            var ext;
            var dot;
            var name;
            var nextFiles = localFiles.slice(0);
            var validationErrors = [];

            if (!selectedFiles || !selectedFiles.length) {
                return;
            }

            for (i = 0; i < selectedFiles.length; i++) {
                file = selectedFiles[i];
                name = String(file.name || 'fichier');
                dot = name.lastIndexOf('.');
                ext = dot >= 0 ? name.substring(dot + 1).toLowerCase() : '';

                if (!(ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif')) {
                    validationErrors.push('Format non autorise: ' + name);
                    continue;
                }
                if (file.size > maxSizeBytes) {
                    validationErrors.push('Fichier trop lourd (' + maxSizeMb + ' Mo max): ' + name);
                    continue;
                }
                nextFiles.push(file);
            }

            if (nextFiles.length > maxFiles) {
                validationErrors.push('Vous ne pouvez pas selectionner plus de ' + maxFiles + ' photos.');
                nextFiles = nextFiles.slice(0, maxFiles);
            }

            localFiles = nextFiles;
            syncInputWithLocalFiles();
            renderLocalFiles();

            if (localFiles.length > 0) {
                setStatus('success', localFiles.length + " photo(s) prete(s) pour l'enregistrement.");
            } else if (validationErrors.length > 0) {
                setStatus('error', 'Aucune photo valide selectionnee.');
            } else {
                setStatus('', '');
            }
            setErrors(validationErrors);
        }

        function removeLocalFileAt(index) {
            index = parseInt(index, 10);
            if (isNaN(index) || index < 0 || index >= localFiles.length) {
                return;
            }
            localFiles.splice(index, 1);
            syncInputWithLocalFiles();
            renderLocalFiles();
            if (localFiles.length > 0) {
                setStatus('info', localFiles.length + ' photo(s) conservee(s).');
            } else {
                setStatus('', '');
            }
            setErrors([]);
        }

        function renderPhotos(photos) {
            var list = photos || [];
            var html = '';
            var i;
            var p;

            photosById = {};
            for (i = 0; i < list.length; i++) {
                photosById[String(list[i].id)] = list[i];
            }

            if (!gallery) {
                return;
            }

            if (!list.length) {
                gallery.innerHTML = '<div class="photo-gallery__empty">Aucune photo sur cet ouvrage.</div>';
                return;
            }

            for (i = 0; i < list.length; i++) {
                p = list[i];
                var orderLabel = p.isPrimary ? 'Principale' : String(p.sortOrder || (i + 1));
                html += '' +
                    '<article class="photo-card" data-photo-id="' + escapeHtml(p.id) + '">' +
                        '<div class="photo-card__header">' +
                            '<span class="photo-card__primary">' + escapeHtml(orderLabel) + '</span>' +
                            '<button type="button" class="photo-btn photo-btn--drag photo-drag-handle" title="Deplacer">' +
                                '<img class="photo-btn__icon" src="' + escapeHtml(iconDrag) + '" alt="Deplacer">' +
                            '</button>' +
                        '</div>' +
                        '<button type="button" class="photo-card__preview" data-photo-open="' + escapeHtml(p.id) + '" data-photo-original="' + escapeHtml(p.original || p.medium || p.thumb || '') + '" data-photo-medium="' + escapeHtml(p.medium || p.original || p.thumb || '') + '" data-photo-description="' + escapeHtml(p.description || '') + '">' +
                            '<img src="' + escapeHtml(p.thumb || p.medium || p.original || '') + '" alt="' + escapeHtml(p.description || '') + '">' +
                        '</button>' +
                        '<div class="photo-card__toolbar">' +
                            '<button type="button" class="photo-btn" data-photo-action="rotate_left" data-photo-id="' + escapeHtml(p.id) + '" title="Rotation gauche"><img class="photo-btn__icon" src="' + escapeHtml(iconRotateLeft) + '" alt="Rotation gauche"></button>' +
                            '<button type="button" class="photo-btn" data-photo-action="zoom" data-photo-id="' + escapeHtml(p.id) + '" data-photo-original="' + escapeHtml(p.original || p.medium || p.thumb || '') + '" data-photo-medium="' + escapeHtml(p.medium || p.original || p.thumb || '') + '" data-photo-description="' + escapeHtml(p.description || '') + '" title="Voir en grand"><img class="photo-btn__icon" src="' + escapeHtml(iconZoom) + '" alt="Voir en grand"></button>' +
                            '<button type="button" class="photo-btn photo-btn--danger" data-photo-action="delete" data-photo-id="' + escapeHtml(p.id) + '" title="Suppression"><img class="photo-btn__icon" src="' + escapeHtml(iconTrash) + '" alt="Suppression"></button>' +
                            '<button type="button" class="photo-btn" data-photo-action="rotate_right" data-photo-id="' + escapeHtml(p.id) + '" title="Rotation droite"><img class="photo-btn__icon" src="' + escapeHtml(iconRotateRight) + '" alt="Rotation droite"></button>' +
                        '</div>' +
                    '</article>';
            }
            gallery.innerHTML = html;
        }

        function openViewer(photo) {
            var src = photo ? (photo.original || photo.medium || photo.thumb || '') : '';
            var desc = photo ? (photo.description || '') : '';
            if (!src || !modal || !modalImage) {
                return;
            }
            modalImage.setAttribute('src', src);
            modalImage.setAttribute('alt', desc || 'Photo ouvrage');
            if (modalCaption) {
                modalCaption.innerHTML = escapeHtml(desc);
            }
            modal.removeAttribute('hidden');
            addClass(modal, 'photo-modal--open');
            modal.scrollTop = 0;
            syncBodyModalLock();
        }

        function openViewerFromElement(el) {
            var src = String(el.getAttribute('data-photo-original') || '');
            if (!src) {
                src = String(el.getAttribute('data-photo-medium') || '');
            }
            var desc = String(el.getAttribute('data-photo-description') || '');
            var card;
            var img;

            if (!src) {
                card = findAncestorWithClass(el, 'photo-card', gallery);
                if (card) {
                    img = card.querySelector('.photo-card__preview img');
                    if (img) {
                        src = String(img.getAttribute('src') || '');
                        if (!desc) {
                            desc = String(img.getAttribute('alt') || '');
                        }
                    }
                }
            }
            if (!src) {
                return;
            }
            openViewer({ original: src, description: desc });
        }

        function openViewerById(id, triggerEl) {
            var photo = photosById[String(id)] || null;
            if (photo) {
                openViewer(photo);
                return;
            }
            if (triggerEl) {
                openViewerFromElement(triggerEl);
            }
        }

        function closeViewer() {
            if (!modal) {
                return;
            }
            modal.setAttribute('hidden', 'hidden');
            removeClass(modal, 'photo-modal--open');
            if (modalImage) {
                modalImage.setAttribute('src', '');
            }
            if (modalCaption) {
                modalCaption.innerHTML = '';
            }
            syncBodyModalLock();
        }

        function ensureConfirmModal() {
            if (confirmModal) {
                return;
            }
            confirmModal = document.createElement('div');
            confirmModal.className = 'photo-confirm-modal';
            confirmModal.setAttribute('hidden', 'hidden');
            confirmModal.innerHTML = '' +
                '<div class="photo-confirm-modal__backdrop" data-photo-confirm-close="1"></div>' +
                '<div class="photo-confirm-modal__dialog" role="dialog" aria-modal="true" aria-label="Confirmation suppression">' +
                    '<div class="photo-confirm-modal__title">Supprimer la photo ?</div>' +
                    '<div class="photo-confirm-modal__message"></div>' +
                    '<div class="photo-confirm-modal__actions">' +
                        '<button type="button" class="photo-confirm-modal__btn photo-confirm-modal__btn--cancel" data-photo-confirm-close="1">Annuler</button>' +
                        '<button type="button" class="photo-confirm-modal__btn photo-confirm-modal__btn--danger" data-photo-confirm-accept="1">Supprimer</button>' +
                    '</div>' +
                '</div>';
            document.body.appendChild(confirmModal);
            confirmMessage = confirmModal.querySelector('.photo-confirm-modal__message');

            confirmModal.addEventListener('click', function (event) {
                var closeEl = findAncestorWithAttr(event.target, 'data-photo-confirm-close', confirmModal);
                var acceptEl = findAncestorWithAttr(event.target, 'data-photo-confirm-accept', confirmModal);
                if (acceptEl) {
                    event.preventDefault();
                    closeConfirm(true);
                    return;
                }
                if (closeEl) {
                    event.preventDefault();
                    closeConfirm(false);
                }
            });
        }

        function openConfirm(message, onAccept) {
            ensureConfirmModal();
            confirmAcceptCallback = (typeof onAccept === 'function') ? onAccept : null;
            if (confirmMessage) {
                confirmMessage.innerHTML = escapeHtml(message || 'Cette action est irreversible.');
            }
            confirmModal.removeAttribute('hidden');
            addClass(confirmModal, 'photo-confirm-modal--open');
            confirmModal.scrollTop = 0;
            syncBodyModalLock();
        }

        function closeConfirm(accepted) {
            var callback = confirmAcceptCallback;
            confirmAcceptCallback = null;
            if (!confirmModal) {
                return;
            }
            confirmModal.setAttribute('hidden', 'hidden');
            removeClass(confirmModal, 'photo-confirm-modal--open');
            syncBodyModalLock();
            if (accepted && callback) {
                callback();
            }
        }

        function ensureSortable() {
            if (!window.Sortable || sortable || !gallery || !enabled) {
                return;
            }
            sortable = new window.Sortable(gallery, {
                animation: 150,
                draggable: '.photo-card',
                handle: '.photo-drag-handle',
                ghostClass: 'photo-card--ghost',
                chosenClass: 'photo-card--chosen',
                onEnd: function () {
                    var cards = gallery.querySelectorAll('.photo-card');
                    var ids = [];
                    var i;
                    for (i = 0; i < cards.length; i++) {
                        ids.push(cards[i].getAttribute('data-photo-id'));
                    }
                    request('reorder', { photo_ids: ids.join(',') }, function (resp) {
                        syncBookModifyDate(resp);
                        if (resp.ok) {
                            setStatus('success', resp.message || 'Ordre sauvegarde.');
                            setErrors([]);
                            renderPhotos(resp.photos || []);
                            return;
                        }
                        setStatus('error', resp.message || 'Impossible de sauvegarder l ordre.');
                        setErrors(resp.errors || []);
                        loadPhotos();
                    });
                }
            });
        }

        function loadPhotos() {
            request('list', {}, function (resp) {
                syncBookModifyDate(resp);
                if (!resp.ok) {
                    setStatus('error', resp.message || 'Impossible de charger les photos.');
                    setErrors(resp.errors || []);
                    return;
                }
                setStatus('', '');
                setErrors([]);
                renderPhotos(resp.photos || []);
                ensureSortable();
            });
        }

        function uploadFiles(files) {
            var formData;
            var i;
            var file;
            var name;
            var dot;
            var ext;
            var validationErrors = [];

            if (!files || !files.length) {
                return;
            }
            if (files.length > maxFiles) {
                setStatus('error', 'Limite depassee: ' + maxFiles + ' fichiers maximum.');
                setErrors(['Vous ne pouvez pas importer plus de ' + maxFiles + ' photos en un lot.']);
                return;
            }

            formData = new FormData();
            for (i = 0; i < files.length; i++) {
                file = files[i];
                name = String(file.name || 'fichier');
                dot = name.lastIndexOf('.');
                ext = dot >= 0 ? name.substring(dot + 1).toLowerCase() : '';

                if (!(ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif')) {
                    validationErrors.push('Format non autorise: ' + name);
                    continue;
                }
                if (file.size > maxSizeBytes) {
                    validationErrors.push('Fichier trop lourd (' + maxSizeMb + ' Mo max): ' + name);
                    continue;
                }
                formData.append('photos[]', file, file.name);
            }

            if (validationErrors.length) {
                setStatus('error', 'Certains fichiers sont invalides.');
                setErrors(validationErrors);
                return;
            }

            addClass(manager, 'photo-manager--busy');
            setStatus('info', 'Import en cours...');
            setErrors([]);

            request('upload', formData, function (resp) {
                removeClass(manager, 'photo-manager--busy');
                syncBookModifyDate(resp);
                if (resp.ok) {
                    setStatus('success', resp.message || 'Import termine.');
                } else {
                    setStatus('error', resp.message || 'Import incomplet.');
                }
                setErrors(resp.errors || []);
                renderPhotos(resp.photos || []);
                ensureSortable();
            });
        }

        if (!enabled) {
            addClass(manager, 'photo-manager--disabled');
            if (browseBtn) {
                browseBtn.setAttribute('disabled', 'disabled');
                addClass(browseBtn, 'photo-uploader__browse--disabled');
                browseBtn.innerHTML = 'Photos indisponibles';
            }
            return;
        }

        if (!isCreateMode && !apiUrl) {
            addClass(manager, 'photo-manager--disabled');
            return;
        }

        addClass(manager, 'photo-manager--enabled');
        window.BookPhotoManagerActive = true;

        if (browseBtn && input) {
            browseBtn.addEventListener('click', function (event) {
                if (event.defaultPrevented) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (isCreateMode) {
                    input.value = '';
                }
                input.click();
            });
        }

        if (input) {
            input.addEventListener('change', function () {
                if (isCreateMode) {
                    updateLocalFilesFromSelection(input.files || []);
                    return;
                }
                uploadFiles(input.files || []);
                input.value = '';
            });
        }

        if (dropzone) {
            dropzone.addEventListener('dragenter', function (event) {
                event.preventDefault();
                addClass(dropzone, 'photo-uploader__dropzone--active');
            });
            dropzone.addEventListener('dragover', function (event) {
                event.preventDefault();
                addClass(dropzone, 'photo-uploader__dropzone--active');
            });
            dropzone.addEventListener('dragleave', function (event) {
                event.preventDefault();
                removeClass(dropzone, 'photo-uploader__dropzone--active');
            });
            dropzone.addEventListener('drop', function (event) {
                var dt = event.dataTransfer || (event.originalEvent ? event.originalEvent.dataTransfer : null);
                event.preventDefault();
                removeClass(dropzone, 'photo-uploader__dropzone--active');
                if (dt && dt.files) {
                    if (isCreateMode) {
                        updateLocalFilesFromSelection(dt.files);
                    } else {
                        uploadFiles(dt.files);
                    }
                }
            });
        }

        if (gallery) {
            gallery.addEventListener('click', function (event) {
                var actionEl = findAncestorWithAttr(event.target, 'data-photo-action', gallery);
                var localOpenEl;
                var localDeleteEl;
                var localIndex;
                var openEl;
                var action;
                var id;

                if (isCreateMode) {
                    localOpenEl = findAncestorWithAttr(event.target, 'data-photo-local-open', gallery);
                    if (localOpenEl) {
                        localIndex = parseInt(localOpenEl.getAttribute('data-photo-local-open') || '-1', 10);
                        event.preventDefault();
                        if (!isNaN(localIndex) && localIndex >= 0 && localIndex < localFiles.length) {
                            openViewer({
                                original: localPreviewUrls[localIndex],
                                description: localFiles[localIndex].name || 'Photo'
                            });
                        }
                        return;
                    }

                    localDeleteEl = findAncestorWithAttr(event.target, 'data-photo-local-delete', gallery);
                    if (localDeleteEl) {
                        localIndex = parseInt(localDeleteEl.getAttribute('data-photo-local-delete') || '-1', 10);
                        event.preventDefault();
                        removeLocalFileAt(localIndex);
                        return;
                    }
                }

                if (actionEl) {
                    action = String(actionEl.getAttribute('data-photo-action') || '');
                    id = parseInt(actionEl.getAttribute('data-photo-id') || '0', 10);
                    event.preventDefault();

                    if (action === 'rotate_left' && id > 0) {
                        setStatus('info', 'Rotation en cours...');
                        setErrors([]);
                        request('rotate_left', { photo_id: id }, function (resp) {
                            syncBookModifyDate(resp);
                            if (resp.ok) {
                                setStatus('success', resp.message || 'Photo tournee.');
                            } else {
                                setStatus('error', resp.message || 'Rotation impossible.');
                            }
                            setErrors(resp.errors || []);
                            renderPhotos(resp.photos || []);
                        });
                        return;
                    }

                    if (action === 'rotate_right' && id > 0) {
                        setStatus('info', 'Rotation en cours...');
                        setErrors([]);
                        request('rotate_right', { photo_id: id }, function (resp) {
                            syncBookModifyDate(resp);
                            if (resp.ok) {
                                setStatus('success', resp.message || 'Photo tournee.');
                            } else {
                                setStatus('error', resp.message || 'Rotation impossible.');
                            }
                            setErrors(resp.errors || []);
                            renderPhotos(resp.photos || []);
                        });
                        return;
                    }

                    if (action === 'delete' && id > 0) {
                        openConfirm('Confirmez-vous la suppression definitive de cette photo ?', function () {
                            setStatus('info', 'Suppression en cours...');
                            setErrors([]);
                            request('delete', { photo_id: id }, function (resp) {
                                syncBookModifyDate(resp);
                                if (resp.ok) {
                                    setStatus('success', resp.message || 'Photo supprimee.');
                                } else {
                                    setStatus('error', resp.message || 'Suppression impossible.');
                                }
                                setErrors(resp.errors || []);
                                renderPhotos(resp.photos || []);
                            });
                        });
                        return;
                    }

                    if (action === 'zoom') {
                        openViewerById(id, actionEl);
                        return;
                    }
                }

                openEl = findAncestorWithAttr(event.target, 'data-photo-open', gallery);
                if (openEl) {
                    id = parseInt(openEl.getAttribute('data-photo-open') || '0', 10);
                    event.preventDefault();
                    openViewerById(id, openEl);
                }
            });
        }

        if (modal) {
            modal.addEventListener('click', function (event) {
                var closer = findAncestorWithAttr(event.target, 'data-photo-close', modal);
                if (closer) {
                    event.preventDefault();
                    closeViewer();
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (hasClass(confirmModal, 'photo-confirm-modal--open')) {
                    closeConfirm(false);
                    return;
                }
                closeViewer();
            }
        });

        if (isCreateMode) {
            renderLocalFiles();
            return;
        }

        loadPhotos();
    }

    function initAll() {
        var managers = document.querySelectorAll('.photo-manager');
        var i;
        for (i = 0; i < managers.length; i++) {
            initPhotoManager(managers[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})(window, document);

(function (window, document) {
    'use strict';

    var QUILL_CSS_URL = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
    var QUILL_JS_URL = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';

    function hasClass(el, className) {
        if (!el) {
            return false;
        }
        if (el.classList) {
            return el.classList.contains(className);
        }
        return (' ' + el.className + ' ').indexOf(' ' + className + ' ') !== -1;
    }

    function addClass(el, className) {
        if (!el) {
            return;
        }
        if (el.classList) {
            el.classList.add(className);
            return;
        }
        if (!hasClass(el, className)) {
            el.className += (el.className ? ' ' : '') + className;
        }
    }

    function dispatchSimpleEvent(el, name) {
        var ev;
        if (!el) {
            return;
        }
        if (typeof window.Event === 'function') {
            ev = new window.Event(name, { bubbles: true });
        } else {
            ev = document.createEvent('Event');
            ev.initEvent(name, true, true);
        }
        el.dispatchEvent(ev);
    }

    function decodeEntities(value) {
        var el;
        if (!value) {
            return '';
        }
        el = document.createElement('textarea');
        el.innerHTML = String(value);
        return String(el.value || '');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function looksLikeHtml(value) {
        return /<\/?[a-z][^>]*>/i.test(String(value || ''));
    }

    function plainTextToHtml(value) {
        var text = String(value || '');
        var blocks;
        var i;
        var out = '';

        text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        if (text.trim() === '') {
            return '';
        }

        blocks = text.split(/\n{2,}/);
        for (i = 0; i < blocks.length; i++) {
            out += '<p>' + escapeHtml(blocks[i]).replace(/\n/g, '<br>') + '</p>';
        }
        return out;
    }

    function normalizeInputToHtml(value) {
        var decoded = decodeEntities(value);
        if (decoded.trim() === '') {
            return '';
        }
        if (looksLikeHtml(decoded)) {
            return decoded;
        }
        return plainTextToHtml(decoded);
    }

    function ensureQuillCss() {
        var id = 'book-description-quill-css';
        var css = document.getElementById(id);
        if (css) {
            return;
        }
        css = document.createElement('link');
        css.id = id;
        css.rel = 'stylesheet';
        css.href = QUILL_CSS_URL;
        document.head.appendChild(css);
    }

    function loadQuillScript(callback) {
        var id = 'book-description-quill-js';
        var script = document.getElementById(id);

        if (window.Quill) {
            callback(true);
            return;
        }

        if (script) {
            script.addEventListener('load', function () { callback(true); });
            script.addEventListener('error', function () { callback(false); });
            return;
        }

        script = document.createElement('script');
        script.id = id;
        script.src = QUILL_JS_URL;
        script.async = true;
        script.onload = function () {
            callback(true);
        };
        script.onerror = function () {
            callback(false);
        };
        document.head.appendChild(script);
    }

    function setupToolbar(toolbar) {
        if (!toolbar) {
            return;
        }
        toolbar.innerHTML = '' +
            '<span class="ql-formats">' +
                '<select class="ql-header" aria-label="Titre">' +
                    '<option selected></option>' +
                    '<option value="1"></option>' +
                    '<option value="2"></option>' +
                    '<option value="3"></option>' +
                '</select>' +
                '<select class="ql-font" aria-label="Police"></select>' +
                '<select class="ql-size" aria-label="Taille">' +
                    '<option value="small"></option>' +
                    '<option selected></option>' +
                    '<option value="large"></option>' +
                    '<option value="huge"></option>' +
                '</select>' +
            '</span>' +
            '<span class="ql-formats">' +
                '<button type="button" class="ql-bold" aria-label="Gras"></button>' +
                '<button type="button" class="ql-italic" aria-label="Italique"></button>' +
                '<button type="button" class="ql-underline" aria-label="Souligné"></button>' +
            '</span>' +
            '<span class="ql-formats">' +
                '<select class="ql-color" aria-label="Couleur texte"></select>' +
                '<select class="ql-background" aria-label="Couleur fond"></select>' +
            '</span>' +
            '<span class="ql-formats">' +
                '<button type="button" class="ql-align" value="" aria-label="Aligner à gauche"></button>' +
                '<button type="button" class="ql-align" value="center" aria-label="Centrer"></button>' +
                '<button type="button" class="ql-align" value="right" aria-label="Aligner à droite"></button>' +
                '<button type="button" class="ql-align" value="justify" aria-label="Justifier"></button>' +
            '</span>' +
            '<span class="ql-formats">' +
                '<button type="button" class="ql-list" value="ordered" aria-label="Liste numérotée"></button>' +
                '<button type="button" class="ql-list" value="bullet" aria-label="Liste à puces"></button>' +
                '<button type="button" class="ql-blockquote" aria-label="Citation"></button>' +
            '</span>' +
            '<span class="ql-formats">' +
                '<button type="button" class="ql-link" aria-label="Lien"></button>' +
                '<button type="button" class="ql-clean" aria-label="Nettoyer"></button>' +
            '</span>';
    }

    function initEditorOnForm(form) {
        var mode = String(form.getAttribute('data-book-form-mode') || '');
        var wrapper;
        var toolbar;
        var surface;
        var textarea;
        var quill;
        var syncingFromQuill = false;
        var syncingFromSource = false;

        if (mode !== 'create' && mode !== 'modify') {
            return;
        }

        wrapper = form.querySelector('[data-book-description-editor]');
        toolbar = form.querySelector('[data-book-description-toolbar]');
        surface = form.querySelector('[data-book-description-surface]');
        textarea = form.querySelector('#book-description');

        if (!wrapper || !toolbar || !surface || !textarea) {
            return;
        }

        setupToolbar(toolbar);

        quill = new window.Quill(surface, {
            theme: 'snow',
            modules: {
                toolbar: toolbar,
                history: {
                    delay: 1000,
                    maxStack: 100,
                    userOnly: true
                }
            },
            placeholder: 'Saisir une description riche...'
        });

        function setQuillHtml(html) {
            syncingFromSource = true;
            quill.setContents([]);
            if (html && html.trim() !== '') {
                quill.clipboard.dangerouslyPasteHTML(0, html);
            }
            syncingFromSource = false;
        }

        function syncSourceFromQuill(emitInput) {
            var html;
            if (syncingFromSource) {
                return;
            }
            syncingFromQuill = true;
            html = String(quill.root.innerHTML || '');
            if (String(quill.getText() || '').trim() === '') {
                html = '';
            }
            textarea.value = html;
            syncingFromQuill = false;

            if (emitInput) {
                dispatchSimpleEvent(textarea, 'input');
            }
        }

        setQuillHtml(normalizeInputToHtml(textarea.value || ''));
        syncSourceFromQuill(false);
        addClass(wrapper, 'book-description-editor--enhanced');

        quill.on('text-change', function () {
            syncSourceFromQuill(true);
        });

        quill.on('selection-change', function (range) {
            if (range) {
                dispatchSimpleEvent(textarea, 'focus');
            } else {
                dispatchSimpleEvent(textarea, 'blur');
            }
        });

        textarea.addEventListener('input', function () {
            var nextHtml;
            if (syncingFromQuill) {
                return;
            }
            nextHtml = normalizeInputToHtml(textarea.value || '');
            if (String(quill.root.innerHTML || '') !== String(nextHtml || '')) {
                setQuillHtml(nextHtml);
                syncSourceFromQuill(false);
            }
        });

        textarea.addEventListener('book:description-updated', function (event) {
            var activeEl;
            var restoreFocus = false;
            var nextHtml;
            var currentHtml;
            var value = '';
            if (event && event.detail && typeof event.detail.value !== 'undefined') {
                value = String(event.detail.value || '');
            } else {
                value = String(textarea.value || '');
            }
            textarea.value = value;
            nextHtml = normalizeInputToHtml(value);
            currentHtml = String(quill.root.innerHTML || '');

            // Idempotent update: don't reset editor if content is identical.
            if (currentHtml === nextHtml) {
                return;
            }

            activeEl = document.activeElement;
            if (activeEl && activeEl !== document.body && activeEl !== textarea) {
                restoreFocus = true;
            }

            setQuillHtml(nextHtml);
            syncSourceFromQuill(false);

            if (restoreFocus && activeEl && typeof activeEl.focus === 'function') {
                try {
                    activeEl.focus({ preventScroll: true });
                } catch (e) {
                    activeEl.focus();
                }
            }
        });

        form.addEventListener('submit', function () {
            syncSourceFromQuill(false);
        });
    }

    function initAll() {
        var forms = document.querySelectorAll('form.book-form');
        var i;
        for (i = 0; i < forms.length; i++) {
            initEditorOnForm(forms[i]);
        }
    }

    function hasEligibleForm() {
        var forms = document.querySelectorAll('form.book-form');
        var i;
        var mode;
        for (i = 0; i < forms.length; i++) {
            mode = String(forms[i].getAttribute('data-book-form-mode') || '');
            if (mode === 'create' || mode === 'modify') {
                return true;
            }
        }
        return false;
    }

    function boot() {
        if (!hasEligibleForm()) {
            return;
        }
        ensureQuillCss();
        loadQuillScript(function (loaded) {
            if (!loaded || !window.Quill) {
                return;
            }
            initAll();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);

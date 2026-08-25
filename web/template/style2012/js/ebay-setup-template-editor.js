(function () {
    'use strict';

    var QUILL_CSS_URL = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
    var QUILL_JS_URL = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';

    function trim(value) {
        return String(value || '').replace(/^\s+|\s+$/g, '');
    }

    function ensureCss() {
        var id = 'ebay-setup-template-quill-css';
        if (document.getElementById(id)) {
            return;
        }
        var link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = QUILL_CSS_URL;
        document.head.appendChild(link);
    }

    function loadScript(done) {
        var id = 'ebay-setup-template-quill-js';
        var existing = document.getElementById(id);
        if (window.Quill) {
            done(true);
            return;
        }
        if (existing) {
            existing.addEventListener('load', function () { done(!!window.Quill); });
            existing.addEventListener('error', function () { done(false); });
            return;
        }
        var script = document.createElement('script');
        script.id = id;
        script.src = QUILL_JS_URL;
        script.async = true;
        script.onload = function () { done(!!window.Quill); };
        script.onerror = function () { done(false); };
        document.head.appendChild(script);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeInputToHtml(value) {
        var input = String(value || '');
        if (trim(input) === '') {
            return '';
        }
        if (/<\/?[a-z][^>]*>/i.test(input)) {
            return input;
        }

        var lines = input.replace(/\r\n?/g, '\n').split('\n');
        var chunks = [];
        var idx;
        for (idx = 0; idx < lines.length; idx++) {
            chunks.push(escapeHtml(lines[idx]));
        }
        return '<p>' + chunks.join('<br>') + '</p>';
    }

    function enhanceEditor(wrapper) {
        var toolbar = wrapper.querySelector('[data-ebay-template-toolbar]');
        var surface = wrapper.querySelector('[data-ebay-template-surface]');
        var textarea = wrapper.querySelector('[data-ebay-template-source]');
        var form;
        var quill;
        var htmlToggleBtn;
        var htmlMode = false;

        if (!toolbar || !surface || !textarea || !window.Quill) {
            return;
        }

        quill = new window.Quill(surface, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [1, 2, 3, false] }],
                        [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline'],
                        [{ color: [] }, { background: [] }],
                        [{ align: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image', 'clean']
                    ],
                    handlers: {
                        image: function () {
                            var range = quill.getSelection(true);
                            var url = window.prompt('URL image (https://...)', 'https://');
                            url = trim(url);
                            if (!url || !/^https?:\/\//i.test(url)) {
                                return;
                            }
                            if (!range) {
                                range = { index: quill.getLength(), length: 0 };
                            }
                            quill.insertEmbed(range.index, 'image', url, 'user');
                            quill.setSelection(range.index + 1, 0, 'silent');
                        }
                    }
                }
            }
        });

        quill.setContents([]);
        if (trim(textarea.value) !== '') {
            quill.clipboard.dangerouslyPasteHTML(0, normalizeInputToHtml(textarea.value));
        }

        function syncSource() {
            var html = String(quill.root.innerHTML || '');
            if (trim(String(quill.getText() || '')) === '') {
                html = '';
            }
            textarea.value = html;
        }

        function setHtmlMode(enabled) {
            htmlMode = !!enabled;
            if (htmlMode) {
                syncSource();
                wrapper.classList.add('ebay-setup-template-editor--html-mode');
                if (htmlToggleBtn) {
                    htmlToggleBtn.classList.add('is-active');
                }
                textarea.focus();
                return;
            }

            wrapper.classList.remove('ebay-setup-template-editor--html-mode');
            if (htmlToggleBtn) {
                htmlToggleBtn.classList.remove('is-active');
            }
            quill.setContents([]);
            if (trim(textarea.value) !== '') {
                quill.clipboard.dangerouslyPasteHTML(0, normalizeInputToHtml(textarea.value));
            }
            syncSource();
            quill.focus();
        }

        function addHtmlToggle() {
            var toolbarNode = wrapper.querySelector('.ql-toolbar.ql-snow');
            var formats;
            if (!toolbarNode) {
                return;
            }
            formats = document.createElement('span');
            formats.className = 'ql-formats';
            htmlToggleBtn = document.createElement('button');
            htmlToggleBtn.type = 'button';
            htmlToggleBtn.className = 'ql-html-toggle';
            htmlToggleBtn.title = 'Mode code HTML';
            htmlToggleBtn.textContent = 'HTML';
            htmlToggleBtn.addEventListener('click', function (event) {
                event.preventDefault();
                setHtmlMode(!htmlMode);
            });
            formats.appendChild(htmlToggleBtn);
            toolbarNode.appendChild(formats);
        }

        syncSource();
        quill.on('text-change', syncSource);

        form = textarea.form;
        if (form) {
            form.addEventListener('submit', function () {
                if (!htmlMode) {
                    syncSource();
                }
            });
        }

        addHtmlToggle();
        wrapper.classList.add('ebay-setup-template-editor--enhanced');
    }

    function init() {
        var editors = document.querySelectorAll('[data-ebay-template-editor]');
        var i;
        if (!editors || editors.length === 0) {
            return;
        }

        ensureCss();
        loadScript(function (loaded) {
            if (!loaded || !window.Quill) {
                return;
            }
            for (i = 0; i < editors.length; i++) {
                enhanceEditor(editors[i]);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

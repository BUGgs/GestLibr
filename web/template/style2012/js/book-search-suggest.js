(function (window, document) {
    'use strict';

    function parseJson(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function strLength(value) {
        if (typeof value !== 'string') {
            value = String(value || '');
        }
        if (typeof window.Array !== 'undefined' && window.Array.from) {
            return window.Array.from(value).length;
        }
        return value.length;
    }

    function findListItem(el, root) {
        var current = el;
        while (current && current !== root) {
            if (current.classList && current.classList.contains('book-suggest-item')) {
                return current;
            }
            current = current.parentNode;
        }
        return null;
    }

    function initBookSearchSuggest() {
        var form = document.querySelector('form.book-form');
        var enabled;
        var suggestUrl;
        var minChars;
        var formAction;
        var states;

        if (!form) {
            return;
        }

        formAction = String(form.getAttribute('action') || '');
        enabled = String(form.getAttribute('data-book-search-autocomplete-enabled') || '0') === '1';
        suggestUrl = String(form.getAttribute('data-book-search-suggest-url') || '');
        minChars = parseInt(form.getAttribute('data-book-search-suggest-min-chars') || '4', 10);

        if (!enabled && /[?&]m=bo(&|$)/.test(formAction) && /[?&]a=vi(&|$)/.test(formAction) && /[?&]sa=re(&|$)/.test(formAction)) {
            enabled = true;
        }
        if (!suggestUrl && formAction) {
            suggestUrl = formAction + (formAction.indexOf('?') === -1 ? '?' : '&') + 'book_action=suggest';
        }

        if (!enabled || !suggestUrl) {
            return;
        }
        if (isNaN(minChars) || minChars < 1) {
            minChars = 4;
        }

        states = {
            author: {
                field: 'author',
                input: form.querySelector('#book-author'),
                list: form.querySelector('[data-book-suggest-list="author"]'),
                timer: null,
                xhr: null,
                requestId: 0,
                activeIndex: -1
            },
            title: {
                field: 'title',
                input: form.querySelector('#book-title'),
                list: form.querySelector('[data-book-suggest-list="title"]'),
                timer: null,
                xhr: null,
                requestId: 0,
                activeIndex: -1
            }
        };

        function hideList(state) {
            if (!state || !state.list) {
                return;
            }
            state.activeIndex = -1;
            state.list.innerHTML = '';
            state.list.setAttribute('hidden', 'hidden');
        }

        function hideAll(exceptField) {
            var key;
            for (key in states) {
                if (!Object.prototype.hasOwnProperty.call(states, key)) {
                    continue;
                }
                if (exceptField && key === exceptField) {
                    continue;
                }
                hideList(states[key]);
            }
        }

        function stopPending(state) {
            if (!state) {
                return;
            }
            if (state.timer) {
                window.clearTimeout(state.timer);
                state.timer = null;
            }
            if (state.xhr && state.xhr.readyState !== 4) {
                state.xhr.abort();
            }
            state.xhr = null;
        }

        function makeUrl(field, query) {
            var glue = suggestUrl.indexOf('?') === -1 ? '?' : '&';
            return suggestUrl + glue +
                'field=' + encodeURIComponent(field) +
                '&q=' + encodeURIComponent(query) +
                '&_=' + String(new Date().getTime());
        }

        function setActiveItem(state, index) {
            var items = state.list.querySelectorAll('.book-suggest-item');
            var i;
            for (i = 0; i < items.length; i++) {
                if (i === index) {
                    items[i].classList.add('book-suggest-item--active');
                } else {
                    items[i].classList.remove('book-suggest-item--active');
                }
            }
            state.activeIndex = index;
        }

        function submitWithValue(state, value) {
            if (!state || !state.input) {
                return;
            }
            state.input.value = value;
            hideList(state);
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        function renderSuggestions(state, suggestions, query) {
            var i;
            var btn;
            var item;

            if (!state || !state.list) {
                return;
            }

            state.list.innerHTML = '';
            state.activeIndex = -1;

            if (!suggestions || !suggestions.length) {
                if (strLength(query) >= minChars) {
                    item = document.createElement('div');
                    item.className = 'book-suggest-empty';
                    item.textContent = 'Aucune suggestion';
                    state.list.appendChild(item);
                    state.list.removeAttribute('hidden');
                } else {
                    state.list.setAttribute('hidden', 'hidden');
                }
                return;
            }

            for (i = 0; i < suggestions.length; i++) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'book-suggest-item';
                btn.setAttribute('data-value', suggestions[i]);
                btn.textContent = suggestions[i];
                state.list.appendChild(btn);
            }
            state.list.removeAttribute('hidden');
        }

        function requestSuggestions(state) {
            var query;
            var url;
            var reqId;
            var xhr;

            if (!state || !state.input || !state.list) {
                return;
            }

            query = String(state.input.value || '').trim();
            if (strLength(query) < minChars) {
                stopPending(state);
                hideList(state);
                return;
            }

            hideAll(state.field);

            state.requestId += 1;
            reqId = state.requestId;
            stopPending(state);

            xhr = new XMLHttpRequest();
            state.xhr = xhr;
            url = makeUrl(state.field, query);
            xhr.open('GET', url, true);
            xhr.onreadystatechange = function () {
                var payload;
                if (xhr.readyState !== 4) {
                    return;
                }
                if (reqId !== state.requestId) {
                    return;
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    hideList(state);
                    return;
                }
                payload = parseJson(xhr.responseText);
                if (!payload || payload.ok !== true || !payload.suggestions) {
                    hideList(state);
                    return;
                }
                renderSuggestions(state, payload.suggestions, query);
            };
            xhr.send(null);
        }

        function bindField(state) {
            if (!state || !state.input || !state.list) {
                return;
            }

            state.input.addEventListener('input', function () {
                if (state.timer) {
                    window.clearTimeout(state.timer);
                }
                state.timer = window.setTimeout(function () {
                    requestSuggestions(state);
                }, 260);
            });

            state.input.addEventListener('focus', function () {
                var query = String(state.input.value || '').trim();
                if (strLength(query) >= minChars) {
                    requestSuggestions(state);
                }
            });

            state.input.addEventListener('blur', function () {
                window.setTimeout(function () {
                    hideList(state);
                }, 140);
            });

            state.input.addEventListener('keydown', function (event) {
                var items;
                var nextIndex;
                if (state.list.hasAttribute('hidden')) {
                    return;
                }
                items = state.list.querySelectorAll('.book-suggest-item');
                if (!items.length) {
                    if (event.key === 'Escape') {
                        hideList(state);
                    }
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    nextIndex = state.activeIndex + 1;
                    if (nextIndex >= items.length) {
                        nextIndex = 0;
                    }
                    setActiveItem(state, nextIndex);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    nextIndex = state.activeIndex - 1;
                    if (nextIndex < 0) {
                        nextIndex = items.length - 1;
                    }
                    setActiveItem(state, nextIndex);
                    return;
                }

                if (event.key === 'Enter' && state.activeIndex >= 0) {
                    event.preventDefault();
                    submitWithValue(state, items[state.activeIndex].getAttribute('data-value') || '');
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    hideList(state);
                }
            });

            state.list.addEventListener('mousedown', function (event) {
                var item = findListItem(event.target, state.list);
                if (!item) {
                    return;
                }
                event.preventDefault();
                submitWithValue(state, item.getAttribute('data-value') || '');
            });
        }

        bindField(states.author);
        bindField(states.title);

        document.addEventListener('click', function (event) {
            var key;
            for (key in states) {
                if (!Object.prototype.hasOwnProperty.call(states, key)) {
                    continue;
                }
                if (!states[key].list || !states[key].input) {
                    continue;
                }
                if (states[key].list.contains(event.target) || states[key].input.contains(event.target)) {
                    return;
                }
            }
            hideAll();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookSearchSuggest);
    } else {
        initBookSearchSuggest();
    }
})(window, document);

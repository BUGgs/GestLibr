(function () {
    function hasClass(el, name) {
        return (' ' + el.className + ' ').indexOf(' ' + name + ' ') !== -1;
    }

    function addClass(el, name) {
        if (!hasClass(el, name)) {
            el.className += (el.className ? ' ' : '') + name;
        }
    }

    function removeClass(el, name) {
        var re = new RegExp('(^|\\s)' + name + '(\\s|$)', 'g');
        el.className = el.className.replace(re, ' ').replace(/\s+/g, ' ').replace(/^\s|\s$/g, '');
    }

    function closeAllSubmenus(nav) {
        var opened = nav.querySelectorAll('.menu-item.has-children.is-open');
        var i;
        for (i = 0; i < opened.length; i++) {
            removeClass(opened[i], 'is-open');
            var btn = opened[i].querySelector('.submenu-toggle');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '+';
            }
        }
    }

    function initSubmenuButtons(nav) {
        var parents = nav.querySelectorAll('.menu-item.has-children');
        var i;
        for (i = 0; i < parents.length; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'submenu-toggle';
            btn.setAttribute('aria-expanded', 'false');
            btn.innerHTML = '+';
            parents[i].appendChild(btn);
        }
    }

    function initCurrentLink(nav) {
        var links = nav.querySelectorAll('.menu-link');
        var current = window.location.search || '?';
        var i;
        for (i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href');
            if (href === current || (href === '?' && current === '')) {
                addClass(links[i], 'is-current');
            }
        }
    }

    function initMenu() {
        var nav = document.getElementById('menu');
        if (!nav) {
            return;
        }

        addClass(nav, 'is-js');
        initSubmenuButtons(nav);
        initCurrentLink(nav);

        var mobileToggle = document.getElementById('menu-mobile-toggle');
        if (mobileToggle) {
            mobileToggle.onclick = function () {
                var opened = hasClass(nav, 'is-mobile-open');
                if (opened) {
                    removeClass(nav, 'is-mobile-open');
                    mobileToggle.setAttribute('aria-expanded', 'false');
                    closeAllSubmenus(nav);
                } else {
                    addClass(nav, 'is-mobile-open');
                    mobileToggle.setAttribute('aria-expanded', 'true');
                }
            };
        }

        nav.onclick = function (event) {
            var e = event || window.event;
            var target = e.target || e.srcElement;
            if (!target || !hasClass(target, 'submenu-toggle')) {
                return;
            }

            var item = target.parentNode;
            var opened = hasClass(item, 'is-open');
            closeAllSubmenus(nav);
            if (!opened) {
                addClass(item, 'is-open');
                target.setAttribute('aria-expanded', 'true');
                target.innerHTML = '-';
            }

            if (e.preventDefault) {
                e.preventDefault();
            }
            e.returnValue = false;
        };

        document.onclick = function (event) {
            var e = event || window.event;
            var target = e.target || e.srcElement;
            if (!target) {
                return;
            }
            if (!nav.contains(target)) {
                closeAllSubmenus(nav);
            }
        };
    }

    if (document.addEventListener) {
        document.addEventListener('DOMContentLoaded', initMenu);
    } else {
        window.attachEvent('onload', initMenu);
    }
})();

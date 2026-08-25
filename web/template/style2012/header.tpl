<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{nomProg}</title>
<link rel="stylesheet" type="text/css" href="{TEMPLATE_FOLDER}/style.css" media="screen" />
<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/tailwind-config.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/jquery.js"></script>
<script type="text/javascript" language="javascript">
<!--

// js form validation stuff
var errorMsg0   = '{FORM_INCOMPLETE}';
var errorMsg1   = '{FORM_NOT_NUMBER}';
var errorMsg2   = '{FORM_NOT_VALID_NUMBER}';
var confirmMsg  = '{FORM_CONFIRM}';
//-->
</script>

<script src="functions.js" type="text/javascript" language="javascript"></script>

<script language="javascript" type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript">
tinyMCE.init({
	mode : "exact",
	elements : "description-catalog",
	theme : "advanced",
	content_css : 'style_mce.css'
});
</script>
<script language="Javascript">
// Transfert une ligne de la liste Origine à la liste Destination
function TransfertListe(idOrigine, idDestination)
{	var objOrigine = document.getElementById(idOrigine);
	var objDestination = document.getElementById(idDestination);
	if (objOrigine.options.selectedIndex<0) return false;
	if (VerifValeurDansListe(idDestination, objOrigine.options[objOrigine.options.selectedIndex].value, true)) return false;
	var ADeplacer = new Option(objOrigine.options[objOrigine.options.selectedIndex].text, objOrigine.options[objOrigine.options.selectedIndex].value);
	objDestination.options[objDestination.length]=ADeplacer;
	objOrigine.options[objOrigine.options.selectedIndex]=null;
}

// Vérifie la présence de Valeur dans IdListe
function VerifValeurDansListe(IdListe, Valeur, blnAlerte) {
	var objListe = document.getElementById(IdListe);
	for (i=objListe.length-1;i>=0;i--) if (objListe.options[i].value == Valeur) {if (blnAlerte) alert('Déjà présent.'); return true;}
	return false;
}
</script>
</head>
<body>
<div id="slow-page-loader" class="slow-page-loader" aria-hidden="true">
    <div class="slow-page-loader__backdrop"></div>
    <div class="slow-page-loader__card" role="status" aria-live="polite">
        <span class="slow-page-loader__spinner" aria-hidden="true"></span>
        <p class="slow-page-loader__title">Traitement en cours</p>
        <p class="slow-page-loader__text">Veuillez patienter, votre demande est en cours de traitement.</p>
    </div>
</div>

<header class="relative z-[2000] mx-4 mt-4 rounded-2xl border border-slate-700 bg-slate-900 text-slate-100 shadow-xl">
    <div class="flex items-center gap-3 px-4 py-3">
        <a href="?" class="flex shrink-0 items-center gap-2 no-underline text-slate-100">
            <span class="rounded-lg bg-brand-600 px-2 py-1 text-xs font-bold uppercase tracking-wider">GestLibr</span>
        </a>

        <nav class="hidden min-w-0 flex-1 md:block" aria-label="Navigation principale">
            <ul class="flex flex-nowrap items-center gap-1 whitespace-nowrap text-[13px]">

                <li class="group relative">
                    <a href="?m=cl&a=vi" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Clients</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=cl&a=cr" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Création</a></li>
                        <li><a href="?m=cl&a=vi" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Rechercher</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=bo&a=vi" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Livres</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=bo&a=cr" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Création</a></li>
                        <li><a href="?m=bo&a=vi" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Rechercher</a></li>
                        <li><a href="?m=bo&a=ci" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Import ISBN</a></li>
                        <li><a href="?m=bo&a=io" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">ISBN en ligne</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=ca&a=vi" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Catalogues</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=ca&a=cr" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Création</a></li>
                        <li><a href="?m=ca&a=vi" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Liste</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=or&a=eb" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Commandes</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=or&a=cr" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Création</a></li>
                        <li><a href="?m=or&a=vi" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Rechercher</a></li>
                        <li><a href="?m=or&a=in" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Site Internet</a></li>
                        <li><a href="?m=or&a=eb" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">eBay</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=ct&a=vi" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Rubriques</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=ct&a=cr" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Création</a></li>
                        <li><a href="?m=ct&a=vi" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Liste</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=ed&a=cl" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Editions</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-56 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=ed&a=cl" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Clients</a></li>
                    </ul>
                </li>

                <li class="group relative">
                    <a href="?m=in&a=eb" class="block rounded-md px-2 py-1.5 font-medium text-slate-200 no-underline hover:bg-slate-800 hover:text-white">Internet</a>
                    <ul class="invisible pointer-events-none absolute left-0 top-full z-[2100] w-64 rounded-lg border border-slate-700 bg-slate-900/95 p-2 opacity-0 shadow-2xl transition duration-150 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                        <li><a href="?m=in&a=eb" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Ebay</a></li>
                        <li><a href="?m=in&a=ws" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Site Web</a></li>
                        <li><a href="?m=in&a=ex" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Exclusions de Catalogue</a></li>
                        <li><a href="?m=in&a=co" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Configuration</a></li>
                        <li><a href="?m=in&a=se" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Sélection Rubriques</a></li>
                        <li><a href="?m=in&a=sy" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Synchronisation</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <button id="nav-toggle" type="button" class="ml-auto md:hidden rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200 hover:bg-slate-800">
            Menu
        </button>

        <div class="hidden shrink-0 items-center gap-2 text-xs text-slate-300 md:flex">
            <details class="relative">
                <summary class="flex list-none cursor-pointer items-center gap-2 rounded-md bg-slate-800 px-3 py-1.5 text-slate-100">
                    <svg class="h-4 w-4 text-cyan-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 14c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6 2.686 6 6 6Zm0 0c-5.523 0-10 2.239-10 5v1h20v-1c0-2.761-4.477-5-10-5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>{USERNAME} / {GROUPNAME}</span>
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </summary>
                <div class="absolute right-0 z-[2300] mt-2 w-64 rounded-lg border border-slate-700 bg-slate-900/95 p-2 shadow-2xl">
                    <a href="{PARAM_LINK_ME}" class="block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800">Paramétrages · Utilisateur</a>
                    {PARAM_ADMIN_LINKS}
                </div>
            </details>
            <a href="?m=lo" class="rounded-md border border-cyan-400/40 bg-cyan-500/15 px-3 py-1.5 font-semibold text-cyan-100 no-underline transition hover:bg-cyan-500/25 hover:text-white">Déconnexion</a>
        </div>
    </div>

    <div id="main-nav-mobile" class="hidden border-t border-slate-700 px-4 py-3 md:hidden" aria-label="Navigation mobile">
        <div class="space-y-2 text-sm">
            <a href="?" class="block rounded-md bg-slate-800 px-3 py-2 font-medium text-slate-100 no-underline">Accueil</a>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Clients</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=cl&a=cr" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Création</a>
                    <a href="?m=cl&a=vi" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Rechercher</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Livres</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=bo&a=cr" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Création</a>
                    <a href="?m=bo&a=vi" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Rechercher</a>
                    <a href="?m=bo&a=ci" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Import ISBN</a>
                    <a href="?m=bo&a=io" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">ISBN en ligne</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Catalogues</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=ca&a=cr" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Création</a>
                    <a href="?m=ca&a=vi" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Liste</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Commandes</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=or&a=cr" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Création</a>
                    <a href="?m=or&a=vi" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Rechercher</a>
                    <a href="?m=or&a=in" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Site Internet</a>
                    <a href="?m=or&a=eb" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">eBay</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Rubriques</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=ct&a=cr" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Création</a>
                    <a href="?m=ct&a=vi" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Liste</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Editions</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=ed&a=cl" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Clients</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">Internet</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="?m=in&a=eb" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Ebay</a>
                    <a href="?m=in&a=ws" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Site Web</a>
                    <a href="?m=in&a=ex" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Exclusions de Catalogue</a>
                    <a href="?m=in&a=co" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Configuration</a>
                    <a href="?m=in&a=se" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Sélection Rubriques</a>
                    <a href="?m=in&a=sy" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Synchronisation</a>
                </div>
            </details>

            <details class="rounded-md bg-slate-800/80">
                <summary class="cursor-pointer list-none px-3 py-2 font-medium text-slate-100">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-cyan-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 14c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6 2.686 6 6 6Zm0 0c-5.523 0-10 2.239-10 5v1h20v-1c0-2.761-4.477-5-10-5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Paramétrages
                    </span>
                </summary>
                <div class="space-y-1 px-2 pb-2">
                    <a href="{PARAM_LINK_ME}" class="block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700">Utilisateur</a>
                    {PARAM_ADMIN_LINKS_MOBILE}
                </div>
            </details>

            <div class="pt-2 text-xs text-slate-400">{USERNAME} / {GROUPNAME}</div>
            <a href="?m=lo" class="block rounded-md border border-cyan-400/40 bg-cyan-500/15 px-3 py-2 text-center font-semibold text-cyan-100 no-underline transition hover:bg-cyan-500/25 hover:text-white">Déconnexion</a>
        </div>
    </div>
</header>

<script type="text/javascript">
(function () {
    var toggle = document.getElementById('nav-toggle');
    var mobileNav = document.getElementById('main-nav-mobile');
    if (!toggle || !mobileNav) {
        return;
    }

    toggle.addEventListener('click', function () {
        mobileNav.classList.toggle('hidden');
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            mobileNav.classList.add('hidden');
        }
    });
})();

(function () {
    var loader = document.getElementById('slow-page-loader');
    var showTimer = null;
    var showDelayMs = 900;

    if (!loader) {
        return;
    }

    function clearTimer() {
        if (showTimer) {
            window.clearTimeout(showTimer);
            showTimer = null;
        }
    }

    function showLoader() {
        showTimer = null;
        loader.classList.add('slow-page-loader--visible');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('slow-page-loader-open');
    }

    function hideLoader() {
        clearTimer();
        loader.classList.remove('slow-page-loader--visible');
        loader.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('slow-page-loader-open');
    }

    function scheduleLoader() {
        clearTimer();
        showTimer = window.setTimeout(showLoader, showDelayMs);
    }

    function isInternalHttpLink(link) {
        if (!link || !link.href) {
            return false;
        }

        try {
            var targetUrl = new URL(link.href, window.location.href);
            return targetUrl.origin === window.location.origin && (targetUrl.protocol === 'http:' || targetUrl.protocol === 'https:');
        } catch (err) {
            return false;
        }
    }

    function findClosestLink(target) {
        var node = target;
        while (node && node !== document) {
            if (node.nodeType === 1 && node.nodeName.toLowerCase() === 'a') {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var link = findClosestLink(event.target);
        if (!link || link.hasAttribute('download')) {
            return;
        }

        if (link.getAttribute('data-no-page-loader') === '1' || link.hasAttribute('data-confirm-text')) {
            return;
        }

        var href = (link.getAttribute('href') || '').trim();
        if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
            return;
        }

        var target = (link.getAttribute('target') || '').toLowerCase();
        if (target !== '' && target !== '_self') {
            return;
        }

        if (!isInternalHttpLink(link)) {
            return;
        }

        scheduleLoader();
    }, true);

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) {
            return;
        }

        var form = event.target;
        if (!form || form.nodeName.toLowerCase() !== 'form') {
            return;
        }

        var formTarget = (form.getAttribute('target') || '').toLowerCase();
        if (formTarget !== '' && formTarget !== '_self') {
            return;
        }

        if (form.getAttribute('data-no-page-loader') === '1') {
            return;
        }

        scheduleLoader();
    }, true);

    window.addEventListener('pageshow', hideLoader);
    window.addEventListener('load', hideLoader);
})();
</script>

<div class="pageContent">
    <div id="main">
        <div class="container">
<!-- Fin header.tpl -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{nomProg} version {verProg}</title>
<link rel="stylesheet" type="text/css" href="{TEMPLATE_FOLDER}/style.css" media="screen" />
</head>
<body class="login-page">

<form class="login" id="loginForm" method="post" action="?" autocomplete="on">
    <div class="login-brand">
        <span class="login-brand__pill">GestLibr 5.0</span>
        <h4>{nomProg}</h4>
        <p class="login-subtitle">Connexion a l'interface de gestion</p>
    </div>

    <fieldset class="login-input">
        <input class="username" name="username" type="text" placeholder="{USERNAME}" autocomplete="username" autocapitalize="off" spellcheck="false" autofocus required>
        <input class="password" name="password" type="password" placeholder="{PASSWORD}" autocomplete="current-password" spellcheck="false" required>
    </fieldset>

    <div class="err">{ERROR_MSG}</div>
    {LOGIN_RECAPTCHA_WIDGET}

    <fieldset class="actions">
        <input type="hidden" name="m" value="li">
        <input type="submit" class="submit" value="{LOGIN}">
        <a href="">{FORGOT_PASSWORD}</a>
    </fieldset>
</form>

<script type="text/javascript">
(function() {
    var form = document.getElementById('loginForm');
    if (!form) {
        return;
    }
    var recaptchaEnabled = ('{LOGIN_RECAPTCHA_ENABLED}' === '1');
    var recaptchaSiteKey = '{LOGIN_RECAPTCHA_SITE_KEY}';
    var recaptchaTokenInput = document.getElementById('login-recaptcha-token');
    var errBox = form.querySelector('.err');

    function setError(msg) {
        if (!errBox) {
            return;
        }
        errBox.textContent = msg || '';
    }

    function submitAfterToken(token) {
        if (!recaptchaTokenInput) {
            setError('Champ reCAPTCHA manquant.');
            return;
        }
        recaptchaTokenInput.value = token;
        form.setAttribute('data-recaptcha-ready', '1');
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    form.addEventListener('submit', function(ev) {
        if (!recaptchaEnabled) {
            return;
        }
        if (form.getAttribute('data-recaptcha-ready') === '1') {
            form.setAttribute('data-recaptcha-ready', '0');
            return;
        }

        ev.preventDefault();
        setError('');

        if (!window.grecaptcha || typeof window.grecaptcha.ready !== 'function' || typeof window.grecaptcha.execute !== 'function') {
            setError('reCAPTCHA indisponible. Vérifiez le chargement des scripts.');
            return;
        }
        if (!recaptchaSiteKey) {
            setError('Clé reCAPTCHA manquante.');
            return;
        }

        window.grecaptcha.ready(function() {
            window.grecaptcha.execute(recaptchaSiteKey, { action: 'login' })
                .then(function(token) {
                    if (!token) {
                        setError('Validation reCAPTCHA impossible.');
                        return;
                    }
                    submitAfterToken(token);
                })
                .catch(function() {
                    setError('Validation reCAPTCHA impossible.');
                });
        });
    });

    form.addEventListener('keydown', function(ev) {
        if (ev.key !== 'Enter') {
            return;
        }
        ev.preventDefault();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
</script>
{LOGIN_RECAPTCHA_SCRIPT}

</body>
</html>

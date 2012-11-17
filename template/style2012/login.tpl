<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>{nomProg} version {verProg}</title>
<link rel="stylesheet" type="text/css" href="{TEMPLATE_FOLDER}/style.css" media="screen" />
</head>
<body>

<form class="login" method="post" action="?">
    <h4>{nomProg}</h4>
    <fieldset class="login-input">
        <input class="username" name="username" type="text" placeholder="{USERNAME}" autofocus required>   
        <input class="password" name="password" type="password" placeholder="{PASSWORD}" required>
    </fieldset>
    <div class="err">{ERROR_MSG}</div>
    <fieldset class="actions">
        <input type="hidden" name="m" value="li">
        <input type="submit" class="submit" value="{LOGIN}">
        <a href="">{FORGOT_PASSWORD}</a>
    </fieldset>
</form>

</body>
</html>


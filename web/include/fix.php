<?php
/*****
 *
 * fix.php  v1.1
 * 10/09/01
 *
 * ~Ashe
 *
 * Go to http://phpbbfix.fr.st  to check for newer versions!
 *
 *
 * History:
 * --------
 * 1.1	More efficient SQL-check algorithm
 * 1.02	Changed $user_id var-reset to SQL-check because it was messing up with Profile, doh!
		Added SQL-check to $email because of phpBB 1.2.1
 * 1.01	Fixed a mySQL trick for 1.2.1 (get_userdata).
 *		Fixed an eventual trick (any version) with get_userdata_from_id.
 * 1.0	Initial release
 *
 *****/

/***
 * var-reset
 ***/
$fix_vars = array('userdata', 'user_logged_in', 'user_lang', 'logged_in', 'l_statsblock', 'l_pwdmessage', 'l_privnotify');
for ($n = 0; $n < sizeof($fix_vars); $n++)
{
	$$fix_vars[$n] = '';
}

/***
 * origin-validation
 ***/
$fix_vars = array('submit', 'save');
for ($n = 0; $n < sizeof($fix_vars); $n++)
{
	$$fix_vars[$n] = (isset($HTTP_POST_VARS[$fix_vars[$n]])) ? 1 : 0;
}

/***
 * SQL-check
 ***/
$fix_vars = array('user_name', 'email', 'viewemail', 'themes', 'sig', 'smile', 'dishtml', 'disbbcode', 'lang', 'username', 'user_id');
for ($n = 0; $n < sizeof($fix_vars); $n++)
{
	if (isset($$fix_vars[$n]))
	{
		while (preg_match("/(.*)\\'(.*)\\,(.*)=/i", $$fix_vars[$n], $matches))
		{
			$$fix_vars[$n] = $matches[1];
		}
		while (preg_match("/(.*)\\'(.*)WHERE/i", $$fix_vars[$n], $matches))
		{
			$$fix_vars[$n] = $matches[1];
		}
	}
}
?>
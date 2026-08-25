<?php

function gl_password_is_legacy_hash($hash)
{
   $hash = trim((string)$hash);
   return (preg_match('/^[a-f0-9]{32}$/i', $hash) === 1);
}

function gl_password_hash_modern($password)
{
   $password = (string)$password;
   if (function_exists('password_hash'))
      return password_hash($password, PASSWORD_DEFAULT);
   return md5($password);
}

function gl_password_verify_compat($password, $storedHash)
{
   $password = (string)$password;
   $storedHash = trim((string)$storedHash);
   if ($storedHash === "")
      return false;

   if (!gl_password_is_legacy_hash($storedHash) && function_exists('password_verify'))
      return password_verify($password, $storedHash);

   return (md5($password) === strtolower($storedHash));
}

function gl_login_recaptcha_settings()
{
   $enabledRaw = config("login_recaptcha_enabled");
   $siteKeyRaw = config("login_recaptcha_site_key");
   $secretKeyRaw = config("login_recaptcha_secret_key");

   $enabledValue = strtolower(trim((string)$enabledRaw));
   $enabled = ($enabledValue === "1" || $enabledValue === "true" || $enabledValue === "on" || $enabledValue === "yes");

   return array(
      "enabled" => $enabled,
      "siteKey" => trim((string)$siteKeyRaw),
      "secretKey" => trim((string)$secretKeyRaw),
      "action" => "login",
      "minScore" => 0.50
   );
}

function gl_login_recaptcha_is_active($settings = null)
{
   if (!is_array($settings))
      $settings = gl_login_recaptcha_settings();

   return (
      !empty($settings["enabled"]) &&
      isset($settings["siteKey"]) &&
      trim((string)$settings["siteKey"]) !== "" &&
      isset($settings["secretKey"]) &&
      trim((string)$settings["secretKey"]) !== ""
   );
}

function gl_login_recaptcha_verify($secretKey, $captchaResponse, $remoteIp = "", $expectedAction = "login", $minScore = 0.50)
{
   $secretKey = trim((string)$secretKey);
   $captchaResponse = trim((string)$captchaResponse);
   $remoteIp = trim((string)$remoteIp);
   $expectedAction = trim((string)$expectedAction);
   $minScore = (float)$minScore;

   if ($secretKey === "" || $captchaResponse === "")
      return array("ok" => false, "message" => "Veuillez valider le reCAPTCHA.");

   $payload = array(
      "secret" => $secretKey,
      "response" => $captchaResponse
   );
   if ($remoteIp !== "")
      $payload["remoteip"] = $remoteIp;

   $query = http_build_query($payload, "", "&");
   $raw = false;
   $httpCode = 0;

   if (function_exists("curl_init"))
   {
      $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
      if ($ch !== false)
      {
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
         curl_setopt($ch, CURLOPT_TIMEOUT, 8);
         $raw = curl_exec($ch);
         if ($raw === false)
         {
            curl_close($ch);
            return array("ok" => false, "message" => "Service reCAPTCHA indisponible.");
         }
         $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);
      }
   }

   if ($raw === false)
   {
      $context = stream_context_create(array(
         "http" => array(
            "method" => "POST",
            "header" => "Content-Type: application/x-www-form-urlencoded\r\n",
            "content" => $query,
            "timeout" => 8
         )
      ));
      $raw = @file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $context);
      if ($raw === false)
         return array("ok" => false, "message" => "Service reCAPTCHA indisponible.");
      if (isset($http_response_header) && is_array($http_response_header))
      {
         $i = 0;
         while ($i < sizeof($http_response_header))
         {
            if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[$i], $m))
            {
               $httpCode = (int)$m[1];
               break;
            }
            $i++;
         }
      }
   }

   if ($httpCode !== 0 && ($httpCode < 200 || $httpCode >= 300))
      return array("ok" => false, "message" => "Validation reCAPTCHA impossible (HTTP " . $httpCode . ").");

   $decoded = json_decode((string)$raw, true);
   if (!is_array($decoded))
      return array("ok" => false, "message" => "Réponse reCAPTCHA invalide.");

   if (empty($decoded["success"]))
      return array("ok" => false, "message" => "Validation reCAPTCHA échouée.");

   if (!isset($decoded["score"]))
      return array("ok" => false, "message" => "Réponse reCAPTCHA sans score (v3 attendu).");

   $score = (float)$decoded["score"];
   if ($score < $minScore)
      return array("ok" => false, "message" => "Score reCAPTCHA insuffisant (" . number_format($score, 2, ".", "") . ").");

   if ($expectedAction !== "")
   {
      $responseAction = isset($decoded["action"]) ? trim((string)$decoded["action"]) : "";
      if ($responseAction === "")
         return array("ok" => false, "message" => "Action reCAPTCHA absente (v3 attendu).");
      if (strtolower($responseAction) !== strtolower($expectedAction))
         return array("ok" => false, "message" => "Action reCAPTCHA invalide.");
   }

   return array("ok" => true, "message" => "");
}

function gl_login_register_failure($throttle, $throttleKey, $throttleEntry, $nowTs)
{
   $fails = isset($throttleEntry["fails"]) ? (int)$throttleEntry["fails"] : 0;
   $fails++;
   $lockedUntil = 0;
   if ($fails >= 5)
   {
      $lockedUntil = (int)$nowTs + 300; // 5 minutes
      $fails = 0;
   }
   $throttle[$throttleKey] = array(
      "fails" => $fails,
      "lockedUntil" => $lockedUntil
   );
   return $throttle;
}

function gl_render_login_form($errorMsg = "")
{
   global $template, $template_path, $template_name, $var, $txt;

   $recaptchaSettings = gl_login_recaptcha_settings();
   $recaptchaWidget = "";
   $recaptchaEnabled = "0";
   $recaptchaSiteKey = "";
   $recaptchaScript = "";
   if (gl_login_recaptcha_is_active($recaptchaSettings))
   {
      $siteKeyPlain = (string)$recaptchaSettings["siteKey"];
      $recaptchaEnabled = "1";
      $recaptchaSiteKey = htmlspecialchars($siteKeyPlain, ENT_QUOTES, "UTF-8");
      $recaptchaWidget = "<input type='hidden' name='g-recaptcha-response' id='login-recaptcha-token' value=''>";
      $recaptchaScript = "<script src='https://www.google.com/recaptcha/api.js?render=" . rawurlencode($siteKeyPlain) . "' async defer></script>";
   }

   $template->set_filenames(array('login' => 'login.tpl'));
   $template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
   $template->assign_vars(array(
      'TEMPLATE_FOLDER'=> $template_path.$template_name,
      'USERNAME' => $txt["username"],
      'PASSWORD' => $txt["password"],
      'FORGOT_PASSWORD' => $txt["ForgetPassword"],
      'LOGIN' => $txt["login"],
      'ERROR_MSG' => (string)$errorMsg,
      'LOGIN_RECAPTCHA_WIDGET' => $recaptchaWidget,
      'LOGIN_RECAPTCHA_ENABLED' => $recaptchaEnabled,
      'LOGIN_RECAPTCHA_SITE_KEY' => $recaptchaSiteKey,
      'LOGIN_RECAPTCHA_SCRIPT' => $recaptchaScript
   ));
   $template->pparse('login');
   die();
}

$requestModule = isset($_GET["m"]) ? $_GET["m"] : "";
$postModule = isset($_POST["m"]) ? $_POST["m"] : "";
$isLoggedIn = !empty($_SESSION["user_logged_in"]);

if ($requestModule=="lo")
{
   // Logout attempt
   $_SESSION["user_logged_in"]=false;
   session_unset();
   session_destroy();
}

if ($isLoggedIn)
{
   // Just checking that user is correctly logged-in
   $idUser = isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0;
   if ($idUser > 0)
      $user = $babase->DbSelect("SELECT * FROM users WHERE `idUser`='" . $idUser . "' AND `active`=1 LIMIT 1",$nb);
   else
   {
      $safeUsername = mysqli_real_escape_string($babase->DB_CONN, isset($_SESSION["username"]) ? (string)$_SESSION["username"] : "");
      $user = $babase->DbSelect("SELECT * FROM users WHERE `username`='" . $safeUsername . "' AND `active`=1 LIMIT 1",$nb);
   }
   if ($nb!=1)
   {
      // Error in the login check !
      session_destroy();
      gl_render_login_form();
   }
   else
   {
      // Refresh session from database to keep role/group updates in sync
      $group = $babase->DbSelect("SELECT * FROM `groups` WHERE idGroup='" . $user[0]["idGroup"] . "' LIMIT 1", $nbGroup);
      $_SESSION["idUser"] = $user[0]["idUser"];
      $_SESSION["idGroup"] = $user[0]["idGroup"];
      $_SESSION["username"] = $user[0]["username"];
      $_SESSION["name"] = $user[0]["name"];
      $_SESSION["groupName"] = ($nbGroup == 1) ? $group[0]["groupName"] : "";
      $_SESSION["email"] = $user[0]["email"];
      $_SESSION["admin"] = $user[0]["admin"];
      $_SESSION["lastLogin"] = $user[0]["lastLogin"];
   }
}
else
{
   // Not yet logged-in, so it could be a login attempt or we display the login form
   if ($postModule=="li")
   {
      // Login attempt
      $usernameRaw = isset($_POST["username"]) ? (string)$_POST["username"] : "";
      $passwordRaw = isset($_POST["password"]) ? (string)$_POST["password"] : "";
      // Request values are sanitized globally in index.php; decode for authentication logic.
      $usernameRaw = html_entity_decode($usernameRaw, ENT_QUOTES, "UTF-8");
      $passwordRaw = html_entity_decode($passwordRaw, ENT_QUOTES, "UTF-8");
      $username = mysqli_real_escape_string($babase->DB_CONN, $usernameRaw);
      $clientIp = isset($_SERVER["REMOTE_ADDR"]) ? (string)$_SERVER["REMOTE_ADDR"] : "0.0.0.0";
      $throttleKey = sha1(strtolower(trim($usernameRaw)) . "|" . $clientIp);
      $throttle = (isset($_SESSION["login_throttle"]) && is_array($_SESSION["login_throttle"])) ? $_SESSION["login_throttle"] : array();
      $throttleEntry = (isset($throttle[$throttleKey]) && is_array($throttle[$throttleKey])) ? $throttle[$throttleKey] : array("fails" => 0, "lockedUntil" => 0);
      $nowTs = time();
      if ((int)$throttleEntry["lockedUntil"] > $nowTs)
      {
         $remaining = (int)$throttleEntry["lockedUntil"] - $nowTs;
         gl_render_login_form("Trop de tentatives. Réessayez dans " . $remaining . " seconde(s).");
      }

      $recaptchaSettings = gl_login_recaptcha_settings();
      if (gl_login_recaptcha_is_active($recaptchaSettings))
      {
         $captchaResponse = isset($_POST["g-recaptcha-response"]) ? (string)$_POST["g-recaptcha-response"] : "";
         $captchaResponse = html_entity_decode($captchaResponse, ENT_QUOTES, "UTF-8");
         $captchaCheck = gl_login_recaptcha_verify(
            $recaptchaSettings["secretKey"],
            $captchaResponse,
            $clientIp,
            isset($recaptchaSettings["action"]) ? $recaptchaSettings["action"] : "login",
            isset($recaptchaSettings["minScore"]) ? $recaptchaSettings["minScore"] : 0.50
         );
         if (empty($captchaCheck["ok"]))
         {
            $throttle = gl_login_register_failure($throttle, $throttleKey, $throttleEntry, $nowTs);
            $_SESSION["login_throttle"] = $throttle;
            usleep(random_int(250000, 650000));
            gl_render_login_form(isset($captchaCheck["message"]) ? $captchaCheck["message"] : "Validation reCAPTCHA échouée.");
         }
      }

      $user = $babase->DbSelect("SELECT * FROM users WHERE `username`='$username' AND `active`=1 LIMIT 1",$nb);
      $isPasswordValid = false;
      if ($nb == 1)
         $isPasswordValid = gl_password_verify_compat($passwordRaw, $user[0]["password"]);

      if ($isPasswordValid)
      {
         if (gl_password_is_legacy_hash($user[0]["password"]))
         {
            $newHash = gl_password_hash_modern($passwordRaw);
            if ($newHash != "")
            {
               $safeHash = mysqli_real_escape_string($babase->DB_CONN, $newHash);
               $babase->DbQuery("UPDATE users SET `password`='" . $safeHash . "' WHERE `idUser`='" . ((int)$user[0]["idUser"]) . "' LIMIT 1");
               $user[0]["password"] = $newHash;
            }
         }
      }

      $group = false;
      $nbGroup = 0;
      if ($nb == 1)
         $group = $babase->DbSelect("SELECT * FROM `groups` WHERE idGroup='".$user[0]["idGroup"]."' LIMIT 1", $nbGroup);

      if (($nb==1) AND $isPasswordValid AND ($nbGroup==1))
      {
		 // Login Ok !
         session_regenerate_id(true);
         $babase->DbQuery("UPDATE users SET `lastLogin`='" . (int)$nowTs . "' WHERE `idUser`='" . ((int)$user[0]["idUser"]) . "' LIMIT 1");
		 $_SESSION["user_logged_in"] = true;
		 $_SESSION["idUser"] = $user[0]["idUser"];
		 $_SESSION["idGroup"] = $user[0]["idGroup"];
		 $_SESSION["username"] = $user[0]["username"];
		 $_SESSION["name"] = $user[0]["name"];
		 $_SESSION["groupName"] = $group[0]["groupName"];
		 $_SESSION["email"] = $user[0]["email"];
		 $_SESSION["admin"] = $user[0]["admin"];
		 $_SESSION["lastLogin"] = $nowTs;
         if (isset($throttle[$throttleKey]))
            unset($throttle[$throttleKey]);
         $_SESSION["login_throttle"] = $throttle;
      }
      else
      {
         $throttle = gl_login_register_failure($throttle, $throttleKey, $throttleEntry, $nowTs);
         $_SESSION["login_throttle"] = $throttle;
         usleep(random_int(250000, 650000));
		 // Login failed !
         gl_render_login_form($txt["loginFailed"]);
      }
   }
   else
   {
      gl_render_login_form();
   }
}

?>

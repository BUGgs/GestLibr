<?php

function param_is_admin()
{
    return (isset($_SESSION["admin"]) && (int)$_SESSION["admin"] === 1);
}

function param_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function param_sql($value)
{
    global $babase;
    return mysqli_real_escape_string($babase->DB_CONN, (string)$value);
}

function param_password_hash_modern($password)
{
    if (function_exists("password_hash"))
        return password_hash((string)$password, PASSWORD_DEFAULT);
    return md5((string)$password);
}

function param_fetch_groups()
{
    global $babase;
    $rows = $babase->DbSelect("SELECT * FROM `groups` ORDER BY `groupName` ASC", $nb);
    if (!is_array($rows))
        return array();
    return $rows;
}

function param_fetch_group($idGroup)
{
    global $babase;
    $idGroup = (int)$idGroup;
    if ($idGroup <= 0)
        return false;
    $row = $babase->DbSelect("SELECT * FROM `groups` WHERE `idGroup`='" . $idGroup . "' LIMIT 1", $nb);
    if (!is_array($row) || $nb !== 1)
        return false;
    return $row[0];
}

function param_group_exists($idGroup)
{
    return (param_fetch_group($idGroup) !== false);
}

function param_group_user_count($idGroup)
{
    global $babase;
    $idGroup = (int)$idGroup;
    $row = $babase->DbSelect("SELECT COUNT(*) AS nb FROM `users` WHERE `idGroup`='" . $idGroup . "'", $nb);
    if (is_array($row) && $nb === 1)
        return (int)$row[0]["nb"];
    return 0;
}

function param_fetch_users()
{
    global $babase;
    $sql = "SELECT u.*, g.groupName FROM `users` u LEFT JOIN `groups` g ON g.idGroup=u.idGroup ORDER BY u.username ASC";
    $rows = $babase->DbSelect($sql, $nb);
    if (!is_array($rows))
        return array();
    return $rows;
}

function param_fetch_user($idUser)
{
    global $babase;
    $idUser = (int)$idUser;
    if ($idUser <= 0)
        return false;
    $rows = $babase->DbSelect("SELECT * FROM `users` WHERE `idUser`='" . $idUser . "' LIMIT 1", $nb);
    if (!is_array($rows) || $nb !== 1)
        return false;
    return $rows[0];
}

function param_count_active_admins()
{
    global $babase;
    $rows = $babase->DbSelect("SELECT COUNT(*) AS nb FROM `users` WHERE `admin`='1' AND `active`='1'", $nb);
    if (is_array($rows) && $nb === 1)
        return (int)$rows[0]["nb"];
    return 0;
}

function param_user_exists_with_username($username, $excludeIdUser = 0)
{
    global $babase;
    $username = trim((string)$username);
    if ($username === "")
        return false;
    $sql = "SELECT `idUser` FROM `users` WHERE `username`='" . param_sql($username) . "'";
    $excludeIdUser = (int)$excludeIdUser;
    if ($excludeIdUser > 0)
        $sql .= " AND `idUser`<>'" . $excludeIdUser . "'";
    $sql .= " LIMIT 1";
    $rows = $babase->DbSelect($sql, $nb);
    return (is_array($rows) && $nb === 1);
}

function param_group_options_html($groups, $selectedIdGroup)
{
    $selectedIdGroup = (int)$selectedIdGroup;
    if (!is_array($groups))
        $groups = array();

    $html = "";
    $i = 0;
    while ($i < sizeof($groups))
    {
        $idGroup = (int)$groups[$i]["idGroup"];
        $selected = ($idGroup === $selectedIdGroup) ? " selected='selected'" : "";
        $html .= "<option value='" . $idGroup . "'" . $selected . ">" . param_h($groups[$i]["groupName"]) . " (#" . $idGroup . ")</option>";
        $i++;
    }
    return $html;
}

function param_validate_email($email)
{
    $email = trim((string)$email);
    return ($email !== "");
}

function param_refresh_session_from_user($user)
{
    if (!is_array($user))
        return;

    global $babase;
    $_SESSION["idUser"] = (int)$user["idUser"];
    $_SESSION["idGroup"] = (int)$user["idGroup"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["admin"] = (int)$user["admin"];
    $_SESSION["lastLogin"] = (int)$user["lastLogin"];

    $group = $babase->DbSelect("SELECT * FROM `groups` WHERE `idGroup`='" . ((int)$user["idGroup"]) . "' LIMIT 1", $nbGroup);
    if (is_array($group) && $nbGroup === 1)
        $_SESSION["groupName"] = $group[0]["groupName"];
}

function param_tab_link($url, $label, $isActive)
{
    $classes = "submit";
    if (!$isActive)
        $classes .= " submit--secondary";
    return "<a href='" . param_h($url) . "' class='" . $classes . "'>" . param_h($label) . "</a>";
}

function param_config_value_map($rows)
{
    $map = array();
    if (!is_array($rows))
        return $map;
    $i = 0;
    while ($i < sizeof($rows))
    {
        $name = isset($rows[$i]["name"]) ? trim((string)$rows[$i]["name"]) : "";
        if ($name != "")
            $map[$name] = isset($rows[$i]["value"]) ? (string)$rows[$i]["value"] : "";
        $i++;
    }
    return $map;
}

function param_env_or_config_value($envName, $configName, $configMap)
{
    $envValue = getenv($envName);
    if (($envValue !== false) && (trim((string)$envValue) != ""))
        return array("value" => trim((string)$envValue), "source" => "env:" . $envName);

    $cfgValue = (isset($configMap[$configName]) ? trim((string)$configMap[$configName]) : "");
    if ($cfgValue != "")
        return array("value" => $cfgValue, "source" => "config:" . $configName);

    return array("value" => "", "source" => "none");
}

function param_mask_secret($value)
{
    $value = trim((string)$value);
    if ($value == "")
        return "";
    $len = strlen($value);
    if ($len <= 6)
        return str_repeat("*", $len);
    return substr($value, 0, 3) . str_repeat("*", max(1, $len - 6)) . substr($value, -3);
}

function param_creators_token_endpoint_for_version($version)
{
    $version = trim((string)$version);
    switch ($version)
    {
        case "2.1":
            return "https://creatorsapi.auth.us-east-1.amazoncognito.com/oauth2/token";
        case "2.2":
            return "https://creatorsapi.auth.eu-south-2.amazoncognito.com/oauth2/token";
        case "2.3":
            return "https://creatorsapi.auth.us-west-2.amazoncognito.com/oauth2/token";
        case "3.1":
            return "https://api.amazon.com/auth/o2/token";
        case "3.2":
            return "https://api.amazon.co.uk/auth/o2/token";
        case "3.3":
            return "https://api.amazon.co.jp/auth/o2/token";
    }
    return "";
}

function param_creators_scope_for_version($version)
{
    $version = trim((string)$version);
    if (substr($version, 0, 2) === "3.")
        return "creatorsapi::default";
    return "creatorsapi/default";
}

function param_render_amazon_creators_status($configRows)
{
    $configMap = param_config_value_map($configRows);

    $credentialId = param_env_or_config_value("AMAZON_CREATORS_CREDENTIAL_ID", "amazon_creators_credential_id", $configMap);
    $credentialSecret = param_env_or_config_value("AMAZON_CREATORS_CREDENTIAL_SECRET", "amazon_creators_credential_secret", $configMap);
    $version = param_env_or_config_value("AMAZON_CREATORS_VERSION", "amazon_creators_version", $configMap);
    $partnerTag = param_env_or_config_value("AMAZON_CREATORS_PARTNER_TAG", "amazon_creators_partner_tag", $configMap);
    $marketplace = param_env_or_config_value("AMAZON_CREATORS_MARKETPLACE", "amazon_creators_marketplace", $configMap);
    $language = param_env_or_config_value("AMAZON_CREATORS_LANGUAGE", "amazon_creators_language", $configMap);
    $baseUrl = param_env_or_config_value("AMAZON_CREATORS_BASE_URL", "amazon_creators_base_url", $configMap);
    $tokenEndpoint = param_env_or_config_value("AMAZON_CREATORS_TOKEN_ENDPOINT", "amazon_creators_token_endpoint", $configMap);
    $scope = param_env_or_config_value("AMAZON_CREATORS_SCOPE", "amazon_creators_scope", $configMap);

    if ($version["value"] == "")
        $version["value"] = "2.2";
    if ($marketplace["value"] == "")
        $marketplace["value"] = "www.amazon.fr";
    if ($language["value"] == "")
        $language["value"] = "fr_FR";
    if ($baseUrl["value"] == "")
        $baseUrl["value"] = "https://creatorsapi.amazon";
    if ($tokenEndpoint["value"] == "")
        $tokenEndpoint["value"] = param_creators_token_endpoint_for_version($version["value"]);
    if ($scope["value"] == "")
        $scope["value"] = param_creators_scope_for_version($version["value"]);

    $missing = array();
    if ($credentialId["value"] == "")
        $missing[] = "Credential ID";
    if ($credentialSecret["value"] == "")
        $missing[] = "Credential Secret";
    if ($partnerTag["value"] == "")
        $missing[] = "Partner tag";
    if ($version["value"] == "")
        $missing[] = "Version";
    if ($tokenEndpoint["value"] == "")
        $missing[] = "Token endpoint";

    $legacyAccess = param_env_or_config_value("AMAZON_PAAPI_ACCESS_KEY", "amazon_paapi_access_key", $configMap);
    $legacySecret = param_env_or_config_value("AMAZON_PAAPI_SECRET_KEY", "amazon_paapi_secret_key", $configMap);
    $legacyTag = param_env_or_config_value("AMAZON_PAAPI_PARTNER_TAG", "amazon_paapi_partner_tag", $configMap);
    $legacyDetected = (($legacyAccess["value"] != "") || ($legacySecret["value"] != "") || ($legacyTag["value"] != ""));

    $content = "";
    $content .= "<fieldset class='inputs'>";
    $content .= "<h2>État Amazon Creators API (ISBN)</h2>";
    if (sizeof($missing) == 0)
        $content .= "<p class='ok'>Configuration Amazon Creators API complète (OK).</p>";
    else
        $content .= "<p class='err'>Configuration incomplète (KO): " . param_h(implode(", ", $missing)) . ".</p>";
    if ($legacyDetected)
        $content .= "<p class='warn'>Des paramètres legacy PA-API sont détectés, mais ils ne sont plus utilisés par GestLibr.</p>";

    $content .= "<table class='bordered'>";
    $content .= "<tr><th>Paramètre</th><th>Valeur</th><th>Source</th><th>Statut</th></tr>";
    $content .= "<tr><td>Credential ID</td><td>" . param_h($credentialId["value"] != "" ? $credentialId["value"] : "—") . "</td><td>" . param_h($credentialId["source"]) . "</td><td>" . ($credentialId["value"] != "" ? "OK" : "KO") . "</td></tr>";
    $content .= "<tr><td>Credential Secret</td><td>" . param_h($credentialSecret["value"] != "" ? param_mask_secret($credentialSecret["value"]) : "—") . "</td><td>" . param_h($credentialSecret["source"]) . "</td><td>" . ($credentialSecret["value"] != "" ? "OK" : "KO") . "</td></tr>";
    $content .= "<tr><td>Version</td><td>" . param_h($version["value"]) . "</td><td>" . param_h($version["source"]) . "</td><td>" . ($version["value"] != "" ? "OK" : "KO") . "</td></tr>";
    $content .= "<tr><td>Partner tag</td><td>" . param_h($partnerTag["value"] != "" ? $partnerTag["value"] : "—") . "</td><td>" . param_h($partnerTag["source"]) . "</td><td>" . ($partnerTag["value"] != "" ? "OK" : "KO") . "</td></tr>";
    $content .= "<tr><td>Marketplace</td><td>" . param_h($marketplace["value"]) . "</td><td>" . param_h($marketplace["source"]) . "</td><td>OK</td></tr>";
    $content .= "<tr><td>Langue préférée</td><td>" . param_h($language["value"]) . "</td><td>" . param_h($language["source"]) . "</td><td>OK</td></tr>";
    $content .= "<tr><td>API base URL</td><td>" . param_h($baseUrl["value"]) . "</td><td>" . param_h($baseUrl["source"]) . "</td><td>OK</td></tr>";
    $content .= "<tr><td>Token endpoint</td><td>" . param_h($tokenEndpoint["value"] != "" ? $tokenEndpoint["value"] : "—") . "</td><td>" . param_h($tokenEndpoint["source"]) . "</td><td>" . ($tokenEndpoint["value"] != "" ? "OK" : "KO") . "</td></tr>";
    $content .= "<tr><td>Scope OAuth</td><td>" . param_h($scope["value"]) . "</td><td>" . param_h($scope["source"]) . "</td><td>OK</td></tr>";
    $content .= "</table>";
    $content .= "<p><small>Variables prises en charge: AMAZON_CREATORS_CREDENTIAL_ID, AMAZON_CREATORS_CREDENTIAL_SECRET, AMAZON_CREATORS_VERSION, AMAZON_CREATORS_PARTNER_TAG, AMAZON_CREATORS_MARKETPLACE, AMAZON_CREATORS_LANGUAGE, AMAZON_CREATORS_BASE_URL, AMAZON_CREATORS_TOKEN_ENDPOINT, AMAZON_CREATORS_SCOPE. Les valeurs <code>env</code> sont prioritaires sur la table <code>config</code>.</small></p>";
    $content .= "</fieldset>";
    return $content;
}

function param_ensure_config_key($name, $description, $defaultValue)
{
    global $babase;
    $name = trim((string)$name);
    if ($name === "")
        return;
    $rows = $babase->DbSelect("SELECT `id` FROM `config` WHERE `name`='" . param_sql($name) . "' LIMIT 1", $nb);
    if (($nb == 1) && is_array($rows))
        return;
    $babase->DbInsert(
        "INSERT INTO `config` (`id`,`name`,`description`,`value`) VALUES (" .
        "NULL,'" . param_sql($name) . "','" . param_sql($description) . "','" . param_sql($defaultValue) . "')",
        1
    );
}

function param_ensure_amazon_creators_config_keys()
{
    param_ensure_config_key("amazon_creators_credential_id", "Amazon Creators API: Credential ID (client_id)", "");
    param_ensure_config_key("amazon_creators_credential_secret", "Amazon Creators API: Credential Secret (client_secret)", "");
    param_ensure_config_key("amazon_creators_version", "Amazon Creators API: version credential (2.1/2.2/2.3 ou 3.1/3.2/3.3)", "2.2");
    param_ensure_config_key("amazon_creators_partner_tag", "Amazon Creators API: Partner tag (tracking/store ID)", "");
    param_ensure_config_key("amazon_creators_marketplace", "Amazon Creators API: marketplace cible (ex: www.amazon.fr)", "www.amazon.fr");
    param_ensure_config_key("amazon_creators_language", "Amazon Creators API: langue préférée (ex: fr_FR)", "fr_FR");
    param_ensure_config_key("amazon_creators_base_url", "Amazon Creators API: base URL API", "https://creatorsapi.amazon");
    param_ensure_config_key("amazon_creators_token_endpoint", "Amazon Creators API: token endpoint OAuth (optionnel, auto si vide)", "");
    param_ensure_config_key("amazon_creators_scope", "Amazon Creators API: scope OAuth (optionnel, auto si vide)", "");
}

function param_ensure_ebay_global_keyset_config_keys()
{
    param_ensure_config_key("ebay_client_id_sandbox", "eBay OAuth sandbox client id (keyset global)", "");
    param_ensure_config_key("ebay_client_secret_sandbox", "eBay OAuth sandbox client secret (keyset global)", "");
    param_ensure_config_key("ebay_redirect_uri_sandbox", "eBay OAuth sandbox RuName/redirect URI (keyset global)", "");
    param_ensure_config_key("ebay_client_id_production", "eBay OAuth production client id (keyset global)", "");
    param_ensure_config_key("ebay_client_secret_production", "eBay OAuth production client secret (keyset global)", "");
    param_ensure_config_key("ebay_redirect_uri_production", "eBay OAuth production RuName/redirect URI (keyset global)", "");
}

function param_render_profile_page(&$errorMsg, &$successMsg)
{
    global $babase;

    $idUser = isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0;
    $user = param_fetch_user($idUser);
    if (!$user)
    {
        $errorMsg = "Utilisateur introuvable.";
        return "<p class='err'>Utilisateur introuvable.</p>";
    }

    if (isset($_POST["param_action"]) && $_POST["param_action"] === "me_save")
    {
        $name = trim((string)(isset($_POST["me_name"]) ? $_POST["me_name"] : ""));
        $email = trim((string)(isset($_POST["me_email"]) ? $_POST["me_email"] : ""));
        $newPassword = (string)(isset($_POST["me_new_password"]) ? $_POST["me_new_password"] : "");
        $confirmPassword = (string)(isset($_POST["me_confirm_password"]) ? $_POST["me_confirm_password"] : "");

        if ($name === "")
            $errorMsg = "Le nom est obligatoire.";
        else if (!param_validate_email($email))
            $errorMsg = "L'email est obligatoire.";
        else if ($newPassword !== "" && strlen($newPassword) < 8)
            $errorMsg = "Le mot de passe doit contenir au moins 8 caractères.";
        else if ($newPassword !== "" && $newPassword !== $confirmPassword)
            $errorMsg = "La confirmation du mot de passe est invalide.";
        else
        {
            $sqlSet = array();
            $sqlSet[] = "`name`='" . param_sql($name) . "'";
            $sqlSet[] = "`email`='" . param_sql($email) . "'";
            if ($newPassword !== "")
                $sqlSet[] = "`password`='" . param_sql(param_password_hash_modern($newPassword)) . "'";

            $babase->DbQuery("UPDATE `users` SET " . implode(", ", $sqlSet) . " WHERE `idUser`='" . $idUser . "' LIMIT 1");
            $user = param_fetch_user($idUser);
            param_refresh_session_from_user($user);
            $successMsg = "Profil utilisateur mis à jour.";
        }
    }

    $content = "";
    $content .= "<fieldset class='inputs'>";
    $content .= "<h2>Utilisateur</h2>";
    $content .= "<form action='?m=pa&a=me' method='post'>";
    $content .= "<input type='hidden' name='m' value='pa'>";
    $content .= "<input type='hidden' name='a' value='me'>";
    $content .= "<input type='hidden' name='param_action' value='me_save'>";
    $content .= "<table class='bordered'>";
    $content .= "<tr><td>Username</td><td><input type='text' readonly='readonly' value='" . param_h($user["username"]) . "'></td></tr>";
    $content .= "<tr><td>Nom</td><td><input type='text' name='me_name' value='" . param_h($user["name"]) . "'></td></tr>";
    $content .= "<tr><td>Email</td><td><input type='email' name='me_email' value='" . param_h($user["email"]) . "'></td></tr>";
    $content .= "<tr><td>Nouveau mot de passe</td><td><input type='password' name='me_new_password' value=''><br><small>Laisser vide pour conserver le mot de passe actuel.</small></td></tr>";
    $content .= "<tr><td>Confirmation</td><td><input type='password' name='me_confirm_password' value=''></td></tr>";
    $content .= "</table>";
    $content .= "<div class='actions'><input class='submit' type='submit' value='Enregistrer'></div>";
    $content .= "</form>";
    $content .= "</fieldset>";
    return $content;
}

function param_render_config_page(&$errorMsg, &$successMsg)
{
    global $babase;

    if (!param_is_admin())
    {
        $errorMsg = "Accès refusé: droits administrateur requis.";
        return "<p class='err'>" . param_h($errorMsg) . "</p>";
    }

    param_ensure_amazon_creators_config_keys();
    param_ensure_ebay_global_keyset_config_keys();

    if (isset($_POST["param_action"]) && $_POST["param_action"] === "cfg_save")
    {
        $valueMap = (isset($_POST["cfg_value"]) && is_array($_POST["cfg_value"])) ? $_POST["cfg_value"] : array();
        $updated = 0;
        foreach ($valueMap as $id => $value)
        {
            if (!ctype_digit((string)$id))
                continue;

            $value = (string)$value;
            $sql = "UPDATE `config` SET `value`='" . param_sql($value) . "' WHERE `id`='" . ((int)$id) . "' LIMIT 1";
            $babase->DbQuery($sql);
            $updated++;
        }
        $successMsg = "Réglages enregistrés (" . $updated . " ligne(s)).";
    }

    $rows = $babase->DbSelect("SELECT * FROM `config` ORDER BY `id` ASC", $nb);
    if (!is_array($rows))
        $rows = array();

    $content = "";
    $content .= param_render_amazon_creators_status($rows);
    $content .= "<fieldset class='inputs'>";
    $content .= "<h2>Réglages système (table config)</h2>";
    $content .= "<form action='?m=pa&a=cfg' method='post'>";
    $content .= "<input type='hidden' name='m' value='pa'>";
    $content .= "<input type='hidden' name='a' value='cfg'>";
    $content .= "<input type='hidden' name='param_action' value='cfg_save'>";
    $content .= "<table class='bordered'>";
    $content .= "<tr><th>ID</th><th>Clé</th><th>Description</th><th>Valeur</th></tr>";
    $i = 0;
    while ($i < sizeof($rows))
    {
        $id = (int)$rows[$i]["id"];
        $content .= "<tr>";
        $content .= "<td>" . $id . "</td>";
        $content .= "<td><input type='text' readonly='readonly' value='" . param_h($rows[$i]["name"]) . "'></td>";
        $content .= "<td><textarea rows='2' readonly='readonly'>" . param_h($rows[$i]["description"]) . "</textarea></td>";
        $content .= "<td><textarea name='cfg_value[" . $id . "]' rows='2'>" . param_h($rows[$i]["value"]) . "</textarea></td>";
        $content .= "</tr>";
        $i++;
    }
    $content .= "</table>";
    $content .= "<div class='actions'><input class='submit' type='submit' value='Enregistrer les réglages'></div>";
    $content .= "</form>";
    $content .= "</fieldset>";
    return $content;
}

function param_handle_group_actions(&$errorMsg, &$successMsg, &$selectedGroupId)
{
    global $babase;

    if (!isset($_POST["param_action"]))
        return;

    $action = (string)$_POST["param_action"];
    if ($action === "group_create")
    {
        $groupName = trim((string)(isset($_POST["group_name_create"]) ? $_POST["group_name_create"] : ""));
        if ($groupName === "")
            $errorMsg = "Le nom de groupe est obligatoire.";
        else
        {
            $babase->DbInsert("INSERT INTO `groups` (`idGroup`, `groupName`) VALUES ('', '" . param_sql($groupName) . "')", 1);
            $successMsg = "Groupe créé.";
        }
    }
    else if ($action === "group_update")
    {
        $idGroup = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
        $groupName = trim((string)(isset($_POST["group_name"]) ? $_POST["group_name"] : ""));
        $selectedGroupId = $idGroup;
        if ($idGroup <= 0 || !param_group_exists($idGroup))
            $errorMsg = "Groupe introuvable.";
        else if ($groupName === "")
            $errorMsg = "Le nom de groupe est obligatoire.";
        else
        {
            $babase->DbQuery("UPDATE `groups` SET `groupName`='" . param_sql($groupName) . "' WHERE `idGroup`='" . $idGroup . "' LIMIT 1");
            $successMsg = "Groupe mis à jour.";
        }
    }
    else if ($action === "group_delete")
    {
        $idGroup = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
        if ($idGroup <= 0 || !param_group_exists($idGroup))
            $errorMsg = "Groupe introuvable.";
        else if (param_group_user_count($idGroup) > 0)
            $errorMsg = "Suppression impossible: des utilisateurs sont encore rattachés à ce groupe.";
        else
        {
            $babase->DbQuery("DELETE FROM `groups` WHERE `idGroup`='" . $idGroup . "' LIMIT 1");
            $successMsg = "Groupe supprimé.";
            if ($selectedGroupId === $idGroup)
                $selectedGroupId = 0;
        }
    }
}

function param_handle_user_actions(&$errorMsg, &$successMsg, &$selectedUserId)
{
    global $babase;

    if (!isset($_POST["param_action"]))
        return;

    $action = (string)$_POST["param_action"];
    if ($action === "user_create")
    {
        $username = trim((string)(isset($_POST["user_username_create"]) ? $_POST["user_username_create"] : ""));
        $name = trim((string)(isset($_POST["user_name_create"]) ? $_POST["user_name_create"] : ""));
        $email = trim((string)(isset($_POST["user_email_create"]) ? $_POST["user_email_create"] : ""));
        $idGroup = isset($_POST["user_group_create"]) ? (int)$_POST["user_group_create"] : 0;
        $admin = isset($_POST["user_admin_create"]) ? 1 : 0;
        $active = isset($_POST["user_active_create"]) ? 1 : 0;
        $password = (string)(isset($_POST["user_password_create"]) ? $_POST["user_password_create"] : "");
        $passwordConfirm = (string)(isset($_POST["user_password_confirm_create"]) ? $_POST["user_password_confirm_create"] : "");

        if ($username === "")
            $errorMsg = "Le username est obligatoire.";
        else if (param_user_exists_with_username($username))
            $errorMsg = "Ce username existe déjà.";
        else if ($name === "")
            $errorMsg = "Le nom est obligatoire.";
        else if (!param_validate_email($email))
            $errorMsg = "L'email est obligatoire.";
        else if (!param_group_exists($idGroup))
            $errorMsg = "Le groupe sélectionné est invalide.";
        else if ($password === "" || strlen($password) < 8)
            $errorMsg = "Le mot de passe est obligatoire (8 caractères minimum).";
        else if ($password !== $passwordConfirm)
            $errorMsg = "La confirmation du mot de passe est invalide.";
        else
        {
            $hash = param_password_hash_modern($password);
            $sql = "INSERT INTO `users` (`idUser`,`username`,`password`,`name`,`email`,`admin`,`idGroup`,`lastLogin`,`active`) VALUES ('', '" .
                param_sql($username) . "', '" . param_sql($hash) . "', '" . param_sql($name) . "', '" . param_sql($email) . "', '" . $admin . "', '" . $idGroup . "', '0', '" . $active . "')";
            $newId = $babase->DbInsert($sql, 1);
            $selectedUserId = (int)$newId;
            $successMsg = "Utilisateur créé.";
        }
    }
    else if ($action === "user_update")
    {
        $idUser = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;
        $selectedUserId = $idUser;
        $current = param_fetch_user($idUser);
        if (!$current)
        {
            $errorMsg = "Utilisateur introuvable.";
            return;
        }

        $username = trim((string)(isset($_POST["user_username"]) ? $_POST["user_username"] : ""));
        $name = trim((string)(isset($_POST["user_name"]) ? $_POST["user_name"] : ""));
        $email = trim((string)(isset($_POST["user_email"]) ? $_POST["user_email"] : ""));
        $idGroup = isset($_POST["user_group"]) ? (int)$_POST["user_group"] : 0;
        $admin = isset($_POST["user_admin"]) ? 1 : 0;
        $active = isset($_POST["user_active"]) ? 1 : 0;
        $newPassword = (string)(isset($_POST["user_password"]) ? $_POST["user_password"] : "");
        $newPasswordConfirm = (string)(isset($_POST["user_password_confirm"]) ? $_POST["user_password_confirm"] : "");

        $isSelf = ($idUser === (int)$_SESSION["idUser"]);
        $isLastActiveAdmin = ((int)$current["admin"] === 1 && (int)$current["active"] === 1 && param_count_active_admins() <= 1);

        if ($username === "")
            $errorMsg = "Le username est obligatoire.";
        else if (param_user_exists_with_username($username, $idUser))
            $errorMsg = "Ce username existe déjà.";
        else if ($name === "")
            $errorMsg = "Le nom est obligatoire.";
        else if (!param_validate_email($email))
            $errorMsg = "L'email est obligatoire.";
        else if (!param_group_exists($idGroup))
            $errorMsg = "Le groupe sélectionné est invalide.";
        else if ($isSelf && $active === 0)
            $errorMsg = "Action refusée: vous ne pouvez pas désactiver votre propre compte.";
        else if ($isLastActiveAdmin && ($admin === 0 || $active === 0))
            $errorMsg = "Action refusée: impossible de désactiver/supprimer le dernier administrateur actif.";
        else if ($newPassword !== "" && strlen($newPassword) < 8)
            $errorMsg = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        else if ($newPassword !== "" && $newPassword !== $newPasswordConfirm)
            $errorMsg = "La confirmation du mot de passe est invalide.";
        else
        {
            $set = array();
            $set[] = "`username`='" . param_sql($username) . "'";
            $set[] = "`name`='" . param_sql($name) . "'";
            $set[] = "`email`='" . param_sql($email) . "'";
            $set[] = "`idGroup`='" . $idGroup . "'";
            $set[] = "`admin`='" . $admin . "'";
            $set[] = "`active`='" . $active . "'";
            if ($newPassword !== "")
                $set[] = "`password`='" . param_sql(param_password_hash_modern($newPassword)) . "'";

            $babase->DbQuery("UPDATE `users` SET " . implode(", ", $set) . " WHERE `idUser`='" . $idUser . "' LIMIT 1");
            $successMsg = "Utilisateur mis à jour.";

            if ($isSelf)
            {
                $updatedSelf = param_fetch_user($idUser);
                if ($updatedSelf)
                    param_refresh_session_from_user($updatedSelf);
            }
        }
    }
    else if ($action === "user_delete")
    {
        $idUser = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;
        $current = param_fetch_user($idUser);
        if (!$current)
            $errorMsg = "Utilisateur introuvable.";
        else if ($idUser === (int)$_SESSION["idUser"])
            $errorMsg = "Action refusée: vous ne pouvez pas supprimer votre propre compte.";
        else if ((int)$current["admin"] === 1 && (int)$current["active"] === 1 && param_count_active_admins() <= 1)
            $errorMsg = "Action refusée: impossible de supprimer le dernier administrateur actif.";
        else
        {
            $babase->DbQuery("DELETE FROM `users` WHERE `idUser`='" . $idUser . "' LIMIT 1");
            $successMsg = "Utilisateur supprimé.";
            if ($selectedUserId === $idUser)
                $selectedUserId = 0;
        }
    }
}

function param_render_users_page(&$errorMsg, &$successMsg)
{
    if (!param_is_admin())
    {
        $errorMsg = "Accès refusé: droits administrateur requis.";
        return "<p class='err'>" . param_h($errorMsg) . "</p>";
    }

    $selectedUserId = isset($_GET["edit_user_id"]) ? (int)$_GET["edit_user_id"] : 0;
    $selectedGroupId = isset($_GET["edit_group_id"]) ? (int)$_GET["edit_group_id"] : 0;
    if (isset($_POST["edit_user_id"]))
        $selectedUserId = (int)$_POST["edit_user_id"];
    if (isset($_POST["edit_group_id"]))
        $selectedGroupId = (int)$_POST["edit_group_id"];

    param_handle_group_actions($errorMsg, $successMsg, $selectedGroupId);
    if ($errorMsg === "")
        param_handle_user_actions($errorMsg, $successMsg, $selectedUserId);

    $groups = param_fetch_groups();
    $users = param_fetch_users();
    $selectedUser = param_fetch_user($selectedUserId);
    $selectedGroup = param_fetch_group($selectedGroupId);

    $content = "";
    $content .= "<fieldset class='inputs'>";
    $content .= "<h2>Groupes</h2>";

    $content .= "<form action='?m=pa&a=usr' method='post'>";
    $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='group_create'>";
    $content .= "<div class='actions'><input type='text' name='group_name_create' placeholder='Nom du nouveau groupe'><input class='submit' type='submit' value='Créer groupe'></div>";
    $content .= "</form>";

    $content .= "<table class='bordered'>";
    $content .= "<tr><th>ID</th><th>Nom</th><th>Utilisateurs</th><th>Actions</th></tr>";
    $i = 0;
    while ($i < sizeof($groups))
    {
        $idGroup = (int)$groups[$i]["idGroup"];
        $content .= "<tr>";
        $content .= "<td>" . $idGroup . "</td>";
        $content .= "<td>" . param_h($groups[$i]["groupName"]) . "</td>";
        $content .= "<td>" . param_group_user_count($idGroup) . "</td>";
        $content .= "<td>";
        $content .= "<a class='submit submit--secondary' href='?m=pa&a=usr&edit_group_id=" . $idGroup . "'>Éditer</a> ";
        $content .= "<form action='?m=pa&a=usr' method='post' style='display:inline;' onsubmit='return confirm(\"Supprimer ce groupe ?\");'>";
        $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='group_delete'><input type='hidden' name='group_id' value='" . $idGroup . "'>";
        $content .= "<input class='submit submit--secondary' type='submit' value='Supprimer'>";
        $content .= "</form>";
        $content .= "</td>";
        $content .= "</tr>";
        $i++;
    }
    $content .= "</table>";

    if ($selectedGroup)
    {
        $content .= "<h3 style='margin-top:12px;'>Modifier groupe #" . (int)$selectedGroup["idGroup"] . "</h3>";
        $content .= "<form action='?m=pa&a=usr' method='post'>";
        $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='group_update'>";
        $content .= "<input type='hidden' name='group_id' value='" . (int)$selectedGroup["idGroup"] . "'>";
        $content .= "<div class='actions'><input type='text' name='group_name' value='" . param_h($selectedGroup["groupName"]) . "'><input class='submit' type='submit' value='Enregistrer groupe'></div>";
        $content .= "</form>";
    }

    $content .= "</fieldset>";

    $content .= "<fieldset class='inputs'>";
    $content .= "<h2>Utilisateurs</h2>";

    $content .= "<h3>Créer un utilisateur</h3>";
    $content .= "<form action='?m=pa&a=usr' method='post'>";
    $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='user_create'>";
    $content .= "<table class='bordered'>";
    $content .= "<tr><td>Username</td><td><input type='text' name='user_username_create' value=''></td></tr>";
    $content .= "<tr><td>Nom</td><td><input type='text' name='user_name_create' value=''></td></tr>";
    $content .= "<tr><td>Email</td><td><input type='email' name='user_email_create' value=''></td></tr>";
    $content .= "<tr><td>Groupe</td><td><select name='user_group_create'>" . param_group_options_html($groups, (isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0)) . "</select></td></tr>";
    $content .= "<tr><td>Admin</td><td><input type='checkbox' name='user_admin_create' value='1'></td></tr>";
    $content .= "<tr><td>Actif</td><td><input type='checkbox' name='user_active_create' value='1' checked='checked'></td></tr>";
    $content .= "<tr><td>Mot de passe</td><td><input type='password' name='user_password_create' value=''></td></tr>";
    $content .= "<tr><td>Confirmation</td><td><input type='password' name='user_password_confirm_create' value=''></td></tr>";
    $content .= "</table>";
    $content .= "<div class='actions'><input class='submit' type='submit' value='Créer utilisateur'></div>";
    $content .= "</form>";

    $content .= "<h3 style='margin-top:12px;'>Liste des utilisateurs</h3>";
    $content .= "<table class='bordered'>";
    $content .= "<tr><th>ID</th><th>Username</th><th>Nom</th><th>Email</th><th>Groupe</th><th>Admin</th><th>Actif</th><th>Actions</th></tr>";
    $i = 0;
    while ($i < sizeof($users))
    {
        $idUser = (int)$users[$i]["idUser"];
        $content .= "<tr>";
        $content .= "<td>" . $idUser . "</td>";
        $content .= "<td>" . param_h($users[$i]["username"]) . "</td>";
        $content .= "<td>" . param_h($users[$i]["name"]) . "</td>";
        $content .= "<td>" . param_h($users[$i]["email"]) . "</td>";
        $content .= "<td>" . param_h($users[$i]["groupName"]) . " (#" . (int)$users[$i]["idGroup"] . ")</td>";
        $content .= "<td>" . ((int)$users[$i]["admin"] === 1 ? "Oui" : "Non") . "</td>";
        $content .= "<td>" . ((int)$users[$i]["active"] === 1 ? "Oui" : "Non") . "</td>";
        $content .= "<td>";
        $content .= "<a class='submit submit--secondary' href='?m=pa&a=usr&edit_user_id=" . $idUser . "'>Éditer</a> ";
        $content .= "<form action='?m=pa&a=usr' method='post' style='display:inline;' onsubmit='return confirm(\"Supprimer cet utilisateur ?\");'>";
        $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='user_delete'><input type='hidden' name='user_id' value='" . $idUser . "'>";
        $content .= "<input class='submit submit--secondary' type='submit' value='Supprimer'>";
        $content .= "</form>";
        $content .= "</td>";
        $content .= "</tr>";
        $i++;
    }
    $content .= "</table>";

    if ($selectedUser)
    {
        $content .= "<h3 style='margin-top:12px;'>Modifier utilisateur #" . (int)$selectedUser["idUser"] . "</h3>";
        $content .= "<form action='?m=pa&a=usr' method='post'>";
        $content .= "<input type='hidden' name='m' value='pa'><input type='hidden' name='a' value='usr'><input type='hidden' name='param_action' value='user_update'>";
        $content .= "<input type='hidden' name='user_id' value='" . (int)$selectedUser["idUser"] . "'>";
        $content .= "<table class='bordered'>";
        $content .= "<tr><td>Username</td><td><input type='text' name='user_username' value='" . param_h($selectedUser["username"]) . "'></td></tr>";
        $content .= "<tr><td>Nom</td><td><input type='text' name='user_name' value='" . param_h($selectedUser["name"]) . "'></td></tr>";
        $content .= "<tr><td>Email</td><td><input type='email' name='user_email' value='" . param_h($selectedUser["email"]) . "'></td></tr>";
        $content .= "<tr><td>Groupe</td><td><select name='user_group'>" . param_group_options_html($groups, (int)$selectedUser["idGroup"]) . "</select></td></tr>";
        $content .= "<tr><td>Admin</td><td><input type='checkbox' name='user_admin' value='1'" . (((int)$selectedUser["admin"] === 1) ? " checked='checked'" : "") . "></td></tr>";
        $content .= "<tr><td>Actif</td><td><input type='checkbox' name='user_active' value='1'" . (((int)$selectedUser["active"] === 1) ? " checked='checked'" : "") . "></td></tr>";
        $content .= "<tr><td>Nouveau mot de passe</td><td><input type='password' name='user_password' value=''></td></tr>";
        $content .= "<tr><td>Confirmation</td><td><input type='password' name='user_password_confirm' value=''></td></tr>";
        $content .= "</table>";
        $content .= "<div class='actions'><input class='submit' type='submit' value='Enregistrer utilisateur'></div>";
        $content .= "</form>";
    }

    $content .= "</fieldset>";
    return $content;
}

$view = ($a === "cfg" || $a === "usr" || $a === "me") ? $a : "me";
$isAdmin = param_is_admin();
$errorMsg = "";
$successMsg = "";

$tabMe = param_tab_link("?m=pa&a=me", "Utilisateur", $view === "me");
$tabCfg = $isAdmin ? param_tab_link("?m=pa&a=cfg", "Réglages", $view === "cfg") : "";
$tabUsr = $isAdmin ? param_tab_link("?m=pa&a=usr", "Utilisateurs", $view === "usr") : "";

$title = "Paramétrages";
$content = "";
if ($view === "cfg")
{
    $title = "Paramétrages - Réglages";
    $content = param_render_config_page($errorMsg, $successMsg);
}
else if ($view === "usr")
{
    $title = "Paramétrages - Utilisateurs";
    $content = param_render_users_page($errorMsg, $successMsg);
}
else
{
    $title = "Paramétrages - Utilisateur";
    $content = param_render_profile_page($errorMsg, $successMsg);
}

$template->set_filenames(array('body' => 'param_page.tpl'));
$template->assign_vars(array(
    'PARAM_TITLE' => $title,
    'PARAM_TAB_ME' => $tabMe,
    'PARAM_TAB_CFG' => $tabCfg,
    'PARAM_TAB_USR' => $tabUsr,
    'PARAM_ERROR' => $errorMsg,
    'PARAM_SUCCESS' => $successMsg,
    'PARAM_CONTENT' => $content
));
$template->pparse('body');

?>

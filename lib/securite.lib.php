<?php

if ($_GET["m"]=="lo")
{
   // Logout attempt
   $_SESSION["user_logged_in"]=false;
   session_destroy();
}

if ($_SESSION["user_logged_in"])
{
   // Just checking that user is correctly logged-in
   $user = $babase->DbSelect("SELECT * FROM users WHERE `username` like '".$_SESSION["username"]."' AND `password`='".$_SESSION["password"]."' AND `active`=1",$nb);
   if ($nb!=1)
   {
      // Error in the login check !
      session_destroy();
      // We show the login form
      $template->set_filenames(array('login' => 'login.tpl'));
      $template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
      $template->assign_vars(array('TEMPLATE_FOLDER'=> $template_path.$template_name, 'USERNAME' => $txt["username"], 'PASSWORD' => $txt["password"], 'FORGOT_PASSWORD' => $txt["ForgetPassword"], 'LOGIN' => $txt["login"]));
      $template->pparse('login');
      die();
   }
}
else
{
   // Not yet logged-in, so it could be a login attempt or we display the login form
   if ($_POST["m"]=="li")
   {
      // Login attempt
      $username = mysql_real_escape_string($_POST["username"]);
      $password = md5(mysql_real_escape_string($_POST["password"]));
      $user = $babase->DbSelect("SELECT * FROM users WHERE `username` like '$username' AND `password`='$password' AND `active`=1",$nb);
      $group = $babase->DbSelect("SELECT * FROM groups WHERE idGroup='".$user[0]["idGroup"]."'", $nbGroup);
      if (($nb==1) AND ($nbGroup==1))
      {
	 // Login Ok !
	 $_SESSION["user_logged_in"] = true;
	 $_SESSION["idUser"] = $user[0]["idUser"];
	 $_SESSION["idGroup"] = $user[0]["idGroup"];
	 $_SESSION["username"] = $user[0]["username"];
	 $_SESSION["name"] = $user[0]["name"];
	 $_SESSION["groupName"] = $group[0]["groupName"];
	 $_SESSION["email"] = $user[0]["email"];
	 $_SESSION["admin"] = $user[0]["admin"];
	 $_SESSION["lastLogin"] = $user[0]["lastLogin"];	 
	 $_SESSION["password"] = $user[0]["password"];	 
      }
      else
      {
	 // Login failed !
         // We show the login form
         $template->set_filenames(array('login' => 'login.tpl'));
         $template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
         $template->assign_vars(array('TEMPLATE_FOLDER'=> $template_path.$template_name, 'USERNAME' => $txt["username"], 'PASSWORD' => $txt["password"], 'FORGOT_PASSWORD' => $txt["ForgetPassword"], 'LOGIN' => $txt["login"]));
         $template->assign_vars(array('ERROR_MSG' => $txt["loginFailed"] . "SELECT * FROM users WHERE `username` like '$username' AND `password`='$password' AND `active`=1" . "<br>SELECT * FROM groups WHERE idGroup='".$user[0]["idGroup"]."'"));
	 $template->pparse('login');
         die();      
      }
   }
   else
   {
      // We show the login form
      $template->set_filenames(array('login' => 'login.tpl'));
      $template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
      $template->assign_vars(array('TEMPLATE_FOLDER'=> $template_path.$template_name, 'USERNAME' => $txt["username"], 'PASSWORD' => $txt["password"], 'FORGOT_PASSWORD' => $txt["ForgetPassword"], 'LOGIN' => $txt["login"]));
      $template->pparse('login');
      die();      
   }
}

?>

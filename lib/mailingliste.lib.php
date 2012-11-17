<?

/* 
----------- Variables ----------
*/
$tableEmail="email";
$tableMailingListe="mailingliste";
$msg["UrlFormPost"]="?action=mailingOk";
$msg["UrlEnvPost"]="/admin/index.php";


$msg["DejaInscrit"]="<center>D�sol�, vous �tes d�j� inscrit � cette mailing liste !<p><a href='javascript:history.back(-1)'>Retour � la page pr�c�dente</a></center>";
$msg["ErreurIns"]="<center>Il y a eu une erreur lors de votre inscription � la Mailing liste, veuillez recommencer<p><a href='javascript:history.back(-1)'>Retour � la page pr�c�dente</a></center>";
$msg["InsOk"]="<center>Votre inscription s'est d�roul�e correctement.<p>Vous allez recevoir un mail de confirmation.</center>";
$msg["ErreurPostManque"]="<center>Erreur, il manque le titre ou le corps du mail :<p>Titre : $titre<p>Corps : <br>$corps<p><a href='javascript:history.back(-1)'>Retour � la page pr�c�dente</a></center>";
$msg["PiedDeMail"]="------------------------------------------------\nCe mail vous est envoy� car vous vous �tes inscrit � cette mailing liste.\nContactez nous pour vous d�sinscrire.";
$msg["NbMsgEnvois"]="Nombre de messages � envoyer : ";
$msg["MailPour"]="Mail pour : ";
$msg["FinEnvois"]="<p>Fin des envois";
$msg["EnteteForm"]="Pour vous inscrire sur une mailing liste, s�lectionnez celle-ci et donner votre adresse mail dans le cadre ci-dessous :";
$msg["Sabonner"]="S'abonner";
$msg["MailConfSujet"]="Confirmation d'inscription � la mailing liste : ";

/* 
----------- Fin Variables ----------
*/

$db=new DataBase;
$db->DbConnect();

function envMail($dest,$sujet,$corps,$exp="")
{
/* permet d'envoyer des mails de mani�re transparente quelque soit l'h�bergeur
et sa m�thode de gestion de la fonction mail. Les 3 variables $TYPE_SERVEUR_MAIL, 
$EXP_MAIL_ADDR, $EXP_MAIL_DOMAINE doivent �tre d�clar�es et contenir les infos suivantes :
$TYPE_SERVEUR_MAIL :
m�thode d'envois des mails:
"php"    : m�thode par la fonction mail normale de php
"proxad" : m�thode par la fonction email d�velopp�e par Proxad
$EXP_MAIL_ADDR :
Nom de l'exp�diteur des mails SANS LE NOM DE DOMAINE
ex: "contact"
$EXP_MAIL_DOMAINE :
Domaine pour l'envoi des mails, utile
uniquement si TYPE_SERVEUR_MAIL vaut "php"
ex: "monsite.com"
*/
global $TYPE_SERVEUR_MAIL, $EXP_MAIL_ADDR, $EXP_MAIL_DOMAINE;
switch ($TYPE_SERVEUR_MAIL)
{
case "proxad":
  @email($EXP_MAIL_ADDR,$dest,$sujet,$corps);
	break;
default:
  if (($exp!="") and ($TYPE_SERVEUR_MAIL=="php")) @mail($dest,$sujet,$corps,"From: ".$exp."\nReply-To: ".$exp."\nX-Mailer: PHP/" . phpversion()."\nContent-Type: text/html; Charset=iso-8859-1");
  else @mail($dest,$sujet,$corps,"From: ".$EXP_MAIL_ADDR."@".$EXP_MAIL_DOMAINE."\nReply-To: ".$EXP_MAIL_ADDR."@".$EXP_MAIL_DOMAINE."\nX-Mailer: PHP/" . phpversion());
  break;
}
}

function mlFormIns()
{
  global $db,$msg, $tableEmail, $tableMailingListe;
	$mailingliste=$db->DbSelect("SELECT * FROM $tableMailingListe",$nbML);
	$result=$msg["EnteteForm"]."<br><form method='post' action='".$msg["UrlFormPost"]."'>
	<input type='text' size='30' name='formEmail'>
				<SELECT NAME='formIdml'>";
	$i=0;
	$selected=" SELECTED";
	while ($i!=$nbML)
	{
	  $result.="<option value='".$mailingliste[$i]["idMailingListe"]."'$selected>".$mailingliste[$i]["nom"]."</option>";
		$selected="";
		$i++;
	}
  $result.="</SELECT><input type='submit' value=\"".$msg["Sabonner"]."\"></form>";
  return($result);
}

function mlFormEnv($sujet,$corps)
{
  global $db,$msg, $tableEmail, $tableMailingListe;
	$result="<form method='POST' action='".$msg["UrlEnvPost"]."'>
  Sujet    : <input type='text' size='50' name='sujet' value='$sujet'>
  Corps 
  <textarea  cols=60 rows=20 name='corps'>$corps</textarea>
  password : <input type='password' size='10' name='pass'>
  <input type='submit' value='Envoyer'>
  </form>";
	return $result;
}

function mlInscription($email,$idml=0)
{
  global $db,$msg, $tableEmail, $tableMailingListe;
	$verif=$db->DbSelect("SELECT * FROM $tableEmail WHERE email like \"$email\" AND idMailingListe=$idml",$nbVerif);
  if ($nbVerif!=0)
	{
	  return($msg["DejaInscrit"]);
	}
	else
	{
	  srand(5);
		$codeActivation=rand(100000,999999);
	  $ins=$db->DbInsert("INSERT INTO $tableEmail (idEmail, email, idMailingListe, inscription, nbEmail, activation) VALUES (\"\", \"$email\", \"$idml\", \"".date('Y-m-d H:i:s')."\", \"0\", \"$codeActivation\")",1);
    if ($ins==0) { return($msg["ErreurIns"]); }
		else 
		{ 
		  $db->DbQuery("UPDATE $tableMailingListe SET nbInscrits=nbInscrits+1 WHERE idMailingListe=\"$idml\"");
			$ml=$db->DbSelect("SELECT * FROM $tableMailingListe WHERE idMailingListe=\"$idml\"",$nul);
      envMail($email,$msg["MailConfSujet"].$ml[0]["nom"],$ml[0]["msgBienvenue"]);
		  return($msg["InsOk"]); 
		}
	} 
}

function mlEnvoi($idml,$sujet,$corps)
{
  global $db,$msg, $tableEmail, $tableMailingListe;
  $sujet=stripslashes($sujet);
	$corps=stripslashes($corps);
	if (($sujet!="") AND ($corps!=""))
	{
		$mail=$db->DbSelect("SELECT email FROM $tableEmail WHERE idMailingListe=\"$idml\" ORDER BY email",$nbMail);
		$mailingliste=$db->DbSelect("SELECT msgEntete FROM $tableMailingListe WHERE idMailingListe=\"$idml\"",$nbML);
	  $result=$msg["NbMsgEnvois"]." $nbMail<p>";
    $i=0;
    while ($i!=$nbMail)
    {
      $result.=$msg["MailPour"].$mail[$i]["email"]."<br>";
      $corps.=$msg["PiedDeMail"];
      envMail($mail[$i]["email"],$mailingliste[0]["msgEntete"]." ".$sujet,$corps,$exp="");
      $i++;
    }
		$result.=$msg["FinEnvois"];
		$mailingliste=$db->DbQuery("UPDATE $tableMailingListe SET nbEmail=nbEmail+1 WHERE idMailingListe=\"$idml\"");
		$mail=$db->DbQuery("UPDATE $tableEmail SET nbEmail=nbEmail+1 WHERE idMailingListe=\"$idml\"");
		return($result);
	}
	else
	{
	  return($msg["ErreurPostManque"]);
  }
}
?>
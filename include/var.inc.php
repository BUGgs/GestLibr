<?
/* Variables Base de Données */
define('DB_SERVEUR', $config["dbServer"]);
define('DB_USER', $config["dbUser"]);
define('DB_PASS', $config["dbPassword"]);
define('DB_BDD', $config["dbBase"]);
define('DB_ADMINMAIL', 'contact@romainduchene.com');
define('DB_NOMSCRIPT', 'GestLibr v3.0');

/* méthode d'envois des mails:
php    : méthode par la fonction mail normale de php
proxad : méthode par la fonction email développée par Proxad 
*/
$TYPE_SERVEUR_MAIL="php"; 

/* Nom de l'expéditeur des mails SANS LE NOM DE DOMAINE
ex: "contact"  
*/
$EXP_MAIL_ADDR="diane";

/* Domaine pour l'envoi des mails, utile
uniquement si TYPE_SERVEUR_MAIL vaut "php" */
$EXP_MAIL_DOMAINE="librairieduchene.com";

/* Param du prog */
$var["nomProg"]="GestLibr";
$var["verProg"]="4.0";
?>
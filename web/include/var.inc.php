<?php 
/* Variables Base de Donn�es */
define('DB_SERVEUR', $config["dbServer"]);
define('DB_USER', $config["dbUser"]);
define('DB_PASS', $config["dbPassword"]);
define('DB_BDD', $config["dbBase"]);
define('DB_ADMINMAIL', 'romain@duchene.fr');
define('DB_NOMSCRIPT', 'GestLibr v5.0');

/* m�thode d'envois des mails:
php    : m�thode par la fonction mail normale de php
proxad : m�thode par la fonction email d�velopp�e par Proxad 
*/
$TYPE_SERVEUR_MAIL="php"; 

/* Nom de l'exp�diteur des mails SANS LE NOM DE DOMAINE
ex: "contact"  
*/
$EXP_MAIL_ADDR="diane";

/* Domaine pour l'envoi des mails, utile
uniquement si TYPE_SERVEUR_MAIL vaut "php" */
$EXP_MAIL_DOMAINE="librairieduchene.com";

/* Param du prog */
$var["nomProg"]="GestLibr";
$var["verProg"]="5.0.3";
?>
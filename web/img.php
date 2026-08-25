<?php
/**
 * * * *    GestLibr: img.php     *
 * * * *   Image display (with rezising if needed and from Id)  *
 * * * *  Romain DUCHENE, 01 March 2012   *
 */

include "config.php";
include "include/var.inc.php";
include "lib/mysql.lib.php";

$babase = new DataBase;
$babase->DbConnect();
global $babase;

include "class/book.class.php";
$book = new Book();

// getting the file
if ($_GET["filename"]!="")
    $filename = $_GET["filename"];
if ($_GET["idBookPhoto"]!="")
{
    $idBookPhoto = (int)$_GET["idBookPhoto"];
    $bookPhoto = $babase->DbSelect("SELECT * FROM `bookPhoto` WHERE `idBookPhoto`='$idBookPhoto'");
    $filename = $_GET["filename"];
}

$percent = 0.5;

// Content type
header('Content-Type: image/jpeg');

// Calcul des nouvelles dimensions
list($width, $height) = getimagesize($filename);
$new_width = $width * $percent;
$new_height = $height * $percent;

// Redimensionnement
$image_p = imagecreatetruecolor($new_width, $new_height);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Affichage
imagejpeg($image_p, null, 100);
?>

?>
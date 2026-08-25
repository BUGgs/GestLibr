<?php
function loadjpeg($imgname) {
  $im = @imagecreatefromjpeg($imgname); /* Tentative d'ouverture */
  if (!$im) { /* Vérification */
    $im = imagecreate(150, 30); /* Création d'une image blanche */
    $bgc = imagecolorallocate($im, 255, 255, 255);
    $tc  = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, 150, 30, $bgc);
// Affichage d'un message d'erreur
    imagestring($im, 1, 5, 5, "Erreur de chargement de l'image $imgname", $tc);
  }
  return $im;
}

$txt=urldecode($_GET["txt"]);
$image=urldecode($_GET["image"]);
$x=urldecode($_GET["x"]);
$y=urldecode($_GET["y"]);
$font=urldecode($_GET["font"]);
$size=urldecode($_GET["size"]);


// nouvelle iamge 100*30
$im = loadjpeg($image);
$black = imagecolorallocate($im, 0,0,0);

// ajout de la phrase en haut &grave; gauche
imagettftext($im, 40, 0, 350, 220, $black, $font, $txt);

// afichage de l'image
header("Content-type: image/jpg");
imagejpeg($im);

//echo "coucou";
?>

<?php

// ------------------------------------------------------------------------- //
// Permet de rÈduire une image en gardant la hauteur proportionnelle ‡ la    //
// largeur. L'image ne se rÈduit que si elle dÈpasse les limites de hauteur  //
// ou de largeur indiquÈes. Au final l'image est enregistrÈe sous un nom     //
// alÈatoire (et empÍche l'Ècrasement).                                      //
// ------------------------------------------------------------------------- //
// Auteur: NobodX                                                            //
// Email:  icecube@fr.fm                                                     //
// Web:    http://icecube.fr.fm/                                             //
// ------------------------------------------------------------------------- //
// ModifiÈ par Romain DUCHENE le 26/12/2001

/* RatioResizeImg avec (height % width) par NobodX */
/*    Suivant la fonction ResizeGif de tjhunter    */

function RatioResizeImg( $image, $newWidth, $newHeight){

// dÈtÈction du type de l'image
preg_match("/(...)$/i", $image, $regs); $type = $regs[1];
switch($type){ 
 case "gif": $srcImage = @imagecreatefromgif( $image ); break; 
 case "jpg": $srcImage = @imagecreatefromjpeg( $image ); break; 
 case "png": $srcImage = @imagecreatefrompng( $image ); break; 
 default : unset($type); break;} 

if($srcImage){

// hauteurs/largeurs
$srcWidth = imagesx( $srcImage ); 
$srcHeight = imagesy( $srcImage ); 
$ratioWidth = $srcWidth/$newWidth;
$ratioHeight = $srcHeight/$newHeight;

// taille maximale dÈpassÈe ?
if (($ratioWidth > 1) || ($ratioHeight > 1)) {
if( $ratioWidth < $ratioHeight){ 
$destWidth = $srcWidth/$ratioHeight;
$destHeight = $newHeight; 
}else{ 
$destWidth = $newWidth; 
$destHeight = $srcHeight/$ratioWidth;}
}else {$destWidth = $srcWidth;  $destHeight = $srcHeight;}

// resize
$destImage = imagecreate( $destWidth, $destHeight); 
imagecopyresized( $destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, 
                                                     $srcWidth, $srcHeight );

// nom du fichier
$dest_file  = random($dest_file,$type);
while (file_exists("$dest_file"))
{$dest_file  = random($dest_file,$type);}

// crÈation et sauvegarde de l'image finale
/* Ici on peut Èditer le chemin de sauvegarde ($dest_file) */
switch($type){ 
 case "gif": @imagegif($destImage, $dest_file); break; 
 case "jpg": @imagejpeg($destImage, $dest_file); break; 
 case "png": @imagepng($destImage, $dest_file); break;}

// libËre la mÈmoire
imagedestroy( $srcImage );
imagedestroy( $destImage );

// renvoit l'URL de l'image
return $dest_file;}

// erreur
else {echo "Image inexistante ou aucun support ";
        if ($type){echo "pour le format $type";}
        else {echo "pour ce format de fichier";}
exit();}}


// nom de fichier suivant la date + nb alÈatoire
function random($dest_file,$type){
srand((double) microtime() * 1000);
$dest_file = date("dhis");
$dest_file .= rand();
$dest_file .= ".$type";
return $dest_file;}
?>

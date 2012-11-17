<?php 

// Utils toolbox by Romain DUCHENE, 2000-2006

function secureTxt($txt)
{
	return(addslashes($txt));
}

function saveDataBase()
{
  $file=DB_BDD.".tgz";
	$result=system("mysqldump --host=".DB_SERVEUR." --complete-insert --add-drop-table -u ".DB_USER." --password='".DB_PASS."' ".DB_BDD." >".DB_BDD.".sql");
  $result=system("tar cfz $file ".DB_BDD.".sql >null");
	sleep(1);
  header("Content-type: application/x-gtar");
 	header("Content-length: ".filesize($file));
 	header("Content-Disposition: attachment; filename=$file");
 	header("Content-Description: PHP Generated Data");
	$data=fread(fopen($file, "r"), filesize($file));
	echo $data;
	unlink($file);
	unlink(DB_BDD.".sql");
}

function uploadDataBase($file,$file_name,$file_type,$file_size)
{
  echo "file:$file<br>file_name:$file_name<br>file_type:$file_type";
	if ($file_name!="")
	{
	  if ($file_type=="application/x-gzip-compressed")
	  {
	    //$result=exec("tar zxf"); 
	  }
		else
		{
		  $result=exec("mysql --host=".DB_SERVEUR." -u ".DB_USER." --password='".DB_PASS."' ".DB_BDD." <".$file);
		}
	}
}

function maj($login, $action, $type, $id){
  $babase = new DataBase;
  $babase->DbConnect();
  $maj=$babase->DbInsert("INSERT INTO maj (idMaj, idLogin, type, idFiche, date, action)	VALUES ('','$login', '$type', '$id','".date("Y-m-d H:i:s")."','$action')"); 
}

function enleve_carac_speciaux($recherche){
	$recherche = ereg_replace("%","",$recherche);
	$recherche = str_replace('\"',"",$recherche);
	$recherche = str_replace("\'","",$recherche);
	$recherche = str_replace(";"," ",$recherche);
	$recherche = str_replace(","," ",$recherche);
	$recherche = str_replace("|"," ",$recherche);
	$recherche = str_replace("*"," ",$recherche);
	$recherche = str_replace("?"," ",$recherche);
	$recherche = stripslashes($recherche);
	return $recherche;
}

/**
 * @return boolean Display the erreur message
 * @param string $msg Error text to display
*/
function erreur($msg)
{
  echo "<h1>ERREUR :</H1>$msg<p>
	<a href='javascript:history.back(-1)'>Retour á la page pr»c»dente</a>";
  traceur("erreur",$msg);
	die;
}

/**
 * @return string Return the value of the config info requested
 * @param string $name Name of config value requested
*/
function config($name)
{
  global $babase;
  $sql = $babase->DbSelect("SELECT * FROM config WHERE name LIKE '" . $name . "'",$nul);
  if ($nul!=1)
    return(false);
  else
    return($sql[0]["value"]);
}

/**
* @return int 0 is ok, other is error
* @param string $im_filename Orginal file name with path
* @param string $th_filename Name of thumbnail file to generate
* @param int $max_width Max Width of thumbnail
* @param int $max_height Max Height of thumbnail
* @param decimal $quality JPG/PNG Quality to generate, by default 0.75
*/
function GenerateThumbnail($im_filename,$th_filename,$max_width,$max_height,$quality = 0.75)
{
// The original image must exist
if(is_file($im_filename))
{
    // Let's create the directory if needed
    $th_path = dirname($th_filename);
    if(!is_dir($th_path))
        mkdir($th_path, 0777, true);
    // If the thumb does not aleady exists
    if(!is_file($th_filename))
    {
        // Get Image size info
        list($width_orig, $height_orig, $image_type) = @getimagesize($im_filename);
        if(!$width_orig)
            return 2;
        switch($image_type)
        {
            case 1: $src_im = @imagecreatefromgif($im_filename);    break;
            case 2: $src_im = @imagecreatefromjpeg($im_filename);   break;
            case 3: $src_im = @imagecreatefrompng($im_filename);    break;
        }
        if(!$src_im)
            return 3;


        $aspect_ratio = (float) $height_orig / $width_orig;

        $thumb_height = $max_height;
        $thumb_width = round($thumb_height / $aspect_ratio);
        if($thumb_width > $max_width)
        {
            $thumb_width    = $max_width;
            $thumb_height   = round($thumb_width * $aspect_ratio);
        }

        $width = $thumb_width;
        $height = $thumb_height;

        $dst_img = imagecreatetruecolor($width, $height);
        if(!$dst_img)
            return 4;
        $success = @imagecopyresampled($dst_img,$src_im,0,0,0,0,$width,$height,$width_orig,$height_orig);
        if(!$success)
            return 4;
        switch ($image_type) 
        {
            case 1: $success = @imagegif($dst_img,$th_filename); break;
            case 2: $success = @imagejpeg($dst_img,$th_filename,intval($quality*100));  break;
            case 3: $success = @imagepng($dst_img,$th_filename,intval($quality*9)); break;
        }
        if(!$success)
            return 4;
    }
    return 0;
}
return 1;
}

?>
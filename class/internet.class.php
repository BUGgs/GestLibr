<?php
/**
 * * * *      GestLibr: internet.class.php     	*
 * * * *					Internet Class             		*
 * * * *  Romain DUCHENE, Mar 2006 - ?				 	*
 */

Class Internet {
    var $babase;
    var $err_msg = "";
    var $error = 0;

    function secureData()
    {
			// To delete <br/> at the end of the description field
			$pattern = "&lt;br /&gt;";
			$patternmini = "gt;";
			$txt = $this->data["description"];
			$nb = preg_match("/" . $patternmini . "$/", $txt);
			if ($nb!=0)
				$this->data["description"] = str_replace($pattern, "", $txt);
			// Now we are securing all data with an addslashes
	    reset($this->data);
			while (list($key, $val) = each($this->data)) {
    	  $this->data[$key]=addslashes($val);
			}
		}

		// Pour les utilisateurs ayant des versions antérieures à PHP 4.3.0 :
		function unhtmlentities($string)
		{
   			// Remplace les entités numériques
    		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    		$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
   			// Remplace les entités litérales
    		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
    		$trans_tbl = array_flip($trans_tbl);
    		return strtr($string, $trans_tbl);
		}
		
		function stripAccents($string)
		{
			return strtr($string,'àáâãäçèéêëìíîïñòóôõöùúûüÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜ', 'aaaaaceeeeiiiinooooouuuuAAAAACEEEEIIIINOOOOOUUUU');
		}

    function decode($txt)
    {
	// Replace some specials char & abbreviation
	$txt = html_entity_decode($txt);
	$txt = html_entity_decode($txt);
	$txt = html_entity_decode(html_entity_decode($txt));
	// Replace pattern source
	$source = array("\r\n", "&#39;", "P.", "coll.", "&hellip;", "br.", "ill.", "couv.", "défr.", "rel.", "gd", "éd.", "nb.", "pp.", "cart.", "h.t.", "in t.", "pl.");
	// Replace pattern source
	$replace = array("<br>", "'", "Paris", "collection", "...", "broché", "illustrations", "couverture", "défraichie", "reliure", "grand", "éditeur", "nombreuses", "pages", "cartonnage", "hors texte", "in texte", "pleine");
	return(str_replace($source, $replace, $txt));
    }

    function decode_html($txt)
    {
        // Replace some specials char & abbreviation
//        $txt = $this->unhtmlentities($txt);
	$txt = html_entity_decode($txt);
	$txt = html_entity_decode($txt);
        // Replace pattern source
//        $source = array('"', "à", "â", "ü", "û", "ù", "ç", "é", "è", "ê", "ë", "°", "&lt;", "&gt;", "&amp;", "\r\n", "&#39;", "P.", "coll.", "&hellip;", "br.", "ill.", "couv.", "dÈfr.", "rel.", "gd", "Èd.", "nb.", "pp.", "cart.", "h.t.", "in t.", "pl.");
        $source = array('"', "\r\n", "&#39;", "P.", "coll.", "&hellip;", "br.", "ill.", "couv.", "dÈfr.", "rel.", "gd", "Èd.", "nb.", "pp.", "cart.", "h.t.", "in t.", "pl.");        // Replace pattern source
//        $replace = array("'", "&agrave;", "&acirc;", "&uuml;", "&ucirc;", "&ugrave;", "&ccedil;", "&eacute;", "&egrave;", "&ecirc;", "&euml;", "&deg;", "<", ">", "&", "<br>", "'", "Paris", "collection", "...", "broch&eacute;", "illustrations", "couverture", "d&eacute;fraichie", "reliure", "grand", "&eacute;diteur", "nombreuses", "pages", "cartonnage", "hors texte", "in texte", "pleine");
       $replace = array("'", "<br>", "'", "Paris", "collection", "...", "broch&eacute;", "illustrations", "couverture", "d&eacute;fraichie", "reliure", "grand", "&eacute;diteur", "nombreuses", "pages", "cartonnage", "hors texte", "in texte", "pleine");
        //return($txt);
        return(str_replace($source, $replace, $txt));
    }

    function viewError($error)
    {
        // Convert the error code in a text message
        // $error: the error code
        // Return the text
        $convert=$this->babase->DbSelect("SELECT text FROM errormsg WHERE errorCode='$error' AND lang='fr'",$nb);
        if ($nb==1)
          return ($convert[0]["text"]);
				else
				  return("Unknown error");
    }
    
    function addHistory($id, $name, $details)
    {
			settype($id, 'integer');
      $name = secureTxt($name);
      $details = secureTxt($details);
      return($this->babase->DbInsert("INSERT INTO internethistory (idInternetHistory, id, name, details, date, idUser) VALUES ('', '$id', '$name', '$details', '" . time() . "', '".$_SESSION["idUser"]."')"));
    }

    function Internet()
    {
      $this->babase = new DataBase();
      $this->babase->DbConnect();
      $this->idBook = -1;
    }
    
    function create($name,$description,$method,$header,$template="",$param="")
    {
      $name = secureTxt($name);
      $description = secureTxt($description);
      $template = secureTxt($template);
      $header = secureTxt($header);
      $method = secureTxt($method);
      $param = secureTxt($param);
      $id=$this->babase->DbInsert("INSERT INTO internet (idInternet, name, description, method, param, header, template, status, idGroup) VALUES
			(\"\", \"$name\", \"$description\", \"$method\", \"$param\", \"$header\", \"$template\", \"NOT-SYNC\", '".$_SESSION["idGroup"]."')",1);
      $this->addHistory($id,"create",$name);
      return($id);
    }

    function delete($idInternet)
    {
	settype($idInternet, 'integer');
        $this->babase->DbQuery("DELETE FROM internetDetails WHERE idInternet = '$idInternet'");
        $this->babase->DbQuery("DELETE FROM internet WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idInternet = '$idInternet'");
        $this->addHistory($idInternet,"delete","ok");
        return(true);
    }

    function modify($idInternet,$name,$description,$method,$header,$template,$param)
    {
	settype($idInternet, 'integer');
	$name = secureTxt($name);
	$description = secureTxt($description);
	$template = secureTxt($template);
	$method = secureTxt($method);
	$param = secureTxt($param);
	if ($this->isExist($idInternet))
	{
	    $this->addHistory($idInternet,"modify","ok");
	    return($this->babase->DbQuery("UPDATE internet SET name = \"$name\", description = \"$description\", method = \"$method\", param = \"$param\", header = \"$header\", template = \"$template\" WHERE idInternet='$idInternet'",1));
	}
	else
	    return(false);
    }

    function get($idInternet)
    {
	settype($idInternet, 'integer');
	if ($this->isExist($idInternet))
	{
	    $data = $this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idInternet = '$idInternet'",$nul);
	    $data = $data[0];
	    $data["details"] = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE idInternet = '$idInternet'",$nul2);
	    if ($nul==1)
		return($data);
	    else
		return(false);
	}
    }

    function listInternet()
    {
	return($this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='".$_SESSION["idGroup"]."' ORDER BY name",$nul));
    }

    function addDetail($idInternet,$type,$detail,$order=1)
    {
	settype($idInternet, 'integer');
	settype($detail, 'integer');
	settype($order, 'integer');
	$type = secureTxt($type);
	if ($this->isExist($idInternet))
	{
	    // We check if it's not a duplicate data
	    $check=$this->babase->DbSelect("SELECT * FROM internetDetails WHERE idInternet='$idInternet' AND type='$type' AND detail='$detail'",$nb);
	    if ($nb==0)
	    {
		$this->addHistory($idInternet,"addDetail","ok");
		return($this->babase->DbInsert("INSERT INTO internetDetails (idInternetDetails, idInternet, type, detail, `order`) VALUES
			  (\"\", \"$idInternet\", \"$type\", \"$detail\", \"$order\")",1));
	    }
	    else
	    {
		$this->addHistory($idInternet,"addDetail","ko: Duplicata");
		return (false); // Duplicate data
	    }
	}
	else
	    return (false); // idInternet doesn't exist !
    }

    function delDetail($idInternetDetails)
    {
	settype($idInternetDetails, 'integer');
	$this->addHistory($idInternet,"delDetail","ok: $idInternetDetails");
	return ($this->babase->DbQuery("DELETE FROM internetDetails WHERE idInternetDetails = '$idInternetDetails'"));
    }

    function isExist($idInternet)
    {
	settype($idInternet, 'integer');
	$this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idInternet = '$idInternet'",$nb);
	if ($nb!=0)
	    return(true);
	else
	    return(false);
    }

    function getBookList($idInternet,$isbnOnly=false)
    {
	settype($idInternet, 'integer');
	$isbnOnly=(boolean)$isbnOnly;
	// Get all details for this - ONLY CATEGORY ARE OK NOW...
	$details = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE type='cat' AND idInternet = '$idInternet'",$nb);
    	$idCategory="(";
	$i=0;
	while($i!=$nb)
	{
	    $idCategory.="bic.idCategory='" . $details[$i]["detail"] . "' OR ";
	    $i++;
   	}
	$idCategory.= " 1=0)";
	if ($isbnOnly)
	    $isbn = "b.isbn!='' AND";
	else
	    $isbn = "";
	$tmp = $this->babase->DbSelect("SELECT * FROM book AS b, bookincategory AS bic WHERE $isbn $idCategory AND bic.idBook=b.idBook AND b.quantity>0 AND b.internet='1' ORDER BY author",$nb);
	// Preparing the Catalog exclusion list
	$exclusion = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE type='excludeCatalog' AND idInternet = '$idInternet'",$nbExclusion);
	if ($nbExclusion==0)
	    return($tmp);
	else
	{
	    // we prepare the list
	    $i=0;
	    while($i!=$nbExclusion)
	    {
		$sqlExclude .= "idCatalog='" . $exclusion[$i]["detail"] . "' OR ";
		$i++;
	    } 
	    $sqlExclude .= "1=0";
	    $i=0;
	    $j=0;
	    while($i!=$nb)
	    {
		$this->babase->DbSelect("SELECT idCatalog FROM catalogdata WHERE name='" . $tmp[$i]["idBook"] . "' AND ($sqlExclude)",$ok);
		if ($ok==0)
		{
		    $final[$j] = $tmp[$i];
		    $j++;
		}
		$i++;
	    }
	    return($final);
	}
    }

    function createCSV($data,$file)
    {
	// Creating file
	//unlink("tmp/" . $file);
	$f = fopen("tmp/" . $file, 'w');
	$i=0;
	while($i!=sizeof($data))
	{
	    // For each line...
	    fputs($f, "\"" . $data[$i]["author"] . "\";\"" .
		$data[$i]["title"] . "\";\"" .
		$data[$i]["description"] . "\";\"" .
		$data[$i]["price"] . "\";\"" .
		$data[$i]["quantity"] . "\"\r\n");
	    $i++;
	}
	// Closing file
	fclose($f);
    }

    function createTAB($data,$file)
    {
	// Creating file
	//unlink("tmp/" . $file);
	$f = fopen("tmp/" . $file, 'w');
	$i=0;
	while($i!=sizeof($data))
	{
	    // For each line...
	    fputs($f, $data[$i]["author"] . "\t" .
	        $data[$i]["title"] . "\t" .
		$data[$i]["description"] . "\t" .
		$data[$i]["price"] . "\t" .
		$data[$i]["quantity"] . "\r\n");
	    $i++;
	}
	// Closing file
	fclose($f);
    }

    function createFile($data, $template, $file, $type, $header)
    {
	// Creating file
	//@unlink("tmp/" . $file);
	$f = fopen("tmp/" . $file, 'w');
	$source = array('&quot;', '&lt;', '&gt;', '&amp;');
	$replace = array('"', '<', '>', '&');
	$template = str_replace($source, $replace, $template);
	$i=0;
	$pattern = array("{TAB}", "{NEWLINE}", "{IDBOOK}", "{AUTHOR}", "{TITLE}", "{TITLE-SHORT80}", "{DESCRIPTION}", "{PRICE}", "{QUANTITY}", "{NOTES}", "{ENTRYDATE}", "{MODIFYDATE}", "{IDCAT}", "{ISBN}","{EDITOR}","{COLLECTION}","{LANGUAGE}","{PUBLISHDATE}","{PUBLISHLOCATION}","{PAGENUMBER}","{FORMAT}","{BINDING}", "{IS_ISBN}");
	if ($header!="")
	    fputs($f,str_replace("{TAB}","\t",$header)."\r\n");
	while($i!=sizeof($data))
	{
	    // For each line...
	    switch ($type)
	    {
	    case "ebay":
		$titleshort = substr(substr($this->decode_html($this->stripAccents($data[$i]["author"])),0,29).": ".($this->decode_html($this->stripAccents($data[$i]["title"]))),0,78);
		$curData = array("\t", "\r\n", ($this->decode_html($data[$i]["idBook"])), ($this->decode_html($data[$i]["author"])), ($this->decode_html($data[$i]["title"])), $titleshort, ($this->decode_html($data[$i]["description"])), utf8_decode($this->decode($data[$i]["price"])), utf8_decode($this->decode($data[$i]["quantity"])), utf8_decode($this->decode($data[$i]["notes"])), utf8_decode($this->decode($data[$i]["entryDate"])), utf8_decode($this->decode($data[$i]["modifyDate"])), utf8_decode($this->decode($data[$i]["idCategory"])),$data[$i]["isbn"],$data[$i]["editor"],$data[$i]["collection"],$data[$i]["language"],$data[$i]["publishDate"],$data[$i]["publishLocation"],$data[$i]["pageNumber"],$data[$i]["format"],$data[$i]["binding"]);
     		$line = $template;
     	    	break;
	    case "amazon":
		// Amazon Price
		if ($data[$i]["price_amazon"]>0)
		    $price=$data[$i]["price_amazon"];
		else
		    $price=$data[$i]["price"];
		// Publishdate : YYYY-MM-DD (or 1900 by default)
		if (($data[$i]["publishDate"])!="")
		    //$publishDate = date("Y-m-d",strtotime($data[$i]["publishDate"]));
		    $publishDate = $data[$i]["publishDate"];
		else
		    $publishDate = "1900";
		// Do you use ISBN or not ?
		if ($data[$i]["isbn"]!="")
		    $is_isbn = "2";
		else
		    $is_isbn = "";
		// Editor
		if ($data[$i]["editor"]!="")
		    $editor = $data[$i]["editor"];
		else
		    $editor = "unknown";
		// Editor
		$curData = array("\t", "\r\n", $data[$i]["idBook"], substr($this->decode_html($data[$i]["author"]),0,199), substr($this->decode_html($data[$i]["title"]),0,499), substr($this->decode($data[$i]["title"]),0,79), substr($this->decode_html($data[$i]["description"]),0,1430), $price, $this->decode($data[$i]["quantity"]), $this->decode_html($data[$i]["notes"]), $this->decode($data[$i]["entryDate"]), $this->decode($data[$i]["modifyDate"]), $this->decode($data[$i]["idCategory"]),$data[$i]["isbn"],substr($editor,0,499),$data[$i]["collection"],$data[$i]["language"],$publishDate,$data[$i]["publishLocation"],$data[$i]["pageNumber"],$data[$i]["format"],$data[$i]["binding"],$is_isbn);
		$line = $template;
		break;
	    default:
		// All other sites
		$curData = array("\t", "\r\n", $this->decode($data[$i]["idBook"]), $this->decode($data[$i]["author"]), $this->decode($data[$i]["title"]), substr($this->decode($data[$i]["title"]),0,79), $this->decode($data[$i]["description"]), $this->decode($data[$i]["price"]), $this->decode($data[$i]["quantity"]), $this->decode($data[$i]["notes"]), $this->decode($data[$i]["entryDate"]), $this->decode($data[$i]["modifyDate"]), $this->decode($data[$i]["idCategory"]),$data[$i]["isbn"],$data[$i]["editor"],$data[$i]["collection"],$data[$i]["language"],$data[$i]["publishDate"],$data[$i]["publishLocation"],$data[$i]["pageNumber"],$data[$i]["format"],$data[$i]["binding"]);
		$line = $template;
		break;
	    }
	    $line = $template;
	    $line = str_replace($pattern, $curData, $line);
	    fputs($f, $line);
	    $i++;
	}
	// Closing file
	fclose($f);
    }

    function update($idInternet)
    {
	settype($idInternet, 'integer');
	$internet = $this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idInternet = '$idInternet'",$nb);
	$internet = $internet[0];
	if ($nb==1)
	{
	    switch($internet["method"])
	    {
		case "fnac":
		case "amazon":
		    // Get parameters; format: filename
		    // ex: upload.txt
		    $param = explode(";", $internet["param"]);
		    $filename = $param[0];
		    // Creating book list
		    $data = $this->getBookList($idInternet);
		    // Creating file in /tmp
		    $this->createFile($data, $internet["template"], $filename, $internet["method"], $internet["header"]);
		    $this->addHistory($idInternet,"update","ok");
		    return(true);
		    break;
		case "ftp":
		    // Get parameters; format: ftpServer;username;password;directory;filename
		    // ex: ftp.wiroo.com;gestlibr;azerty;/upload/;upload.txt
		    $param = explode(";", $internet["param"]);
		    $ftpServer = $param[0];
		    $ftpUsername = $param[1];
		    $ftpPassword = $param[2];
		    $ftpDirectory = $param[3];
		    $filename = $param[4];
		    $url = $param[5];
		    // Creating book list
		    $data = $this->getBookList($idInternet);
		    // Creating file in /tmp
		    $this->createFile($data, $internet["template"], $filename, $internet["method"], $internet["header"]);
		    if ($internet["method"]!="ftp")
		    {
			$this->addHistory($idInternet,"update","ok");
			return(true);
		    }
		    // Uploading file
		    if ($this->ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename))
		    {
			$this->addHistory($idInternet,"update","ok");
			if ($url!="")
			{
			    // If we need to fetch an URL after FTP sent
			    $file = fopen($url, "r");
			    while(!feof($file)) 
			    {
				$data = $data . fgets($file, 4096);
			    }
			    fclose ($file);
			}
			return(true);
		    }
		    else
		    {
			$this->addHistory($idInternet,"update","error");
			return(false);
		    }

		    break;
		case "website":
		    // Get parameters; format: ftpServer;username;password;directory;filename;url
		    // ex: ftp.wiroo.com;gestlibr;azerty;/upload/;upload.txt;http://www.website.com/update.php
		    $param = explode(";", $internet["param"]);
		    $ftpServer = $param[0];
		    $ftpUsername = $param[1];
		    $ftpPassword = $param[2];
		    $ftpDirectory = $param[3];
		    $filename = $param[4];
		    $url = $param[5];
		    // Creating book list
		    $data = $this->getBookList($idInternet);
		    // Creating file in /tmp
		    $this->createFile($data, $internet["template"], $filename);
		    // Uploading file
		    if ($this->ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename))
		    {
			$handle = fopen($url, "rb");
			$contents = '';
			while (!feof($handle))
			{
			    $contents .= fread($handle, 8192);
			}
			fclose($handle);
			$this->addHistory($idInternet,"update","ok");
			return(true);
		    }
		    else
		    {
			$this->addHistory($idInternet,"update","error");
			return(false);
		    }
		    break;
		case "oscommerce":
		    // Get parameters; format: sqlServer;username;password;DBname
		    // ex: sql.wiroo.com;gestlibr;azerty;gestlibr
		    $param = explode(";", $internet["param"]);
		    $sqlServer = $param[0];
		    $sqlUsername = $param[1];
		    $sqlPassword = $param[2];
		    $sqlDatabase = $param[3];
		    // Creating book list
		    $data = $this->getBookList($idInternet);
		    if ($this->oscUpdate($sqlServer, $sqlUsername, $sqlPassword, $sqlDatabase, $data))
		    {
			$this->addHistory($idInternet,"update","ok");
			return(true);
		    }
		    else
		    {
			$this->addHistory($idInternet,"update","error");
			return(false);
		    }
		    break;
		default:
		    // Error... unknow Method !!!
		    return(false);
		    break;
	    }
	}
	else
	    return(false); // idInternet doesn't exist !
    }

    function ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename)
    {
	$source_file = './tmp/' . $filename;
	$conn_id = @ftp_connect($ftpServer);
	$login_result = @ftp_login($conn_id, $ftpUsername, $ftpPassword);
	if ((!$conn_id) || (!$login_result)) 
	    return(false); // ftp connection failed
	else
	{
	    ftp_pasv($conn_id, true);
	    if (ftp_chdir($conn_id, $ftpDirectory))
	    {
		if (ftp_put($conn_id, $filename, $source_file, FTP_BINARY))
		    return(true);
		else
		    return(false); // Upload failed
	    }
	    else
		return(false); // chdir failed
    	}
	ftp_quit($conn_id);
	return(false);
    }

    function oscUpdate($sqlServer, $sqlUsername, $sqlPassword, $sqlDatabase, $data)
    {
      // Get category list
      $cat = $this->babase->DbSelect("SELECT * FROM category;",$nbCat);
      // Save actual database parameters
      $saveServer = $this->babase->DB_SERVEUR;
      $saveUsername = $this->babase->DB_USER;
      $savePassword = $this->babase->DB_PASS;
      $saveDatabase = $this->babase->DB_BDD;
      // Creating SQL connection to OSCommerce
      $this->babase->DbInit($sqlUsername,$sqlPassword,$sqlServer,$sqlDatabase);
      $this->babase->DbConnect();

      $modify_date = date("Y-m-d H:i:s");

      // Updating category list
      $i=0;
      while($i!=(sizeof($cat)))
      {
        $osc = $this->babase->DbSelect("SELECT * FROM categories WHERE categories_id LIKE '" . $cat[$i]["idCategory"] . "'",$nb);
        if ($nb==1)
        {
          // Category already exists... It's just an update
          // We update description also (only for all languages)
          $this->babase->DbQuery("UPDATE categories_description SET categories_name='" . addslashes(html_entity_decode($cat[$i]["name"])) . "' WHERE categories_id LIKE '" . $cat[$i]["idCategory"] . "'");
        }
        else
        {
          // Category doesn't exist... We have to create it !
          // Creating categories record
          $osc = $this->babase->DbInsert("INSERT INTO `categories` ( `categories_id` , `date_added`) VALUES ('" . $cat[$i]["idCategory"] . "', '" . $modify_date . "')");
          // Creating categories_description record
          $osc = $this->babase->DbInsert("INSERT INTO `categories_description` (`categories_id` , `language_id` , `categories_name`) VALUES ('" . $cat[$i]["idCategory"] . "', '1', '" . addslashes(html_entity_decode($cat[$i]["name"])) . "')");
        }
        $i++;
      }

      // Update for each record
      $i=0;
      while($i!=(sizeof($data)))
      {
        $osc = $this->babase->DbSelect("SELECT * FROM products WHERE products_id LIKE '" . $data[$i]["idBook"] . "'",$nb);
        if ($nb==1)
        {
          // Record already exists... It's just an update
          // We update price, status, and modify_date
          $this->babase->DbQuery("UPDATE products SET products_price='" . addslashes($data[$i]["price"]) . "', products_last_modified='" . $modify_date . "', products_status='1' WHERE products_id LIKE '" . $data[$i]["idBook"] . "'");
          // We update description also (only for all languages)
          $this->babase->DbQuery("UPDATE products_description SET products_name='" . addslashes(html_entity_decode($data[$i]["author"])) . " - " . addslashes(html_entity_decode($data[$i]["title"])) . "', products_description='" . addslashes(html_entity_decode($data[$i]["description"])) . "' WHERE products_id LIKE '" . $data[$i]["idBook"] . "'");
          // Deleting & Creating products_to_categories record
          $osc = $this->babase->DbQuery("DELETE FROM `products_to_categories` WHERE `products_id` LIKE '" . $data[$i]["idBook"] . "'");
          $osc = $this->babase->DbInsert("INSERT INTO `products_to_categories` (`products_id` , `categories_id`) VALUES ('" . $data[$i]["idBook"] . "', '" . $data[$i]["idCategory"] . "')");
        }
        else
        {
          // Record doesn't exist... We have to create it !
          // Creating products record
          $osc = $this->babase->DbInsert("INSERT INTO `products` ( `products_id` , `products_quantity` , `products_model` , `products_image` , `products_price` , `products_date_added` , `products_last_modified` , `products_date_available` , `products_weight` , `products_status` , `products_tax_class_id` , `manufacturers_id` , `products_ordered` ) VALUES ('" . $data[$i]["idBook"] . "', '" . addslashes($data[$i]["quantity"]) . "', 'ID-" . $data[$i]["idBook"] . "' , NULL , '" . addslashes($data[$i]["price"]) . "', '" . $modify_date . "', '" . $modify_date . "' , '0000-00-00 00:00:00' , '500.00', '1', '1', '1' , '0')");
          // Creating products_description record
          $osc = $this->babase->DbInsert("INSERT INTO `products_description` (`products_id` , `language_id` , `products_name` , `products_description` , `products_url` , `products_viewed` ) VALUES ('" . $data[$i]["idBook"] . "', '1', '" . addslashes(html_entity_decode($data[$i]["author"])) . " - " . addslashes(html_entity_decode($data[$i]["title"])) . "', '" . addslashes(html_entity_decode($data[$i]["description"])) . "' , NULL , '0')");
          // Creating products_to_categories record
          $osc = $this->babase->DbInsert("INSERT INTO `products_to_categories` (`products_id` , `categories_id`) VALUES ('" . $data[$i]["idBook"] . "', '" . $data[$i]["idCategory"] . "')");
        }
        $i++;
      }
      // Now we change products_status to 0 for all records that we did not modifiy in last actions... ie: if products_last_modified != $modify_date
      $osc = $this->babase->DbQuery("UPDATE products SET products_status='0' WHERE products_last_modified != '" . $modify_date . "'");
      // Resoting database parameters
      // Save actual database parameters
      $this->babase->DB_SERVEUR = $saveServer;
      $this->babase->DB_USER = $saveUsername;
      $this->babase->DB_PASS = $savePassword;
      $this->babase->DB_BDD = $saveDatabase;
      $this->babase->DbConnect();
      return(true);
    }
    
    /**
    * @return int ItemID or false if an error occured
    * @param int $idBook
    */
    function addOnEbay($idBook)
    {
	global $book;
        if (($book->get($idBook)) AND ($book->data["ebayItemId"]=="") AND ($book->data["onEbay"]=="1"))
        {
	    $titleshort = substr(substr($book->data["author"],0,29).": ".$book->data["title"],0,78);
	    $x = new ebay_AddItem();
	    $id_ebay=$x->dispatchCall
	    (
	        array
		    (
		    'title' => $book->data["title"],
		    'title80' => $titleshort,
		    'author' => $book->data["author"],
		    'quantity' => $book->data["quantity"],
		    'price' => $book->data["price"],
		    'description' => $this->decode($book->data["description"]),
		    'ebayCategoryId' => $book->data["ebayCategoryId"],
		    'ebayCategoryIdSecondary' => $book->data["ebayCategoryIdSecondary"],
		    'idBook'    => $book->data["idBook"],
		    'ebayShippingCost'    => $book->data["ebayShippingCost"],
		    'ebayShippingCostInternational'    => $book->data["ebayShippingCostInternational"],		    
		    )
	    );
	    if ($id_ebay)
		$book->addEbayId($id_ebay,$idBook);
	    return($id_ebay);
	}
	else
	    return(false);
    }
    
    /**
    * @return boolean
    * @param int $idBook
    */
    function delOnEbay($idBook)
    {
	global $book;
        if (($book->get($idBook)) AND ($book->data["ebayItemId"]!=""))
        {
	    $x = new ebay_EndItem();
	    $result=$x->dispatchCall
	    (
	    array
		(
		    'ItemID' => $book->data["ebayItemId"],
		    'EndingReason' => 'NotAvailable'
		)
	    );
	    if ($result)
		$book->delEbayId($idBook);
	    return($result);
	}
	else
	    return(false);
    }
    
    /**
    * @return boolean
    * @idBook int
    */
   function updateEbayItemId($idBook)
    {
	global $book;
        if (($book->get($idBook)) AND ($book->data["ebayItemId"]!=""))
        {
	    $x = new ebay_ReviseItem();
	    $result=$x->dispatchCall
	    (
		array
		(
		    'ebayItemId' => $book->data["ebayItemId"],
		    'title' => $book->data["title"],
		    'title80' => $titleshort,
		    'author' => $book->data["author"],
		    'quantity' => $book->data["quantity"],
		    'price' => $book->data["price"],
		    'description' => $this->decode($book->data["description"]),
		    'ebayCategoryId' => $book->data["ebayCategoryId"],
		    'ebayCategoryIdSecondary' => $book->data["ebayCategoryIdSecondary"],
		    'idBook'    => $book->data["idBook"],
		    'ebayShippingCost'    => $book->data["ebayShippingCost"],
		    'ebayShippingCostInternational'    => $book->data["ebayShippingCostInternational"]
		)
	    );
	    if ($result)
		$book->updateEbayId($idBook);
	    return($result);
	}
	else
	    return(false);	
    }
        
    /**
    * @return boolean
    */
    function getEbayOrder()
    {
	global $book;
	$x = new ebay_GetOrders();
	$x->dispatchCall
	(
	array
	    (
		'CreateTimeFrom' => date("Y-d",(time()-5184000)).'-01 00:00',
		// Order from 2 months old
		'CreateTimeTo' => '2020-12-30 15:00',
		'OrderRole' => 'Seller',
		'OrderStatus' => 'Active'
	    )
	);
    }

}

?>
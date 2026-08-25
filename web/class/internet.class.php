<?php
/**
 * * * *      GestLibr: internet.class.php     	*
 * * * *	Internet Class           	*
 * * * *  Romain DUCHENE, Mar 2006 - 2018 	*
 */

require_once __DIR__ . '/ebay.class.php';

Class Internet
{
    var $babase;
    var $err_msg = "";
    var $error = 0;
    var $idBook = -1;
    var $data = array();

    function __construct()
    {
        $this->Internet();
    }
    
    function secureData()
    {
        // To delete <br/> at the end of the description field
        $pattern     = "&lt;br /&gt;";
        $patternmini = "gt;";
        $txt         = $this->data["description"];
        $nb          = preg_match("/" . $patternmini . "$/", $txt);
        if ($nb != 0)
            $this->data["description"] = str_replace($pattern, "", $txt);
        // Now we are securing all data with an addslashes
        foreach ($this->data as $key => $val) {
            $this->data[$key] = addslashes($val);
        }
    }
    
    // Pour les utilisateurs ayant des versions antérieures à PHP 4.3.0 :
    function unhtmlentities($string)
    {
        // Remplace les entités numériques
        $string = preg_replace_callback(
            '~&#x([0-9a-f]+);~i',
            function ($matches) {
                return chr(hexdec($matches[1]));
            },
            $string
        );
        $string = preg_replace_callback(
            '~&#([0-9]+);~',
            function ($matches) {
                return chr((int) $matches[1]);
            },
            $string
        );
        // Remplace les entités litérales
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        return strtr($string, $trans_tbl);
    }
    
    function stripAccents($string)
    {
        return strtr($string, 'à·‚„‰ÁËéÍÎÏÌÓÔÒÚÛÙıˆ˘˙˚¸¿¡¬√ƒ«»… ÀÃÕŒœ—“”‘’÷Ÿ⁄€‹', 'aaaaaceeeeiiiinooooouuuuAAAAACEEEEIIIINOOOOOUUUU');
    }
    
    function decode($txt)
    {
        // Replace some specials char & abbreviation
        $txt     = html_entity_decode($txt);
        $txt     = html_entity_decode($txt);
        $txt     = html_entity_decode(html_entity_decode($txt));
        // Replace pattern source
        $source  = array(
            "\r\n",
            "&#39;",
            "P.",
            "coll.",
            "&hellip;",
            "br.",
            "ill.",
            "couv.",
            "défr.",
            "rel.",
            "gd",
            "éd.",
            "nb.",
            "pp.",
            "cart.",
            "h.t.",
            "in t.",
            "pl."
        );
        // Replace pattern source
        $replace = array(
            "<br>",
            "'",
            "Paris",
            "collection",
            "...",
            "broché",
            "illustrations",
            "couverture",
            "défraichie",
            "reliure",
            "grand",
            "éditeur",
            "nombreuses",
            "pages",
            "cartonnage",
            "hors texte",
            "in texte",
            "pleine"
        );
        return (str_replace($source, $replace, $txt));
    }
    
    function decode_html($txt)
    {
        // Replace some specials char & abbreviation
        //        $txt = $this->unhtmlentities($txt);
        $txt     = html_entity_decode($txt);
        $txt     = html_entity_decode($txt);
        // Replace pattern source
        //        $source = array('"', "à", "‚", "¸", "˚", "˘", "Á", "é", "Ë", "Í", "Î", "∞", "&lt;", "&gt;", "&amp;", "\r\n", "&#39;", "P.", "coll.", "&hellip;", "br.", "ill.", "couv.", "d»fr.", "rel.", "gd", "»d.", "nb.", "pp.", "cart.", "h.t.", "in t.", "pl.");
        $source  = array(
            '"',
            "\r\n",
            "&#39;",
            "P.",
            "coll.",
            "&hellip;",
            "br.",
            "ill.",
            "couv.",
            "d»fr.",
            "rel.",
            "gd",
            "»d.",
            "nb.",
            "pp.",
            "cart.",
            "h.t.",
            "in t.",
            "pl."
        ); // Replace pattern source
        //        $replace = array("'", "&agrave;", "&acirc;", "&uuml;", "&ucirc;", "&ugrave;", "&ccedil;", "&eacute;", "&egrave;", "&ecirc;", "&euml;", "&deg;", "<", ">", "&", "<br>", "'", "Paris", "collection", "...", "broch&eacute;", "illustrations", "couverture", "d&eacute;fraichie", "reliure", "grand", "&eacute;diteur", "nombreuses", "pages", "cartonnage", "hors texte", "in texte", "pleine");
        $replace = array(
            "'",
            "<br>",
            "'",
            "Paris",
            "collection",
            "...",
            "broch&eacute;",
            "illustrations",
            "couverture",
            "d&eacute;fraichie",
            "reliure",
            "grand",
            "&eacute;diteur",
            "nombreuses",
            "pages",
            "cartonnage",
            "hors texte",
            "in texte",
            "pleine"
        );
        //return($txt);
        return (str_replace($source, $replace, $txt));
    }
    
    function viewError($error)
    {
        // Convert the error code in a text message
        // $error: the error code
        // Return the text
        $convert = $this->babase->DbSelect("SELECT text FROM errormsg WHERE errorCode='$error' AND lang='fr'", $nb);
        if ($nb == 1)
            return ($convert[0]["text"]);
        else
            return ("Unknown error");
    }
    
    function addHistory($id, $name, $details)
    {
        settype($id, 'integer');
        $name    = secureTxt($name);
        $details = secureTxt($details);
        return ($this->babase->DbInsert("INSERT INTO internethistory (idInternetHistory, id, name, details, date, idUser) VALUES ('', '$id', '$name', '$details', '" . time() . "', '" . $_SESSION["idUser"] . "')"));
    }
    
    function Internet()
    {
        $this->babase = new DataBase();
        $this->babase->DbConnect();
        $this->idBook = -1;
    }
    
    function create($name, $description, $method, $header, $template = "", $param = "")
    {
        $name        = secureTxt($name);
        $description = secureTxt($description);
        $template    = secureTxt($template);
        $header      = secureTxt($header);
        $method      = secureTxt($method);
        $param       = secureTxt($param);
        $id          = $this->babase->DbInsert("INSERT INTO internet (idInternet, name, description, method, param, header, template, status, idGroup) VALUES
			(\"\", \"$name\", \"$description\", \"$method\", \"$param\", \"$header\", \"$template\", \"NOT-SYNC\", '" . $_SESSION["idGroup"] . "')", 1);
        $this->addHistory($id, "create", $name);
        return ($id);
    }
    
    function delete($idInternet)
    {
        settype($idInternet, 'integer');
        $this->babase->DbQuery("DELETE FROM internetDetails WHERE idInternet = '$idInternet'");
        $this->babase->DbQuery("DELETE FROM internet WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND idInternet = '$idInternet'");
        $this->addHistory($idInternet, "delete", "ok");
        return (true);
    }
    
    function modify($idInternet, $name, $description, $method, $header, $template, $param)
    {
        settype($idInternet, 'integer');
        $name        = secureTxt($name);
        $description = secureTxt($description);
        $template    = secureTxt($template);
        $method      = secureTxt($method);
        $param       = secureTxt($param);
        if ($this->isExist($idInternet)) {
            $this->addHistory($idInternet, "modify", "ok");
            return ($this->babase->DbQuery("UPDATE internet SET name = \"$name\", description = \"$description\", method = \"$method\", param = \"$param\", header = \"$header\", template = \"$template\" WHERE idInternet='$idInternet'", 1));
        } else
            return (false);
    }
    
    function get($idInternet)
    {
        settype($idInternet, 'integer');
        if ($this->isExist($idInternet)) {
            $data            = $this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND idInternet = '$idInternet'", $nul);
            $data            = $data[0];
            $data["details"] = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE idInternet = '$idInternet'", $nul2);
            if ($nul == 1)
                return ($data);
            else
                return (false);
        }
    }
    
    function listInternet()
    {
        return ($this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='" . $_SESSION["idGroup"] . "' ORDER BY name", $nul));
    }
    
    function addDetail($idInternet, $type, $detail, $order = 1)
    {
        settype($idInternet, 'integer');
        settype($detail, 'integer');
        settype($order, 'integer');
        $type = secureTxt($type);
        if ($this->isExist($idInternet)) {
            // We check if it's not a duplicate data
            $check = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE idInternet='$idInternet' AND type='$type' AND detail='$detail'", $nb);
            if ($nb == 0) {
                $this->addHistory($idInternet, "addDetail", "ok");
                return ($this->babase->DbInsert("INSERT INTO internetDetails (idInternetDetails, idInternet, type, detail, `order`) VALUES
			  (\"\", \"$idInternet\", \"$type\", \"$detail\", \"$order\")", 1));
            } else {
                $this->addHistory($idInternet, "addDetail", "ko: Duplicata");
                return (false); // Duplicate data
            }
        } else
            return (false); // idInternet doesn't exist !
    }
    
    function delDetail($idInternetDetails)
    {
        settype($idInternetDetails, 'integer');
        $this->addHistory($idInternet, "delDetail", "ok: $idInternetDetails");
        return ($this->babase->DbQuery("DELETE FROM internetDetails WHERE idInternetDetails = '$idInternetDetails'"));
    }
    
    function isExist($idInternet)
    {
        settype($idInternet, 'integer');
        $this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND idInternet = '$idInternet'", $nb);
        if ($nb != 0)
            return (true);
        else
            return (false);
    }
    
    function getBookList($idInternet)
    {
        settype($idInternet, 'integer');
	$data = $this->get($idInternet);
        // If we want to sync ALL BOOKS from all user groups or only for current user group
	if (config("internet_bypass_group") == "1")
            $groupFilter = "";
        else
            $groupFilter = "b.idGroup='".$_SESSION["idGroup"]."' AND ";        
	if (config("internet_include_all_books") == "1")
            // We are bypassing the category and user filter... All books are included
            $idCategory = "(1=1)";
	else
        {
            // Get all details for this - ONLY CATEGORY ARE OK NOW...
            $details    = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE type='cat' AND idInternet = '$idInternet'", $nb);
            $idCategory = "(";
            $i          = 0;
            while ($i != $nb) 
            {
               	$idCategory .= "bic.idCategory='" . $details[$i]["detail"] . "' OR ";
               	$i++;
            }
            $idCategory .= " 1=0)";
        }
        switch($data["method"])
		{
	    	case "isbn-only":
				$isbn = "b.isbn!='' AND";
				break;
	    	case "amazon":
				$isbn = "b.isbn LIKE '' AND";
				break;
	    	default:
				$isbn = "";
				break;
		}
        $tmp       = $this->babase->DbSelect("SELECT * FROM `book` AS b, `bookincategory` AS bic, `condition` AS c, `binding`, `format`, `language` WHERE $groupFilter $isbn $idCategory AND `language`.idLanguage=b.language AND `format`.idFormat=b.format AND `binding`.idBinding=b.binding AND c.idCondition=b.condition AND bic.idBook=b.idBook AND b.quantity>0 AND b.internet='1' ORDER BY author", $nb);
        // Preparing the Catalog exclusion list
        $exclusion = $this->babase->DbSelect("SELECT * FROM internetDetails WHERE type='excludeCatalog' AND idInternet = '$idInternet'", $nbExclusion);
        if ($nbExclusion == 0)
            return ($tmp);
        else 
        {
            // we prepare the list
            $i = 0;
            while ($i != $nbExclusion) 
            {
                $sqlExclude .= "idCatalog='" . $exclusion[$i]["detail"] . "' OR ";
                $i++;
            }
            $sqlExclude .= "1=0";
            $i = 0;
            $j = 0;
            while ($i != $nb) 
            {
                $this->babase->DbSelect("SELECT idCatalog FROM catalogdata WHERE name='" . $tmp[$i]["idBook"] . "' AND ($sqlExclude)", $ok);
                if ($ok == 0) 
                {
                    $final[$j] = $tmp[$i];
                    $j++;
                }
                $i++;
            }
            return ($final);
        }
    }
    
    function createCSV($data, $file)
    {
        // Creating file
        //unlink("tmp/" . $file);
        $f = fopen("tmp/" . $file, 'w');
        $i = 0;
        while ($i != sizeof($data)) {
            // For each line...
            fputs($f, "\"" . $data[$i]["author"] . "\";\"" . $data[$i]["title"] . "\";\"" . $data[$i]["description"] . "\";\"" . $data[$i]["price"] . "\";\"" . $data[$i]["quantity"] . "\"\r\n");
            $i++;
        }
        // Closing file
        fclose($f);
    }
    
    function createTAB($data, $file)
    {
        // Creating file
        //unlink("tmp/" . $file);
        $f = fopen("tmp/" . $file, 'w');
        $i = 0;
        while ($i != sizeof($data)) {
            // For each line...
            fputs($f, $data[$i]["author"] . "\t" . $data[$i]["title"] . "\t" . $data[$i]["description"] . "\t" . $data[$i]["price"] . "\t" . $data[$i]["quantity"] . "\r\n");
            $i++;
        }
        // Closing file
        fclose($f);
    }
    
    function createFile($data, $template, $file, $type, $header)
    {
        // Creating file
        //@unlink("tmp/" . $file);
        $encoding = config("ftp_file_encoding");
        $f        = fopen("tmp/" . $file, 'w');
        $source   = array(
            '&quot;',
            '&lt;',
            '&gt;',
            '&amp;'
        );
        $replace  = array(
            '"',
            '<',
            '>',
            '&'
        );
        $template = str_replace($source, $replace, $template);
        $i        = 0;
        $pattern  = array(
            "{TAB}",
            "{NEWLINE}",
            "{IDBOOK}",
            "{AUTHOR}",
            "{TITLE}",
            "{TITLE-SHORT80}",
            "{DESCRIPTION}",
            "{PRICE}",
            "{QUANTITY}",
            "{NOTES}",
            "{ENTRYDATE}",
            "{MODIFYDATE}",
            "{IDCAT}",
            "{ISBN}",
            "{EDITOR}",
            "{COLLECTION}",
            "{LANGUAGE}",
            "{PUBLISHDATE}",
            "{PUBLISHLOCATION}",
            "{PAGENUMBER}",
            "{FORMAT}",
            "{LOCATION}",
            "{BINDING}",
            "{BINDING-EBAY}",
            "{BINDING-AMAZON}",
            "{BINDING-FNAC}",
	    "{CONDITION}",
	    "{CONDITION-EBAY}",
	    "{CONDITION-AMAZON}",
	    "{CONDITION-FNAC}",
            "{IS_ISBN}"
        );
        if ($header != "")
            fputs($f, str_replace("{TAB}", "\t", $header) . "\r\n");
        while ($i != sizeof($data)) {
            // For each line...
            switch ($type) {
                case "ebay":
                    $titleshort = substr(substr($this->decode_html($this->stripAccents($data[$i]["author"])), 0, 29) . ": " . ($this->decode_html($this->stripAccents($data[$i]["title"]))), 0, 78);
                    $curData    = array(
                        "\t",
                        "\r\n",
                        ($this->decode_html($data[$i]["idBook"])),
                        ($this->decode_html($data[$i]["author"])),
                        ($this->decode_html($data[$i]["title"])),
                        $titleshort,
                        ($this->decode_html($data[$i]["description"])),
                        utf8_decode($this->decode($data[$i]["price"])),
                        utf8_decode($this->decode($data[$i]["quantity"])),
                        utf8_decode($this->decode($data[$i]["notes"])),
                        utf8_decode($this->decode($data[$i]["entryDate"])),
                        utf8_decode($this->decode($data[$i]["modifyDate"])),
                        utf8_decode($this->decode($data[$i]["idCategory"])),
                        $data[$i]["isbn"],
                        $data[$i]["editor"],
                        $data[$i]["collection"],
                        $data[$i]["languageName"],
                        $data[$i]["publishDate"],
                        $data[$i]["publishLocation"],
                        $data[$i]["pageNumber"],
                        $data[$i]["formatName"],
                        $data[$i]["location"],
                        $data[$i]["bindingName"],
                        $data[$i]["bindingEbay"],
                        $data[$i]["bindingAmazon"],
                        $data[$i]["bindingFnac"],
                        $data[$i]["conditionName"],
                        $data[$i]["idConditionEbay"],
                        $data[$i]["idConditionAmazon"],
                        $data[$i]["idConditionFnac"],
			""
                    );
                    $line       = $template;
                    break;
                case "amazon":
                    // Amazon Price
                    if ($data[$i]["price_amazon"] > 0)
                        $price = $data[$i]["price_amazon"];
                    else
                        $price = $data[$i]["price"];
                    // Publishdate : YYYY-MM-DD (or 1900 by default)
                    if (($data[$i]["publishDate"]) != "")
                    //$publishDate = date("Y-m-d",strtotime($data[$i]["publishDate"]));
                        $publishDate = $data[$i]["publishDate"];
                    else
                        $publishDate = "1900";
                    // Do you use ISBN or not ?
                    if ($data[$i]["isbn"] != "")
                        $is_isbn = "2";
                    else
                        $is_isbn = "";
                    // Editor
                    if ($data[$i]["editor"] != "")
                        $editor = $data[$i]["editor"];
                    else
                        $editor = "unknown";
                    // Is there an ISBN ?
		    if ($data[$i]["isbn"] == "")
			// No ISBN, so we put all data
			$curData = array(
			    "\t",
			    "\r\n",
			    $data[$i]["idBook"],
			    substr($this->decode_html($data[$i]["author"]), 0, 199),
			    substr($this->decode_html($data[$i]["title"]), 0, 499),
			    substr($this->decode($data[$i]["title"]), 0, 79),
			    substr($this->decode_html($data[$i]["description"]), 0, 1430),
			    $price,
			    $this->decode($data[$i]["quantity"]),
			    $this->decode_html($data[$i]["notes"]),
			    $this->decode($data[$i]["entryDate"]),
			    $this->decode($data[$i]["modifyDate"]),
			    $this->decode($data[$i]["idCategory"]),
			    $data[$i]["isbn"],
			    substr($editor, 0, 499),
			    $data[$i]["collection"],
			    $data[$i]["languageName"],
			    $publishDate,
			    $data[$i]["publishLocation"],
			    $data[$i]["pageNumber"],
			    $data[$i]["formatName"],
                $data[$i]["location"],
			    $data[$i]["bindingName"],
			    $data[$i]["bindingEbay"],
			    $data[$i]["bindingAmazon"],
			    $data[$i]["bindingFnac"],
			    $data[$i]["conditionName"],
			    $data[$i]["idConditionEbay"],
			    $data[$i]["idConditionAmazon"],
			    $data[$i]["idConditionFnac"],
			    $is_isbn
			);
		    else
			// There is an ISBN, so no Title, Author, editor,
			$curData = array(
			    "\t",
			    "\r\n",
			    $data[$i]["idBook"],
			    "",
			    "",
			    "",
			    substr($this->decode_html($data[$i]["description"]), 0, 1430),
			    $price,
			    $this->decode($data[$i]["quantity"]),
			    $this->decode_html($data[$i]["notes"]),
			    $this->decode($data[$i]["entryDate"]),
			    $this->decode($data[$i]["modifyDate"]),
			    $this->decode($data[$i]["idCategory"]),
			    $data[$i]["isbn"],
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    "",
			    $data[$i]["conditionName"],
			    $data[$i]["idConditionEbay"],
			    $data[$i]["idConditionAmazon"],
			    $data[$i]["idConditionFnac"],
			    $is_isbn
			);		    
                    $line    = $template;
                    break;
                case "isbn-only":
                    $price = $data[$i]["price"];
                    // Publishdate : YYYY-MM-DD (or 1900 by default)
                    if (($data[$i]["publishDate"]) != "")
                    //$publishDate = date("Y-m-d",strtotime($data[$i]["publishDate"]));
                        $publishDate = $data[$i]["publishDate"];
                    else
                        $publishDate = "1900";
                    // Editor
                    if ($data[$i]["editor"] != "")
                        $editor = $data[$i]["editor"];
                    else
                        $editor = "unknown";
                    // Parsing data
		    $curData = array(
		        "\t",
		        "\r\n",
		        $data[$i]["idBook"],
		        substr($this->decode_html($data[$i]["author"]), 0, 199),
		        substr($this->decode_html($data[$i]["title"]), 0, 499),
		        substr($this->decode($data[$i]["title"]), 0, 79),
		        substr($this->decode_html($data[$i]["description"]), 0, 1430),
		        $price,
		        $this->decode($data[$i]["quantity"]),
		        $this->decode_html($data[$i]["notes"]),
		        $this->decode($data[$i]["entryDate"]),
		        $this->decode($data[$i]["modifyDate"]),
		        $this->decode($data[$i]["idCategory"]),
		        $data[$i]["isbn"],
		        substr($editor, 0, 499),
		        $data[$i]["collection"],
		        $data[$i]["languageName"],
		        $publishDate,
		        $data[$i]["publishLocation"],
		        $data[$i]["pageNumber"],
		        $data[$i]["formatName"],
                $data[$i]["location"],
			$data[$i]["bindingName"],
			$data[$i]["bindingEbay"],
			$data[$i]["bindingAmazon"],
			$data[$i]["bindingFnac"],
                        $data[$i]["conditionName"],
                        $data[$i]["idConditionEbay"],
                        $data[$i]["idConditionAmazon"],
                        $data[$i]["idConditionFnac"],
		        $is_isbn
		    );
                    $line    = $template;
                    break;
                default:
                    // All other sites
                    $curData = array(
                        "\t",
                        "\r\n",
                        $this->decode($data[$i]["idBook"]),
                        $this->decode($data[$i]["author"]),
                        $this->decode($data[$i]["title"]),
                        substr($this->decode($data[$i]["title"]), 0, 79),
                        $this->decode($data[$i]["description"]),
                        $this->decode($data[$i]["price"]),
                        $this->decode($data[$i]["quantity"]),
                        $this->decode($data[$i]["notes"]),
                        $this->decode($data[$i]["entryDate"]),
                        $this->decode($data[$i]["modifyDate"]),
                        $this->decode($data[$i]["idCategory"]),
                        $data[$i]["isbn"],
                        $data[$i]["editor"],
                        $data[$i]["collection"],
                        $data[$i]["languageName"],
                        $data[$i]["publishDate"],
                        $data[$i]["publishLocation"],
                        $data[$i]["pageNumber"],
                        $data[$i]["formatName"],
		                $data[$i]["location"],
			$data[$i]["bindingName"],
			$data[$i]["bindingEbay"],
			$data[$i]["bindingAmazon"],
			$data[$i]["bindingFnac"],
                        $data[$i]["conditionName"],
                        $data[$i]["idConditionEbay"],
                        $data[$i]["idConditionAmazon"],
                        $data[$i]["idConditionFnac"],
			""
                    );
                    $line    = $template;
                    break;
            }
            $line = $template;
            $line = str_replace($pattern, $curData, $line);
            $line = convert_to($line,$encoding);
            fputs($f, $line);
            $i++;
        }
        // Closing file
        fclose($f);
    }
    
    function update($idInternet)
    {
        settype($idInternet, 'integer');
        $internet = $this->babase->DbSelect("SELECT * FROM internet WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND idInternet = '$idInternet'", $nb);
        $internet = $internet[0];
        if ($nb == 1) {
            switch ($internet["method"]) {
		case "isbn-only":
                case "amazon":
                case "fnac":
		case "full-file":
                    // Get parameters; format: filename
                    // ex: upload.txt
                    $param    = explode(";", $internet["param"]);
                    $filename = $param[0];
                    // Creating book list
                    $data     = $this->getBookList($idInternet);
                    // Creating file in /tmp
                    $this->createFile($data, $internet["template"], $filename, $internet["method"], $internet["header"]);
                    $this->addHistory($idInternet, "update", "ok");
                    return (true);
                    break;
                case "ftp":
                    // Get parameters; format: ftpServer;username;password;directory;filename
                    // ex: ftp.wiroo.com;gestlibr;azerty;/upload/;upload.txt
                    $param        = explode(";", $internet["param"]);
                    $ftpServer    = $param[0];
                    $ftpUsername  = $param[1];
                    $ftpPassword  = $param[2];
                    $ftpDirectory = $param[3];
                    $filename     = $param[4];
                    $url          = $param[5];
                    // Creating book list
                    $data         = $this->getBookList($idInternet);
                    // Creating file in /tmp
                    $this->createFile($data, $internet["template"], $filename, $internet["method"], $internet["header"]);
                    if ($internet["method"] != "ftp") {
                        $this->addHistory($idInternet, "update", "ok");
                        return (true);
                    }
                    // Uploading file
                    if ($this->ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename, "secure")) {
                        $this->addHistory($idInternet, "update", "ok");
                        if ($url != "") {
                            // If we need to fetch an URL after FTP sent
                            $file = fopen($url, "r");
                            while (!feof($file)) {
                                $data = $data . fgets($file, 4096);
                            }
                            fclose($file);
                        }
                        return (true);
                    } else {
                        $this->addHistory($idInternet, "update", "error");
                        return (false);
                    }
                    
                    break;
                case "website":
                    // Get parameters; format: ftpServer;username;password;directory;filename;url
                    // ex: ftp.wiroo.com;gestlibr;azerty;/upload/;upload.txt;http://www.website.com/update.php
                    $param        = explode(";", $internet["param"]);
                    $ftpServer    = $param[0];
                    $ftpUsername  = $param[1];
                    $ftpPassword  = $param[2];
                    $ftpDirectory = $param[3];
                    $filename     = $param[4];
                    $url          = $param[5];
                    // Creating book list
                    $data         = $this->getBookList($idInternet);
                    // Creating file in /tmp
                    $this->createFile($data, $internet["template"], $filename);
                    // Uploading file
                    if ($this->ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename, "unsecure")) {
                        $handle   = fopen($url, "rb");
                        $contents = '';
                        while (!feof($handle)) {
                            $contents .= fread($handle, 8192);
                        }
                        fclose($handle);
                        $this->addHistory($idInternet, "update", "ok");
                        return (true);
                    } else {
                        $this->addHistory($idInternet, "update", "error");
                        return (false);
                    }
                    break;
                case "oscommerce":
                    // Get parameters; format: sqlServer;username;password;DBname
                    // ex: sql.wiroo.com;gestlibr;azerty;gestlibr
                    $param       = explode(";", $internet["param"]);
                    $sqlServer   = $param[0];
                    $sqlUsername = $param[1];
                    $sqlPassword = $param[2];
                    $sqlDatabase = $param[3];
                    // Creating book list
                    $data        = $this->getBookList($idInternet);
                    if ($this->oscUpdate($sqlServer, $sqlUsername, $sqlPassword, $sqlDatabase, $data)) {
                        $this->addHistory($idInternet, "update", "ok");
                        return (true);
                    } else {
                        $this->addHistory($idInternet, "update", "error");
                        return (false);
                    }
                    break;
                case "sql":
                    // Execute SQL request only (no output)
                    // SQL resquest must be in TEMPLATE field (not param)
                    $request = html_entity_decode($internet["template"], ENT_QUOTES | ENT_HTML401);
//                    echo "<pre>".$request;
                    if ($this->babase->DbQuery($request)) {
                        $this->addHistory($idInternet, "update", "ok");
                        return (true);
                    } else {
                        $this->addHistory($idInternet, "update", "error");
                        return (false);
                    }
                    break;
                default:
                    // Error... unknow Method !!!
                    return (false);
                    break;
            }
        } else
            return (false); // idInternet doesn't exist !
    }
    
    function ftpUpload($ftpServer, $ftpUsername, $ftpPassword, $ftpDirectory, $filename, $secure = "secure")
    {
        $source_file  = './tmp/' . $filename;
        if ($secure == "secure")
        	$cmd = "curl --user $ftpUsername:$ftpPassword --disable-epsv --ftp-pasv --ftp-skip-pasv-ip -k --ftp-ssl -T $source_file $ftpServer$ftpDirectory -Q \"*DELE $ftpDirectory$filename\" -Q \"*DELE ".$ftpDirectory.".in.".$filename.".\" ";
        else
        	$cmd = "curl --user $ftpUsername:$ftpPassword --disable-epsv --ftp-pasv --ftp-skip-pasv-ip -k -T $source_file $ftpServer$ftpDirectory -Q \"*DELE $ftpDirectory$filename\" -Q \"*DELE ".$ftpDirectory.".in.".$filename.".\" ";
        exec($cmd,$result,$return);
//        echo "<pre>$cmd<hr>Resultat: ";
//        var_dump($result);
//        echo "<hr>Resultat: $return</pre>";
        if ($return == 0)
			return(true);
		else 
			return(false);
/*        $conn_id      = ftp_ssl_connect($ftpServer);
        $login_result = ftp_login($conn_id, $ftpUsername, $ftpPassword);
        if ((!$conn_id) || (!$login_result))
            return (false); // ftp connection failed
        else {
                    echo "LOGIN OK !";
            ftp_pasv($conn_id, true);
                if (ftp_put($conn_id, $filename, $source_file, FTP_ASCII))
                    return (true);
                else
                {
                    echo "TRANSFERT KO !";
                    return (false); // Upload failed
                }
//            } else
 //               return (false); // chdir failed
        }
        ftp_quit($conn_id);
        echo "FINI !";
        return (false);
*/
    }
    
    function oscUpdate($sqlServer, $sqlUsername, $sqlPassword, $sqlDatabase, $data)
    {
        // Get category list
        $cat          = $this->babase->DbSelect("SELECT * FROM category;", $nbCat);
        // Save actual database parameters
        $saveServer   = $this->babase->DB_SERVEUR;
        $saveUsername = $this->babase->DB_USER;
        $savePassword = $this->babase->DB_PASS;
        $saveDatabase = $this->babase->DB_BDD;
        // Creating SQL connection to OSCommerce
        $this->babase->DbInit($sqlUsername, $sqlPassword, $sqlServer, $sqlDatabase);
        $this->babase->DbConnect();
        
        $modify_date = date("Y-m-d H:i:s");
        
        // Updating category list
        $i = 0;
        while ($i != (sizeof($cat))) {
            $osc = $this->babase->DbSelect("SELECT * FROM categories WHERE categories_id LIKE '" . $cat[$i]["idCategory"] . "'", $nb);
            if ($nb == 1) {
                // Category already exists... It's just an update
                // We update description also (only for all languages)
                $this->babase->DbQuery("UPDATE categories_description SET categories_name='" . addslashes(html_entity_decode($cat[$i]["name"])) . "' WHERE categories_id LIKE '" . $cat[$i]["idCategory"] . "'");
            } else {
                // Category doesn't exist... We have to create it !
                // Creating categories record
                $osc = $this->babase->DbInsert("INSERT INTO `categories` ( `categories_id` , `date_added`) VALUES ('" . $cat[$i]["idCategory"] . "', '" . $modify_date . "')");
                // Creating categories_description record
                $osc = $this->babase->DbInsert("INSERT INTO `categories_description` (`categories_id` , `language_id` , `categories_name`) VALUES ('" . $cat[$i]["idCategory"] . "', '1', '" . addslashes(html_entity_decode($cat[$i]["name"])) . "')");
            }
            $i++;
        }
        
        // Update for each record
        $i = 0;
        while ($i != (sizeof($data))) {
            $osc = $this->babase->DbSelect("SELECT * FROM products WHERE products_id LIKE '" . $data[$i]["idBook"] . "'", $nb);
            if ($nb == 1) {
                // Record already exists... It's just an update
                // We update price, status, and modify_date
                $this->babase->DbQuery("UPDATE products SET products_price='" . addslashes($data[$i]["price"]) . "', products_last_modified='" . $modify_date . "', products_status='1' WHERE products_id LIKE '" . $data[$i]["idBook"] . "'");
                // We update description also (only for all languages)
                $this->babase->DbQuery("UPDATE products_description SET products_name='" . addslashes(html_entity_decode($data[$i]["author"])) . " - " . addslashes(html_entity_decode($data[$i]["title"])) . "', products_description='" . addslashes(html_entity_decode($data[$i]["description"])) . "' WHERE products_id LIKE '" . $data[$i]["idBook"] . "'");
                // Deleting & Creating products_to_categories record
                $osc = $this->babase->DbQuery("DELETE FROM `products_to_categories` WHERE `products_id` LIKE '" . $data[$i]["idBook"] . "'");
                $osc = $this->babase->DbInsert("INSERT INTO `products_to_categories` (`products_id` , `categories_id`) VALUES ('" . $data[$i]["idBook"] . "', '" . $data[$i]["idCategory"] . "')");
            } else {
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
        $osc                      = $this->babase->DbQuery("UPDATE products SET products_status='0' WHERE products_last_modified != '" . $modify_date . "'");
        // Resoting database parameters
        // Save actual database parameters
        $this->babase->DB_SERVEUR = $saveServer;
        $this->babase->DB_USER    = $saveUsername;
        $this->babase->DB_PASS    = $savePassword;
        $this->babase->DB_BDD     = $saveDatabase;
        $this->babase->DbConnect();
        return (true);
    }
    
    /**
     * @return int ItemID or false if an error occured
     * @param int $idBook
     */
    function buildBookPhotoUrls($bookData)
    {
        $urls = array();
        if (!is_array($bookData) || !isset($bookData["photo"]) || !is_array($bookData["photo"]))
            return $urls;

        $baseUrl = rtrim((string)config('website_baseurl'), '/');
        $i = 0;
        while ($i < sizeof($bookData["photo"]))
        {
            $file = isset($bookData["photo"][$i]["file"]) ? trim((string)$bookData["photo"][$i]["file"]) : "";
            if ($file == "")
            {
                $i++;
                continue;
            }
            if (preg_match('/^https?:\/\//i', $file))
                $url = $file;
            else if ($baseUrl != "")
                $url = $baseUrl . (substr($file, 0, 1) == "/" ? $file : "/" . $file);
            else
                $url = $file;

            if (!in_array($url, $urls))
                $urls[] = $url;
            if (sizeof($urls) >= 24)
                break;
            $i++;
        }
        return $urls;
    }

    function composeEbayListingTitle($author, $title, $subtitle = "")
    {
        $author = trim((string)$author);
        $title = trim((string)$title);
        $subtitle = trim((string)$subtitle);

        $result = "";
        if (($author != "") && ($title != ""))
            $result = $author . ": " . $title;
        else if ($title != "")
            $result = $title;
        else if ($author != "")
            $result = $author;

        if ($subtitle != "")
            $result = ($result != "") ? ($result . " - " . $subtitle) : $subtitle;

        return trim((string)$result);
    }

    function resolveTargetGroupId($idGroup = 0)
    {
        $idGroup = (int)$idGroup;
        if ($idGroup > 0)
            return $idGroup;
        return (isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0);
    }

    function addOnEbay($idBook, $idGroup = 0)
    {
        global $book;
        $targetGroupId = $this->resolveTargetGroupId($idGroup);
        $sessionGroupExists = isset($_SESSION["idGroup"]);
        $sessionGroupOriginal = $sessionGroupExists ? (int)$_SESSION["idGroup"] : 0;
        $sessionGroupChanged = ($targetGroupId > 0) && (!$sessionGroupExists || $sessionGroupOriginal !== $targetGroupId);
        if ($sessionGroupChanged)
            $_SESSION["idGroup"] = $targetGroupId;

        try {
        if (($book->get($idBook)) AND ($book->data["ebayItemId"] == "") AND ($book->data["onEbay"] == "1")) {
            $author = trim((string)$book->data["author"]);
            $title = trim((string)$book->data["title"]);
            $subtitle = trim((string)(isset($book->data["subtitle"]) ? $book->data["subtitle"] : ""));
            $ebayTitle = $this->composeEbayListingTitle($author, $title, $subtitle);
            if ($ebayTitle == "")
                $ebayTitle = $title;
            $titleshort = convert_to(substr($ebayTitle, 0, 72) . " (" . $book->data["idBook"] . ")",'UTF-8');
            $effectivePrice = trim((string)$book->data["price_ebay"]);
            if ($effectivePrice == "" || (float)str_replace(",", ".", $effectivePrice) <= 0)
                $effectivePrice = $book->data["price"];
            $photoUrls = $this->buildBookPhotoUrls($book->data);
            if (sizeof($photoUrls) > 0)
                $photo = $photoUrls[0];
            else
                $photo = "";
            $id_ebay = ebay_AddItem(array(
                'title' => $book->data["title"],
                'title80' => $titleshort,
                'author' => $book->data["author"],
                'subtitle' => (isset($book->data["subtitle"]) ? $book->data["subtitle"] : ""),
                'quantity' => $book->data["quantity"],
                'price' => $effectivePrice,
                'price_ebay' => $book->data["price_ebay"],
                'description' => $this->decode($book->data["description"]),
                'ebayUseDescriptionTemplate' => (isset($book->data["ebayUseDescriptionTemplate"]) ? $book->data["ebayUseDescriptionTemplate"] : 0),
                'ebayCategoryId' => $book->data["ebayCategoryId"],
                'ebayCategoryIdSecondary' => $book->data["ebayCategoryIdSecondary"],
                'idBook' => $book->data["idBook"],
                'idGroup' => $targetGroupId,
                'language' => (isset($book->data["language"]) ? $book->data["language"] : ""),
                'pageNumber' => (isset($book->data["pageNumber"]) ? $book->data["pageNumber"] : ""),
                'publishLocation' => (isset($book->data["publishLocation"]) ? $book->data["publishLocation"] : ""),
                'weight_g' => (isset($book->data["weight_g"]) ? $book->data["weight_g"] : 0),
                'ebayShippingCost' => $book->data["ebayShippingCost"],
                'ebayShippingCostInternational' => $book->data["ebayShippingCostInternational"],
                'ebayPolicyMode' => (isset($book->data["ebayPolicyMode"]) ? $book->data["ebayPolicyMode"] : "default"),
                'ebayPaymentPolicyIdOverride' => (isset($book->data["ebayPaymentPolicyIdOverride"]) ? $book->data["ebayPaymentPolicyIdOverride"] : ""),
                'ebayFulfillmentPolicyIdOverride' => (isset($book->data["ebayFulfillmentPolicyIdOverride"]) ? $book->data["ebayFulfillmentPolicyIdOverride"] : ""),
                'ebayReturnPolicyIdOverride' => (isset($book->data["ebayReturnPolicyIdOverride"]) ? $book->data["ebayReturnPolicyIdOverride"] : ""),
                'photoUrls' => $photoUrls,
                'photo' => $photo
            ));
            if ($id_ebay)
                $book->addEbayId($id_ebay, $idBook);
            return ($id_ebay);
        } else
            return (false);
        } finally {
            if ($sessionGroupChanged)
            {
                if ($sessionGroupExists)
                    $_SESSION["idGroup"] = $sessionGroupOriginal;
                else
                    unset($_SESSION["idGroup"]);
            }
        }
    }

    function getEbayLastError($idBook, $idGroup = 0)
    {
        $idBook = (int) $idBook;
        if ($idBook <= 0) {
            return '';
        }

        $idGroup = $this->resolveTargetGroupId($idGroup);
        $rows = $this->babase->DbSelect(
            "SELECT `lastErrorMessage` FROM `ebay_listing_state` WHERE `idGroup`='" . $idGroup . "' AND `idBook`='" . $idBook . "' ORDER BY `updatedAt` DESC LIMIT 1",
            $nb
        );

        if ($nb == 1 && !empty($rows[0]["lastErrorMessage"])) {
            return (string) $rows[0]["lastErrorMessage"];
        }

        return '';
    }
    
    /**
     * @return boolean
     * @param int $idBook
     */
    function delOnEbay($idBook, $idGroup = 0)
    {
        global $book;
        $targetGroupId = $this->resolveTargetGroupId($idGroup);
        $sessionGroupExists = isset($_SESSION["idGroup"]);
        $sessionGroupOriginal = $sessionGroupExists ? (int)$_SESSION["idGroup"] : 0;
        $sessionGroupChanged = ($targetGroupId > 0) && (!$sessionGroupExists || $sessionGroupOriginal !== $targetGroupId);
        if ($sessionGroupChanged)
            $_SESSION["idGroup"] = $targetGroupId;
        try {
        if (($book->get($idBook)) AND ($book->data["ebayItemId"] != "")) {
            $result = ebay_EndItem(array(
                'ItemID' => $book->data["ebayItemId"],
                'SKU'=> $book->data["idBook"],
                'idGroup' => $targetGroupId,
                'EndingReason' => 'NotAvailable'
            ));
            if ($result)
            	// Ok, we delete the EbayId
                $book->delEbayId($idBook);
            return ($result);
        } else
            return (false);
        } finally {
            if ($sessionGroupChanged)
            {
                if ($sessionGroupExists)
                    $_SESSION["idGroup"] = $sessionGroupOriginal;
                else
                    unset($_SESSION["idGroup"]);
            }
        }
    }
    
    /**
     * @return boolean
     * @idBook int
     */
    function updateEbayItemId($idBook, $idGroup = 0)
    {
        global $book;
        $targetGroupId = $this->resolveTargetGroupId($idGroup);
        $sessionGroupExists = isset($_SESSION["idGroup"]);
        $sessionGroupOriginal = $sessionGroupExists ? (int)$_SESSION["idGroup"] : 0;
        $sessionGroupChanged = ($targetGroupId > 0) && (!$sessionGroupExists || $sessionGroupOriginal !== $targetGroupId);
        if ($sessionGroupChanged)
            $_SESSION["idGroup"] = $targetGroupId;
        try {
        if (($book->get($idBook)) AND ($book->data["ebayItemId"] != "")) {
            $author = trim((string)$book->data["author"]);
            $title = trim((string)$book->data["title"]);
            $subtitle = trim((string)(isset($book->data["subtitle"]) ? $book->data["subtitle"] : ""));
            $ebayTitle = $this->composeEbayListingTitle($author, $title, $subtitle);
            if ($ebayTitle == "")
                $ebayTitle = $title;
            $titleshort = convert_to(substr($ebayTitle, 0, 72) . " (" . $book->data["idBook"] . ")",'UTF-8');
            $effectivePrice = trim((string)$book->data["price_ebay"]);
            if ($effectivePrice == "" || (float)str_replace(",", ".", $effectivePrice) <= 0)
                $effectivePrice = $book->data["price"];
            $photoUrls = $this->buildBookPhotoUrls($book->data);
            if (sizeof($photoUrls) > 0)
                $photo = $photoUrls[0];
            else
                $photo = "";
            $result = ebay_ReviseItem(array(
                'ebayItemId' => $book->data["ebayItemId"],
                'title' => $book->data["title"],
                'title80' => $titleshort,
                'author' => $book->data["author"],
                'subtitle' => (isset($book->data["subtitle"]) ? $book->data["subtitle"] : ""),
                'quantity' => $book->data["quantity"],
                'price' => $effectivePrice,
                'price_ebay' => $book->data["price_ebay"],
                'description' => $this->decode($book->data["description"]),
                'ebayUseDescriptionTemplate' => (isset($book->data["ebayUseDescriptionTemplate"]) ? $book->data["ebayUseDescriptionTemplate"] : 0),
                'ebayCategoryId' => $book->data["ebayCategoryId"],
                'ebayCategoryIdSecondary' => $book->data["ebayCategoryIdSecondary"],
                'idBook' => $book->data["idBook"],
                'idGroup' => $targetGroupId,
                'language' => (isset($book->data["language"]) ? $book->data["language"] : ""),
                'pageNumber' => (isset($book->data["pageNumber"]) ? $book->data["pageNumber"] : ""),
                'publishLocation' => (isset($book->data["publishLocation"]) ? $book->data["publishLocation"] : ""),
                'weight_g' => (isset($book->data["weight_g"]) ? $book->data["weight_g"] : 0),
                'ebayShippingCost' => $book->data["ebayShippingCost"],
                'ebayShippingCostInternational' => $book->data["ebayShippingCostInternational"],
                'ebayPolicyMode' => (isset($book->data["ebayPolicyMode"]) ? $book->data["ebayPolicyMode"] : "default"),
                'ebayPaymentPolicyIdOverride' => (isset($book->data["ebayPaymentPolicyIdOverride"]) ? $book->data["ebayPaymentPolicyIdOverride"] : ""),
                'ebayFulfillmentPolicyIdOverride' => (isset($book->data["ebayFulfillmentPolicyIdOverride"]) ? $book->data["ebayFulfillmentPolicyIdOverride"] : ""),
                'ebayReturnPolicyIdOverride' => (isset($book->data["ebayReturnPolicyIdOverride"]) ? $book->data["ebayReturnPolicyIdOverride"] : ""),
                'photoUrls' => $photoUrls,
                'photo' => $photo
            ));
            if ($result)
                $book->updateEbayId($idBook);
            return ($result);
        } else
            return (false);
        } finally {
            if ($sessionGroupChanged)
            {
                if ($sessionGroupExists)
                    $_SESSION["idGroup"] = $sessionGroupOriginal;
                else
                    unset($_SESSION["idGroup"]);
            }
        }
    }
    
    /**
     * @return boolean
     */
    function getEbayOrder($idGroup = 0)
    {
        global $book;
        return(ebay_GetOrders(array(
            'idGroup' => $this->resolveTargetGroupId($idGroup),
            'CreateTimeFrom' => date_create_from_format('Y-m-d H:i',(date("Y-m", (time() - 5184000)) . '-01 00:00')),
            // Order from 2 months old
            'CreateTimeTo' => date_create_from_format('Y-m-d H:i', date('Y-m-d H:i')),
            'OrderRole' => 'Seller',
            'OrderStatus' => 'Active'
        )));
    }
    
    /**
     * @return boolean
     */
    function resyncEbay($PageNumber=1,$EntriesPerPage=50,$idGroup = 0)
    {
        global $book;
        return(ebay_GetSellerList(array(
            'idGroup' => $this->resolveTargetGroupId($idGroup),
            'PageNumber' => $PageNumber,
            'EntriesPerPage' => $EntriesPerPage,
            'EndTimeFrom' => date_create_from_format('Y-m-d H:i',(date("Y-m-d", (time())) . ' 00:00')),
            'EndTimeTo' => date_create_from_format('Y-m-d H:i',(date("Y-m-d", (time() + 5184000)) . ' 00:00'))
        )));
    }
    
}

?>

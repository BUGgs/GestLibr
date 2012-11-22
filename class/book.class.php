<?php

/* Book Class
 * v 1.0
 * Romain DUCHENE
 * April 2005
 */

Class Book
{
    var $defaultRow = 10000;
    var $babase;
    var $idBook = "-1";
    var $err_msg = "";
    var $searchResultNb = "0";
    var $searchResult = "";
    var $data = "";
    var $error = 0;
    
    function secureData()
    {
        // To delete <br/> at the end of the description field
        $pattern     = "&lt;br /&gt;";
        $patternmini = "gt;";
        $txt         = $this->data["description"];
        $nb          = preg_match("/" . $patternmini . "$/", $txt);
        if ($nb != 0)
            $this->data["description"] = str_replace($pattern, "", $txt);
        // To delete <p> and </p> into the description field
        $this->data["description"] = str_replace("&lt;p&gt;", "", $this->data["description"]);
        $this->data["description"] = str_replace("&lt;/p&gt;", "", $this->data["description"]);
        // Now we are securing all data with an addslashes
        reset($this->data);
        // To avoid htmlarea to convert html entities...
        $this->data["description"] = html_entity_decode($this->data["description"]);
        $this->data["notes"]       = html_entity_decode($this->data["notes"]);
        while (list($key, $val) = each($this->data)) {
            $this->data[$key] = addslashes($val);
        }
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
    
    function Book()
    {
        $this->babase = new DataBase();
        $this->babase->DbConnect();
        $this->idBook = -1;
    }
    
    function assignData($postData)
    {
        // Assign all the data to the $this->data from $postData
        $this->data["idBook"]                        = $postData["idBook"];
        $this->data["id"]                            = $postData["id"]; // Same as idBook, but only for search
        $this->data["author"]                        = $postData["author"];
        $this->data["title"]                         = $postData["title"];
        $this->data["description"]                   = $postData["description"];
        $this->data["price"]                         = $postData["price"];
        $this->data["price_ebay"]                    = $postData["price_ebay"];
        $this->data["price_amazon"]                  = $postData["price_amazon"];
        $this->data["quantity"]                      = $postData["quantity"];
        $this->data["category"]                      = $postData["category"];
        $this->data["notes"]                         = $postData["notes"];
        $this->data["quantitynotnull"]               = $postData["quantitynotnull"];
        $this->data["internet"]                      = $postData["internet"];
        $this->data["onEbay"]                        = $postData["onEbay"];
        $this->data["location"]                      = $postData["location"];
        $this->data["internetSearch"]                = $postData["internetSearch"];
        $this->data["inCatalog"]                     = $postData["inCatalog"];
        $this->data["modifyDate"]                    = $postData["modifyDate"];
        $this->data["entryDate"]                     = $postData["entryDate"];
        $this->data["isbn"]                          = $postData["isbn"];
        $this->data["editor"]                        = $postData["editor"];
        $this->data["collection"]                    = $postData["collection"];
        $this->data["publishDate"]                   = $postData["publishDate"];
        $this->data["publishLocation"]               = $postData["publishLocation"];
        $this->data["format"]                        = $postData["format"];
        $this->data["pageNumber"]                    = $postData["pageNumber"];
        $this->data["binding"]                       = $postData["binding"];
        $this->data["condition"]                     = $postData["condition"];
        $this->data["language"]                      = $postData["language"];
        $this->data["ebayCategoryId"]                = $postData["ebayCategoryId"];
        $this->data["ebayCategoryIdSecondary"]       = $postData["ebayCategoryIdSecondary"];
        $this->data["ebayShippingCost"]              = $postData["ebayShippingCost"];
        $this->data["ebayShippingCostInternational"] = $postData["ebayShippingCostInternational"];
        $this->data["ebayItemId"]                    = $postData["ebayItemId"];
        $this->data["ebayLastSync"]                  = $postData["ebayLastSync"];
    }
    
    function search($cache = "no-cache", $order = "", $start = 0, $row = 0)
    {
        // Seacrh for a book in the database
        // $cache : to use or not the sql cache query (to not use the cache, value is no-cache or nothing)
        // $order : order to classify the results
        // $start : integer to start the results
        // $row : how many row to display
        // $this->data : table with all books fields
        // Return the results number in a table and the results in $this->data
        if ($row == 0)
            $row = $this->defaultRow;
        if (($cache == "no-cache") OR ($cache == "")) {
            // We create all the new search request
            // Is it a not null quantity search ?
            if ($this->data["quantitynotnull"] == 1)
                $sqladd = " AND `quantity` > 0";
            else
                $sqladd = "";
            // Do we want to include Internet attribute in search ?
            if ($this->data["internetSearch"] == 1) {
                if ($this->data["internet"] == 1)
                    $sqladd .= " AND `internet` = '1'";
                else
                    $sqladd .= " AND `internet` = '0'";
            }
            // Book not on Ebay ?
            if ($this->data["onEbay"] != 1)
                $sqladd .= " AND `onEbay` = '0'";
            // Do we want to search for a specific ID ?
            if ($this->data["id"] != "")
                $sqladd .= " AND book.`idBook` LIKE '" . $this->data["id"] . "'";
            
            // Do we want to search in a specific catalog ?
            //    if ($this->data["inCatalog"]!='') $sqladd .= " AND book.idBook=catalogdata.name AND catalogdata.idCatalog='" . $this->data["inCatalog"] . "'";
            
            // We made the sql request
            $this->secureData();
            if ($this->data["category"] != "") // It's a search with a category
                $sqlSearch = "SELECT * FROM `book` as book, `bookincategory` as bookincategory  WHERE book.`idGroup`='" . $_SESSION["idGroup"] . "' AND (book.`author` LIKE '%" . $this->data["author"] . "%' AND book.`title` LIKE '%" . $this->data["title"] . "%' AND book.`description` LIKE '%" . $this->data["description"] . "%' AND book.`price` LIKE '%" . $this->data["price"] . "%' AND book.`quantity` LIKE '%" . $this->data["quantity"] . "%' AND book.`notes` LIKE '%" . $this->data["notes"] . "%' AND book.`location` LIKE '%" . $this->data["location"] . "%' AND book.idBook=bookincategory.idBook AND bookincategory.idCategory='" . $this->data["category"] . "' AND book.`isbn` LIKE '%" . $this->data["isbn"] . "%' AND book.`editor` LIKE '%" . $this->data["editor"] . "%' AND book.`collection` LIKE '%" . $this->data["collection"] . "%' AND book.`publishDate` LIKE '%" . $this->data["publishDate"] . "%' AND book.`publishLocation` LIKE '%" . $this->data["publishLocation"] . "%' AND book.`format` LIKE '%" . $this->data["format"] . "%' AND book.`pageNumber` LIKE '%" . $this->data["pageNumber"] . "%' AND book.`condition` LIKE '%" . $this->data["condition"] . "%' AND book.`binding` LIKE '%" . $this->data["binding"] . "%' AND book.`language` LIKE '%" . $this->data["language"] . "%'  AND book.`ebayCategoryId` LIKE '%" . $this->data["ebayCategoryId"] . "%' AND book.`ebayCategoryIdSecondary` LIKE '%" . $this->data["ebayCategoryIdSecondary"] . "%' AND book.`ebayShippingCost` LIKE '%" . $this->data["ebayShippingCost"] . "%' AND book.`ebayShippingCostInternational` LIKE '%" . $this->data["ebayShippingCostInternational"] . "%')" . $sqladd;
            else // A search without category
                $sqlSearch = "SELECT * FROM `book` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND (`author` LIKE '%" . $this->data["author"] . "%' AND `title` LIKE '%" . $this->data["title"] . "%' AND `description` LIKE '%" . $this->data["description"] . "%' AND `price` LIKE '%" . $this->data["price"] . "%' AND `quantity` LIKE '%" . $this->data["quantity"] . "%' AND `notes` LIKE '%" . $this->data["notes"] . "%' AND book.`location` LIKE '%" . $this->data["location"] . "%' AND book.`isbn` LIKE '%" . $this->data["isbn"] . "%' AND book.`editor` LIKE '%" . $this->data["editor"] . "%' AND book.`collection` LIKE '%" . $this->data["collection"] . "%' AND book.`publishDate` LIKE '%" . $this->data["publishDate"] . "%' AND book.`publishLocation` LIKE '%" . $this->data["publishLocation"] . "%' AND book.`format` LIKE '%" . $this->data["format"] . "%' AND book.`pageNumber` LIKE '%" . $this->data["pageNumber"] . "%' AND book.`condition` LIKE '%" . $this->data["condition"] . "%' AND book.`binding` LIKE '%" . $this->data["binding"] . "%' AND book.`language` LIKE '%" . $this->data["language"] . "%'  AND book.`ebayCategoryId` LIKE '%" . $this->data["ebayCategoryId"] . "%' AND book.`ebayCategoryIdSecondary` LIKE '%" . $this->data["ebayCategoryIdSecondary"] . "%' AND book.`ebayShippingCost` LIKE '%" . $this->data["ebayShippingCost"] . "%' AND book.`ebayShippingCostInternational` LIKE '%" . $this->data["ebayShippingCostInternational"] . "%')" . $sqladd;
            if ($order != "")
                $sqlOrder = " ORDER BY `" . $order . "`";
            else
                $sqlOrder = " ORDER BY author,title";
            $sqlLimit                = " LIMIT " . $start . ", " . $row;
            // Creating the new cache
            $_SESSION["searchcache"] = $sqlSearch;
            $_SESSION["ordercache"]  = $sqlOrder;
            $_SESSION["limitcache"]  = $sqlLimit;
        } else {
            // We use the cache !
            $sqlSearch = $_SESSION["searchcache"];
            if ($order != "")
                $sqlOrder = " ORDER BY `" . $order . "`";
            else
                $sqlOrder = $_SESSION["ordercache"];
            if ((isset($start)) AND ($row != $this->defaultRow))
                $sqlLimit = " LIMIT " . $start . ", " . $row;
            else
                $sqlLimit = $_SESSION["limitcache"];
        }
        // THE search request
        $searchResult = $this->babase->DbSelect($sqlSearch . $sqlOrder . $sqlLimit, $nul);
        $this->babase->DbSelect($sqlSearch, $searchResult["nb"]);
        return ($searchResult);
    }
    
    function get($id)
    {
        // Get a record in the book database
        // $id : id books field
        // Return the $data of this book or -1 if error
        $id       = (int) $id;
        $bookData = $this->babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook` LIKE '$id'", $result);
        if ($result != 1)
            return (false);
        else {
            $this->assignData($bookData[0]);
            return ($this->data);
        }
    }
    
    function getFromIsbn($isbn)
    {
        // Get a record in the book database
        // @param int $isbn: book isbn
        // @return array Return the $data of this book or -1 if error
        $isbn     = (int) $isbn;
        $bookData = $this->babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `isbn` LIKE '$isbn'", $result);
        if ($result != 1)
            return (false);
        else {
            $this->assignData($bookData[0]);
            return ($this->data);
        }
    }
    
    function add()
    {
        // Add a new record in the book database
        // $this->data : table with all books fields
        // Return the new book id or -1 if error
        $this->secureData();
        if ($this->data["internet"] != 1)
            $this->data["internet"] = 0;
        $this->idBook = $this->babase->DbInsert("INSERT INTO `book` (`idBook`, `author`, `title`, `description`, `price`, `price_ebay`, `price_amazon`, `quantity`, `notes`, `internet`, `location`, `entryDate`, `modifyDate`, `isbn`, `onEbay`, `editor`, `publishDate`, `publishLocation`, `format`, `pageNumber`, `binding`, `language`, `collection`, `ebayCategoryId`, `ebayCategoryIdSecondary`, `ebayShippingCost`, `ebayShippingCostInternational`, `condition`, `idGroup`) VALUES ('', '" . $this->data["author"] . "', '" . $this->data["title"] . "', '" . $this->data["description"] . "', '" . $this->data["price"] . "', '" . $this->data["price_ebay"] . "', '" . $this->data["price_amazon"] . "', '0', '" . $this->data["notes"] . "', '" . $this->data["internet"] . "', '" . $this->data["location"] . "', '" . time() . "', '" . time() . "', '" . $this->data["isbn"] . "', '" . $this->data["onEbay"] . "', '" . $this->data["editor"] . "', '" . $this->data["publishDate"] . "', '" . $this->data["publishLocation"] . "', '" . $this->data["format"] . "', '" . $this->data["pageNumber"] . "', '" . $this->data["binding"] . "', '" . $this->data["language"] . "', '" . $this->data["collection"] . "', '" . $this->data["ebayCategoryId"] . "', '" . $this->data["ebayCategoryIdSecondary"] . "', '" . $this->data["ebayShippingCost"] . "', '" . $this->data["ebayShippingCostInternational"] . "', '" . $this->data["condition"] . "', '" . $_SESSION["idGroup"] . "')", 1);
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`type` ,
`old_value` ,`new_value` ,`comment`, `idUser`) VALUES (NULL ,  '" . $this->idBook . "',  '" . time() . "',  'create',  '',  '',  '', '" . $_SESSION["idUser"] . "')");
        $this->addQty("manual", 1, $this->idBook);
        return ($this->idBook);
    }
    
    function update()
    {
        // Update the current record with $this->data for the book id $thid->idBook
        // $this->data : table with all books fields
        // $this->idBook : books to update
        // Return the update result
        $this->secureData();
        if ($this->data["internet"] != 1)
            $this->data["internet"] = 0;
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '" . $this->idBook . "',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'update',  '',  '',  '')");
        return ($this->babase->DbQuery("UPDATE `book` SET author='" . $this->data["author"] . "', title='" . $this->data["title"] . "', description='" . $this->data["description"] . "', price='" . $this->data["price"] . "', price_ebay='" . $this->data["price_ebay"] . "', price_amazon='" . $this->data["price_amazon"] . "', notes='" . $this->data["notes"] . "', internet='" . $this->data["internet"] . "', location='" . $this->data["location"] . "', modifyDate='" . time() . "', isbn='" . $this->data["isbn"] . "', onEbay='" . $this->data["onEbay"] . "', editor='" . $this->data["editor"] . "', publishDate='" . $this->data["publishDate"] . "', publishLocation='" . $this->data["publishLocation"] . "', format='" . $this->data["format"] . "', pageNumber='" . $this->data["pageNumber"] . "', binding='" . $this->data["binding"] . "', language='" . $this->data["language"] . "', `condition`='" . $this->data["condition"] . "', collection='" . $this->data["collection"] . "', ebayCategoryId='" . $this->data["ebayCategoryId"] . "', ebayCategoryIdSecondary='" . $this->data["ebayCategoryIdSecondary"] . "', ebayShippingCost='" . $this->data["ebayShippingCost"] . "', ebayShippingCostInternational='" . $this->data["ebayShippingCostInternational"] . "' WHERE idBook='" . $this->idBook . "'"));
    }
    
    function del()
    {
        // Delete the record in the book database
        // $this->idBook : id to delete
        // Return the delete result
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '" . $this->idBook . "',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'delete',  '',  '',  '')");
        return ($this->babase->DbQuery("DELETE FROM `book` WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook='" . $this->idBook . "'"));
    }
    
    function addCategory($idCategory, $idBook = -1)
    {
        // Add a book in a category
        // $idCategory : category to add to the book
        // $idBook : to specify antoher book to add (not the current in $this->data[idBook])
        // Return true if ok, false if not (error message in $error)
        
        if ($idBook == -1)
            $idBook = $this->idBook;
        
        // We check if the book isn't already in this category and if the book&cat exist
        $check = $this->babase->DbSelect("SELECT count(*) AS nb FROM bookincategory WHERE idBook='$idBook' AND idCategory='$idCategory'", $nul);
        if ($check[0]["nb"] != 0) {
            $this->error = 1;
            return (false);
        }
        // We check if the book exist
        $check = $this->babase->DbSelect("SELECT count(*) AS nb FROM book WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook='$idBook'", $nul);
        if ($check[0]["nb"] != 1) {
            $this->error = 2;
            return (false);
        }
        // We check if the cat exist
        $check = $this->babase->DbSelect("SELECT count(*) AS nb FROM category WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCategory='$idCategory'", $nul);
        if ($check[0]["nb"] != 1) {
            $this->error = 3;
            return (false);
        }
        // Good : We add the category
        $add = $this->babase->DbInsert("INSERT INTO bookincategory (idBookInCategory, idBook, idCategory) VALUES ('','$idBook','$idCategory')", 1);
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '" . $idBook . "',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'addInCategory',  '',  '$idCategory',  '')");
        
        if ($add > 0)
            return (true);
        else {
            $this->error = 4;
            return (false);
        }
    }
    
    function listCategory($idBook = -1)
    {
        // List the categories of a book
        // $idBook : to specify antoher book to list (not the current in $this->idBook)
        // Return a table of the member categories ([idCategory][name][description]) if ok, 0 if there is no category
        
        if ($idBook == -1)
            $idBook = $this->idBook;
        $list = $this->babase->DbSelect("SELECT bic.idCategory, cat.name, cat.description FROM bookincategory AS bic, category AS cat WHERE bic.idCategory=cat.idCategory AND bic.idBook='$idBook'", $nb);
        if ($nb = 0)
            return (0);
        else
            return ($list);
    }
    
    function delCategory($idCategory, $idBook = -1)
    {
        // Del a book in a category
        // $idCategory : category to del to the book
        // $idBook : to specify antoher book to del (not the current in $this->data[idBook])
        // Return true if ok, false if not (error message in $error)
        
        if ($idBook == -1)
            $idBook = $this->idBook;
        
        // We check if the book isn't already in this category and if the book&cat exist
        $check = $this->babase->DbSelect("SELECT count(*) AS nb FROM bookincategory WHERE idBook='$idBook' AND idCategory='$idCategory'", $nul);
        if ($check[0]["nb"] < 1) {
            $this->error = 5;
            return (false);
        }
        // Good : We delete the category
        $this->babase->DbQuery("DELETE FROM bookincategory WHERE idBook='$idBook' AND idCategory='$idCategory'");
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '" . $idBook . "',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'removeFromCategory',  '$idCategory',  '',  '')");
        return (true);
    }
    
    function listCatalog($idBook = -1)
    {
        // List the parutions of a book in any catalog
        // $idBook : to specify antoher book to list (not the current in $this->idBook)
        // Return a table of the catalog concerned ([idCatalog][name][description][date]) if ok, 0 if there is no category
        if ($idBook == -1)
            $idBook = $this->idBook;
        
        $list = $this->babase->DbSelect("SELECT cd.idCatalog, cat.name, cat.description, cat.dateModify AS date FROM catalogdata AS cd, catalog AS cat WHERE cd.idCatalog=cat.idCatalog AND cd.name='$idBook' AND type='book' GROUP BY `idCatalog` ORDER BY cd.date DESC", $nb);
        if ($nb = 0)
            return (0);
        else
            return ($list);
    }
    
    function stats()
    {
        // Make General statistics on books
        // 
        // Return a table of stats with these colums
        // [totalBooks] [totalBooksAvailable] [totalPriceAvailable] [averagePriceAvailable]
        
        // for totalBooks
        $tempo              = $this->babase->DbSelect("SELECT count(*) AS nb FROM book WHERE idGroup='" . $_SESSION["idGroup"] . "'", $null);
        $data["totalBooks"] = $tempo[0]["nb"];
        
        // for totalBooksAvailable
        $tempo                       = $this->babase->DbSelect("SELECT count(*) AS nb FROM book WHERE idGroup='" . $_SESSION["idGroup"] . "' AND quantity>0", $null);
        $data["totalBooksAvailable"] = $tempo[0]["nb"];
        
        // for totalPriceAvailable
        $tempo                       = $this->babase->DbSelect("SELECT sum(price) AS nb FROM book WHERE idGroup='" . $_SESSION["idGroup"] . "' AND quantity>0", $null);
        $data["totalPriceAvailable"] = $tempo[0]["nb"];
        
        // for averagePriceAvailable
        $data["averagePriceAvailable"] = round($data["totalPriceAvailable"] / $data["totalBooksAvailable"], 2);
        
        return ($data);
    }
    
    function resetOnInternet()
    {
        // Reset all "internet" field to 1 (to allow all books to be on Internet)
        // Return true / the UPDATE query request (no other interest)
        $this->babase->DbQuery("UPDATE `catalog` SET `onInternet`='1' WHERE idGroup='" . $_SESSION["idGroup"] . "'");
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '-1',  '" . time() . "',  '" . $_SESSION["idGroup"] . "',  'resetOnInternet',  '',  '',  '')");
        return ($this->babase->DbQuery("UPDATE `book` SET internet='1' WHERE idGroup='" . $_SESSION["idGroup"] . "'"));
    }
    
    function disableCatalogOnInternet($idCatalog)
    {
        // Set "internet" field to 0 (to disable book to be on Internet) for a specific Catalog
        // Return true / false (if catalog doesn't exist)
        settype($idCatalog, "int");
        $this->babase->DbSelect("SELECT * FROM `catalog` WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $idCatalog . "'", $nb);
        if ($nb == 1) {
            // Catalog exists ! We can fetch all records to make an update
            // Pb with this: $this->babase->DbQuery("UPDATE `book` SET internet=0 WHERE idBook LIKE (SELECT `name` FROM `catalogdata` WHERE idCatalog='323' AND `type` LIKE 'book')");
            $list = $this->babase->DbSelect("SELECT `name` FROM `catalogdata` WHERE idCatalog='$idCatalog' AND `type` LIKE 'book'", $nb);
            $i    = 0;
            while ($i != $nb) {
                $this->babase->DbQuery("UPDATE `book` SET internet=0 WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook LIKE '" . $list[$i]["name"] . "'");
                $i++;
            }
            $this->babase->DbQuery("UPDATE `catalog` SET `onInternet`='0' WHERE idCatalog='" . $idCatalog . "'");
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '-1',  '" . time() . "',  '" . $_SESSION["idGroup"] . "',  'disableCatalogOnInternet',  '',  '$idCatalog',  '')");
            return (true);
        } else
            return (false);
    }
    
    /**
     * @return string
     * @param int $selected 
     */
    function getLanguageHtmlForm($selected = -1)
    {
        // Return a ready to use HTML SELECT form with all language + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `language` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `languageName`", $nb);
        $html = "<SELECT NAME='language'><OPTION VALUE=''></OPTION>";
        $i    = 0;
        while ($i != $nb) {
            if (strlen($sql[$i]["languageName"]) > 30)
                $name = substr($sql[$i]["languageName"], 0, 30) . "...";
            else
                $name = $sql[$i]["languageName"];
            if ($sql[$i]["idLanguage"] == $selected)
            // If this is the selected one		
                $html .= "<OPTION VALUE='" . $sql[$i]["idLanguage"] . "' SELECTED>" . $name . "</OPTION>";
            else
                $html .= "<OPTION VALUE='" . $sql[$i]["idLanguage"] . "'>" . $name . "</OPTION>";
            $i++;
        }
        $html .= "</SELECT>";
        return ($html);
    }
    
    /**
     * @return string
     * @param int $selected 
     */
    function getBindingHtmlForm($selected = -1)
    {
        // Return a ready to use HTML SELECT form with all binding + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `binding` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $html = "<SELECT NAME='binding'><OPTION VALUE=''></OPTION>";
        $i    = 0;
        while ($i != $nb) {
            if (strlen($sql[$i]["bindingName"]) > 30)
                $name = substr($sql[$i]["bindingName"], 0, 30) . "...";
            else
                $name = $sql[$i]["bindingName"];
            if ($sql[$i]["idBinding"] == $selected)
            // If this is the selected one		
                $html .= "<OPTION VALUE='" . $sql[$i]["idBinding"] . "' SELECTED>" . $name . "</OPTION>";
            else
                $html .= "<OPTION VALUE='" . $sql[$i]["idBinding"] . "'>" . $name . "</OPTION>";
            $i++;
        }
        $html .= "</SELECT>";
        return ($html);
    }
    
    /**
     * @return string
     * @param int $selected 
     */
    function getConditionHtmlForm($selected = -1)
    {
        // Return a ready to use HTML SELECT form with all conditions + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `condition` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $html = "<SELECT NAME='condition'><OPTION VALUE=''></OPTION>";
        $i    = 0;
        while ($i != $nb) {
            if (strlen($sql[$i]["conditionName"]) > 30)
                $name = substr($sql[$i]["conditionName"], 0, 30) . "...";
            else
                $name = $sql[$i]["conditionName"];
            if ($sql[$i]["idCondition"] == $selected)
            // If this is the selected one		
                $html .= "<OPTION VALUE='" . $sql[$i]["idCondition"] . "' SELECTED>" . $name . "</OPTION>";
            else
                $html .= "<OPTION VALUE='" . $sql[$i]["idCondition"] . "'>" . $name . "</OPTION>";
            $i++;
        }
        $html .= "</SELECT>";
        return ($html);
    }
    
    /**
     * @return string
     * @param int $selected 
     */
    function getFormatHtmlForm($selected = -1)
    {
        // Return a ready to use HTML SELECT form with all format + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `format` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $html = "<SELECT NAME='format'><OPTION VALUE=''></OPTION>";
        $i    = 0;
        while ($i != $nb) {
            if (strlen($sql[$i]["formatName"]) > 30)
                $name = substr($sql[$i]["formatName"], 0, 30) . "...";
            else
                $name = $sql[$i]["formatName"];
            if ($sql[$i]["idFormat"] == $selected)
            // If this is the selected one		
                $html .= "<OPTION VALUE='" . $sql[$i]["idFormat"] . "' SELECTED>" . $name . "</OPTION>";
            else
                $html .= "<OPTION VALUE='" . $sql[$i]["idFormat"] . "'>" . $name . "</OPTION>";
            $i++;
        }
        $html .= "</SELECT>";
        return ($html);
    }
    
    /**
     * @return string
     * @param int $selected
     * @param int $type 1 for primary, 2 for secondary
     */
    function getEbayCategoryHtmlForm($type, $selected = -1)
    {
        // Return a ready to use HTML SELECT form with all ebay category (primary 1 or secondary 2) + the selected one
        $sql = $this->babase->DbSelect("SELECT * FROM `ebaycategory` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `categoryName`", $nb);
        if ($type == 1)
            $html = "<SELECT NAME='ebayCategoryId'><OPTION VALUE=''></OPTION>";
        else
            $html = "<SELECT NAME='ebayCategoryIdSecondary'><OPTION VALUE=''></OPTION>";
        $i = 0;
        while ($i != $nb) {
            if (strlen($sql[$i]["categoryName"]) > 50)
                $name = substr($sql[$i]["categoryName"], 0, 50) . "...";
            else
                $name = $sql[$i]["categoryName"];
            if ($sql[$i]["categoryId"] == $selected)
            // If this is the selected one		
                $html .= "<OPTION VALUE='" . $sql[$i]["categoryId"] . "' SELECTED>" . $name . "</OPTION>";
            else
                $html .= "<OPTION VALUE='" . $sql[$i]["categoryId"] . "'>" . $name . "</OPTION>";
            $i++;
        }
        $html .= "</SELECT>";
        return ($html);
    }
    
    /**
     * @return boolean
     * @param string $type Type of qty change: manual, buy
     * @param int $qty
     * @param int $idBook
     */
    function addQty($type, $qty = 1, $idBook = -1)
    {
        // Add $qty to the book with idBook and update table bookUpdate
        if ($idBook == -1)
            $idBook = $this->idBook;
        $idBook = (int) $idBook;
        if ($this->get($idBook)) {
            // We update the book
            $this->babase->DbQuery("UPDATE `book` SET `quantity`=`quantity`+$qty, `modifyDate`='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'qtyAdd',  '" . $this->data["quantity"] . "',  '" . (($this->data["quantity"]) + $qty) . "',  '$type')");
            return (true);
        } else
            return (false);
    }
    
    /**
     * @return boolean
     * @param string $type Type of qty change: manual, sell
     * @param int $qty
     * @param int $idBook
     */
    function delQty($type, $qty = 1, $idBook = -1)
    {
        // Reduce $qty to the book with idBook and update table bookUpdate
        if ($idBook == -1)
            $idBook = $this->idBook;
        $idBook = (int) $idBook;
        if ($this->get($idBook)) {
            if ($this->data["quantity"] > 0) {
                // We update the book if quantity is >0
                $this->babase->DbQuery("UPDATE `book` SET `quantity`=`quantity`-$qty, `modifyDate`='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
                $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'qtyDel',  '" . $this->data["quantity"] . "',  '" . (($this->data["quantity"]) - $qty) . "',  '$type')");
                return (true);
            } else
                return (false);
        } else
            return (false);
    }
    
    /**
     * @return boolean
     * @param int $idItemEbay Ebay ItemID to add to this book
     * @param int $idBook
     */
    function addEbayId($idItemEbay, $idBook = -1)
    {
        // Add an ItemID on the book record
        if ($idBook == -1)
            $idBook = $this->idBook;
        $idBook     = (int) $idBook;
        $idItemEbay = (int) $idItemEbay;
        if ($this->get($idBook)) {
            $this->babase->DbQuery("UPDATE `book` SET `ebayItemId`='$idItemEbay', `ebayLastSync`='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'addOnEbay',  '',  '" . $idItemEbay . "',  '')");
            return (true);
        } else
            return (false);
    }
    
    /**
     * @return boolean
     * @param int $idBook
     */
    function delEbayId($idBook = -1)
    {
        // Remove an ItemID on the book record
        if ($idBook == -1)
            $idBook = $this->idBook;
        $idBook = (int) $idBook;
        if ($this->get($idBook)) {
            $this->babase->DbQuery("UPDATE `book` SET `ebayItemId`='', `ebayLastSync`='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'delOnEbay',  '',  '',  '')");
            return (true);
        } else
            return (false);
    }
    
    /**
     * @return boolean
     * @param int $idBook
     */
    function updateEbayId($idBook = -1)
    {
        // Log an update an ItemID on the book record
        if ($idBook == -1)
            $idBook = $this->idBook;
        $idBook = (int) $idBook;
        if ($this->get($idBook)) {
            $this->babase->DbQuery("UPDATE `book` SET `ebayLastSync`='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idGroup"] . "',  'updateOnEbay',  '',  '',  '')");
            return (true);
        } else
            return (false);
    }
    
    /**
     * @return array idBookPhoto, name, description, file, fileMiniature, default, sizeV, sizeH, date
     * @param int $idBook
     */
    function getPhoto($idBook)
    {
        // Return full list of all photos attached to this idBook
        $id = (int) $id;
        return ($this->babase->DbSelect("SELECT * FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook` LIKE '$idBook'", $result));
    }
    
    /**
     * @return boolean true if ok, false if error occured
     * @param int $idBook
     * @param string $description
     */
    function addPhoto($idBook, $description = "")
    {
        // Return full list of all photos attached to this idBook
        global $txt;
        $idBook      = (int) $idBook;
        $description = mysql_real_escape_string($description);
        
        $SafeFile = $_FILES['photo']['name'];
        $SafeFile = str_replace("#", "No.", $SafeFile);
        $SafeFile = str_replace("$", "Dollar", $SafeFile);
        $SafeFile = str_replace("%", "Percent", $SafeFile);
        $SafeFile = str_replace("^", "", $SafeFile);
        $SafeFile = str_replace("&", "and", $SafeFile);
        $SafeFile = str_replace("*", "", $SafeFile);
        $SafeFile = str_replace("?", "", $SafeFile);
        
        $random        = rand(1000, 9999);
        $path          = config("photo_path") . $idBook . "-" . $random . "-" . $SafeFile;
        $pathMedium    = config("photo_medium_path") . $idBook . "-" . $random . "-" . $SafeFile;
        $pathThumbnail = config("photo_thumbnail_path") . $idBook . "-" . $random . "-" . $SafeFile;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $path))
        // Move it
            {
            //IF IT HAS BEEN COPIED... 
            //GET FILE NAME 
            $fileName = $_FILES['photo']['name'];
            //GET FILE SIZE 
            $fileSize = $_FILES['photo']['size'];
            $theDiv   = $theFileSize / 1000;
            $fileSize = round($theDiv, 0);
            
            // Resize it
            if ((GenerateThumbnail($path, $pathThumbnail, config("photo_thumbnail_max_width"), config("photo_thumbnail_max_height")) == 0) AND (GenerateThumbnail($path, $pathMedium, config("photo_medium_max_width"), config("photo_medium_max_height")) == 0)) {
                // Saving and Medium/Thumbnail ok, we can save in DB
                list($width, $height, $type, $attr) = getimagesize($path);
                $idBookPhoto = $this->babase->DbInsert("INSERT INTO  `gestlibr`.`bookphoto` (`idBookPhoto` ,`idBook` ,`name` ,`description` ,`file` ,`fileThumbnail` ,`fileMedium` ,`fileSize` ,`default` ,`height` ,`width` ,`date` ,`idGroup`) VALUES (NULL ,  '$idBook',  '$fileName',  '$descrition',  '$path',  '$pathThumbnail', '$pathMedium',  '$fileSize',  '',  '$height',  '$width',  '" . time() . "',  '" . $_SESSION["idGroup"] . "');", 1);
                $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'addPhoto',  '',  '$idBookPhoto',  '')");
                return (true);
            } else {
                // Error in resizing... Image incorrect... Deleting orginal file
                @unlink($path);
                @unlink($pathThumbnail);
                @unlink($pathMedium);
                return (false);
            }
        } else
        // Impossible to copy the uploaded file
            return (false);
    }
    
    /**
     * @return boolean true if deletion is ok, false is error
     * @param int $idBookPhoto
     */
    function delPhoto($idBookPhoto)
    {
        // Delete a photo in DB and the real file
        $idBookPhoto   = (int) $idBookPhoto;
        $bookPhotoData = $this->babase->DbSelect("SELECT * FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBookPhoto` LIKE '$idBookPhoto'", $result);
        if ($result == 1) {
            @unlink($bookPhotoData[0]["file"]);
            @unlink($bookPhotoData[0]["fileThumbnail"]);
            @unlink($bookPhotoData[0]["fileMedium"]);
            $bookPhoto = $this->babase->DbQuery("DELETE FROM`bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBookPhoto` LIKE '$idBookPhoto'", $result);
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'delPhoto',  '$idBookPhoto',  '',  '')");
            return (true);
        } else
            return (false);
        
        
    }
    
    /*
     *@return array Array of data result if array[found]=true result has been found
     *@param string $isbn ISBN Number to search for 10 or 13 digits
     */
    
    function IsbnSearch($isbn)
    {
        // Cleaning ISBN
        $isbn            = str_replace("-", "", $isbn);
        $isbn            = str_replace(" ", "", $isbn);
        $isbn            = (int) $isbn;
        $result          = array();
        $result["found"] = false;
        if ($isbn >= 0) {
            switch (config("isbn_search_method")) {
                case "googlebooks":
                default:
                    // Google Books method to get ISBN
                    require_once 'lib/google-api-php-client/src/apiClient.php';
                    require_once 'lib/google-api-php-client/src/contrib/apiBooksService.php';
                    $client = new apiClient();
                    $client->setApplicationName("My_Books_API");
                    $service = new apiBooksService($client);
                    
                    $optParams = array(
                        'filter' => 'full'
                    );
                    $results   = $service->volumes->listVolumes('isbn:' . $isbn);
                    
                    if (sizeof($results['items'])) {
                        foreach ($results['items'] as $item) {
                            $result["title"] = utf8_decode($item['volumeInfo']['title']);
                            if ($item['volumeInfo']['title'] != "")
                                $result["title"] .= " - " . utf8_decode($item['volumeInfo']['subtitle']);
                            $i = 0;
                            while ($i != sizeof($item['volumeInfo']['authors'])) {
                                if ($i == 0)
                                    $result["author"] = utf8_decode($item['volumeInfo']['authors'][$i]);
                                else
                                    $result["author"] .= ", " . utf8_decode($item['volumeInfo']['authors'][$i]);
                                $i++;
                            }
                            $result["editor"]      = utf8_decode($item['volumeInfo']['publisher']);
                            $result["publishDate"] = utf8_decode($item['volumeInfo']['publishedDate']);
                            $result["pageNumber"]  = utf8_decode($item['volumeInfo']['pageCount']);
                            $result["description"] = utf8_decode($item['volumeInfo']['description']);
                            $result["infoLink"]    = utf8_decode($item['volumeInfo']['infoLink']);
                            $result["isbn"]        = $item['volumeInfo']['industryIdentifiers'][1]['identifier'];
                            $result["image"]       = $item['volumeInfo']['imageLinks']['thumbnail'];
                            $result["found"]       = true;
                            $result["raw"]         = $results['items'];
                            return ($result);
                        }
                    } else
                        return ($result);
                    break;
            }
            
        } else
            return ($result);
    }
}

?>
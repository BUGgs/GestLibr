<?php

/* Book Class
 * v 1.0
 * Romain DUCHENE
 * April 2005
 */

require_once 'vendor/autoload.php';
require_once __DIR__ . '/integration_settings.class.php';

Class Book
{
    var $defaultRow = 10000;
    var $babase;
    var $idBook = "-1";
    var $err_msg = "";
    var $searchResultNb = "0";
    var $searchResult = "";
    var $data = array();
    var $error = 0;
    var $bookPhotoSortOrderColumn = null;
    var $isbnCreatorsTokenCache = array();

    function __construct()
    {
        $this->Book();
    }
    
    function secureData()
    {
        if (!is_array($this->data))
            $this->data = array();
        if (!isset($this->data["description"]))
            $this->data["description"] = "";
        if (!isset($this->data["notes"]))
            $this->data["notes"] = "";
        // Decode request entities so SQL escaping stores canonical text/HTML.
        $this->data["description"] = html_entity_decode($this->data["description"]);
        $this->data["notes"]       = html_entity_decode($this->data["notes"]);
        foreach ($this->data as $key => $val)
        {
            if (is_array($val))
                continue;
            $this->data[$key] = mysqli_real_escape_string($this->babase->DB_CONN, (string)$val);
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
        $this->data = array();
    }
    
    function assignData($postData)
    {
        // Assign all the data to the $this->data from $postData
        if (!is_array($postData))
            $postData = array();
        if (!is_array($this->data))
            $this->data = array();
        $this->data["idBook"]                        = isset($postData["idBook"]) ? $postData["idBook"] : "";
        $this->data["id"]                            = isset($postData["id"]) ? $postData["id"] : ""; // Same as idBook, but only for search
        $this->data["author"]                        = isset($postData["author"]) ? $postData["author"] : "";
        $this->data["title"]                         = isset($postData["title"]) ? $postData["title"] : "";
        $this->data["subtitle"]                      = isset($postData["subtitle"]) ? $postData["subtitle"] : "";
        $this->data["description"]                   = isset($postData["description"]) ? $postData["description"] : "";
        $this->data["price"]                         = isset($postData["price"]) ? $postData["price"] : "";
        $this->data["price_ebay"]                    = isset($postData["price_ebay"]) ? $postData["price_ebay"] : "";
        $this->data["price_amazon"]                  = isset($postData["price_amazon"]) ? $postData["price_amazon"] : "";
        $this->data["quantity"]                      = isset($postData["quantity"]) ? $postData["quantity"] : "";
        $this->data["category"]                      = isset($postData["category"]) ? $postData["category"] : "";
        $this->data["notes"]                         = isset($postData["notes"]) ? $postData["notes"] : "";
        $this->data["quantitynotnull"]               = isset($postData["quantitynotnull"]) ? $postData["quantitynotnull"] : "";
        $this->data["internet"]                      = isset($postData["internet"]) ? $postData["internet"] : "";
        $this->data["onEbay"]                        = isset($postData["onEbay"]) ? $postData["onEbay"] : "";
        $this->data["ebayUseDescriptionTemplate"]    = isset($postData["ebayUseDescriptionTemplate"]) ? $postData["ebayUseDescriptionTemplate"] : "0";
        $this->data["ebayPolicyMode"]                = isset($postData["ebayPolicyMode"]) ? $postData["ebayPolicyMode"] : "default";
        $this->data["ebayPaymentPolicyIdOverride"]   = isset($postData["ebayPaymentPolicyIdOverride"]) ? $postData["ebayPaymentPolicyIdOverride"] : "";
        $this->data["ebayFulfillmentPolicyIdOverride"] = isset($postData["ebayFulfillmentPolicyIdOverride"]) ? $postData["ebayFulfillmentPolicyIdOverride"] : "";
        $this->data["ebayReturnPolicyIdOverride"]    = isset($postData["ebayReturnPolicyIdOverride"]) ? $postData["ebayReturnPolicyIdOverride"] : "";
        $this->data["location"]                      = isset($postData["location"]) ? $postData["location"] : "";
        $this->data["internetSearch"]                = isset($postData["internetSearch"]) ? $postData["internetSearch"] : "";
        $this->data["inCatalog"]                     = isset($postData["inCatalog"]) ? $postData["inCatalog"] : "";
        $this->data["modifyDate"]                    = isset($postData["modifyDate"]) ? $postData["modifyDate"] : "";
        $this->data["entryDate"]                     = isset($postData["entryDate"]) ? $postData["entryDate"] : "";
        $this->data["isbn"]                          = isset($postData["isbn"]) ? $postData["isbn"] : "";
        $this->data["editor"]                        = isset($postData["editor"]) ? $postData["editor"] : "";
        $this->data["collection"]                    = isset($postData["collection"]) ? $postData["collection"] : "";
        $this->data["publishDate"]                   = isset($postData["publishDate"]) ? $postData["publishDate"] : "";
        $this->data["publishLocation"]               = isset($postData["publishLocation"]) ? $postData["publishLocation"] : "";
        $this->data["format"]                        = isset($postData["format"]) ? $postData["format"] : "";
        $this->data["pageNumber"]                    = isset($postData["pageNumber"]) ? $postData["pageNumber"] : "";
        $this->data["weight_g"]                      = isset($postData["weight_g"]) ? $postData["weight_g"] : "0";
        $this->data["binding"]                       = isset($postData["binding"]) ? $postData["binding"] : "";
        $this->data["condition"]                     = isset($postData["condition"]) ? $postData["condition"] : "";
        $this->data["language"]                      = isset($postData["language"]) ? $postData["language"] : "";
        $this->data["ebayCategoryId"]                = isset($postData["ebayCategoryId"]) ? $postData["ebayCategoryId"] : "";
        $this->data["ebayCategoryIdSecondary"]       = isset($postData["ebayCategoryIdSecondary"]) ? $postData["ebayCategoryIdSecondary"] : "";
        $this->data["ebayShippingCost"]              = isset($postData["ebayShippingCost"]) ? $postData["ebayShippingCost"] : "";
        $this->data["ebayShippingCostInternational"] = isset($postData["ebayShippingCostInternational"]) ? $postData["ebayShippingCostInternational"] : "";
        $this->data["ebayItemId"]                    = isset($postData["ebayItemId"]) ? $postData["ebayItemId"] : "";
        $this->data["ebayLastSync"]                  = isset($postData["ebayLastSync"]) ? $postData["ebayLastSync"] : "";
        $this->data["photo"]                         = isset($postData["photo"]) ? $postData["photo"] : "";
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
	    // If condition / format / binding / language aren't filled, we sent empty field for the search, and not -1
            if ($this->data["condition"] == "-1")
                $this->data["condition"] = "";
            if ($this->data["format"] == "-1")
                $this->data["format"] = "";
            if ($this->data["binding"] == "-1")
                $this->data["binding"] = "";
            if ($this->data["language"] == "-1")
                $this->data["language"] = "";
            
            // We made the sql request
            $this->secureData();
            if ($this->data["category"] != "") // It's a search with a category
                $sqlSearch = "SELECT * FROM `book` as book, `bookincategory` as bookincategory  WHERE book.`idGroup`='" . $_SESSION["idGroup"] . "' AND (book.`author` LIKE '%" . $this->data["author"] . "%' AND book.`title` LIKE '%" . $this->data["title"] . "%' AND book.`subtitle` LIKE '%" . $this->data["subtitle"] . "%' AND book.`description` LIKE '%" . $this->data["description"] . "%' AND book.`price` LIKE '%" . $this->data["price"] . "%' AND book.`quantity` LIKE '%" . $this->data["quantity"] . "%' AND book.`notes` LIKE '%" . $this->data["notes"] . "%' AND book.`location` LIKE '%" . $this->data["location"] . "%' AND book.idBook=bookincategory.idBook AND bookincategory.idCategory='" . $this->data["category"] . "' AND book.`isbn` LIKE '%" . $this->data["isbn"] . "%' AND book.`editor` LIKE '%" . $this->data["editor"] . "%' AND book.`collection` LIKE '%" . $this->data["collection"] . "%' AND book.`publishDate` LIKE '%" . $this->data["publishDate"] . "%' AND book.`publishLocation` LIKE '%" . $this->data["publishLocation"] . "%' AND book.`format` LIKE '%" . $this->data["format"] . "%' AND book.`pageNumber` LIKE '%" . $this->data["pageNumber"] . "%' AND book.`condition` LIKE '%" . $this->data["condition"] . "%' AND book.`binding` LIKE '%" . $this->data["binding"] . "%' AND book.`language` LIKE '%" . $this->data["language"] . "%'  AND book.`ebayCategoryId` LIKE '%" . $this->data["ebayCategoryId"] . "%' AND book.`ebayCategoryIdSecondary` LIKE '%" . $this->data["ebayCategoryIdSecondary"] . "%' AND book.`ebayShippingCost` LIKE '%" . $this->data["ebayShippingCost"] . "%' AND book.`ebayShippingCostInternational` LIKE '%" . $this->data["ebayShippingCostInternational"] . "%')" . $sqladd;
            else // A search without category
                $sqlSearch = "SELECT * FROM `book` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND (`author` LIKE '%" . $this->data["author"] . "%' AND `title` LIKE '%" . $this->data["title"] . "%' AND `subtitle` LIKE '%" . $this->data["subtitle"] . "%' AND `description` LIKE '%" . $this->data["description"] . "%' AND `price` LIKE '%" . $this->data["price"] . "%' AND `quantity` LIKE '%" . $this->data["quantity"] . "%' AND `notes` LIKE '%" . $this->data["notes"] . "%' AND book.`location` LIKE '%" . $this->data["location"] . "%' AND book.`isbn` LIKE '%" . $this->data["isbn"] . "%' AND book.`editor` LIKE '%" . $this->data["editor"] . "%' AND book.`collection` LIKE '%" . $this->data["collection"] . "%' AND book.`publishDate` LIKE '%" . $this->data["publishDate"] . "%' AND book.`publishLocation` LIKE '%" . $this->data["publishLocation"] . "%' AND book.`format` LIKE '%" . $this->data["format"] . "%' AND book.`pageNumber` LIKE '%" . $this->data["pageNumber"] . "%' AND book.`condition` LIKE '%" . $this->data["condition"] . "%' AND book.`binding` LIKE '%" . $this->data["binding"] . "%' AND book.`language` LIKE '%" . $this->data["language"] . "%'  AND book.`ebayCategoryId` LIKE '%" . $this->data["ebayCategoryId"] . "%' AND book.`ebayCategoryIdSecondary` LIKE '%" . $this->data["ebayCategoryIdSecondary"] . "%' AND book.`ebayShippingCost` LIKE '%" . $this->data["ebayShippingCost"] . "%' AND book.`ebayShippingCostInternational` LIKE '%" . $this->data["ebayShippingCostInternational"] . "%')" . $sqladd;
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

    function normalizeSuggestionValue($value)
    {
        $value = trim((string)$value);
        if (function_exists("mb_strtolower"))
            return mb_strtolower($value, "UTF-8");
        return strtolower($value);
    }

    function suggestValues($field, $term, $limit = 10)
    {
        $allowed = array("author", "title");
        $suggestions = array();
        $seen = array();

        if (!in_array($field, $allowed, true))
            return $suggestions;

        $term = trim((string)$term);
        if ($term === "")
            return $suggestions;

        $limit = (int)$limit;
        if ($limit <= 0)
            $limit = 10;
        if ($limit > 20)
            $limit = 20;

        $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
        $escapedTerm = str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $term);
        $escapedTerm = mysqli_real_escape_string($this->babase->DB_CONN, $escapedTerm);

        $prefixPattern = $escapedTerm . "%";
        $containsPattern = "%" . $escapedTerm . "%";

        $sqlPrefix = "SELECT DISTINCT `" . $field . "` AS value FROM `book` " .
            "WHERE `idGroup`='" . $idGroup . "' AND TRIM(`" . $field . "`)<>'' " .
            "AND `" . $field . "` LIKE '" . $prefixPattern . "' ESCAPE '\\\\' " .
            "ORDER BY `" . $field . "` ASC LIMIT " . $limit;

        $rowsPrefix = $this->babase->DbSelect($sqlPrefix, $nbPrefix);
        if (!is_array($rowsPrefix))
            $rowsPrefix = array();

        $i = 0;
        while ($i < sizeof($rowsPrefix))
        {
            $value = trim((string)(isset($rowsPrefix[$i]["value"]) ? $rowsPrefix[$i]["value"] : ""));
            if ($value !== "")
            {
                $key = $this->normalizeSuggestionValue($value);
                if (!isset($seen[$key]))
                {
                    $suggestions[] = $value;
                    $seen[$key] = 1;
                }
            }
            if (sizeof($suggestions) >= $limit)
                return $suggestions;
            $i++;
        }

        $remaining = $limit - sizeof($suggestions);
        if ($remaining <= 0)
            return $suggestions;

        $sqlContains = "SELECT DISTINCT `" . $field . "` AS value FROM `book` " .
            "WHERE `idGroup`='" . $idGroup . "' AND TRIM(`" . $field . "`)<>'' " .
            "AND `" . $field . "` LIKE '" . $containsPattern . "' ESCAPE '\\\\' " .
            "AND `" . $field . "` NOT LIKE '" . $prefixPattern . "' ESCAPE '\\\\' " .
            "ORDER BY `" . $field . "` ASC LIMIT " . $remaining;

        $rowsContains = $this->babase->DbSelect($sqlContains, $nbContains);
        if (!is_array($rowsContains))
            $rowsContains = array();

        $i = 0;
        while ($i < sizeof($rowsContains))
        {
            $value = trim((string)(isset($rowsContains[$i]["value"]) ? $rowsContains[$i]["value"] : ""));
            if ($value !== "")
            {
                $key = $this->normalizeSuggestionValue($value);
                if (!isset($seen[$key]))
                {
                    $suggestions[] = $value;
                    $seen[$key] = 1;
                }
            }
            if (sizeof($suggestions) >= $limit)
                break;
            $i++;
        }

        return $suggestions;
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
            $bookData[0]["photo"] = $this->getPhoto($id);
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

    /**
     * @return array|false
     * @param string $isbn
     */
    function findByIsbnStrict($isbn)
    {
        $isbn = trim((string)$isbn);
        if ($isbn === "")
            return false;

        $isbnSafe = mysqli_real_escape_string($this->babase->DB_CONN, $isbn);
        $rows = $this->babase->DbSelect(
            "SELECT * FROM `book` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `isbn`='" . $isbnSafe . "' LIMIT 1",
            $nb
        );

        if ($nb != 1 || !is_array($rows) || !isset($rows[0]))
            return false;

        return $rows[0];
    }
    
    function add()
    {
        // Add a new record in the book database
        // $this->data : table with all books fields
        // Return the new book id or -1 if error
        $this->secureData();
        if ($this->data["internet"] != 1)
            $this->data["internet"] = 0;
        if ($this->data["ebayUseDescriptionTemplate"] != 1)
            $this->data["ebayUseDescriptionTemplate"] = 0;
        if (!isset($this->data["ebayPolicyMode"]) || ($this->data["ebayPolicyMode"] != "override" && $this->data["ebayPolicyMode"] != "default"))
            $this->data["ebayPolicyMode"] = "default";
        $this->idBook = $this->babase->DbInsert("INSERT INTO `book` (`idBook`, `author`, `title`, `subtitle`, `description`, `price`, `price_ebay`, `price_amazon`, `quantity`, `notes`, `internet`, `location`, `entryDate`, `modifyDate`, `isbn`, `onEbay`, `ebayUseDescriptionTemplate`, `ebayPolicyMode`, `ebayPaymentPolicyIdOverride`, `ebayFulfillmentPolicyIdOverride`, `ebayReturnPolicyIdOverride`, `editor`, `publishDate`, `publishLocation`, `format`, `pageNumber`, `weight_g`, `binding`, `language`, `collection`, `ebayCategoryId`, `ebayCategoryIdSecondary`, `ebayShippingCost`, `ebayShippingCostInternational`, `condition`, `idGroup`) VALUES ('', '" . $this->data["author"] . "', '" . $this->data["title"] . "', '" . $this->data["subtitle"] . "', '" . $this->data["description"] . "', '" . $this->data["price"] . "', '" . $this->data["price_ebay"] . "', '" . $this->data["price_amazon"] . "', '0', '" . $this->data["notes"] . "', '" . $this->data["internet"] . "', '" . $this->data["location"] . "', '" . time() . "', '" . time() . "', '" . $this->data["isbn"] . "', '" . $this->data["onEbay"] . "', '" . $this->data["ebayUseDescriptionTemplate"] . "', '" . $this->data["ebayPolicyMode"] . "', '" . $this->data["ebayPaymentPolicyIdOverride"] . "', '" . $this->data["ebayFulfillmentPolicyIdOverride"] . "', '" . $this->data["ebayReturnPolicyIdOverride"] . "', '" . $this->data["editor"] . "', '" . $this->data["publishDate"] . "', '" . $this->data["publishLocation"] . "', '" . $this->data["format"] . "', '" . $this->data["pageNumber"] . "', '" . $this->data["weight_g"] . "', '" . $this->data["binding"] . "', '" . $this->data["language"] . "', '" . $this->data["collection"] . "', '" . $this->data["ebayCategoryId"] . "', '" . $this->data["ebayCategoryIdSecondary"] . "', '" . $this->data["ebayShippingCost"] . "', '" . $this->data["ebayShippingCostInternational"] . "', '" . $this->data["condition"] . "', '" . $_SESSION["idGroup"] . "')", 1);
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
        if ($this->data["ebayUseDescriptionTemplate"] != 1)
            $this->data["ebayUseDescriptionTemplate"] = 0;
        if (!isset($this->data["ebayPolicyMode"]) || ($this->data["ebayPolicyMode"] != "override" && $this->data["ebayPolicyMode"] != "default"))
            $this->data["ebayPolicyMode"] = "default";
        $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '" . $this->idBook . "',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'update',  '',  '',  '')");
        return ($this->babase->DbQuery("UPDATE `book` SET author='" . $this->data["author"] . "', title='" . $this->data["title"] . "', subtitle='" . $this->data["subtitle"] . "', description='" . $this->data["description"] . "', price='" . $this->data["price"] . "', price_ebay='" . $this->data["price_ebay"] . "', price_amazon='" . $this->data["price_amazon"] . "', notes='" . $this->data["notes"] . "', internet='" . $this->data["internet"] . "', location='" . $this->data["location"] . "', modifyDate='" . time() . "', isbn='" . $this->data["isbn"] . "', onEbay='" . $this->data["onEbay"] . "', ebayUseDescriptionTemplate='" . $this->data["ebayUseDescriptionTemplate"] . "', ebayPolicyMode='" . $this->data["ebayPolicyMode"] . "', ebayPaymentPolicyIdOverride='" . $this->data["ebayPaymentPolicyIdOverride"] . "', ebayFulfillmentPolicyIdOverride='" . $this->data["ebayFulfillmentPolicyIdOverride"] . "', ebayReturnPolicyIdOverride='" . $this->data["ebayReturnPolicyIdOverride"] . "', editor='" . $this->data["editor"] . "', publishDate='" . $this->data["publishDate"] . "', publishLocation='" . $this->data["publishLocation"] . "', format='" . $this->data["format"] . "', pageNumber='" . $this->data["pageNumber"] . "', weight_g='" . $this->data["weight_g"] . "', binding='" . $this->data["binding"] . "', language='" . $this->data["language"] . "', `condition`='" . $this->data["condition"] . "', collection='" . $this->data["collection"] . "', ebayCategoryId='" . $this->data["ebayCategoryId"] . "', ebayCategoryIdSecondary='" . $this->data["ebayCategoryIdSecondary"] . "', ebayShippingCost='" . $this->data["ebayShippingCost"] . "', ebayShippingCostInternational='" . $this->data["ebayShippingCostInternational"] . "' WHERE idBook='" . $this->idBook . "'"));
    }

    /**
     * Partial update for autosave workflows.
     * @return boolean
     * @param int $idBook
     * @param array $changes
     * @param string $source
     */
    function normalizeAutosaveValue($field, $value)
    {
        $value = (string)$value;
        $flags = ENT_QUOTES;

        // Legacy data can contain nested HTML entities (&amp;#039;). Decode to plain text first.
        $decoded = $value;
        $i = 0;
        while ($i < 5)
        {
            $next = html_entity_decode($decoded, $flags, 'UTF-8');
            if ($next === $decoded)
                break;
            $decoded = $next;
            $i++;
        }

        if ($field === "description")
            return $decoded;

        // Other fields keep canonical HTML-escaped storage to preserve current behavior.
        return htmlspecialchars($decoded, $flags, 'UTF-8', false);
    }

    function updateFields($idBook, $changes, $source = "autosave")
    {
        $idBook = (int)$idBook;
        if ($idBook <= 0 || !is_array($changes) || sizeof($changes) == 0)
            return false;

        $allowed = array(
            "author", "title", "subtitle", "description", "price", "price_ebay", "price_amazon", "notes",
            "internet", "onEbay", "location", "isbn", "editor", "collection", "publishDate",
            "publishLocation", "format", "pageNumber", "weight_g", "binding", "language", "condition",
            "ebayCategoryId", "ebayCategoryIdSecondary", "ebayShippingCost", "ebayShippingCostInternational",
            "ebayUseDescriptionTemplate", "ebayPolicyMode", "ebayPaymentPolicyIdOverride", "ebayFulfillmentPolicyIdOverride", "ebayReturnPolicyIdOverride"
        );

        $prepared = array();
        foreach ($changes as $field => $value)
        {
            if (!in_array($field, $allowed, true))
                continue;
            if (is_array($value))
                continue;
            $prepared[$field] = (string)$value;
        }

        if (sizeof($prepared) == 0)
            return false;

        $setParts = array();
        foreach ($prepared as $field => $value)
        {
            $normalizedValue = $this->normalizeAutosaveValue($field, $value);
            $escapedValue = mysqli_real_escape_string($this->babase->DB_CONN, $normalizedValue);
            $setParts[] = "`" . $field . "`='" . $escapedValue . "'";
        }

        if (sizeof($setParts) == 0)
            return false;

        $now = time();
        $setParts[] = "`modifyDate`='" . $now . "'";
        $sql = "UPDATE `book` SET " . implode(", ", $setParts) . " WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook`='" . $idBook . "' LIMIT 1";
        $updated = $this->babase->DbQuery($sql);
        if (!$updated)
            return false;

        $userId = isset($_SESSION["idUser"]) ? $_SESSION["idUser"] : 0;
        $comment = mysqli_real_escape_string($this->babase->DB_CONN, $source . ":" . implode(",", array_keys($prepared)));
        $this->babase->DbInsert("INSERT INTO `bookupdate` (`idBookupdate`,`idBook`,`date`,`idUser`,`type`,`old_value`,`new_value`,`comment`) VALUES (NULL,'" . $idBook . "','" . $now . "','" . $userId . "','update','','','" . $comment . "')");
        return true;
    }

    /**
     * Touch book modifyDate and keep a trace in bookupdate.
     * @return int|false Unix timestamp or false on failure
     * @param int $idBook
     * @param string $source
     */
    function touchModifyDate($idBook, $source = "autosave:touch")
    {
        $idBook = (int)$idBook;
        if ($idBook <= 0)
            return false;

        $now = time();
        $updated = $this->babase->DbQuery(
            "UPDATE `book` SET `modifyDate`='" . $now . "' WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook`='" . $idBook . "' LIMIT 1"
        );
        if (!$updated)
            return false;

        $userId = isset($_SESSION["idUser"]) ? $_SESSION["idUser"] : 0;
        $comment = mysqli_real_escape_string($this->babase->DB_CONN, (string)$source);
        $this->babase->DbInsert(
            "INSERT INTO `bookupdate` (`idBookupdate`,`idBook`,`date`,`idUser`,`type`,`old_value`,`new_value`,`comment`) VALUES (NULL,'" . $idBook . "','" . $now . "','" . $userId . "','update','','','" . $comment . "')"
        );
        return $now;
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
        $this->touchModifyDate($idBook, "autosave:category_add");
        
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
        $this->touchModifyDate($idBook, "autosave:category_del");
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
    function getLanguageHtmlForm($selected = -1, $autosaveField = "")
    {
        // Return a ready to use HTML SELECT form with all language + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `language` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `languageName`", $nb);
        $autosaveAttr = ($autosaveField != "") ? " data-autosave-field='" . $autosaveField . "'" : "";
        $html = "<SELECT NAME='language'" . $autosaveAttr . "><OPTION VALUE='-1'></OPTION>";
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
    function getBindingHtmlForm($selected = -1, $autosaveField = "")
    {
        // Return a ready to use HTML SELECT form with all binding + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `binding` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $autosaveAttr = ($autosaveField != "") ? " data-autosave-field='" . $autosaveField . "'" : "";
        $html = "<SELECT NAME='binding'" . $autosaveAttr . "><OPTION VALUE='-1'></OPTION>";
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
    function getConditionHtmlForm($selected = -1, $autosaveField = "")
    {
        // Return a ready to use HTML SELECT form with all conditions + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `condition` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $autosaveAttr = ($autosaveField != "") ? " data-autosave-field='" . $autosaveField . "'" : "";
        $html = "<SELECT NAME='condition'" . $autosaveAttr . "><OPTION VALUE='-1'></OPTION>";
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
    function getFormatHtmlForm($selected = -1, $autosaveField = "")
    {
        // Return a ready to use HTML SELECT form with all format + the selected one
        $sql  = $this->babase->DbSelect("SELECT * FROM `format` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `order`", $nb);
        $autosaveAttr = ($autosaveField != "") ? " data-autosave-field='" . $autosaveField . "'" : "";
        $html = "<SELECT NAME='format'" . $autosaveAttr . "><OPTION VALUE='-1'></OPTION>";
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
    function getEbayCategoryHtmlForm($type, $selected = -1, $autosaveField = "")
    {
        // Return a ready to use HTML SELECT form with all ebay category (primary 1 or secondary 2) + the selected one
        $sql = $this->babase->DbSelect("SELECT * FROM `ebaycategory` WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER by `categoryName`", $nb);
        $autosaveAttr = ($autosaveField != "") ? " data-autosave-field='" . $autosaveField . "'" : "";
        if ($type == 1)
            $html = "<SELECT NAME='ebayCategoryId'" . $autosaveAttr . "><OPTION VALUE=''></OPTION>";
        else
            $html = "<SELECT NAME='ebayCategoryIdSecondary'" . $autosaveAttr . "><OPTION VALUE=''></OPTION>";
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
        if ($this->get($idBook)) {
            $this->babase->DbQuery("UPDATE `book` SET `ebayItemId`='$idItemEbay', `ebayLastSync`='" . time() . "', `onEbay`='1' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
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
            $this->babase->DbQuery("UPDATE `book` SET `ebayItemId`='', `ebayLastSync`='0', `onEbay`='0' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
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
            $this->babase->DbQuery("UPDATE `book` SET `ebayLastSync`='" . time() . "', `onEbay`='1' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `idBook`='$idBook'");
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
        $idBook = (int) $idBook;
        if ($this->hasBookPhotoSortOrderColumn())
            $sql = "SELECT * FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook` LIKE '$idBook' ORDER BY `sortOrder` ASC, `idBookPhoto` ASC";
        else
            $sql = "SELECT * FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook` LIKE '$idBook' ORDER BY `date` ASC, `idBookPhoto` ASC";

        $rows = $this->babase->DbSelect($sql, $result);
        if (($result <= 0) || !is_array($rows))
            return array();
        return $rows;
    }

    /**
     * @return array|false
     * @param int $idBookPhoto
     */
    function getPhotoById($idBookPhoto)
    {
        $idBookPhoto = (int) $idBookPhoto;
        $rows = $this->babase->DbSelect("SELECT * FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBookPhoto` LIKE '$idBookPhoto' LIMIT 1", $result);
        if (($result != 1) || !is_array($rows) || !isset($rows[0]))
            return false;
        return $rows[0];
    }
    
    /**
     * @return boolean true if ok, false if error occured
     * @param int $idBook
     * @param string $description
     */
    function addPhoto($idBook, $description = "")
    {
        $result = $this->addPhotosBulk($idBook, isset($_FILES['photo']) ? $_FILES['photo'] : array(), $description);
        return (!empty($result["added"]) && (int)$result["added"] > 0);
    }

    /**
     * @return array
     * @param int $idBook
     * @param array $files
     * @param string $description
     */
    function addPhotosBulk($idBook, $files, $description = "")
    {
        $idBook = (int) $idBook;
        $normalizedFiles = $this->normalizeUploadedFiles($files);
        $result = array(
            "added" => 0,
            "errors" => array(),
            "photoIds" => array(),
        );

        if ($idBook <= 0)
        {
            $result["errors"][] = "ID ouvrage invalide.";
            return $result;
        }

        $countFiles = sizeof($normalizedFiles);
        if ($countFiles == 0)
        {
            $result["errors"][] = "Aucun fichier uploadé.";
            return $result;
        }

        if ($countFiles > 20)
        {
            $result["errors"][] = "Vous ne pouvez pas importer plus de 20 photos d'un coup.";
            return $result;
        }

        $i = 0;
        while ($i < $countFiles)
        {
            $current = $normalizedFiles[$i];
            $add = $this->addPhotoFromFileArray($idBook, $current, $description);
            if ($add["ok"])
            {
                $result["added"]++;
                $result["photoIds"][] = $add["idBookPhoto"];
            }
            else
            {
                $result["errors"][] = $add["error"];
            }
            $i++;
        }

        return $result;
    }
    
    /**
     * @return boolean true if deletion is ok, false is error
     * @param int $idBookPhoto
     */
    function delPhoto($idBookPhoto)
    {
        // Delete a photo in DB and the real file
        $idBookPhoto = (int) $idBookPhoto;
        $bookPhotoData = $this->getPhotoById($idBookPhoto);
        if ($bookPhotoData !== false) {
            @unlink($bookPhotoData["file"]);
            @unlink($bookPhotoData["fileThumbnail"]);
            @unlink($bookPhotoData["fileMedium"]);
            $this->babase->DbQuery("DELETE FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBookPhoto` LIKE '$idBookPhoto'");
            $idBook = (int)$bookPhotoData["idBook"];
            $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'delPhoto',  '$idBookPhoto',  '',  '')");
            $this->normalizePhotoOrder($idBook);
            $this->touchModifyDate($idBook, "autosave:photo_delete");
            return (true);
        } else
            return (false);
    }

    /**
     * @return boolean
     * @param int $idBookPhoto
     */
    function deletePhoto($idBookPhoto)
    {
        return $this->delPhoto($idBookPhoto);
    }
    
    /**
     * @return boolean true if rotation is ok, false is error
     * @param int $idBookPhoto, string $rotation (left ou right)
     */
    function rotatePhoto($idBookPhoto, $rotation)
    {
        // Rotate a photo in DB and the real file
        $idBookPhoto = (int) $idBookPhoto;
        if ($rotation == "right")
            $angle = 270;
        else
            $angle = 90;
        $bookPhotoData = $this->getPhotoById($idBookPhoto);
        if ($bookPhotoData !== false) {
            @unlink($bookPhotoData["fileThumbnail"]);
            @unlink($bookPhotoData["fileMedium"]);
            
            // Rotating image
            list($width_orig, $height_orig, $image_type) = @getimagesize($bookPhotoData["file"]);
            if(!$width_orig)
                return false;
            switch($image_type)
            {
                case 1: $src_im = @imagecreatefromgif($bookPhotoData["file"]);    break;
                case 2: $src_im = @imagecreatefromjpeg($bookPhotoData["file"]);   break;
                case 3: $src_im = @imagecreatefrompng($bookPhotoData["file"]);    break;
                default: return false;
            }
            if(!$src_im)
                return false;
            $dst_im = imagerotate($src_im,$angle,0);
            switch($image_type)
            {
                case 1: @imagegif($dst_im,$bookPhotoData["file"]);    break;
                case 2: @imagejpeg($dst_im,$bookPhotoData["file"]);   break;
                case 3: @imagepng($dst_im,$bookPhotoData["file"]);    break;
            }
            imagedestroy($dst_im);
            imagedestroy($src_im);
            
            // Regerating new miniatures
            if ((GenerateThumbnail($bookPhotoData["file"], $bookPhotoData["fileThumbnail"], config("photo_thumbnail_max_width"), config("photo_thumbnail_max_height")) == 0) AND (GenerateThumbnail($bookPhotoData["file"], $bookPhotoData["fileMedium"], config("photo_medium_max_width"), config("photo_medium_max_height")) == 0))
            {
                // Saving and Medium/Thumbnail ok, we can save in DB
                list($width, $height, $type, $attr) = getimagesize($bookPhotoData["file"]);
                $this->babase->DbQuery("UPDATE `bookphoto` SET `height`='" . ((int)$height) . "' ,`width`='" . ((int)$width) . "' WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBookPhoto` LIKE '$idBookPhoto'");
                $idBook = (int)$bookPhotoData["idBook"];
                $this->babase->DbInsert("INSERT INTO  `bookupdate` (`idBookupdate` ,`idBook` ,`date` ,`idUser` ,`type` ,
`old_value` ,`new_value` ,`comment`) VALUES (NULL ,  '$idBook',  '" . time() . "',  '" . $_SESSION["idUser"] . "',  'rotatePhoto',  '$idBookPhoto',  '',  '')");
                $this->touchModifyDate($idBook, "autosave:photo_rotate");
                return (true);
            }
            else return (false);
        } else
            return (false);
    }

    /**
     * @return boolean
     * @param int $idBookPhoto
     * @param string $direction
     */
    function rotatePhotoByDirection($idBookPhoto, $direction)
    {
        $direction = ($direction == "right") ? "right" : "left";
        return $this->rotatePhoto($idBookPhoto, $direction);
    }

    /**
     * @return boolean
     * @param int $idBook
     * @param array $orderedIds
     */
    function reorderPhotos($idBook, $orderedIds)
    {
        $idBook = (int)$idBook;
        if ($idBook <= 0)
            return false;

        if (!$this->hasBookPhotoSortOrderColumn())
            return false;

        $photos = $this->getPhoto($idBook);
        if (!is_array($photos))
            return false;

        $allowed = array();
        $i = 0;
        while ($i < sizeof($photos))
        {
            $allowed[(int)$photos[$i]["idBookPhoto"]] = true;
            $i++;
        }

        $seen = array();
        $ordered = array();
        if (!is_array($orderedIds))
            $orderedIds = array();

        $i = 0;
        while ($i < sizeof($orderedIds))
        {
            $pid = (int)$orderedIds[$i];
            if (($pid > 0) && isset($allowed[$pid]) && !isset($seen[$pid]))
            {
                $ordered[] = $pid;
                $seen[$pid] = true;
            }
            $i++;
        }

        $i = 0;
        while ($i < sizeof($photos))
        {
            $pid = (int)$photos[$i]["idBookPhoto"];
            if (!isset($seen[$pid]))
                $ordered[] = $pid;
            $i++;
        }

        $position = 1;
        $i = 0;
        while ($i < sizeof($ordered))
        {
            $pid = (int)$ordered[$i];
            $this->babase->DbQuery("UPDATE `bookphoto` SET `sortOrder`='" . $position . "' WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook`='" . $idBook . "' AND `idBookPhoto`='" . $pid . "' LIMIT 1");
            $position++;
            $i++;
        }
        $this->touchModifyDate($idBook, "autosave:photo_reorder");
        return true;
    }

    function hasBookPhotoSortOrderColumn()
    {
        if ($this->bookPhotoSortOrderColumn !== null)
            return $this->bookPhotoSortOrderColumn;

        $rows = $this->babase->DbSelect("SHOW COLUMNS FROM `bookphoto` LIKE 'sortOrder'", $nb);
        $this->bookPhotoSortOrderColumn = ($nb == 1);
        return $this->bookPhotoSortOrderColumn;
    }

    function normalizeUploadedFiles($files)
    {
        $normalized = array();
        if (!is_array($files) || !isset($files["name"]))
            return $normalized;

        if (is_array($files["name"]))
        {
            $i = 0;
            $total = sizeof($files["name"]);
            while ($i < $total)
            {
                $normalized[] = array(
                    "name" => isset($files["name"][$i]) ? $files["name"][$i] : "",
                    "type" => isset($files["type"][$i]) ? $files["type"][$i] : "",
                    "tmp_name" => isset($files["tmp_name"][$i]) ? $files["tmp_name"][$i] : "",
                    "error" => isset($files["error"][$i]) ? $files["error"][$i] : UPLOAD_ERR_NO_FILE,
                    "size" => isset($files["size"][$i]) ? $files["size"][$i] : 0,
                );
                $i++;
            }
            return $normalized;
        }

        $normalized[] = $files;
        return $normalized;
    }

    function addPhotoFromFileArray($idBook, $file, $description)
    {
        $idBook = (int)$idBook;
        $maxBytes = 10 * 1024 * 1024;

        if (!is_array($file))
            return array("ok" => false, "error" => "Fichier invalide.");

        if (!isset($file["error"]) || (int)$file["error"] !== UPLOAD_ERR_OK)
        {
            if (isset($file["error"]) && (int)$file["error"] === UPLOAD_ERR_NO_FILE)
                return array("ok" => false, "error" => "Aucun fichier reçu.");
            return array("ok" => false, "error" => "Upload impossible (code " . ((int)$file["error"]) . ").");
        }

        $fileName = isset($file["name"]) ? (string)$file["name"] : "";
        $tmpName = isset($file["tmp_name"]) ? (string)$file["tmp_name"] : "";
        $fileSizeBytes = isset($file["size"]) ? (int)$file["size"] : 0;

        if ($tmpName === "" || !is_uploaded_file($tmpName))
            return array("ok" => false, "error" => "Fichier temporaire introuvable pour " . $fileName . ".");

        if ($fileSizeBytes > $maxBytes)
            return array("ok" => false, "error" => "Le fichier " . $fileName . " dépasse 10 Mo.");

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, array("jpg", "jpeg", "png", "gif"), true))
            return array("ok" => false, "error" => "Format non autorisé pour " . $fileName . ".");

        $safeFile = basename($fileName);
        $safeFile = str_replace("#", "No.", $safeFile);
        $safeFile = str_replace("$", "Dollar", $safeFile);
        $safeFile = str_replace("%", "Percent", $safeFile);
        $safeFile = str_replace("^", "", $safeFile);
        $safeFile = str_replace("&", "and", $safeFile);
        $safeFile = str_replace("*", "", $safeFile);
        $safeFile = str_replace("?", "", $safeFile);
        $safeFile = preg_replace("/[^A-Za-z0-9._-]/", "_", $safeFile);
        if ($safeFile === "")
            $safeFile = "photo." . $extension;

        $random = rand(1000, 9999);
        $path = config("photo_path") . $idBook . "-" . $random . "-" . $safeFile;
        $pathMedium = config("photo_medium_path") . $idBook . "-" . $random . "-" . $safeFile;
        $pathThumbnail = config("photo_thumbnail_path") . $idBook . "-" . $random . "-" . $safeFile;

        @mkdir(dirname($path), 0777, true);
        @mkdir(dirname($pathMedium), 0777, true);
        @mkdir(dirname($pathThumbnail), 0777, true);

        if (!move_uploaded_file($tmpName, $path))
            return array("ok" => false, "error" => "Impossible de copier " . $fileName . ".");

        $imageInfo = @getimagesize($path);
        if (!$imageInfo)
        {
            @unlink($path);
            return array("ok" => false, "error" => "Le fichier " . $fileName . " n'est pas une image valide.");
        }

        if ((GenerateThumbnail($path, $pathThumbnail, config("photo_thumbnail_max_width"), config("photo_thumbnail_max_height")) != 0) || (GenerateThumbnail($path, $pathMedium, config("photo_medium_max_width"), config("photo_medium_max_height")) != 0))
        {
            @unlink($path);
            @unlink($pathThumbnail);
            @unlink($pathMedium);
            return array("ok" => false, "error" => "Erreur de génération des miniatures pour " . $fileName . ".");
        }

        $width = isset($imageInfo[0]) ? (int)$imageInfo[0] : 0;
        $height = isset($imageInfo[1]) ? (int)$imageInfo[1] : 0;
        $fileSize = (int)round($fileSizeBytes / 1000);
        $sortOrder = $this->nextPhotoSortOrder($idBook);

        $escapedName = mysqli_real_escape_string($this->babase->DB_CONN, $fileName);
        $escapedDescription = mysqli_real_escape_string($this->babase->DB_CONN, $description);
        $escapedPath = mysqli_real_escape_string($this->babase->DB_CONN, $path);
        $escapedThumb = mysqli_real_escape_string($this->babase->DB_CONN, $pathThumbnail);
        $escapedMedium = mysqli_real_escape_string($this->babase->DB_CONN, $pathMedium);

        if ($this->hasBookPhotoSortOrderColumn())
            $sqlInsert = "INSERT INTO `bookphoto` (`idBookPhoto`,`idBook`,`name`,`description`,`file`,`fileThumbnail`,`fileMedium`,`fileSize`,`default`,`height`,`width`,`date`,`idGroup`,`sortOrder`) VALUES (NULL,'" . $idBook . "','" . $escapedName . "','" . $escapedDescription . "','" . $escapedPath . "','" . $escapedThumb . "','" . $escapedMedium . "','" . $fileSize . "','','" . $height . "','" . $width . "','" . time() . "','" . $_SESSION["idGroup"] . "','" . $sortOrder . "')";
        else
            $sqlInsert = "INSERT INTO `bookphoto` (`idBookPhoto`,`idBook`,`name`,`description`,`file`,`fileThumbnail`,`fileMedium`,`fileSize`,`default`,`height`,`width`,`date`,`idGroup`) VALUES (NULL,'" . $idBook . "','" . $escapedName . "','" . $escapedDescription . "','" . $escapedPath . "','" . $escapedThumb . "','" . $escapedMedium . "','" . $fileSize . "','','" . $height . "','" . $width . "','" . time() . "','" . $_SESSION["idGroup"] . "')";

        $idBookPhoto = $this->babase->DbInsert($sqlInsert, 1);
        if (!$idBookPhoto)
        {
            @unlink($path);
            @unlink($pathThumbnail);
            @unlink($pathMedium);
            return array("ok" => false, "error" => "Erreur SQL à l'ajout de " . $fileName . ".");
        }

        $this->babase->DbInsert("INSERT INTO `bookupdate` (`idBookupdate`,`idBook`,`date`,`idUser`,`type`,`old_value`,`new_value`,`comment`) VALUES (NULL,'" . $idBook . "','" . time() . "','" . $_SESSION["idUser"] . "','addPhoto','','" . $idBookPhoto . "','')");
        $this->touchModifyDate($idBook, "autosave:photo_add");
        return array("ok" => true, "idBookPhoto" => $idBookPhoto);
    }

    function nextPhotoSortOrder($idBook)
    {
        $idBook = (int)$idBook;
        if (!$this->hasBookPhotoSortOrderColumn())
            return 0;

        $sql = $this->babase->DbSelect("SELECT MAX(`sortOrder`) AS maxSortOrder FROM `bookphoto` WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook`='" . $idBook . "'", $nb);
        if ($nb != 1 || !is_array($sql) || !isset($sql[0]["maxSortOrder"]))
            return 1;

        return ((int)$sql[0]["maxSortOrder"]) + 1;
    }

    function normalizePhotoOrder($idBook)
    {
        $idBook = (int)$idBook;
        if ($idBook <= 0 || !$this->hasBookPhotoSortOrderColumn())
            return false;

        $photos = $this->getPhoto($idBook);
        if (!is_array($photos))
            return false;

        $position = 1;
        $i = 0;
        while ($i < sizeof($photos))
        {
            $pid = (int)$photos[$i]["idBookPhoto"];
            $this->babase->DbQuery("UPDATE `bookphoto` SET `sortOrder`='" . $position . "' WHERE `idGroup`='" . $_SESSION["idGroup"] . "' AND `idBook`='" . $idBook . "' AND `idBookPhoto`='" . $pid . "' LIMIT 1");
            $position++;
            $i++;
        }
        return true;
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
        $isbn            = strtoupper(trim((string)$isbn));
//        $isbn            = (int) $isbn;
        $result          = array();
        $result["found"] = false;
        $result["debug"] = array();
        $configuredMethod = strtolower(trim((string)config("isbn_search_method")));
        if ($configuredMethod == "")
            $configuredMethod = "amazon";
        if (($configuredMethod != "amazon") && ($configuredMethod != "googlebooks") && ($configuredMethod != "googlebooks-old") && ($configuredMethod != "openlibrary"))
        {
            $result["debug"][] = "Méthode ISBN inconnue dans config(isbn_search_method): " . $configuredMethod . ". Fallback amazon.";
            $configuredMethod = "amazon";
        }
        $result["method"] = $configuredMethod;
        if ($isbn != "") {
            switch ($result["method"]) {
                case "googlebooks-old":
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
                    
                    if (is_array($results) && isset($results['items']) && is_array($results['items']) && sizeof($results['items'])) {
                        foreach ($results['items'] as $item) {
                            $result["title"] = utf8_decode($item['volumeInfo']['title']);
                            $result["subtitle"] = "";
                            if (isset($item['volumeInfo']['subtitle']))
                                $result["subtitle"] = utf8_decode($item['volumeInfo']['subtitle']);
                            if ($item['volumeInfo']['title'] != "" && $result["subtitle"] != "")
                                $result["title"] .= " - " . $result["subtitle"];
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
                case "googlebooks":
                    // Google Books method to get ISBN
                    $client = new Google_Client();
                    $client->setApplicationName("GestLibr");
                    $googleBooksApiKey = trim((string)getenv("GOOGLE_BOOKS_API_KEY"));
                    if (($googleBooksApiKey == "") && function_exists("config"))
                        $googleBooksApiKey = trim((string)config("google_books_api_key"));
                    if ($googleBooksApiKey != "")
                        $client->setDeveloperKey($googleBooksApiKey);
                    $service = new Google_Service_Books($client);
                    $optParams = array('filter' => 'full');
                    $results = $service->volumes->listVolumes(''.$isbn,$optParams);
                    
                    if (is_array($results) && isset($results['items']) && is_array($results['items']) && sizeof($results['items'])) {
                        foreach ($results['items'] as $item) {
                            $result["title"] = utf8_decode($item['volumeInfo']['title']);
                            $result["subtitle"] = "";
                            if (isset($item['volumeInfo']['subtitle']))
                                $result["subtitle"] = utf8_decode($item['volumeInfo']['subtitle']);
                            if ($item['volumeInfo']['title'] != "" && $result["subtitle"] != "")
                                $result["title"] .= " - " . $result["subtitle"];
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
                case "openlibrary":
                    $openLibraryResult = $this->isbnSearchOpenLibrary($isbn);
                    $openLibraryResult["method"] = "openlibrary";
                    return ($openLibraryResult);
                case "amazon":
                default:
                    return $this->isbnSearchAmazonCreators($isbn, "amazon");
            }
            
        } else
            return ($result);
    }

    function isbnConfigOrEnv($envName, $configName, $defaultValue = "")
    {
        $envValue = getenv($envName);
        if (($envValue !== false) && (trim((string)$envValue) != ""))
            return trim((string)$envValue);

        $cfgValue = config($configName);
        if (($cfgValue !== false) && (trim((string)$cfgValue) != ""))
            return trim((string)$cfgValue);

        return $defaultValue;
    }

    function isbnAmazonSetting($settingKey, $defaultValue = "")
    {
        $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
        if ($idGroup <= 0)
            return $defaultValue;

        $value = IntegrationGroupSettings::getGroupSetting($idGroup, "amazon_creators", (string)$settingKey, "");
        $value = trim((string)$value);
        if ($value === "")
            return $defaultValue;

        return $value;
    }

    function isbnNormalizeIdentifier($value)
    {
        $value = strtoupper(trim((string)$value));
        return preg_replace('/[^0-9A-Z]/', '', $value);
    }

    function isbnArrayValue($data, $path, $defaultValue = "")
    {
        if (!is_array($path))
            return $defaultValue;
        $cursor = $data;
        $i = 0;
        while ($i < sizeof($path))
        {
            $key = $path[$i];
            if (!is_array($cursor) || !array_key_exists($key, $cursor))
                return $defaultValue;
            $cursor = $cursor[$key];
            $i++;
        }
        return $cursor;
    }

    function isbnToUtf8($value)
    {
        if ($value === null)
            return "";
        if (is_array($value))
            return "";
        $value = trim((string)$value);
        if ($value == "")
            return "";
        return convert_to($value, "UTF-8");
    }

    function isbnCreatorsVersionFamily($version)
    {
        $version = trim((string)$version);
        if (substr($version, 0, 2) === "2.")
            return "v2";
        if (substr($version, 0, 2) === "3.")
            return "v3";
        return "";
    }

    function isbnCreatorsTokenEndpointForVersion($version)
    {
        $version = trim((string)$version);
        switch ($version)
        {
            case "2.1":
                return "https://creatorsapi.auth.us-east-1.amazoncognito.com/oauth2/token";
            case "2.2":
                return "https://creatorsapi.auth.eu-south-2.amazoncognito.com/oauth2/token";
            case "2.3":
                return "https://creatorsapi.auth.us-west-2.amazoncognito.com/oauth2/token";
            case "3.1":
                return "https://api.amazon.com/auth/o2/token";
            case "3.2":
                return "https://api.amazon.co.uk/auth/o2/token";
            case "3.3":
                return "https://api.amazon.co.jp/auth/o2/token";
        }
        return "";
    }

    function isbnCreatorsScopeForVersion($version)
    {
        if ($this->isbnCreatorsVersionFamily($version) === "v3")
            return "creatorsapi::default";
        return "creatorsapi/default";
    }

    function isbnCreatorsTokenCacheKey($credentialId, $tokenEndpoint, $version)
    {
        return sha1($credentialId . "|" . $tokenEndpoint . "|" . $version);
    }

    function isbnCreatorsFetchAccessToken($credentialId, $credentialSecret, $version, $tokenEndpoint, $scope, &$errorMessage)
    {
        $errorMessage = "";
        if (!function_exists('curl_init'))
        {
            $errorMessage = "Amazon Creators API: extension CURL absente.";
            return false;
        }

        $cacheKey = $this->isbnCreatorsTokenCacheKey($credentialId, $tokenEndpoint, $version);
        $now = time();
        if (isset($this->isbnCreatorsTokenCache[$cacheKey]) && is_array($this->isbnCreatorsTokenCache[$cacheKey]))
        {
            $cached = $this->isbnCreatorsTokenCache[$cacheKey];
            if (isset($cached["token"]) && isset($cached["expiresAt"]) && ((int)$cached["expiresAt"] > ($now + 45)))
                return (string)$cached["token"];
        }

        $versionFamily = $this->isbnCreatorsVersionFamily($version);
        if ($versionFamily === "")
        {
            $errorMessage = "Amazon Creators API: version d'identifiant invalide (" . $version . ").";
            return false;
        }

        $headers = array();
        $payload = "";
        if ($versionFamily === "v3")
        {
            $headers[] = "Content-Type: application/json";
            $tokenPayload = array(
                "grant_type" => "client_credentials",
                "client_id" => $credentialId,
                "client_secret" => $credentialSecret,
                "scope" => $scope
            );
            $payload = json_encode($tokenPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false)
            {
                $errorMessage = "Amazon Creators API: payload token v3 invalide.";
                return false;
            }
        }
        else
        {
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
            $payload = http_build_query(array(
                "grant_type" => "client_credentials",
                "client_id" => $credentialId,
                "client_secret" => $credentialSecret,
                "scope" => $scope
            ), '', '&');
        }

        $ch = curl_init($tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $responseBody = curl_exec($ch);
        if ($responseBody === false)
        {
            $errorMessage = "Amazon Creators API token: " . curl_error($ch);
            curl_close($ch);
            return false;
        }
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($responseBody, true);
        if (!is_array($response))
        {
            $errorMessage = "Amazon Creators API token: réponse JSON invalide (HTTP " . $httpCode . ").";
            return false;
        }

        if ($httpCode >= 400)
        {
            $errorDesc = "";
            if (isset($response["error_description"]))
                $errorDesc = (string)$response["error_description"];
            elseif (isset($response["message"]))
                $errorDesc = (string)$response["message"];
            $errorCode = (isset($response["error"]) ? (string)$response["error"] : "http_" . $httpCode);
            $errorMessage = "Amazon Creators API token [" . $errorCode . "]: " . $errorDesc;
            return false;
        }

        $token = (isset($response["access_token"]) ? trim((string)$response["access_token"]) : "");
        if ($token === "")
        {
            $errorMessage = "Amazon Creators API token: access_token absent.";
            return false;
        }

        $expiresIn = (isset($response["expires_in"]) ? (int)$response["expires_in"] : 3600);
        if ($expiresIn <= 0)
            $expiresIn = 3600;
        $this->isbnCreatorsTokenCache[$cacheKey] = array(
            "token" => $token,
            "expiresAt" => ($now + max(60, $expiresIn - 60))
        );
        return $token;
    }

    function isbnCreatorsRequest($baseUrl, $path, $payload, $accessToken, $version, $marketplace, &$httpCode, &$errorMessage)
    {
        $httpCode = 0;
        $errorMessage = "";
        if (!function_exists('curl_init'))
        {
            $errorMessage = "Amazon Creators API: extension CURL absente.";
            return false;
        }
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false)
        {
            $errorMessage = "Amazon Creators API: payload JSON invalide.";
            return false;
        }

        $baseUrl = trim((string)$baseUrl);
        if ($baseUrl === "")
            $baseUrl = "https://creatorsapi.amazon";
        $baseUrl = rtrim($baseUrl, "/");
        if ((stripos($baseUrl, "http://") !== 0) && (stripos($baseUrl, "https://") !== 0))
            $baseUrl = "https://" . $baseUrl;
        $url = $baseUrl . $path;

        $authHeader = "Authorization: Bearer " . $accessToken;
        if ($this->isbnCreatorsVersionFamily($version) === "v2")
            $authHeader .= ", Version " . $version;

        $headers = array(
            $authHeader,
            "Content-Type: application/json",
            "x-marketplace: " . $marketplace
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $responseBody = curl_exec($ch);
        if ($responseBody === false)
        {
            $errorMessage = "Amazon Creators API: " . curl_error($ch);
            curl_close($ch);
            return false;
        }
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($responseBody, true);
        if (!is_array($response))
        {
            $errorMessage = "Amazon Creators API: réponse JSON invalide (HTTP " . $httpCode . ").";
            return false;
        }

        if ($httpCode >= 400)
        {
            $type = (isset($response["type"]) ? (string)$response["type"] : "HTTP_" . $httpCode);
            $reason = (isset($response["reason"]) ? (string)$response["reason"] : "");
            $message = (isset($response["message"]) ? (string)$response["message"] : "");
            $suffix = ($reason != "" ? " / " . $reason : "");
            $errorMessage = "Amazon Creators API [" . $type . $suffix . "]: " . $message;
            return false;
        }

        return $response;
    }

    function isbnAmazonSelectBestItem($items, $isbn)
    {
        if (!is_array($items) || sizeof($items) == 0)
            return false;

        $normalizedIsbn = $this->isbnNormalizeIdentifier($isbn);
        $i = 0;
        while ($i < sizeof($items))
        {
            $item = $items[$i];
            $isbnValues = $this->isbnArrayValue($item, array("itemInfo", "externalIds", "isbns", "displayValues"), array());
            if (is_array($isbnValues))
            {
                $j = 0;
                while ($j < sizeof($isbnValues))
                {
                    if ($this->isbnNormalizeIdentifier($isbnValues[$j]) == $normalizedIsbn)
                        return $item;
                    $j++;
                }
            }
            $i++;
        }

        return $items[0];
    }

    function isbnAmazonBuildDescription($item)
    {
        $parts = array();

        $featureValues = $this->isbnArrayValue($item, array("itemInfo", "features", "displayValues"), array());
        if (is_array($featureValues))
        {
            $i = 0;
            while ($i < sizeof($featureValues))
            {
                $line = trim((string)$featureValues[$i]);
                if ($line != "")
                    $parts[] = $line;
                $i++;
            }
        }

        if (sizeof($parts) == 0)
            return "";

        return implode("\n", array_unique($parts));
    }

    function isbnSearchAmazonCreators($isbn, $methodLabel = "amazon")
    {
        $result = array(
            "found" => false,
            "method" => $methodLabel,
            "debug" => array()
        );

        $credentialId = $this->isbnAmazonSetting("credential_id", "");
        $credentialSecret = $this->isbnAmazonSetting("credential_secret", "");
        $credentialVersion = $this->isbnAmazonSetting("version", "2.2");
        $partnerTag = $this->isbnAmazonSetting("partner_tag", "");
        $marketplace = $this->isbnAmazonSetting("marketplace", "www.amazon.fr");
        $language = $this->isbnAmazonSetting("language", "fr_FR");
        $baseUrl = $this->isbnAmazonSetting("base_url", "https://creatorsapi.amazon");
        $tokenEndpoint = $this->isbnAmazonSetting("token_endpoint", "");
        $scope = $this->isbnAmazonSetting("scope", "");

        $versionFamily = $this->isbnCreatorsVersionFamily($credentialVersion);
        if ($tokenEndpoint === "")
            $tokenEndpoint = $this->isbnCreatorsTokenEndpointForVersion($credentialVersion);
        if ($scope === "")
            $scope = $this->isbnCreatorsScopeForVersion($credentialVersion);

        if (($credentialId == "") || ($credentialSecret == "") || ($partnerTag == ""))
        {
            $result["debug"][] = "Amazon Creators API non configurée (credential id / secret / partner tag manquants).";
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }
        if (($versionFamily === "") || ($tokenEndpoint === ""))
        {
            $result["debug"][] = "Amazon Creators API: version ou token endpoint invalide.";
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        $tokenError = "";
        $accessToken = $this->isbnCreatorsFetchAccessToken($credentialId, $credentialSecret, $credentialVersion, $tokenEndpoint, $scope, $tokenError);
        if ($accessToken === false)
        {
            $result["debug"][] = $tokenError;
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        $payload = array(
            "keywords" => $isbn,
            "searchIndex" => "Books",
            "itemCount" => 10,
            "partnerTag" => $partnerTag,
            "marketplace" => $marketplace,
            "languagesOfPreference" => array($language),
            "resources" => array(
                "images.primary.large",
                "images.primary.medium",
                "images.primary.small",
                "images.variants.large",
                "itemInfo.byLineInfo",
                "itemInfo.contentInfo",
                "itemInfo.externalIds",
                "itemInfo.features",
                "itemInfo.manufactureInfo",
                "itemInfo.productInfo",
                "itemInfo.title"
            )
        );

        $httpCode = 0;
        $errorMessage = "";
        $response = $this->isbnCreatorsRequest(
            $baseUrl,
            "/catalog/v1/searchItems",
            $payload,
            $accessToken,
            $credentialVersion,
            $marketplace,
            $httpCode,
            $errorMessage
        );

        if ($response === false)
        {
            $result["debug"][] = $errorMessage;
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        if (isset($response["errors"]) && is_array($response["errors"]) && sizeof($response["errors"]) > 0)
        {
            $i = 0;
            while ($i < sizeof($response["errors"]))
            {
                $errType = (string)$this->isbnArrayValue($response["errors"][$i], array("type"), "");
                $errReason = (string)$this->isbnArrayValue($response["errors"][$i], array("reason"), "");
                $errMsg = (string)$this->isbnArrayValue($response["errors"][$i], array("message"), "");
                $label = trim($errType . (($errReason != "") ? "/" . $errReason : ""));
                $result["debug"][] = "Amazon Creators API [" . $label . "]: " . $errMsg;
                $i++;
            }
        }

        $items = $this->isbnArrayValue($response, array("searchResult", "items"), array());
        if (!is_array($items) || sizeof($items) == 0)
        {
            $result["debug"][] = "Amazon Creators API: aucun résultat exploitable.";
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        $item = $this->isbnAmazonSelectBestItem($items, $isbn);
        if ($item === false)
        {
            $result["debug"][] = "Amazon Creators API: impossible de sélectionner un item.";
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        $title = $this->isbnToUtf8($this->isbnArrayValue($item, array("itemInfo", "title", "displayValue"), ""));
        if ($title == "")
        {
            $result["debug"][] = "Amazon Creators API: résultat sans titre.";
            $fallback = $this->isbnSearchOpenLibrary($isbn);
            $fallback["method"] = $methodLabel . " -> openlibrary";
            $fallback["debug"] = array_merge($result["debug"], (isset($fallback["debug"]) && is_array($fallback["debug"])) ? $fallback["debug"] : array());
            return $fallback;
        }

        $authors = array();
        $contributors = $this->isbnArrayValue($item, array("itemInfo", "byLineInfo", "contributors"), array());
        if (is_array($contributors))
        {
            $i = 0;
            while ($i < sizeof($contributors))
            {
                $name = $this->isbnToUtf8($this->isbnArrayValue($contributors[$i], array("name"), ""));
                $role = strtolower(trim((string)$this->isbnArrayValue($contributors[$i], array("role"), "")));
                $roleType = strtolower(trim((string)$this->isbnArrayValue($contributors[$i], array("roleType"), "")));
                if (($name != "") && (($roleType == "author") || ($role == "author") || ($role == "auteur")))
                    $authors[] = $name;
                $i++;
            }
        }
        if (sizeof($authors) == 0 && is_array($contributors))
        {
            $i = 0;
            while ($i < sizeof($contributors))
            {
                $name = $this->isbnToUtf8($this->isbnArrayValue($contributors[$i], array("name"), ""));
                if ($name != "")
                    $authors[] = $name;
                $i++;
            }
        }
        $author = implode(", ", array_unique($authors));

        $description = $this->isbnToUtf8($this->isbnAmazonBuildDescription($item));
        $editor = $this->isbnToUtf8($this->isbnArrayValue($item, array("itemInfo", "byLineInfo", "manufacturer", "displayValue"), ""));
        if ($editor == "")
            $editor = $this->isbnToUtf8($this->isbnArrayValue($item, array("itemInfo", "manufactureInfo", "model", "displayValue"), ""));

        $publishDate = $this->isbnToUtf8($this->isbnArrayValue($item, array("itemInfo", "contentInfo", "publicationDate", "displayValue"), ""));
        if ($publishDate == "")
            $publishDate = $this->isbnToUtf8($this->isbnArrayValue($item, array("itemInfo", "productInfo", "releaseDate", "displayValue"), ""));

        $pageNumber = trim((string)$this->isbnArrayValue($item, array("itemInfo", "contentInfo", "pagesCount", "displayValue"), ""));
        if ($pageNumber == "")
            $pageNumber = trim((string)$this->isbnArrayValue($item, array("itemInfo", "contentInfo", "pagesCount"), ""));

        $isbnValues = $this->isbnArrayValue($item, array("itemInfo", "externalIds", "isbns", "displayValues"), array());
        $resolvedIsbn = $isbn;
        if (is_array($isbnValues) && sizeof($isbnValues) > 0)
        {
            $normalizedInput = $this->isbnNormalizeIdentifier($isbn);
            $i = 0;
            while ($i < sizeof($isbnValues))
            {
                if ($this->isbnNormalizeIdentifier($isbnValues[$i]) == $normalizedInput)
                {
                    $resolvedIsbn = trim((string)$isbnValues[$i]);
                    break;
                }
                $i++;
            }
            if ($i >= sizeof($isbnValues))
                $resolvedIsbn = trim((string)$isbnValues[0]);
        }

        $image = $this->isbnToUtf8($this->isbnArrayValue($item, array("images", "primary", "large", "url"), ""));
        if ($image == "")
            $image = $this->isbnToUtf8($this->isbnArrayValue($item, array("images", "primary", "medium", "url"), ""));
        if ($image == "")
            $image = $this->isbnToUtf8($this->isbnArrayValue($item, array("images", "primary", "small", "url"), ""));
        if ($image == "")
            $image = $this->isbnToUtf8($this->isbnArrayValue($item, array("images", "variants", 0, "large", "url"), ""));

        // Enrichissement résumé: si Amazon ne fournit rien d'exploitable, compléter depuis OpenLibrary.
        if ($description == "")
        {
            $openLibraryFallback = $this->isbnSearchOpenLibrary($isbn);
            if (isset($openLibraryFallback["found"]) && $openLibraryFallback["found"] && isset($openLibraryFallback["description"]))
            {
                $description = $this->isbnToUtf8($openLibraryFallback["description"]);
                if ($description != "")
                    $result["debug"][] = "Résumé complété depuis OpenLibrary (Amazon incomplet).";
            }
        }

        $result["title"] = $title;
        $result["subtitle"] = "";
        $result["author"] = $author;
        $result["editor"] = $editor;
        $result["publishDate"] = $publishDate;
        $result["pageNumber"] = $pageNumber;
        $result["description"] = $description;
        $result["infoLink"] = $this->isbnToUtf8($this->isbnArrayValue($item, array("detailPageURL"), ""));
        $result["isbn"] = $resolvedIsbn;
        $result["image"] = $image;
        $result["raw"] = $item;
        $result["found"] = true;
        return $result;
    }

    function isbnSearchOpenLibrary($isbn)
    {
        $result = array("found" => false, "debug" => array());
        $isbn = trim((string) $isbn);
        if ($isbn == "") {
            $result["debug"][] = "ISBN vide.";
            return ($result);
        }
        $url = "https://openlibrary.org/isbn/" . rawurlencode($isbn) . ".json";
        $ctx = stream_context_create(array('http' => array('timeout' => 8)));
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $result["debug"][] = "OpenLibrary: requête échouée ($url).";
            return ($result);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $result["debug"][] = "OpenLibrary: réponse JSON invalide.";
            return ($result);
        }

        $authors = array();
        if (isset($data["authors"]) && is_array($data["authors"])) {
            foreach ($data["authors"] as $authorRef) {
                if (!is_array($authorRef) || !isset($authorRef["key"]))
                    continue;
                $authorUrl = "https://openlibrary.org" . $authorRef["key"] . ".json";
                $authorRaw = @file_get_contents($authorUrl, false, $ctx);
                if ($authorRaw === false)
                    continue;
                $authorData = json_decode($authorRaw, true);
                if (is_array($authorData) && isset($authorData["name"]))
                    $authors[] = $authorData["name"];
            }
        }

        $description = "";
        if (isset($data["description"])) {
            if (is_array($data["description"]) && isset($data["description"]["value"]))
                $description = $data["description"]["value"];
            elseif (is_string($data["description"]))
                $description = $data["description"];
        }

        $result["title"] = isset($data["title"]) ? $data["title"] : "";
        $result["subtitle"] = isset($data["subtitle"]) ? $data["subtitle"] : "";
        $result["author"] = sizeof($authors) ? implode(", ", $authors) : "";
        $result["editor"] = (isset($data["publishers"][0]) ? $data["publishers"][0] : "");
        $result["publishDate"] = isset($data["publish_date"]) ? $data["publish_date"] : "";
        $result["pageNumber"] = isset($data["number_of_pages"]) ? $data["number_of_pages"] : "";
        $result["description"] = $description;
        $result["infoLink"] = "https://openlibrary.org/isbn/" . rawurlencode($isbn);
        $result["isbn"] = $isbn;
        $result["image"] = "https://covers.openlibrary.org/b/isbn/" . rawurlencode($isbn) . "-L.jpg";
        $result["raw"] = $data;
        $result["found"] = ($result["title"] != "");
        if (!$result["found"])
            $result["debug"][] = "OpenLibrary: aucun titre exploitable.";
        return ($result);
    }
}

?>

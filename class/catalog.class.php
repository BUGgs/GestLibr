<?php

/* Catalog Class
 * v 1.0
 * Romain DUCHENE
 * 20 May 2005 - 1 October 2005
 */

Class Catalog
{
    var $defaultRow = 10000;
    var $babase;
    var $idCatalog;
    var $err_msg = "";
    var $error = 0;
    var $lockedStatus = "?";
    
    function Catalog()
    {
        $this->babase = new DataBase();
        $this->babase->DbConnect();
        $this->idCatalog = -1;
    }
    
    function secureData($data)
    {
        return (addslashes($data));
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
    
    function getList()
    {
        // Listing all Catalogs in the database
        // Return an array : [0..n][idCatalog, name, description, dateCreate, dateModify, lock, nbGeneration]
        return ($this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' ORDER BY idCatalog DESC;", $null));
    }
    
    function getListUnlocked()
    {
        // Listing all Catalogs Unlocked in the database
        // Return an array : [0..n][idCatalog, name, description, dateCreate, dateModify, lock, nbGeneration]
        return ($this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND `lock`='N' ORDER BY idCatalog DESC;", $null));
    }
    
    function create($name, $description, $bypassNoQuantity)
    {
        // Creating a new Catalog in the database
        // $name : The new catalog name
        // $description : Catalog description
        // $bypassNoQuantity : used to include all books, without checking there availability
        // Return the id of the new catalog just created
        $name             = $this->secureData($name);
        $description      = $this->secureData($description);
        $bypassNoQuantity = $this->secureData($bypassNoQuantity);
        return ($this->babase->DbInsert("INSERT INTO catalog (idCatalog, name, description, bypassNoQuantity, dateCreate, dateModify, idGroup)		VALUES ('','$name', '$description', '$bypassNoQuantity', '" . time() . "', '" . time() . "', '" . $_SESSION["idGroup"] . "');", 1));
    }
    
    function update($name, $description, $bypassNoQuantity)
    {
        // Update a Catalog in the database
        // $this->idCatalog : catalog id to update
        // $name : The catalog name
        // $description : Catalog description
        // $bypassNoQuantity : used to include all books, without checking there availability
        // Return true if ok
        $name             = $this->secureData($name);
        $description      = $this->secureData($description);
        $bypassNoQuantity = $this->secureData($bypassNoQuantity);
        if ($this->idCatalog != -1) {
            $this->babase->DbQuery("UPDATE catalog SET name='$name', description='$description', bypassNoQuantity='$bypassNoQuantity', dateModify='" . time() . "' WHERE idCatalog='" . $this->idCatalog . "'");
            return (true);
        } else
            return (false);
    }
    
    function sortIntoChapter()
    {
        // Sort books into each chapter in current catalog
        // $this->idCatalog : catalog id to sort
        // Return true if ok, false if not
        settype($this->idCatalog, 'integer');
        $cat    = $this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $this->idCatalog . "';", $nul);
        $data   = $this->babase->DbSelect("SELECT idCatalogData, orderInCatalog, name, type  FROM catalogdata WHERE idCatalog='" . $this->idCatalog . "' ORDER BY `orderInCatalog`", $nbData);
        $i      = 0;
        $cpt    = 0;
        $toSort = false;
        while ($i != $nbData) {
            if ($data[$i]["type"] == "book") {
                $toSort                      = true;
                $book                        = $this->babase->DbSelect("SELECT author, title FROM book WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook='" . $data[$i]["name"] . "'", $nul);
                $tab[$cpt]["author"]         = $book[0]["author"];
                $tab[$cpt]["idCatalogData"]  = $data[$i]["idCatalogData"];
                $tab[$cpt]["orderInCatalog"] = $data[$i]["orderInCatalog"];
                $cpt++;
            } else {
                if ($toSort == true) {
                    // End of chapter
                    $endOrder = $data[$i]["orderInCatalog"];
                    $endI     = $i;
                    sort($tab);
                    $j = 0;
                    while ($startOrder < $endOrder) {
                        $this->babase->DbQuery("UPDATE catalogdata SET `orderInCatalog`='$startOrder' WHERE idCatalogData='" . $tab[$j]["idCatalogData"] . "'");
                        $startOrder++;
                        $j++;
                    }
                    // We reset variables to start a new chapter
                    $cpt    = 0;
                    $toSort = false;
                    reset($tab);
                    unset($tab);
                    // We start a new chapter
                    $startOrder = $data[$i]["orderInCatalog"] + 1;
                    $startI     = $i;
                } else {
                    if ($data[$i]["type"] == "chapter") {
                        // We start a new chapter
                        $startOrder = $data[$i]["orderInCatalog"] + 1;
                        $startI     = $i;
                    }
                }
                //        unset($tab);
            }
            $i++;
        }
        // End of Catalog... We need to sort the last chapter...
        $endOrder = $data[$i - 1]["orderInCatalog"] + 1;
        $endI     = $i - 1;
        sort($tab);
        $j = 0;
        while ($startOrder < $endOrder) {
            $this->babase->DbQuery("UPDATE catalogdata SET `orderInCatalog`='$startOrder' WHERE idCatalogData='" . $tab[$j]["idCatalogData"] . "'");
            $startOrder++;
            $j++;
        }
    }
    
    function get()
    {
        // Get informations about a Catalog in the database
        // $this->idCatalog : catalog id to update
        // Return information array: [idCatalog, name, description, dateCreate, dateModify, lock, nbGeneration, bypassNoQuantity]
        settype($this->idCatalog, 'integer');
        $data = $this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $this->idCatalog . "'", $null);
        return ($data[0]);
    }
    
    function delete()
    {
        // Deleting the active Catalog in the database (the catalog MUST BE UNLOCKED)
        // $this->idCatalog : catalog id to delete
        // Return true if deleting is ok, false if not
        settype($this->idCatalog, 'integer');
        $cat = $this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $this->idCatalog . "';", $nb);
        if (($nb == 1) AND ($cat[0]['lock'] == "N")) {
            // Deleting all data for this catalog
            $this->babase->DbQuery("DELETE FROM catalogdata WHERE idCatalog='" . $this->idCatalog . "'");
            // Deleting the catalog
            $this->babase->DbQuery("DELETE FROM catalog WHERE idCatalog='" . $this->idCatalog . "'");
            return (true);
        } else
        // Catalog is lock or doesn't exist...
            return (false);
    }
    
    function addBook($idBook, $order = -1)
    {
        // Insert a book in the current catalog
        // $this->idCatalog : catalog id to add the book
        // $idBook : id of the book to add from the book table
        // $order : if given, we will insert it into all the others books
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($order, 'integer');
            settype($idBook, 'integer');
            settype($this->idCatalog, 'integer');
            // Is it just a new book to add ? Or do we have to insert it in the catalog ?
            if ($order == -1)
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $this->nextRecord() . "', 'book', '$idBook', '', '" . time() . "');", 1);
            else {
                // We have to insert this new record into the others books...
                // We add 1 to all order after the new one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog+1 WHERE orderInCatalog>=$order;");
                // And now we can add the new book...
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $order . "', 'book', '$idBook', '', '" . time() . "');", 1);
            }
            return ($add);
        } else // Catalog is locked
            return (false);
    }
    
    function addChapter($chapter, $description = "", $order = -1)
    {
        // Insert a chapter in the current catalog
        // $this->idCatalog : catalog id to add the book
        // $chapter : Chapter text to add
        // $order : if given, we will insert it into all the others books
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($order, 'integer');
            settype($this->idCatalog, 'integer');
            $chapter     = addslashes($chapter);
            $description = addslashes($description);
            // Is it just a new chapter to add ? Or do we have to insert it in the catalog ?
            if ($order == -1)
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $this->nextRecord() . "', 'chapter', '$chapter', '$description', '" . time() . "');", 1);
            else {
                // We have to insert this new record into the others books...
                // We add 1 to all order after the new one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog+1 WHERE orderInCatalog>=$order;");
                // And now we can add the new book...
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $order . "', 'chapter', '$chapter', '$description', '" . time() . "');", 1);
            }
            return ($add);
        } else // Catalog is locked
            return (false);
    }
    
    function addTitle($title, $description, $order = -1)
    {
        // Insert a title in the current catalog
        // $this->idCatalog : catalog id to add the chapter
        // $title : Title name
        // $description : Title text to display
        // $order : if given, we will insert it into all the others books
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($order, 'integer');
            settype($this->idCatalog, 'integer');
            $title       = addslashes($title);
            $description = addslashes($description);
            // Is it just a new title to add ? Or do we have to insert it in the middle of the catalog ?
            if ($order == -1)
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $this->nextRecord() . "', 'title', '$title', '$description', '" . time() . "');", 1);
            else {
                // We have to insert this new record into the others...
                // We add 1 to all order after the new one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog+1 WHERE orderInCatalog>=$order;");
                // And now we can add the new book...
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $order . "', 'title', '$title', '$description', '" . time() . "');", 1);
            }
            return ($add);
        } else // Catalog is locked
            return (false);
    }
    
    function addText($title, $description, $order = -1)
    {
        // Insert a text in the current catalog
        // $this->idCatalog : catalog id to add the chapter
        // $title : Text name
        // $description : Text to display
        // $order : if given, we will insert it into all the others books
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($order, 'integer');
            settype($this->idCatalog, 'integer');
            $title       = addslashes($title);
            $description = addslashes($description);
            // Is it just a new title to add ? Or do we have to insert it in the middle of the catalog ?
            if ($order == -1)
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $this->nextRecord() . "', 'text', '$title', '$description', '" . time() . "');", 1);
            else {
                // We have to insert this new record into the others...
                // We add 1 to all order after the new one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog+1 WHERE orderInCatalog>=$order;");
                // And now we can add the new book...
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $order . "', 'text', '$title', '$description', '" . time() . "');", 1);
            }
            return ($add);
        } else // Catalog is locked
            return (false);
    }
    
    function addNewPage($description = "", $order = -1)
    {
        // Insert a New Page in the current catalog
        // $this->idCatalog : catalog id to add the book
        // $order : if given, we will insert it into all the others books
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($order, 'integer');
            settype($this->idCatalog, 'integer');
            $description = addslashes($description);
            // Is it just a new page to add ? Or do we have to insert it in the catalog ?
            if ($order == -1)
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $this->nextRecord() . "', 'newpage', 'newpage', '$description', '" . time() . "');", 1);
            else {
                // We have to insert this new record into the others books...
                // We add 1 to all order after the new one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog+1 WHERE orderInCatalog>=$order;");
                // And now we can add the new book...
                $add = $this->babase->DbInsert("INSERT INTO catalogdata (idCatalogData, idCatalog, orderInCatalog, type, name, description, date) VALUES ('','" . $this->idCatalog . "', '" . $order . "', 'newpage', 'newpage', '$description', '" . time() . "');", 1);
            }
            return ($add);
        } else // Catalog is locked
            return (false);
    }
    
    function delData($idCatalogData)
    {
        // Delete a data record in the current catalog
        // $this->idCatalog : catalog id to delete the data record
        // $idCatalogData : The id to delete
        // Return true if ok, false if not
        
        if (!$this->islocked()) {
            settype($idCatalogData, 'integer');
            settype($this->idCatalog, 'integer');
            // We check if idCatalogData exists...
            $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalog ='" . $this->idCatalog . "' AND idCatalogData='" . $idCatalogData . "'", $nb);
            if ($nb == 1) {
                // We delete the record in the catalog database
                $this->babase->DbQuery("DELETE FROM `catalogdata` WHERE idCatalog ='" . $this->idCatalog . "' AND idCatalogData='" . $idCatalogData . "'");
                // We substract 1 to all order after the old one
                $this->babase->DbQuery("UPDATE `catalogdata` SET orderInCatalog=orderInCatalog-1 WHERE orderInCatalog>=" . $data[0][orderInCatalog]);
                return (true);
            } else // idCatalogData doesn't exist !
                return (false);
        } else // Catalog is locked
            return (false);
    }
    
    function delIdBook($idBook)
    {
        // Delete a book from a catalog from his idBook
        // $this->idCatalog : catalog id to delete the data record
        // $idBook : The idBook to delete
        // Return true if ok, false if not
        
        settype($idBook, 'integer');
        settype($this->idCatalog, 'integer');
        // We check if idBook exists...
        $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalog ='" . $this->idCatalog . "' AND type='book' AND name='" . $idBook . "' LIMIT 0,1", $nb);
        if ($nb == 1) {
            // We delete the record in the catalog database
            return ($this->delData($data[0]["idCatalogData"]));
        } else // idCatalogData doesn't exist !
            return (false);
    }
    
    function up($idCatalogData)
    {
        // Up a record in the current catalog (lower order)
        // $this->idCatalog : catalog id to modify
        // $idCatalogData : id of the record to up
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($idCatalogData, 'integer');
            settype($this->idCatalog, 'integer');
            $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalogData='$idCatalogData';", $nbResult);
            if ($nbResult == 1) {
                $dataNext = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE `orderInCatalog`<" . $data[0]["orderInCatalog"] . " AND `idCatalog`='" . $data[0]["idCatalog"] . "' ORDER BY `orderInCatalog` DESC LIMIT 0,1;", $nbResultNext);
                if ($nbResultNext == 1) // We found a record upper... So we can switch order betwen them !
                    {
                    $this->babase->DbQuery("UPDATE `catalogdata` SET `orderInCatalog`='" . $dataNext[0]["orderInCatalog"] . "' WHERE `idCatalogData`='" . $data[0]["idCatalogData"] . "'");
                    $this->babase->DbQuery("UPDATE `catalogdata` SET `orderInCatalog`='" . $data[0]["orderInCatalog"] . "' WHERE `idCatalogData`='" . $dataNext[0]["idCatalogData"] . "'");
                    return (true);
                } else // There is no record upper... We do nothing ! No error...
                    return (true);
            } else
                return (false); // idCatalogData Doesn't exist !
        }
    }
    
    function down($idCatalogData)
    {
        // Down a record in the current catalog (upper order)
        // $this->idCatalog : catalog id to modify
        // $idCatalogData : id of the record to down
        // Return true (the id) if ok, false if not
        
        if (!$this->islocked()) {
            settype($idCatalogData, 'integer');
            settype($this->idCatalog, 'integer');
            $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalogData='$idCatalogData';", $nbResult);
            if ($nbResult == 1) {
                $dataNext = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE `orderInCatalog`>" . $data[0]["orderInCatalog"] . " AND `idCatalog`='" . $data[0]["idCatalog"] . "' ORDER BY `orderInCatalog` ASC LIMIT 0,1;", $nbResultNext);
                if ($nbResultNext == 1) // We found a record upper... So we can switch order betwen them !
                    {
                    $this->babase->DbQuery("UPDATE `catalogdata` SET `orderInCatalog`='" . $dataNext[0]["orderInCatalog"] . "' WHERE `idCatalogData`='" . $data[0]["idCatalogData"] . "'");
                    $this->babase->DbQuery("UPDATE `catalogdata` SET `orderInCatalog`='" . $data[0]["orderInCatalog"] . "' WHERE `idCatalogData`='" . $dataNext[0]["idCatalogData"] . "'");
                    return (true);
                } else // There is no record upper... We do nothing ! No error...
                    return (true);
            } else
                return (false); // idCatalogData Doesn't exist !
        }
    }
    
    
    function getData($idCatalogData = -1, $start = 0, $row = -1)
    {
        // List all records in the current Catalog
        // Return an array : [0..n][idCatalogData, idCatalog, orderInCatalog, type, name, description, date]
        $idCatalog = $this->idCatalog;
        settype($idCatalog, 'integer');
        settype($idCatalogData, 'integer');
        settype($start, 'integer');
        settype($row, 'integer');
        if ($row == -1)
            $row = $this->defaultRow;
        
        if ($idCatalogData != -1)
            $where = " AND idCatalogData='$idCatalogData'";
        else
            $where = "";
        
        $data = $this->babase->DbSelect("SELECT * FROM catalogdata WHERE idCatalog='$idCatalog'$where ORDER BY `orderInCatalog` LIMIT $start,$row;", $nb);
        return ($data);
    }
    
    function getChapter()
    {
        // Get Chapter list in a Catalog in the database
        // $this->idCatalog : catalog id to get info
        // Return information array: [idCatalog, name, description, dateCreate, dateModify, lock, nbGeneration, bypassNoQuantity]
        settype($this->idCatalog, 'integer');
        $data = $this->babase->DbSelect("SELECT * FROM catalogdata WHERE idCatalog='" . $this->idCatalog . "' AND type='chapter' ORDER BY `orderInCatalog`;", $nb);
        return ($data);
    }
    
    function nbBooks($idCatalog = -1)
    {
        // Return number of books in the current catalog
        // $this->idCatalog : catalog id to count in
        // Return number of books in current catalog
        if ($idCatalog == -1)
            $idCatalog = $this->idCatalog;
        settype($idCatalog, 'integer');
        $data = $this->babase->DbSelect("SELECT count(*) AS nb FROM catalogdata WHERE idCatalog='" . $idCatalog . "' AND type='book';", $nb);
        return ($data[0]["nb"]);
    }
    
    function nbCatalogData($idCatalog = -1)
    {
        // Return number of books in the current catalog
        // $this->idCatalog : catalog id to count in
        // Return number of books in current catalog
        if ($idCatalog == -1)
            $idCatalog = $this->idCatalog;
        settype($idCatalog, 'integer');
        $data = $this->babase->DbSelect("SELECT count(*) AS nb FROM catalogdata WHERE idCatalog='" . $idCatalog . "';", $nb);
        return ($data[0]["nb"]);
    }
    
    function islocked($idCatalog = -1)
    {
        // Check if the current catalog is locked or not
        // $this->idCatalog : catalog id to lock
        // Return the mysql result
        if (($this->lockedStatus == "?") OR ($this->currentCatalog != $this->idCatalog)) {
            if ($idCatalog == -1)
                $idCatalog = $this->idCatalog;
            settype($idCatalog, 'integer');
            $cat = $this->babase->DbSelect("SELECT `lock` FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $idCatalog . "';", $nb);
            if ($cat[0]["lock"] == 'Y') {
                $this->lockedStatus   = 'Y';
                $this->currentCatalog = $idCatalog;
            } else {
                $this->lockedStatus   = 'N';
                $this->currentCatalog = $idCatalog;
            }
        }
        if ($this->lockedStatus == 'Y')
            return (true);
        else
            return (false);
    }
    
    function lock($idCatalog = -1)
    {
        // Locking the current catalog
        // $this-idCatalog : catalog id to lock
        // Return the mysql result
        if ($idCatalog == -1)
            $idCatalog = $this->idCatalog;
        settype($idCatalog, 'integer');
        $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalog='" . $idCatalog . "' AND type='book' ORDER BY `orderInCatalog`", $nb);
        $i    = 0;
        $cat  = $this->get();
        if ($cat["bypassNoQuantity"] == 'N')
            $bypass = " AND quantity>0";
        else
            $bypass = "";
        $number = 1;
        while ($nb != $i) {
            $book = $this->babase->DbSelect("SELECT * FROM `book` WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook ='" . $data[$i]["name"] . "'$bypass", $nbBook);
            if ($nbBook == 1) {
                $description = "<b>" . $book[0]["author"] . "</b> - " . $book[0]["title"] . "\n<br /><i>" . $book[0]["description"] . "</i>";
                $this->babase->DbQuery("UPDATE `catalogdata` SET `number`=$number, `description` = '" . addslashes($description) . "', `price` = '" . $book[0]["price"] . "' WHERE `idCatalogData`='" . $data[$i]["idCatalogData"] . "'");
                $number++;
            } else
                $this->delData($data[$i]["idCatalogData"]);
            $i++;
        }
        return ($this->babase->DbQuery("UPDATE catalog SET `lock`='Y', dateModify='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $idCatalog . "'"));
    }
    
    function unlock($idCatalog = -1)
    {
        // Unlocking the current catalog
        // $this-idCatalog : catalog id to unlock
        // Return the mysql result
        
        if ($idCatalog == -1)
            $idCatalog = $this->idCatalog;
        settype($idCatalog, 'integer');
        $this->babase->DbQuery("UPDATE catalogdata SET `number`='0' WHERE idCatalog='" . $idCatalog . "'");
        $this->babase->DbQuery("UPDATE catalogdata SET `description`='', price='0' WHERE idCatalog = '" . $idCatalog . "' AND `type` = 'book'");
        return ($this->babase->DbQuery("UPDATE catalog SET `lock`='N', dateModify='" . time() . "' WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $idCatalog . "'"));
    }
    
    function nextRecord()
    {
        // Get the next order number in the current catalog
        // $this-idCatalog : catalog id to lock
        // Return the next order number
        
        settype($this->idCatalog, 'integer');
        $cat = $this->babase->DbSelect("SELECT max(cat.orderInCatalog) AS next FROM `catalogdata` AS cat WHERE idCatalog ='" . $this->idCatalog . "';", $nb);
        if ($cat[0][next] == NULL)
            return (1);
        else
            return ($cat[0]['next'] + 1);
    }
    
    function getTemplate($idCatalogTemplate = -1)
    {
        // Get templates from catalogtemplate table.
        // If no id is specified, all templates are returned
        // $this->idCatalog : catalog id to update
        // $idCatalogTemplate : if specified, we return only this template.
        // Return information array: [idCatalogTemplate, name, content, date]
        settype($idCatalogData, 'integer');
        if ($idCatalogTemplate != -1)
            $where = " AND idCatalogTemplate='$idCatalogTemplate'";
        else
            $where = "";
        $data = $this->babase->DbSelect("SELECT * FROM catalogtemplate WHERE idGroup='" . $_SESSION["idGroup"] . "'" . $where, $null);
        return ($data);
    }
    
    function generate($type, $save = 'n', $opt = '')
    {
        // Generate the catalog with the specified type:
        // $type: htmllist, pdf, html, web...
        // $save: y or n to save the generated catalog
        // $opt: optionnal field
        
        if ($this->islocked() OR ($type == "htmllist")) {
            settype($idCatalogData, 'integer');
            settype($this->idCatalog, 'integer');
            // We update nbGeneration in table
            $this->babase->DbQuery("UPDATE catalog SET nbGeneration=nbGeneration+1 WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $this->idCatalog . "';");
            // Is catalog can bypass NoQuantity ?
            $cat = $this->babase->DbSelect("SELECT * FROM catalog WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idCatalog='" . $this->idCatalog . "';", $null);
            if ($cat[0]["bypassNoQuantity"] == N)
                $bypassNoQuantity = " AND quantity>0";
            else
                $bypassNoQuantity = "";
            // We fetch all data
            $data = $this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE idCatalog ='" . $this->idCatalog . "' ORDER BY orderInCatalog", $nb);
            switch ($type) {
                // HTML Simple list generation v1.0
                case "htmllist":
                    $i      = 0;
                    $result = "<h1>" . $cat[0]["name"] . "</h1><table>";
                    while ($i != $nb) {
                        switch ($data[$i][type]) {
                            case "book":
                                $book = $this->babase->DbSelect("SELECT * FROM `book` WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook ='" . $data[$i]["name"] . "'" . $bypassNoQuantity, $nbBook);
                                if ($nbBook == 1)
                                    $result .= "<tr><td><b>" . $data[$i]["number"] . " - " . html_entity_decode($book[0]["author"]) . "</b>\n<br />" . html_entity_decode($book[0]["title"]) . "\n<br />" . html_entity_decode($book[0]["description"]) . "\n<br />" . $book[0]["price"] . " Euros\n</td></tr>";
                                break;
                            case "image":
                                $result .= "<tr><td><img src='" . $data[$i]["name"] . "' alt='" . $data[$i]["description"] . "'></td></tr>";
                                break;
                            case "title":
                                $result .= "</table>" . $data[$i]["name"] . "<table>";
                                break;
                            case "chapter":
                                $result .= "<tr><td><h1> <br />" . $data[$i]["name"] . " <br /> </h1></td></tr>";
                                break;
                            case "newpage":
                                $result .= "<tr><td>--------------------------------------------</td></tr>";
                                break;
                            default:
                                $result .= "<tr><td>ERROR: type non recognized : " . $data[$i][type] . "</td></tr>";
                                break;
                        }
                        $i++;
                    }
                    $result .= "</table>";
                    return ($result);
                    break;
                // PDF generation v1.0
                case "pdf":
                case "pdfloc":
                    $i         = 0;
                    $result    = '<style>
table
{
width: 90%;
border:0px none;
border-collapse:collapse;
border-spacing: 0px;
}
table, td, th
{
border:0px none;
padding: 0px;
}
.chapter
{
font-family: arial;
font-size: 22px;
text-align: center;
font-style: italic;
font-weight: bold;
text-decoration: none;
border: none;
background: #FFFFFF;
}
.book
{
font-family: arial;
font-size: 15px;
text-align: left;
}
.price
{
font-family: arial;
font-size: 15px;
text-align: right;
}
.catalogueSommaireTitre
{
font-family: arial;
font-size: 35px;
text-align: center;
}
.catalogueSommaireDetails
{
font-family: arial;
font-size: 25px;
text-align: left;
}
.catalogueSommaireDetailsNumero
{
font-family: arial;
font-size: 25px;
text-align: left;
}
.footer
{
width: 100%;
border:none;
border-top: solid 1px black;
}
</style>';
                    $page_book = '<page backtop="2mm" backbottom="5mm" backleft="0mm" backright="0mm">
    <page_header>
    </page_header>
    <page_footer>
        <table class="footer">
            <tr>
                <td style="text-align: left;    width: 50%; border-top: solid 1px black;">http://www.librairieduchene.com</td>
                <td style="text-align: right;    width: 50%; border-top: solid 1px black;">page [[page_cu]]/[[page_nb]]</td>
            </tr>
        </table>
    </page_footer>
    <table>
';
                    while ($i != $nb) {
                        switch ($data[$i][type]) {
                            case "book":
                                if ($data[$i]["price"] == (round($data[$i]["price"])))
                                    $price = round($data[$i]["price"]);
                                else
                                    $price = $data[$i]["price"];
                                // If we want to have a PDF version with location of the books
                                if ($type == "pdfloc") {
                                    $book = $this->babase->DbSelect("SELECT * FROM `book` WHERE idGroup='" . $_SESSION["idGroup"] . "' AND idBook ='" . $data[$i]["name"] . "'", $nbBook);
                                    if ($book[0]["location"] != "")
                                        $price = "(" . $book[0]["location"] . ") " . $price;
                                }
                                //$book=$this->babase->DbSelect("SELECT * FROM `book` WHERE idBook ='".$data[$i]["name"]."'".$bypassNoQuantity,$nbBook);
                                //if ($nbBook==1)
                                //{
                                //$result.= "<tr><td class='catalog'><b>".$data[$i]["number"]." - ".html_entity_decode($data[$i]["description"])."</td></tr>
                                //									<tr><td align='right'>".$data[$i]["price"]." Ä</td></tr>";
                                $result .= "<tr><td class='book' width='90%'>" . $data[$i]["number"] . " - " . html_entity_decode($data[$i]["description"]) . "<br/><div class='price'>$price €</div></td></tr>";
                                //}
                                break;
                            case "image":
                                $result .= "<div class='book'><img src='" . $data[$i]["name"] . "' alt='" . $data[$i]["description"] . "'></div>";
                                break;
                            case "title":
                                if (($in_book))
                                    $result .= '</table></page>';
                                $in_book = false;
                                $result .= '<page>' . html_entity_decode($data[$i]["description"]) . '</page>';
                                break;
                            case "chapter":
                                if (!($in_book))
                                    $result .= $page_book;
                                $in_book = true;
                                //	      $result.= "<br />&nbsp;<div class='chapter'>".$data[$i]["name"]."</div>&nbsp;<br />&nbsp;<br />";
                                $result .= "<tr><td class='chapter'>&nbsp;<br/>" . $data[$i]["name"] . "<br/>&nbsp;</td></tr>";
                                break;
                            case "text":
                                $result .= html_entity_decode($data[$i]["description"]);
                                break;
                            case "newpage":
                                if (($in_book))
                                    $result .= '</table></page>';
                                $in_book = false;
                                $result .= "<page></page>";
                                break;
                            default:
                                $result .= "ERROR: type non recognized : " . $data[$i][type];
                                break;
                        }
                        $i++;
                    }
                    if ($in_book)
                        $result .= '</table></page>';
                    // Report simple running errors
                    //	echo $result;
                    //	die;
                    error_reporting(E_ERROR | E_WARNING | E_PARSE);
                    require_once('html2pdf/html2pdf.class.php');
                    try {
                        $html2pdf               = new HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', array(
                            12,
                            15,
                            12,
                            15
                        ));
                        $html2pdf->setModeDebug = true;
                        $html2pdf->pdf->SetDisplayMode('fullpage');
                        $html2pdf->writeHTML(utf8_encode($result));
                        $html2pdf->Output();
                    }
                    catch (HTML2PDF_exception $e) {
                        echo $e;
                        exit;
                    }
                    break;
                default:
                    echo "Nothing to do !";
                    break;
            }
        }
    }
    
}
?>
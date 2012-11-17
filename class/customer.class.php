<?php

/* Customer Class
* v 1.0
* Romain DUCHENE
* July 2004
*/

Class Customer {
    var $defaultRow = 1500;

    var $babase;
    var $idCustomer = "-1";
    var $err_msg = "";
    var $sqlSearch = "";
    var $sqlOrder = "";
    var $nbResult = "0";
    var $data = "";

    function Customer()
    {
        $this->babase = new DataBase();
        $this->babase->DbConnect();
        $this->idCustomer = -1;
    } 

    function secureData()
    {
	reset($this->data);
	while (list($key, $val) = each($this->data))
	{
	    $this->data[$key]=addslashes($val);
	}
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

    function assignData($postData)
    { 
        // Assign all the data to the $this->data from $postData
        $this->data["name"] = $postData["name"];
        $this->data["firstname"] = $postData["firstname"];
        $this->data["civility"] = $postData["civility"];
        $this->data["organisation"] = $postData["organisation"];
        $this->data["address"] = $postData["address"];
        $this->data["postCode"] = $postData["postCode"];
        $this->data["city"] = $postData["city"];
        $this->data["country"] = $postData["country"];
        $this->data["entryDate"] = $postData["entryDate"];
        $this->data["notes"] = $postData["notes"];
        $this->data["telephone"] = $postData["telephone"];
        $this->data["telephone2"] = $postData["telephone2"];
        $this->data["fax"] = $postData["fax"];
        $this->data["mobile"] = $postData["mobile"];
        $this->data["email"] = $postData["email"];
        if ($postData["active"]=="on") $this->data["active"]=1;
        elseif ($postData["active"]=="1") $this->data["active"]=1;
        else $this->data["active"]=0;
        //$this->data["active"] = $postData["active"];
    } 

    function search($cache = "no-cache", $order = "", $start = 0, $row = 0)
    { 
        // Seacrh for a customer in the database
				// $cache : to use or not the sql cache query (to not use the cache, value is no-cache or nothing)
        // $order : order to classify the results
        // $start : integer to start the results
        // $row : how many row to display
        // $this->data : table with all books fields
        // Return the results number in a table and the results in $this->data
        if ($row == 0) $row = $this->defaultRow;
				if (($cache == "no-cache") OR ($cache == "")) {
					// We create all the new search request
					// We made the sql request
          $sqlSearch = "SELECT * FROM `customer` WHERE idGroup='".$_SESSION["idGroup"]."' AND (`name` LIKE '%" . $this->data["name"] . "%' AND `firstname` LIKE '%" . $this->data["firstname"] . "%' AND `civility` LIKE '%" . $this->data["civility"] . "%' AND `organisation` LIKE '%" . $this->data["organisation"] . "%' AND `address` LIKE '%" . $this->data["address"] . "%' AND `postCode` LIKE '%" . $this->data["postCode"] . "%' AND `city` LIKE '%" . $this->data["city"] . "%' AND `country` LIKE '%" . $this->data["country"] . "%' AND `entryDate` LIKE '%" . $this->data["entryDate"] . "%' AND `notes` LIKE '%" . $this->data["notes"] . "%' AND `telephone` LIKE '%" . $this->data["telephone"] . "%' AND `telephone2` LIKE '%" . $this->data["telephone2"] . "%' AND `mobile` LIKE '%" . $this->data["mobile"] . "%' AND `fax` LIKE '%" . $this->data["fax"] . "%' AND `email` LIKE '%" . $this->data["email"] . "%')";
          if ($order != "")
            $sqlOrder = " ORDER BY `" . $order . "`";
          else $sqlOrder = " ORDER BY name,firstname";
          $sqlLimit = " LIMIT " . $start . ", " . $row;
   			  // Creating the new cache
	 	  	  $_SESSION["searchcache"] = $sqlSearch;
          $_SESSION["ordercache"] = $sqlOrder;
          $_SESSION["limitcache"] = $sqlLimit;
 			  } else {
				// We use the cache !
          $sqlSearch = $_SESSION["searchcache"];
          $sqlOrder = $_SESSION["ordercache"];
          $sqlLimit = $_SESSION["limitcache"];
 			  }
 			  // THE search request
        $searchResult = $this->babase->DbSelect($sqlSearch.$sqlOrder.$sqlLimit, $searchResultNb);
        return ($searchResult);
    } 

/*    function search($order = "", $start = 0, $row = 0, $cache = "no-cache")
    { 
        // Seacrh for a customer in the database
        // $order : order to classify the results
        // $start : integer to start the results
        // $row : how many row to display
				// $cache : to use or not the sql cache query
        // $this->data : table with all customers fields
        // Return the results number in a table and the results in $this->data
        if ($row == 0) $row = $this->defaultRow;
				if ($cache=="cache") {
          $this->sqlSearch = "SELECT * FROM `customer` WHERE `name` LIKE '%" . $this->data["name"] . "%' AND `firstname` LIKE '%" . $this->data["firstname"] . "%' AND `civility` LIKE '%" . $this->data["civility"] . "%' AND `organisation` LIKE '%" . $this->data["organisation"] . "%' AND `address` LIKE '%" . $this->data["address"] . "%' AND `postCode` LIKE '%" . $this->data["postCode"] . "%' AND `city` LIKE '%" . $this->data["city"] . "%' AND `country` LIKE '%" . $this->data["country"] . "%' AND `entryDate` LIKE '%" . $this->data["entryDate"] . "%' AND `notes` LIKE '%" . $this->data["notes"] . "%' AND `telephone` LIKE '%" . $this->data["telephone"] . "%' AND `telephone2` LIKE '%" . $this->data["telephone2"] . "%' AND `mobile` LIKE '%" . $this->data["mobile"] . "%' AND `fax` LIKE '%" . $this->data["fax"] . "%' AND `email` LIKE '%" . $this->data["email"] . "%' AND `active` LIKE '%" . $this->data["active"] . "%'";
          if ($order != "") {
            $this->sqlOrder = " ORDER BY `" . $order . "`";
          } else $this->sqlOrder = "";
          $sqlTmp .= " LIMIT " . $start . ", " . $row;
		    }
        $this->sqlCache = $this->sqlSearch . $this->sqlOrder . $sqlTmp;
        $this->data = $this->babase->DbSelect($this->sqlCache, $this->nbResult);
        return ($this->nbResult);
    } 
*/
    function get($id)
    { 
        // Get a record in the customer database
        // $data : table with all customers fields
        // Return the $data of this custormer or -1 if error
        $customerData = $this->babase->DbSelect("SELECT * FROM `customer` WHERE `idGroup`='".$_SESSION["idGroup"]."' AND `idCustomer` LIKE '$id'",$result);
	if ($result!=1)
	    return(-1);
	else
	{
	    $this->assignData($customerData[0]);
	    return ($this->data);
	}
    } 

    function add($entryDate=-1)
    { 
        // Add a new record in the customer database
        // $data : table with all customers fields
        // Return the new custormer id or -1 if error
        if ($entryDate==-1)
				  $entry=time();
				else
				  $entry=$entryDate;
        $this->secureData();
        $this->idCustomer = $this->babase->DbInsert("INSERT INTO `customer` (`idCustomer`, `name`, `firstname`, `civility`, `organisation`, `address`, `postCode`, `city`, `country`, `entryDate`, `notes`, `telephone`, `telephone2`, `mobile`, `fax`, `email`, `active`, `idGroup`) VALUES ('', '" . $this->data["name"] . "', '" . $this->data["firstname"] . "', '" . $this->data["civility"] . "', '" . $this->data["organisation"] . "', '" . $this->data["address"] . "', '" . $this->data["postCode"] . "', '" . $this->data["city"] . "', '" . $this->data["country"] . "', '" . $entry . "', '" . $this->data["notes"] . "', '" . $this->data["telephone"] . "', '" . $this->data["telephone2"] . "', '" . $this->data["mobile"] . "', '" . $this->data["fax"] . "', '" . $this->data["email"] . "', '" . $this->data["active"] . "', '".$_SESSION["idGroup"]."')", 1);
        return ($this->idCustomer);
    } 

    function update()
    { 
        // Update the current record with $this->data for the customer id $thid->idCustomer
				settype($this->idCustomer, 'integer');
        $this->secureData();
        return ($this->babase->DbQuery("UPDATE `customer` SET name='" . $this->data["name"] . "', firstname='" . $this->data["firstname"] . "', civility='" . $this->data["civility"] . "', organisation='" . $this->data["organisation"] . "', address='" . $this->data["address"] . "', postCode='" . $this->data["postCode"] . "', city='" . $this->data["city"] . "', country='" . $this->data["country"] . "', notes='" . $this->data["notes"] . "', telephone='" . $this->data["telephone"] . "', telephone2='" . $this->data["telephone2"] . "', mobile='" . $this->data["mobile"] . "', fax='" . $this->data["fax"] . "', email='" . $this->data["email"] . "', active='" . $this->data["active"] . "' WHERE idCustomer='" . $this->idCustomer . "'"));
    } 

    function del()
    { 
        // Delete the record in the customer database
        // $this-->idCustomer : id to delete
        // Return the delete result
	settype($this->idCustomer, 'integer');
        return ($this->babase->DbQuery("DELETE FROM `customer` WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idCustomer='" . $this->idCustomer . "'"));
    } 

    function addCategory($idCategory,$idCustomer=-1)
		{
		    // Add a customer in a category
		    // $idCategory : category to add to the customer
		    // $idCustomer : to specify antoher customer to add (not the current in $this->data[idCustomer])
		    // Return true if ok, false if not (error message in $error)
				settype($this->idCategory, 'integer');
		    if ($idCustomer==-1) $idCustomer=$this->idCustomer;
				settype($idCustomer, 'integer');
		    // We check if the customer isn't already in this category and if the customer&cat exist
		    $check=$this->babase->DbSelect("SELECT count(*) AS nb FROM customerincategory WHERE idCustomer='$idCustomer' AND idCategory='$idCategory'",$nul);
		    if ($check[0]["nb"]!=0) {
				  $this->error=6;
				  return(false);
				}
		    // We check if the customer exist
		    $check=$this->babase->DbSelect("SELECT count(*) AS nb FROM customer WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idCustomer='$idCustomer'",$nul);
		    if ($check[0]["nb"]!=1) {
				  $this->error=7;
				  return(false);
				}
		    // We check if the cat exist
		    $check=$this->babase->DbSelect("SELECT count(*) AS nb FROM category WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idCategory='$idCategory'",$nul);
		    if ($check[0]["nb"]!=1) {
				  $this->error=3;
				  return(false);
				}
				// Good : We add the category
				$add=$this->babase->DbInsert("INSERT INTO customerincategory (idCustomerInCategory, idCustomer, idCategory) VALUES ('','$idCustomer','$idCategory')",1);
				if ($add>0) return(true);
				else {
				  $this->error=4;
				  return(false);
				}

		}

    function listCategory($idCustomer=-1)
		{
		    // List the categories of a customer
		    // $idCustomer : to specify antoher customer to list (not the current in $this->idCustomer)
		    // Return a table of the member categories ([idCategory][name][description]) if ok, 0 if there is no category
		    if ($idCustomer==-1) $idCustomer=$this->idCustomer;
		    settype($idCustomer, 'integer');
		    $list=$this->babase->DbSelect("SELECT cic.idCategory, cat.name, cat.description FROM customerincategory AS cic, category AS cat WHERE cic.idCategory=cat.idCategory AND cic.idCustomer='$idCustomer'",$nb);
		    if ($nb=0) return(0);
		    else return($list);
    }

    function delCategory($idCategory,$idCustomer=-1)
		{
		    // Del a customer in a category
		    // $idCategory : category to del to the customer
		    // $idCustomer : to specify antoher customer to del (not the current in $this->data[idCustomer])
		    // Return true if ok, false if not (error message in $error)
				settype($this->idCategory, 'integer');
		    if ($idCustomer==-1) $idCustomer=$this->idCustomer;
				settype($idCustomer, 'integer');
		    // We check if the customer isn't already in this category and if the customer&cat exist
		    $check=$this->babase->DbSelect("SELECT count(*) AS nb FROM customerincategory WHERE idCustomer='$idCustomer' AND idCategory='$idCategory'",$nul);
		    if ($check[0]["nb"]!=1) {
				  $this->error=8;
				  return(false);
				}
				// Good : We add the category
				$this->babase->DbQuery("DELETE FROM customerincategory WHERE idCustomer='$idCustomer' AND idCategory='$idCategory'");
				return(true);
		}

    function listOrder($idCustomer=-1)
		{
		    // List the order of a customer
		    // $idCustomer : to specify antoher customer to list (not the current in $this->idCustomer)
		    // Return a table of order ([idOrder][refOrder][source][date][idCustomer][price][vtaPrice][totalPrice][status][idUser]) if ok, 0 if there is no order
		    if ($idCustomer==-1) $idCustomer=$this->idCustomer;
				settype($idCustomer, 'integer');
		    $list=$this->babase->DbSelect("SELECT * FROM `order` WHERE `idGroup`='".$_SESSION["idGroup"]."' AND `idCustomer`='$idCustomer' ORDER BY date DESC",$nb);
		    if ($nb=0) return(0);
		    else return($list);
    }

    function getAllActives()
    {
        // Get all data of actives customers ordered by name
        // Return the $data of all actives custormers or -1 if error
        $customerData = $this->babase->DbSelect("SELECT * FROM `customer` WHERE `idGroup`='".$_SESSION["idGroup"]."' AND `active` LIKE '1' ORDER BY name, firstname, organisation",$result);
	if ($result==0)
	    return(-1);
	else
	    return ($customerData);
    }

}
?>
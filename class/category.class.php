<?php

/* Category Class
* v 1.0
* Romain DUCHENE
* May 2005
*/

Class Category {

    var $babase;
    var $idCategory = "-1";
    var $err_msg = "";
    var $data = "";
    
    var $error = 0;

    function Category()
    {
        $this->babase = new DataBase();
        $this->babase->DbConnect();
        $this->idCategory = -1;
    } 

    function search()
    {
		 // TODO...
        return (true);
    } 

    function get($idCategory=-1)
    { 
        // Get a record in the category database
        // $id : id category field
        // Return the table data of this book or -1 if error
        if ($idCategory==-1) $idCategory=$this->idCategory;
        $categoryData = $this->babase->DbSelect("SELECT * FROM `category` WHERE idGroup='".$_SESSION["idGroup"]."' AND `idCategory` LIKE '$idCategory'",$result);
				if ($result!=1) return(false);
				else
          return ($categoryData[0]);
    } 

    function add($name,$description)
    { 
        // Add a new record in the category database
        // $name & $desciption : all category fields
        // Return the new category id or -1 if error
        $this->idCategory = $this->babase->DbInsert("INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`,`idGroup`) VALUES ('', '$name', '$description', '" . time() . "', '" . time() . "', '".$_SESSION["idGroup"]."')", 1);
        return ($this->idCategory);
    } 

    function update($name,$description,$idCategory=-1)
    { 
        // Update the current record $this->idCategory or the given $idCategory
        // $name, description : table with all category fields
        // $this->idCategory : Optionnal id category to update (by default: using $this->idCategory)
        // Return the update result
        if ($idCategory==-1) $idCategory=$this->idCategory;
        return ($this->babase->DbQuery("UPDATE `category` SET name='$name', description='$description', modifyDate='" . time() . "' WHERE idGroup='".$_SESSION["idGroup"]."' AND idCategory='$idCategory'"));
    } 

    function del($idCategory=-1)
    { 
        // Delete the record in the category database
        // $this->idCategory : id to delete
        // Return the delete result
        if ($idCategory==-1) $idCategory=$this->idCategory;
        return ($this->babase->DbQuery("DELETE FROM `category` WHERE idGroup='".$_SESSION["idGroup"]."' AND idCategory='$idCategory'"));
    } 
    
    function getList()
    {
        // List all category in database
        // Return an array: 2 cols (name, description)
        return($this->babase->DbSelect("SELECT * FROM `category` WHERE idGroup='".$_SESSION["idGroup"]."' ORDER BY name",$nul));
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

    function stats()
		{
		    // Make General statistics on categories
		    //
		    // Return a table of stats with these colums
				// [totalCategories]

				// for totalCategories
				$tempo=$this->babase->DbSelect("SELECT count(*) AS nb FROM category WHERE idGroup='".$_SESSION["idGroup"]."'",$null);
				$data["totalCategories"]=$tempo[0]["nb"];

		    return($data);
    }

}

?>
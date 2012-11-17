<?php
/**
 * * * *      GestLibr: internet.lib.php      	*
 * * * *					Internet Lib               		*
 * * * *  Romain DUCHENE, Mar 2006 - ?				 	*
 */

include "class/book.class.php";
$book = new Book();
include "class/category.class.php";
$category=New Category();
include "class/catalog.class.php";
$catalog=New Catalog();
include "class/internet.class.php";
$internet=New Internet();

include "class/ebay.class.php";


switch ($a)
{
    case "co":
        $template->set_filenames(array('body' => 'internet_configure.tpl'));
	// If a Internet link is submited :
	if ((isset($_POST["id"])) AND (isset($_POST["name"])))
	{
	    if ($_POST["id"]==-1)
		// New creating
		$internet->create($_POST["name"],$_POST["description"],$_POST["method"],$_POST["header"],$_POST["template"],$_POST["param"]);
	    else
		// Update
		$internet->modify($_POST["id"],$_POST["name"],$_POST["description"],$_POST["method"],$_POST["header"],$_POST["template"],$_POST["param"]);
	}

	// If a Internet link is selected :
	if (isset($_POST["id"]))
	{
	    if ($_POST["id"]!=-1)
	    {
		$data = $internet->get($_POST["id"]);
		$optionListSelected = "<OPTION VALUE='" . $_POST["id"] . "'>" . $data["name"] . "</OPTION>";
		$optionMethodSelected = "<OPTION VALUE='" . $data["method"] . "'>" . $data["method"] . "</OPTION>";
	    }
	    else
	    {
		$data["name"] = $txt["new"];
		$data["idInternet"] = "-1";
	    }
	    $template->assign_vars(array('OPTION_LIST_SELECTED' => $optionListSelected, 'NAME_VALUE' => $data["name"], 'DESCRIPTION_VALUE' => $data["description"], 'METHOD_SELECTED' => $optionMethodSelected,
                'PARAM_VALUE' => $data["param"], 'HEADER_VALUE' => $data["header"], 'TEMPLATE_VALUE' => $data["template"],
                'ID_VALUE' => $data["idInternet"]));
	}

	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	$optionList = "";
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'CONFIGURE' => $txt["configure"],
                'NEW' => $txt["new"], 'CHANGE' => $txt["change"], 'OPTION_LIST' => $optionList, 'NAME' => $txt["name"], 'DESCRIPTION' => $txt["description"], 'METHOD' => $txt["method"],
                'PARAM' => $txt["parameters"], 'TEMPLATE' => $txt["template"], 'HEADER' => $txt["header"], 'SAVE' => $txt["submit"]));
        $template->pparse('body');
        break;
    case "se":
	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	$optionList = "";
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
	// If an action is asked (add or del), we do it now
        if ((isset($_GET["id"])))
        {
	    if ((isset($_GET["add"])))
		$internet->addDetail($_GET["id"],$_GET["type"],$_GET["add"]);
	    if ((isset($_GET["del"])))
	        $internet->delDetail($_GET["del"]);
          $_POST["id"] = $_GET["id"];
        }
        // And now, we create list internet available
	if ((isset($_POST["id"])))
	{
	    $selected = $internet->get($_POST["id"]);
	    if ($selected)
	    {
		// We now check if there is an update
		if (isset($_POST["available"]))
		{
		    // TODO
		}
		// We create all the listings (available / selected)
		$optionSelected = "<OPTION VALUE='" . $selected["idInternet"] . "'>" . $selected["name"] . "</option>";
		$selected_list = "";
		$excluded_list = "";
		$i=0;
		while($i!=(sizeof($selected["details"])))
		{
		    switch($selected["details"][$i]["type"])
		    {
			case "cat":
			    $sel = $category->get($selected["details"][$i]["detail"]);
			    $selected_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&del=" . $selected["details"][$i]["idInternetDetails"] . "'>" . $sel["name"] . "</a><br/>";
			    break;
			case "excludeCatalog":
			    $catalog->idCatalog = $selected["details"][$i]["detail"]; 
			    $sel = $catalog->get();
			    $excluded_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&del=" . $selected["details"][$i]["idInternetDetails"] . "'>" . $sel["name"] . "</a><br/>";
			    break;
		    	default:
			    break;
		    }
		    $i++;
		}
		// Creating available category list, without selected !
		$cat = $category->getList();
		$available_list = "";
		$i=0;
		while($i!=(sizeof($cat)))
		{
		    // We check if current category isn't already in selected list
		    $j=0;
		    $isSelected = false;
		    while($j!=(sizeof($selected["details"])))
		    {
			if ($selected["details"][$j]["detail"]==$cat[$i]["idCategory"])
			    $isSelected = true;
			$j++;
		    }
		    // If current category ins't in selected list, we add it here in available list
		    if (!($isSelected))
			$available_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&add=" . $cat[$i]["idCategory"] . "&type=cat'>" . $cat[$i]["name"] . "</a><br/>";
		    $i++;
		}
        	$template->assign_vars(array('ID' => $_POST["id"], 'SELECTED_LIST' => $selected_list,
					'AVAILABLE_LIST' => $available_list, 'OPTION_LIST_SELECTED' => $optionSelected, '' => $txt[""], '' => $txt[""]));

		// Creating available catalog (for exclusion), without selected !
		$cat = $catalog->getList();
		$available_exclude = "";
		$i=0;
		while($i!=(sizeof($cat)))
		{
		    // We check if current category isn't already in selected list
		    $j=0;
		    $isSelected = false;
		    while($j!=(sizeof($selected["details"])))
		    {
		        if ($selected["details"][$j]["detail"]==$cat[$i]["idCatalog"])
		            $isSelected = true;
		        $j++;
		    }
		    // If current category ins't in selected list, we add it here in available list
		    if (!($isSelected))
			$available_exclude .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&add=" . $cat[$i]["idCatalog"] . "&type=excludeCatalog'>" . $cat[$i]["name"] . "</a><br/>";
		    $i++;
		}
        	$template->assign_vars(array('EXCLUDED_LIST' => $excluded_list,	'EXCLUDE_LIST' => $available_exclude));
	    }
	}

        $template->set_filenames(array('body' => 'internet_select.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'SAVE' => $txt["submit"], 'SELECTION' => $txt["selection"],
                'OPTION_LIST' => $optionList, 'CHANGE' => $txt["change"], 'AVAILABLE_CATEGORY' => $txt["categorieList"],
								'SELECTED_CATEGORY' => $txt["seletedCategories"], 'EXCLUDE_CATALOG_LIST' => $txt["excludeCatalogList"], 'EXCLUDED_CATALOG_LIST' => $txt["excludedCatalogList"]));
        $template->pparse('body');
        break;
    case "sy":
        if (isset($_GET["sync"]))
        {
	    echo "Sélection en cours...";
	    if ($internet->update($_GET["sync"]))
	        $template->assign_vars(array('MSG' => $txt["syncOK"]));
	    else
	        $template->assign_vars(array('MSG' => $txt["syncKO"]));
        }
	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
	// If there is a selection
	if ((isset($_POST["id"])))
	{
	    $selected = $internet->get($_POST["id"]);
	    if ($selected)
	    {
		$optionListSelected = "<OPTION VALUE='" . $selected["idInternet"] . "'>" . $selected["name"] . "</OPTION>";
		$bookList = $internet->getBookList($_POST["id"]);
		$template->assign_vars(array('OPTION_LIST_SELECTED' => $optionListSelected, 'AVAILABLE_TOTAL_BOOKS' => $txt["totalBooks"],
						  'NB_BOOKS' => (sizeof($bookList)), 'SYNCHRO_NOW' => $txt["synchroLaunch"], 'ID' => $_POST["id"]));
		// TO CONTINUE
            }
	}

//	$list=$internet->getBookListByCat($catList);
//     	$internet->createTAB($list,"abebooks.txt");
//     	$internet->ftpUploadAbebooks("abebooks.txt");
        $template->set_filenames(array('body' => 'internet_sync.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'SYNCHRO_LAUNCH' => $txt["synchro"],
                'CHANGE' => $txt["change"], 'OPTION_LIST' => $optionList));
        $template->pparse('body');
        break;

    // Ebay
    case "eb":
	// If we need to add a book to ebay
	if ($_GET["addIdBook"]!="")
	{
	    if ($book->get($_GET["addIdBook"])) // If the book exists ?
	    {
		// We add the book on Ebay
		$id_ebay = $internet->addOnEbay($_GET["addIdBook"]);
		if ($id_ebay)
		    // Ok
		    $msg = $txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		    $msg = $txt["errorDuringAddingBookOnEbay"].$_GET["addIdBook"];		    
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to delete a book from ebay
	if ($_GET["delIdBook"]!="")
	{
	    if ($book->get($_GET["delIdBook"])) // If the book exists ?
	    {
		// We delete the book on Ebay
		if ($internet->delOnEbay($_GET["delIdBook"]))
		    // Ok
		    $msg = $txt["bookDeletedOnEbayOk"];
		else
		    $msg = $txt["bookDeletedOnEbayError"].$_GET["delIdBook"];		    
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to show Orders from ebay
	if ($_GET["getOrders"]!="")
	{
	    $internet->getEbayOrder();
	}

	// If we need to update a book to ebay
	if ($_GET["update"]!="")
	{
	    if ($book->get($_GET["update"])) // If the book exists ?
	    {
		// We update the book on Ebay
		if ($internet->updateEbayItemId($_GET["update"]))
		    // Ok
		    $msg = $txt["bookUpdatedOnEbayOk"].$book->data["ebayItemId"];
		else
		    $msg = $txt["bookUpdatedOnEbayError"].$_GET["update"];		    
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to show Orders from ebay
	if ($_GET["updateAll"]!="")
	{
	    echo "<table border='1'>";
	    // 1) New book to add on Ebay (onEbay = 1 and quantity > 0 and no ebayItemId yet)
	    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `ebayCategoryId`>0 AND `quantity`>0 AND `ebayItemId`='' AND `onEbay`='1' AND `internet`='1' LIMIT 0,".config("ebay_max_book_per_batch"),$nbNew);
	    echo "<tr><td>&nbsp;</td><td>NEW ($nbNew)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbNew)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["add"]."</td><td>";
		// We add the book on Ebay
		$id_ebay = $internet->addOnEbay($sql[$i]["idBook"]);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["errorDuringAddingBookOnEbay"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 2) Book to remove (because qty = 0)
	    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `quantity`=0 AND `ebayItemId`!='' LIMIT 0,".config("ebay_max_book_per_batch"),$nbSold);
	    echo "<tr><td>&nbsp;</td><td>SOLD ($nbSold)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbSold)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["del"]."</td><td>";
		// We add the book on Ebay
		$id_ebay = $internet->delOnEbay($sql[$i]["idBook"]);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookDeletedOnEbayOk"];
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["bookDeletedOnEbayError"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 3) Book to remove (because onEbay has been removed and an ebayItemId exist)
	    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `ebayItemId`!='' AND (`onEbay`='0'  OR `internet`='0') LIMIT 0,".config("ebay_max_book_per_batch"),$nbRemoved);
	    echo "<tr><td>&nbsp;</td><td>REMOVED ($nbRemoved)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbRemoved)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["del"]."</td><td>";
		// We add the book on Ebay
		$id_ebay = $internet->delOnEbay($sql[$i]["idBook"]);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookDeletedOnEbayOk"];
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["bookDeletedOnEbayError"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 4) Book to update (because ebayLastSync < modifyDate)
	    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `ebayItemId`!='' AND `ebayLastSync`<`modifyDate` LIMIT 0,".config("ebay_max_book_per_batch"),$nbUpdate);
	    echo "<tr><td>&nbsp;</td><td>UPDATE ($nbUpdate)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbUpdate)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["update"]."</td><td>";
		// We add the book on Ebay
		$id_ebay = $internet->updateEbayItemId($sql[$i]["idBook"]);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["errorDuringAddingBookOnEbay"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // End of update  
	    echo "</table>";
	    
	    // checking if there is more update to do after, we put a javascript to auto update the page
	    if (($nbNew+$nbSold+$nbRemoved+$nbUpdate) > 0)
		echo "<script type=\"text/JavaScript\">
<!--
setTimeout(\"location.href = 'http://". $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "';\",3000);
-->
</script>";
	    else
		$msg = $txt["AllBookUpdatedOnEbayOk"];
	    	    
	}
	
	// Form to display
        $template->set_filenames(array('body' => 'internet_ebay.tpl'));
        $template->assign_vars(array('EBAY' => $txt["ebay"],
                'ADD' => $txt["add"], 'ADD_BOOK_FROM_ID' => $txt["addIdBookOnEbay"], 
                'DEL_BOOK_FROM_ID' => $txt["delIdBookOnEbay"], 'DEL' => $txt["del"],
                'SYNC_ALL_BOOKS_ON_EBAY' => $txt["updateAllBooksOnEbay"], 'UPDATE' => $txt["update"], 'UPDATE_BOOK_FROM_ID' => $txt["bookUpdatedOnEbay"],
                'GET_EBAY_ORDERS' => $txt["getEbayOrders"], 'MSG' => $msg));
        $template->pparse('body');
	break;
    case "ex":
        if ($_GET["resetoninternet"]==1)
        {
	    $book->resetOnInternet();
	    $msg .= $txt["catalogExclusionReseted"];
        }
        if (isset($_POST["removecatalogoninternet"]))
        {
	    $book->disableCatalogOnInternet($_POST["removecatalogoninternet"]);
	    $msg .= $txt["catalogExcluded"];
        }
	// Creating available catalog (for exclusion), without selected !
	$cat = $catalog->getList();
	$excluded = "";
	$i=0;
	while($i!=(sizeof($cat)))
	{
	    $optionList .= "<OPTION VALUE='" . $cat[$i]["idCatalog"] . "'>" . $cat[$i]["name"] . "</OPTION>";
	    if ($cat[$i]["onInternet"]==0) // We find an excluded catalog
		$excluded .= $cat[$i]["name"] ."<br />";
	    $i++;
	}

        $template->set_filenames(array('body' => 'internet_exclude.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"],
                'EXCLUSION' => $txt["exclusion"], 'RESET_ON_INTERNET_CATALOG' => $txt["resetOnInternetCatalog"], 
                'CHOOSE_CATALOG_TO_DISABLE' => $txt["chooseCatalogToDisable"], 'EXCLUDED_LIST' => $excluded,
                'OPTION_LIST_CATALOG' => $optionList, 'EXCLUDED_CATALOG' => $txt["excludedCatalogList"],
                'CHANGE' => $txt["change"], 'MSG' => $msg));
        $template->pparse('body');
        
        break;
    default:
        $template->set_filenames(array('body' => 'internet_home.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'EDITIONS_HOME' => ""));
        $template->pparse('body');
        break;
}

?>
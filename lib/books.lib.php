<?php
include "class/book.class.php";
$book = new Book();
include "class/category.class.php";
$category = new Category();
include "class/catalog.class.php";
$catalog = new Catalog();

switch ($a) {
    case "cr": 
        // Display create header
        $template->set_filenames(array('bookCreateHeader' => 'book_create_header.tpl'));
        $template->assign_vars(array('BOOK_CREATE' => $txt["createBook"], 'CREATEFROMISBN' => $txt["returnIsbn"]));
	
        // Filling category data
	$catList=$category->getList();
        $cat="<SELECT NAME='category'><OPTION VALUE='' SELECTED></OPTION>";
        $i=0;
        while($i!=(sizeof($catList)))
        {
            if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
	    else $name=$catList[$i]["name"];
	    $cat.="<OPTION VALUE='".$catList[$i]["idCategory"]."'>".$name."</OPTION>";
	    $i++;
	}
        $cat.="</SELECT>";
        
        // Generating shorcuts to add to an open catalog
	$catUnlocked=$catalog->getListUnlocked();
	$inCatalog.= $txt["addToCatalog"] . " :<br /><SELECT NAME='addInCatalog'><OPTION VALUE='' SELECTED></OPTION>";
	$j=0;
	while($j!=(sizeof($catUnlocked)))
	{
	    $catalog->idCatalog=$catUnlocked[$j]["idCatalog"];
	    $chap=$catalog->getChapter();
	    $jj=0;
	    while($jj!=(sizeof($chap)))
	    {
		$inCatalog .= "<OPTION VALUE='" . $chap[$jj]["idCatalog"] . "-" . $chap[$jj]["orderInCatalog"] . "'>" . $catUnlocked[$j]["name"] . " - " . substr($chap[$jj]["name"], 0, 30) . "</OPTION>";
		$jj++;
	    }
	    $j++;
        }
	$inCatalog .= "</SELECT>";
				
        // Display empty mask
        $template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
        $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 'BOOKS_PHOTOS' => $txt["photos"],
		'LANGUAGE_V' => $book->getLanguageHtmlForm(config("defaultBookLanguage")),
		'BINDING_V' => $book->getBindingHtmlForm(),
		'FORMAT_V' => $book->getFormatHtmlForm(),
		'CONDITION_V' => $book->getConditionHtmlForm(),
		'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1),
		'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2),
		'IN_CATALOG_V' => $inCatalog,
                'ONEBAY_V' => "checked='checked'",
                'INTERNET_V' => "checked='checked'",
                'QUANTITY_V' => "1",
                'EBAYSHIPPINGCOST_V' => config("ebayShippingCost"),
                'EBAYSHIPPINGCOSTINTERNATIONAL_V' => config("ebayShippingCostInternational"),
                'CATEGORY_V' => $cat,
                'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                'BOOKS_SUBMIT' => $txt["submit"]));

        if ($_POST["sub"] == "ok")
	{ // If form is filled...
            if (($_POST["title"] AND $_POST["author"]))
	    { // Check for correct entry
                // Ok, we're recording the new book
                $book->assignData($_POST);
                $add=$book->add();
                if ($add > -1)
                {
                    $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateOk"]));
		    if ($_POST["category"]!="")
                    {
			$book->idBook=$add;
			$book->addCategory($_POST["category"]);
		    }
		    if ($_POST["addInCatalog"]!="")
		    {
			$addInCatalog=explode('-', $_POST["addInCatalog"]);
                	$catalog->idCatalog=$addInCatalog[0];
                	$catalog->addBook($book->idBook,$addInCatalog[1]+1);
                	$msg.=$txt["addInCatalogDone"]."<br />";
		    }
		    if ($_POST["opt1"]=="fromisbnimport")
			$template->assign_block_vars('isbnImportReturn', array());
		}
                else
		    $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateDbError"]));
                $template->pparse('bookCreateHeader');
            }
	    else
	    { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('AUTHOR_V' => $_POST["author"], 'TITLE_V' => $_POST["title"], 'DESCRIPTION_V' => $_POST["description"], 'PRICE_V' => $_POST["price"], 'PRICE_EBAY_V' => $_POST["price_ebay"], 'PRICE_AMAZON_V' => $_POST["price_amazon"], 'QUANTITY_V' => $_POST["quantity"], 'LOCATION_V' => $_POST["location"], 'NOTES_V' => $_POST["notes"], 'ISBN_V' => $_POST["isbn"], 'ONEBAY_V' => "checked='checked'", 'INTERNET_V' => "checked='checked'", 'EDITOR_V' => $_POST["editor"], 'COLLECTION_V' => $_POST["collection"], 'PUBLISHDATE_V' => $_POST["publishDate"], 'PUBLISHLOCATION_V' => $_POST["publishLocation"], 'PAGENUMBER_V' => $_POST["pageNumber"],
                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"])); 
                // Display mask with error message
                $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateMissedField"]));
                $template->pparse('bookCreateHeader');
                $template->pparse('bookCreateMask');
            } 
        }
	else
	{ // form not filled... New form
	    if (isset($_GET["fromisbn"]))
	    {
		// If the page has been called by ISBN Import, we search online for infos
		$result = $book->IsbnSearch($_GET["fromisbn"]);
		if ($result["found"])
		{
		    // Book found online !
	            $template->assign_vars(array('AUTHOR_V' => $result["author"], 'TITLE_V' => $result["title"], 'DESCRIPTION_V' => $result["description"], 'ISBN_V' => $result["isbn"], 'ONEBAY_V' => "checked='checked'", 'INTERNET_V' => "checked='checked'", 'EDITOR_V' => $result["editor"], 'PUBLISHDATE_V' => $result["publishDate"], 'PUBLISHLOCATION_V' => $result["publishLocation"], 'PAGENUMBER_V' => $result["pageNumber"], 'OPT1_V' => "fromisbnimport", 'IMAGEFROMURL_V' => $result["image"],
                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"])); 
	    
		}
	    }
            // display empty mask
            $template->pparse('bookCreateHeader');
            $template->pparse('bookCreateMask');
        } 
        break;
    case "vi":
        switch ($sa)
	{
            case "re": // Display search results
//                if (($_POST["sub"] == "ok") OR ($_GET["sub"] == "ok")) // If form is filled...
                if ($_POST['id'] != "")
                    $id = $_POST['id'];
                else
		    $id = $_GET['id'];
		// We check the start & row attributes...
                if (($_GET["start"]!="") AND ($_GET["row"]!=""))
                {
                    $start=$_GET["start"];
                    $row=$_GET["row"];
                    $_SESSION["start"]=$start;
                    $_SESSION["row"]=$row;
                }
                else
                {
                    if (($_SESSION["start"]!="") AND ($_SESSION["row"]!=""))
                    {
		        $start=$_SESSION["start"];
                        $row=$_SESSION["row"];
                    }
                    else
                    {
                        $start=0;
                        $row=$config["maxBookRow"];
                    }
                }
                if ($_GET["del"] == "1")
		{ // Delete the record whith the id
		    $book->idBook=$id;
                      if ($book->del())
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseDone"])));
                      else
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseNotOk"])));
                } 

                if (($_GET["research"] == "ok") or ($_POST["research"] == "ok"))
                    $search=$book->search("cache","",$start,$row);
                else
		{
                    $book->assignData($_POST);
		    $search=$book->search("","",$start,$row);
		}
		$nbResult=$search["nb"];

		$prenav= $txt["nbResultDisplayed"] . $start . " - " . ($start+$row) . " ";
		if ($start!=0)
		    $prenav.=" | <a href='?m=bo&a=vi&sa=re&research=ok&start=0&row=" . $row . "'><img src='template/".$config["template"]."/images/left-full.png' alt='" . $txt["previous"] . "' border='0' width=24></a> <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/left.png' alt='" . $txt["previous"] . "' border='0' width=24> " . $txt["previous"] . "</a>";
		if (($row+$start)<$nbResult)
		    $prenav.=" | <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start+$row) . "&row=" . $row . "'>" . $txt["next"] . " <img src='template/".$config["template"]."/images/right.png' alt='" . $txt["next"] . "' border='0' width=24></a> <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($nbResult-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/right-full.png' alt='" . $txt["next"] . "' border='0' width=24></a>";
                    
                // ROW NUMBER MODIFICATION
                if (isset($_GET['start'])) $start = $_GET['start'];
                    elseif (!(isset($start))) $start = 0;
                if (isset($_GET['row'])) $row = $_GET['row'];
                    else $row = $config["maxBookRow"];
                $nav = "<form action='?m=bo&a=vi&sa=re&research=ok" . $_GET['start'] . "' method='GET'>
                    <INPUT TYPE='hidden' NAME='m' VALUE='bo'>
                    <INPUT TYPE='hidden' NAME='a' VALUE='vi'>
                    <INPUT TYPE='hidden' NAME='sa' VALUE='re'>
                    <INPUT TYPE='hidden' NAME='research' VALUE='ok'>
                    <INPUT TYPE='hidden' NAME='start' VALUE='" . $start . "'>
                    $prenav | " . $txt["nbPerPage"] . " <INPUT TYPE='text' NAME='row' SIZE='3' VALUE='" . $row . "'></form>";
                    // $config["maxBookRow"]
                  
                //$search = $babase->DbSelect($sqlSearch, $nbResult);
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display search table header
                $template->set_filenames(array('bookDisplaySearchResultsHeader' => 'book_display_search_results_header.tpl'));
                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_QUANTITY' => $txt["quantity"],  'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_MODIFY' => "<img src='template/".$config["template"]."/images/edit.png' alt='" . $txt["modify"] . "' border='0' width='32'>", 'BOOKS_ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='" . $txt["erase"] . "' border='0' width='32'>", 'NAV' => $nav));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookDisplaySearchResultsHeader'); 
                // Display search results body
                $template->set_filenames(array('bookDisplaySearchResultsBody' => 'book_display_search_results_body.tpl'));
                $i = 0;
                while ($i != (sizeof($search)-1))
		{
		    // Looking for photos to display
		    $photos = $book->getPhoto($search[$i]["idBook"]);
		    $template->_tpldata["bookPhoto."] = array();
		    if (sizeof($photos) > 0)
		    {
		        $template->assign_block_vars("bookPhoto", array(
				'FILEMINIATURE' => $photos[0]["fileThumbnail"],
				'DESCRIPTION' => $photos[0]["description"],
				));
		    } 
                    // trunk the description
                    $description = substr($search[$i]["description"], 0, 30) . "...";
                    $select="<input type='checkbox' name='selected_book[]'' value='".$search[$i]["idBook"]."' id='checkbox_book_".$search[$i]["idBook"]."' />";
                    $template->assign_vars(array('BOOKS_RESULT_MODIFY' => "?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($i+$start), 'BOOKS_RESULT_ERASE' => "?m=bo&a=vi&sa=re&del=1&sub=ok&research=ok&id=" . $search[$i]["idBook"],
                                'BOOKS_RESULT_AUTHOR' => $search[$i]["author"], 'BOOKS_RESULT_TITLE' => "<label for='checkbox_book_".$search[$i]["idBook"]."'>".$search[$i]["title"]."</label>", 'BOOKS_RESULT_DESCRIPTION' => $description, 'BOOKS_RESULT_PRICE' => $search[$i]["price"], 'BOOKS_RESULT_QUANTITY' => $search[$i]["quantity"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_RESULT_SELECT' => $select,
                                'BOOK_CONFIRM_ERASE' => $txt["bookConfirmErase"]));
                    $template->pparse('bookDisplaySearchResultsBody');
                    $i++;
                } 
                // Footer
                $template->set_filenames(array('bookDisplaySearchResultsFooter' => 'book_display_search_results_footer.tpl'));
                $template->assign_vars(array('SELECT_ALL' => $txt["selectAll"], 'UNSELECT_ALL' => $txt["unselectAll"], 'FOR_SELECTION' => $txt["forSelection"]));
                $template->pparse('bookDisplaySearchResultsFooter');
                break;
            case "mo": // Display Modify form
                if ($_POST['id'] != "")
                    $id = $_POST['id'];
                else
                    $id = $_GET['id'];
                if (isset($_GET['fromcatalog']))
                {
		    // We want to display a book from a catalogData list
		    $id=$_GET['id'];
		    $fromresultsearch = "&fromcatalog=" . $_GET['fromcatalog'];
		    $returnToResult = "?m=ca&a=ed&sa=vi&idCatalog=" . $_GET['fromcatalog'];
		    // TODO: Create an 'else' to display error
                }
                $book->idBook=$id;
                if (isset($_GET["delfromcatalog"]))
                {
		    $catalog->idCatalog=$_GET["delfromcatalog"];
		    if ($catalog->delIdBook($book->idBook))
			$msg.=$txt["delInCatalogOk"]."<br />";
		    else
		    	$msg.=$txt["delInCatalogNotOk"]."<br />";
		}
                if (isset($_GET["delcategory"]))
                {
		    if ($book->delCategory($_GET["delcategory"]))
			$msg.=$txt["delCategoryDone"]."<br />";
		}
                if (isset($_GET["qtyAdd"]))
                {
		    if ($book->addQty("manual",1,$book->idBook))
			$msg.=$txt["addQtyDone"]."<br />";
		}
                if (isset($_GET["qtyDel"]))
                {
		    if ($book->delQty("manual",1,$book->idBook))
			$msg.=$txt["delQtyDone"]."<br />";
		}
                if (isset($_GET["delphoto"]))
                {
		    if ($book->delPhoto($_GET["delphoto"]))
			$msg.=$txt["delPhotoDone"]."<br />";
		}
		if (isset($_FILES['photo']['tmp_name']))
		{
		    // Adding picture
		    if ($book->addPhoto($book->idBook))
			$msg.=$txt["addPhotoDone"]."<br />";
		}
                if (isset($_GET["fromisbn"]))
                {
		    if ($book->addQty("ISBN Import",1,$book->idBook))
			$msg.=$txt["addQtyDone"]."<br />";
		    $template->assign_vars(array('OPT1_V' => "fromisbnimport"));
		    $template->assign_block_vars('isbnImportReturn', array());
		}
                if ($_POST["sub"] == "ok")
		{ // Update the current record
                    $book->assignData($_POST);
                    $book->update();
              	    if ($_POST["category"]!="")
		    {
			if ($book->addCategory($_POST["category"]))
			    $msg.=$txt["addCategoryDone"]."<br />";
			else
			    $msg.=$book->viewError($book->error)."<br />";
		    }
		    if ($_POST["addInCatalog"]!="")
		    {
			$addInCatalog=explode('-', $_POST["addInCatalog"]);
                	$catalog->idCatalog=$addInCatalog[0];
                	$catalog->addBook($book->idBook,$addInCatalog[1]+1);
                	$msg.=$txt["addInCatalogDone"]."<br />";
		    }
                    $msg.=$txt["bookModificationDone"]."<br />";
                }
		$template->assign_vars(array('BOOK_MSG' => $msg, 'CREATEFROMISBN' => $txt["returnIsbn"]));
		// To get the current result record
                if ($_GET['fromsearch']==1)
                {
		    // We want to display a book from a search result
		    if (isset($_GET['numres']))
		    {
			$data=$book->search("cache","",$_GET['numres'],1);
		        $id=$data[0]["idBook"];
		    }
		    if ($_GET['numres']>0)
			$navLeft="<a href='?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($_GET['numres']-1) . "'><img src='template/".$config["template"]."/images/left.png' alt='" . $txt["previous"] . "' border='0' width=16>" . $txt["previous"] . "</a>";
		    else
			$navLeft="";
		    $navRight="<a href='?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($_GET['numres']+1) . "'>" . $txt["next"] . "<img src='template/".$config["template"]."/images/right.png' alt='" . $txt["next"] . "' border='0' width=16></a>";
		    $nav=$navLeft . " | " . $navRight;
		    $fromresultsearch = "&fromsearch=" . $_GET['fromsearch'] . "&numres=" . $_GET['numres'];
		    $returnToResult = "?m=bo&a=vi&sa=re&research=ok";
		    // TODO: Create an 'else' to display error
                }
                // Get book data
                $book->idBook=$id;
                $record=$book->get($id);
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display Modify txt header
                $template->set_filenames(array('bookModify' => 'book_modify.tpl'));
                $template->assign_vars(array('BOOKS_DATABASE' => $txt["bookDatabase"], 'BOOKS_MODIFY_TXT' => $txt["bookModification"])); 
                // Re-search cache form
                $template->assign_vars(array('BOOKS_SEARCH_CACHE_SUBMIT' => $txt["bookSearchCacheSubmit"], 'BOOKS_SEARCH_FORM' => $returnToResult));
                
		// Fetching Photo data
                $photos=$book->getPhoto($id);
		// Assigning row data
                $i=0;
		$template->_tpldata["bookPhoto"] = array();
                while($i!=sizeof($photos))
                {
		    $template->assign_block_vars('bookPhoto', array(
				'ID' => $photos[$i]["idBookPhoto"],
				'FILE' => $photos[$i]["file"],
				'FILEMINIATURE' => $photos[$i]["fileThumbnail"],
				'DESCRIPTION' => $photos[$i]["description"],
				'DELETEURL' => "?m=bo&a=vi&sa=mo&id=".$book->idBook."&delphoto=".$photos[$i]["idBookPhoto"]."$fromresultsearch"
				));
		    $i++;
		}
			


                // Fetch category Data
                $bookcategory=$book->listCategory();
                $cat="<table border=1>";
                $i=0;
                while($i!=sizeof($bookcategory))
                {
		    $cat.="<tr><td><a href='?m=bo&a=vi&sa=mo&id=".$book->idBook."&delcategory=".$bookcategory[$i]["idCategory"]."$fromresultsearch' onclick=\"return confirmLink(this, '".$txt["bookInCatConfirmErase"]."')\"><img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0' width='16'></a></td><td>".$bookcategory[$i]["name"]."</td></tr>";
		    $i++;
		}
		$cat.="</table>".$txt["addCategory"]."<br />";
		// To add a new category...
		$catList=$category->getList();
		$cat.="<SELECT NAME='category' onchange=\"this.form.submit();\"><OPTION VALUE='' SELECTED></OPTION>";
        	$i=0;
		while($i!=(sizeof($catList)))
        	{
		    if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
		    else $name=$catList[$i]["name"];
		    $cat.="<OPTION VALUE='".$catList[$i]["idCategory"]."'>".$name."</OPTION>";
		    $i++;
		}
        	$cat.="</SELECT>";

		// We search for any parution in any catalog
		$catalogList = $book->listCatalog();
                $inCatalog = "<table border=1>";
                $i=0;
                while($i!=sizeof($catalogList))
                {
		    if (strlen($catalogList[$i]["name"])>20) $name = substr($catalogList[$i]["name"], 0, 20) . "..."; else $name=$catalogList[$i]["name"];
		    $catalog->idCatalog=$catalogList[$i]["idCatalog"];
		    if (!($catalog->islocked()))
                    $del="<a href='?m=bo&a=vi&sa=mo&id=".$book->idBook."&delfromcatalog=".$catalogList[$i]["idCatalog"]."$fromresultsearch' onclick=\"return confirmLink(this, '".$txt["bookInCatalogConfirmErase"]."')\"><img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0' width='16'></a>";
		    else
		        $del="";
		    $inCatalog .= "<tr><td>$del" . $name . " (" . date("d/m/Y",$catalogList[$i]["date"]) . ")</td></tr>";
		    $i++;
		}
		// Generating shorcuts to add to an open catalog
		$catUnlocked=$catalog->getListUnlocked();
		$inCatalog.="</table>" . $txt["addToCatalog"] . " :<br /><SELECT NAME='addInCatalog' onchange=\"this.form.submit();\"><OPTION VALUE='' SELECTED></OPTION>";
		$j=0;
		while($j!=(sizeof($catUnlocked)))
		{
		    $catalog->idCatalog=$catUnlocked[$j]["idCatalog"];
		    $chap=$catalog->getChapter();
		    $jj=0;
		    while($jj!=(sizeof($chap)))
		    {
			$inCatalog .= "<OPTION VALUE='" . $chap[$jj]["idCatalog"] . "-" . $chap[$jj]["orderInCatalog"] . "'>" . $catUnlocked[$j]["name"] . " - " . substr($chap[$jj]["name"], 0, 30) . "</OPTION>";
			$jj++;
		    }
		    $j++;
                }
		$inCatalog .= "</SELECT>";
		
		// OnInternet value
		if ($record["internet"]==1)
		    $onInternet = "checked='checked'";
		else
		    $onInternet = "";								
		// onEbay value
		if ($record["onEbay"]==1)
		    $onEbay = "checked='checked'";
		else
		    $onEbay= "";
		// Ebay Add/del/update
		if ($record["ebayItemId"]=="")
		    $ebayAdd = "<a href='?m=in&a=eb&addIdBook=".$record["idBook"]."' target='_blank'>" . $txt["add"] . "</a>";
		else
		{
		    $ebayDel = "<a href='?m=in&a=eb&delIdBook=".$record["idBook"]."' target='_blank'>" . $txt["del"] . "</a>";
		    $ebayUpdate = "<a href='?m=in&a=eb&update=".$record["idBook"]."' target='_blank'>" . $txt["update"] . "</a>";
		}
		// Assign Data
		$template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_PHOTOS' => $txt["photos"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_IN_CATALOG' => $txt["bookInCatalog"], 'BOOKS_MODIFYDATE' => $txt["bookModifyDate"], 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 
                'BOOKS_FORM' => "?m=bo&a=vi&sa=mo&id=$id".$fromresultsearch, // FORM Target URL <----------------------
                'BOOKS_SUBMIT' => $txt["submit"], 'ID_V' => $record["idBook"], 'AUTHOR_V' => $record["author"], 'TITLE_V' => $record["title"], 'DESCRIPTION_V' => $record["description"], 'PRICE_V' => $record["price"], 'PRICE_EBAY_V' => $record["price_ebay"], 'PRICE_AMAZON_V' => $record["price_amazon"], 'QUANTITY_V' => $record["quantity"], 'NOTES_V' => $record["notes"], 'INTERNET_V' => $onInternet, 'LOCATION_V' => $record["location"], 'MODIFYDATE_V' => date("d/m/Y H:i", $record["modifyDate"]), 'CATEGORY_V' => $cat, 'IN_CATALOG_V' => $inCatalog, 'ISBN_V' => $record["isbn"], 'ONEBAY_V' => $onEbay, 'EDITOR_V' => $record["editor"], 'COLLECTION_V' => $record["collection"], 'PUBLISHDATE_V' => $record["publishDate"], 'PUBLISHLOCATION_V' => $record["publishLocation"], 'PAGENUMBER_V' => $record["pageNumber"], 'EBAYSHIPPINGCOST_V' => $record["ebayShippingCost"], 'EBAYSHIPPINGCOSTINTERNATIONAL_V' => $record["ebayShippingCostInternational"], 'EBAYITEMID_V' => $record["ebayItemId"], 'EBAYLASTSYNC_V' => date("d/m/Y H:i", $record["ebayLastSync"]), 'LANGUAGE_V' => $book->getLanguageHtmlForm($record["language"]), 'BINDING_V' => $book->getBindingHtmlForm($record["binding"]), 'FORMAT_V' => $book->getFormatHtmlForm($record["format"]), 'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1,$record["ebayCategoryId"]), 'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2,$record["ebayCategoryIdSecondary"]), 'EBAYADD_V' => $ebayAdd, 'EBAYDEL_V' => $ebayDel, 'EBAYUPDATE_V' => $ebayUpdate, 'CONDITION_V' => $book->getConditionHtmlForm($record["condition"]), 'NAV' => $nav));
                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"]));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookModify');
                $template->pparse('bookCreateMask');
                break;
            default: // Display default search screen
                // We reset sessions variable for start & row attributes
                $_SESSION["start"]="";
                $_SESSION["row"]="";
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display search txt
                $template->set_filenames(array('bookSearchForm' => 'book_search_form.tpl'));
                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'BOOKS_SEARCH_TXT' => $txt["bookSearchTxt"]));

                $template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
                $catList=$category->getList();
                $cat="<SELECT NAME='category'><OPTION VALUE='' SELECTED></OPTION>";
                $i=0;
                while($i!=(sizeof($catList)))
                {
		    if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
		    else $name=$catList[$i]["name"];
		    $cat.="<OPTION VALUE='".$catList[$i]["idCategory"]."'>".$name."</OPTION>";
		    $i++;
		}
                $cat.="</SELECT>";
                // We're creating the catalog list
		$catList=$catalog->getList();
		$inCatalog.="<SELECT NAME='inCatalog'><OPTION VALUE='' SELECTED></OPTION>";
		$j=0;
		while($j!=(sizeof($catList)))
		{
		    $inCatalog .= "<OPTION VALUE='" . $catList[$j]["idCatalog"] . "'>" . $catList[$j]["name"] . "</OPTION>";
		     $j++;
                }
		$inCatalog .= "</SELECT>";
		// Display search page                
                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'CATEGORY_V' => $cat, 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 'BOOKS_PHOTOS' => $txt["photos"],
			'LANGUAGE_V' => $book->getLanguageHtmlForm(),
			'BINDING_V' => $book->getBindingHtmlForm(),
			'FORMAT_V' => $book->getFormatHtmlForm(),
			'CONDITION_V' => $book->getConditionHtmlForm(),
			'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1),
			'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2),                       
			'INTERNET_SEARCH' => " (active <input type='checkbox' name='internetSearch' value='1'>)",
			'ONEBAY_V' => "checked='checked'",
			'BOOKS_IN_CATALOG' => $txt["catalogs"], 'IN_CATALOG_V' => $inCatalog,
                        'QUANTITY_NOT_NULL' => $txt["notNull"]." <input type='checkbox' name='quantitynotnull' value='1'>",
                        'BOOKS_FORM' => "?m=bo&a=vi&sa=re", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["search"]));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookSearchForm');
                $template->pparse('bookCreateMask');
                break;
        } 
        break;
    case "io": 
        // Display template
        $template->set_filenames(array('bookIsbnOnlineSearch' => 'book_isbn_search_online.tpl'));
        $template->assign_vars(array('BOOK_ISBN_ONLINE_SEARCH' => $txt["IsbnOnlineSearch"], 'ISBN' => $txt["isbn"], 'SEARCH_SUBMIT' => $txt["search"], 'ISBN_FORM' => "?m=bo&a=io", 'AUTHOR' => $txt["author"], 'TITLE' => $txt["title"], 'EDITOR' => $txt["editor"], 'PUBLISHDATE' => $txt["publishDate"], 'PAGENUMBER' => $txt["pageNumber"], 'DESCRIPTION' => $txt["description"], 'IMAGE' => $txt["photos"], 'INFOLINK' => $txt["infoLink"]));
	if (isset($_POST["isbn"]))
	{
	    // An ISBN has been submitted
	    $result = $book->IsbnSearch($_POST["isbn"]);
	    if ($result["found"])
		// Book found !
		$template->assign_block_vars('isbnResult', array('ISBN_V' => $result["isbn"], 'AUTHOR_V' => $result["author"], 'TITLE_V' => $result["title"], 'EDITOR_V' => $result["editor"], 'PUBLISHDATE_V' => $result["publishDate"], 'PAGENUMBER_V' => $result["pageNumber"], 'DESCRIPTION' => $result["description"], 'IMAGE_V' => $result["image"], 'INFOLINK_V' => $result["infoLink"]));
	    else
		$template->assign_vars(array('ISBN_MSG' => $txt["isbnNotFound"]));
	}	
        $template->pparse('bookIsbnOnlineSearch');
	break;
    case "ci": 
        // Display template
        $template->set_filenames(array('bookCreateFromIsbn' => 'book_create_from_isbn.tpl'));
        $template->assign_vars(array('BOOK_CREATE_FROM_ISBN' => $txt["createFromIsbn"], 'ISBN' => $txt["isbn"], 'SEARCH_SUBMIT' => $txt["search"], 'ISBN_FORM' => "?m=bo&a=ci"));
	
	// If ISBN has been posted
	if ($_POST["createfromisbn"]==1)
	{
	    if (isset($_POST["isbn"]))
	    {
		// An ISBN has been submitted
		$search = $book->getFromIsbn($_POST["isbn"]);
		if ($search)
		{
		    // Book is already existing in database
		    $template->assign_vars(array('ISBN_MSG' => '<script type="text/javascript">
<!--
window.location = "http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?m=bo&a=vi&sa=mo&id=' . $search["idBook"] . '&fromisbn=' . $_POST["isbn"] . '"
//-->
</script>'));
		}
		else
		{
		    // Book isn't already in databse
		    $result = $book->IsbnSearch($_POST["isbn"]);
		    if ($result["found"])
			// Book found online ! Redirection on creating page
			$template->assign_vars(array('ISBN_MSG' => '<script type="text/javascript">
<!--
window.location = "http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?m=bo&a=cr&fromisbn=' . $_POST["isbn"] . '"
//-->
</script>'));
		    else
			// Book not found
			$template->assign_vars(array('ISBN_MSG' => $txt["isbnNotFound"]));

		}
	    }	
	}
	
        $template->pparse('bookCreateFromIsbn');
	break;
    default:
        $template->set_filenames(array('body' => 'book_home.tpl'));
        $stats=$book->stats();
        $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"],
                'BOOK_HOME' => $txt["booksHome"],
                'STATS' => $txt["statistics"],
                'TOTAL_BOOKS' => $txt["totalBooks"],
                'TOTAL_BOOKS_VALUE' => $stats["totalBooks"],
                'TOTAL_BOOKS_AVAILABLE' => $txt["totalBooksAvailable"],
                'TOTAL_BOOKS_AVAILABLE_VALUE' => $stats["totalBooksAvailable"],
                'TOTAL_PRICE_AVAILABLE' => $txt["totalPriceAvailable"],
                'TOTAL_PRICE_AVAILABLE_VALUE' => $stats["totalPriceAvailable"],
                'AVERAGE_PRICE_AVAILABLE' => $txt["averagePriceAvailable"],
                'AVERAGE_PRICE_AVAILABLE_VALUE' => $stats["averagePriceAvailable"],
                'BOOK' => $txt["book"],
                'verProg' => $txt["verProg"]));
        $template->pparse('body');
        break;
} 

?>

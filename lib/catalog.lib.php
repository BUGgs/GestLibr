<?php
include "class/book.class.php";
$book = new Book();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/category.class.php";
$category=New Category();

switch ($a) {
    case "cr":
        // Display create header
        $template->set_filenames(array('catalogCreateHeader' => 'catalog_create_header.tpl'));
        $template->assign_vars(array('CATALOG_CREATE' => $txt["catalogsCreate"]));
        // Display empty mask
        $template->set_filenames(array('catalogMainMask' => 'catalog_main_mask.tpl'));
        $template->assign_vars(array('CATALOG_NAME' => $txt["name"], 'CATALOG_DESCRIPTION' => $txt["description"], 'CATALOG_BYPASSNOQUANTITY' => $txt["bypassNoQuantity"],
                'CATALOG_FORM' => "?m=ca&a=cr", // FORM Target URL <----------------------
                'CATALOG_SUBMIT' => $txt["submit"]));

        if ($_POST["sub"] == "ok") { // If form is filled...
            if ($_POST["name"])
						{ // Check for correct entry
                // Ok, we're recording the new catalog
                if ($catalog->create($_POST["name"],$_POST["description"],$_POST["bypassnoquantity"]))
                	 $template->assign_vars(array('CATALOG_MSG' => $txt["catalogCreateOk"]));
                else $template->assign_vars(array('CATALOG_MSG' => $txt["catalogCreateDbError"]));
                $template->pparse('catalogCreateHeader');
            } else { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('NAME_V' => $_POST["name"], 'DESCRIPTION_V' => $_POST["description"], 'BYPASSNOQUANTITY_V' => $_POST["bypassNoQuantity"]));
                // Display mask with error message
                $template->assign_vars(array('CATALOG_MSG' => $txt["catalogCreateMissedField"]));
                $template->pparse('catalogCreateHeader');
                $template->pparse('catalogMainMask');
            }
        } else { // form not filled... New form
            // display empty mask
            $template->pparse('catalogCreateHeader');
            $template->pparse('catalogMainMask');
        }
        break;
    case "ed":
      $msg="";
	  if ($_POST['idCatalog'] != "")
       	$catalog->idCatalog = $_POST['idCatalog'];
      else
       	$catalog->idCatalog = $_GET['idCatalog'];
	  if (isset($_GET['sortintochapter'])) // We want to sort all books into chapter
	  {
	    $catalog->sortIntoChapter();
        $msg.="<br />".$txt["sortIntoChapterDone"];
	  }
	  if (isset($_GET['up'])) // We want to up a record in the global order
	  {
	    if ($catalog->up($_GET['up']))
     	  $msg.="<br />".$txt["recordUp"];
		else
          $msg.="<br />".$txt["error"];
	  }
	  if (isset($_GET['down']))  // We want to down a record in the global order
	  {
	    if ($catalog->down($_GET['down']))
          $msg.="<br />".$txt["recordDown"];
		else
          $msg.="<br />".$txt["error"];
	  }
	  if (isset($_GET['delete']))  // We want to delete a record
	  {
	    if ($catalog->delData($_GET['delete']))
          $msg.="<br />".$txt["deleteRecordInCatalogOk"];
		else
          $msg.="<br />".$txt["error"];
	  }
      switch ($sa)
      {
      case "vi":
      // list all content from a catalog
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
        // Set NAVigation tag
        $nbCatalogData=$catalog->nbCatalogData();
        $nav = $txt["nbResultDisplayed"] . $start . " - " . ($start+$row) . " ";
				if ($start!=0)
				{
				  $nav.=" | <a href='?m=ca&a=ed&sa=vi&idCatalog=" . $catalog->idCatalog . "&start=0&row=" . $row . "'><img src='template/".$config["template"]."/images/left-full.png' alt='" . $txt["previous"] . "' border='0' width=24></a> <a href='?m=ca&a=ed&sa=vi&idCatalog=" . $catalog->idCatalog . "&start=" . ($start-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/left.png' alt='" . $txt["previous"] . "' border='0' width=24> " . $txt["previous"] . "</a>";
        }
				if (($row+$start)<$nbCatalogData)
				{
				  $nav.=" | <a href='?m=ca&a=ed&sa=vi&idCatalog=" . $catalog->idCatalog . "&start=" . ($start+$row) . "&row=" . $row . "'>" . $txt["next"] . " <img src='template/".$config["template"]."/images/right.png' alt='" . $txt["next"] . "' border='0' width=24></a> <a href='?m=ca&a=ed&sa=vi&idCatalog=" . $catalog->idCatalog . "&start=" . ($nbCatalogData-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/right-full.png' alt='" . $txt["next"] . "' border='0' width=24></a>";
        }
 
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=vi'>" . $txt["clicToReturnToMain"] . "</a>".$msg));
        // Display Catalog content header
        $template->set_filenames(array('catalogEditContentHeader' => 'catalog_edit_content_header.tpl'));
        $template->assign_vars(array('CATALOG_NB_BOOKS' => $catalog->nbBooks(),
                'NAV' => $nav,
                'IDCATALOG' => $catalog->idCatalog,
                'SORT_INTO_CHAPTER' => $txt["sortIntoChapter"],
								'BOOKS_IN_CATALOG' => $txt["booksInCatalog"],
								'ADD_BEFORE' => $txt["addBefore"],
								'MODIFY' => $txt["modify"],
								'INSERT' => $txt["insert"],
								'ADD_TO_END' => $txt["addToEnd"],
								'ADD_TO_END_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $catalog->nextRecord(),
								'ERASE' => $txt["erase"],
								'ORDER' => $txt["order"],
								'CATALOG_CONTENT' => $txt["content"]));
				$template->pparse('catalogEditHeader');
        $template->pparse('catalogEditContentHeader');
        // Display Catalog content body
        $template->set_filenames(array('catalogEditContentBody' => 'catalog_edit_content_body.tpl'));

        // Fetch all content
        $cat=$catalog->get();
        $data=$catalog->getData(-1,$start,$row);
        $i=0;
        while($i!=(sizeof($data)))
        {
          $display=false;
          switch ($data[$i]["type"])
          {
				  case "book":
				    $bookData=$book->get($data[$i]["name"]);
				    if (($cat["bypassNoQuantity"]=='Y') OR ($bookData["quantity"]>0))
				    {
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/edit.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "?m=bo&a=vi&sa=mo&fromcatalog=" . $catalog->idCatalog . "&id=".$bookData["idBook"],
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => html_entity_decode("<b>".$bookData["author"]."</b><br/>".$bookData["title"]."<br/>".$bookData["description"]."<br/>".$bookData["price"]." €")));
							$display=true;
						}
						break;
				  case "image":
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify-disable.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "#",
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => "<img src='".$data[$i]["name"]."' alt='".$data[$i]["description"]."'>"));
							$display=true;
						break;
				  case "text":
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "?m=ca&a=ed&sa=mt&type=title&idCatalog=" . $catalog->idCatalog . "&idCatalogData=" . $data[$i]["idCatalogData"] . "&id=" . $data[$i]["orderInCatalog"],
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => "<h1>".$data[$i]["name"]."</h1>"));
							$display=true;
						break;
				  case "title":
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "?m=ca&a=ed&sa=mt&type=title&idCatalog=" . $catalog->idCatalog . "&idCatalogData=" . $data[$i]["idCatalogData"] . "&id=" . $data[$i]["orderInCatalog"],
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => "<h1>".$data[$i]["name"]."</h1>"));
							$display=true;
						break;
				  case "chapter":
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "?m=ca&a=ed&sa=mt&type=chapter&idCatalog=" . $catalog->idCatalog . "&idCatalogData=" . $data[$i]["idCatalogData"] . "&id=" . $data[$i]["orderInCatalog"],
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => "<h2>".$data[$i]["name"]."</h2>"));
							$display=true;
						break;
				  case "newpage":
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify-disable.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "#",
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => $data[$i]["orderInCatalog"],
								'CONTENT' => "--------------------------------------------"));
							$display=true;
						break;
				  default:
				      $template->assign_vars(array('MODIFY' => "<img src='template/".$config["template"]."/images/modify-disable.png' alt='".$txt["modify"]."' border='0'>",
								'MODIFY_URL' => "#",
								'ADD_BEFORE' => "<img src='template/".$config["template"]."/images/addtocatalog.png' alt='".$txt["addBefore"]."' border='0'>",
								'ADD_BEFORE_URL' => "?m=ca&a=ed&idCatalog=" . $catalog->idCatalog . "&id=" . $data[$i]["orderInCatalog"],
								'ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["delete"]."' border='0'>",
								'ERASE_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&delete=".$data[$i]["idCatalogData"],
								'UP' => "<img src='template/".$config["template"]."/images/up.png' alt='".$txt["up"]."' border='0'>",
								'UP_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&up=".$data[$i]["idCatalogData"],
								'DOWN' => "<img src='template/".$config["template"]."/images/down.png' alt='".$txt["down"]."' border='0'>",
								'DOWN_URL' => "?m=ca&a=ed&sa=vi&idCatalog=".$catalog->idCatalog."&down=".$data[$i]["idCatalogData"],
								'ORDER' => "-",
								'CONTENT' => "ERROR: type non recognized : ".$data[$i][type].""));
							$display=true;
						break;
					}
					if ($display==true)
					{
						if ($tableClass == "result-color-1")
          		$tableClass = "result-color-2";
						else
							$tableClass = "result-color-1";
        		$template->assign_vars(array('CLASS' => $tableClass));
        		$template->pparse('catalogEditContentBody');
					}
          $i++;
				}
        // Display edit footer
        $template->set_filenames(array('catalogEditFooter' => 'catalog_edit_content_footer.tpl'));
				$template->pparse('catalogEditFooter');
        break;
      case "as": // Add a search result to current catalog : the form search
      	// Display display/search header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
				$template->pparse('catalogEditHeader');
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
        $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'CATEGORY_V' => $cat,
						'QUANTITY_NOT_NULL' => $txt["notNull"]." <input type='checkbox' name='quantitynotnull' value='1'>",
        		'BOOKS_FORM' => "?m=ca&a=ed&sa=as2&idCatalog=".$_GET["idCatalog"]."&id=".$_GET["id"], // FORM Target URL <----------------------
            'BOOKS_SUBMIT' => $txt["search"]));
				$template->pparse('bookSearchForm');
        $template->pparse('bookCreateMask');
        break;
      case "as2": // Add a search result to current catalog : The search results
      	// Display display/search header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
				$template->pparse('catalogEditHeader');
				if (($_POST["sub"] == "ok") OR ($_GET["sub"] == "ok")) // If form is filled...
        if ((isset($_POST["research"])) and ($_POST["research"] == "ok"))
        {
					$search=$book->search("cache");
				}
        else
        {
        	$book->assignData($_POST);
					$search=$book->search();
				}
				$nbResult=sizeof($search);

        // Display search table header
        $template->set_filenames(array('bookDisplaySearchResultsHeader' => 'book_display_search_results_header.tpl'));
        $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"],
						'NB_RESULTS' => $nbResult, 'SELECTION_URL' => "?m=ca&a=ed&sa=as3&idCatalog=".$_GET["idCatalog"]."&id=".$_GET["id"]));
        $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"],  'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_IN_CATALOG' => $txt["bookInCatalog"], 'BOOKS_MODIFY' => "", 'BOOKS_ERASE' => ""));
        $template->pparse('bookDisplaySearchResultsHeader');
        // Display search results body
        $template->set_filenames(array('bookDisplaySearchResultsBody' => 'book_display_search_results_body.tpl'));
        $i = 0;
        $currentcatalog=$catalog->get();
        while ($i != $nbResult) {
          if (!(($currentcatalog["bypassNoQuantity"]=="N") AND ($search[$i]["quantity"]<1)))
          {
						if ($tableClass == "result-color-1")
          		$tableClass = "result-color-2";
						else
							$tableClass = "result-color-1";
        		$template->assign_vars(array('CLASS' => $tableClass));
          	// trunk the description
          	$description = substr($search[$i]["description"], 0, 30) . "...";
          	$select="<input type='checkbox' name='selected_book[]'' value='".$search[$i]["idBook"]."' id='checkbox_book_".$search[$i]["idBook"]."' />";
          	$template->assign_vars(array('BOOKS_RESULT_MODIFY' => "?m=bo&a=vi&sa=mo&id=" . $search[$i]["idBook"], 'BOOKS_RESULT_ERASE' => "",
          		'BOOKS_RESULT_AUTHOR' => "<label for='checkbox_book_".$search[$i]["idBook"]."'>".$search[$i]["author"]."</label>", 'BOOKS_RESULT_TITLE' => "<label for='checkbox_book_".$search[$i]["idBook"]."'>".$search[$i]["title"]."</label>", 'BOOKS_RESULT_DESCRIPTION' => $description, 'BOOKS_RESULT_PRICE' => $search[$i]["price"], 'BOOKS_RESULT_QUANTITY' => $search[$i]["quantity"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_RESULT_SELECT' => $select,
              'BOOK_CONFIRM_ERASE' => $txt["bookConfirmErase"] . $search[$i]["title"]));
          	$template->pparse('bookDisplaySearchResultsBody');
					}
          $i++;
        }
        // Footer
        $template->set_filenames(array('bookDisplaySearchResultsFooter' => 'book_display_search_results_footer.tpl'));
        $template->assign_vars(array('SELECT_ALL' => $txt["selectAll"], 'UNSELECT_ALL' => $txt["unselectAll"],
					'FOR_SELECTION' => $txt["forSelection"]));
        $template->assign_vars(array('ACTION1' => $txt["addSelectionToCatalog"],'ACTION1_VALUE' => "addSelection",
						'EXTRA_FORM' => "&nbsp;<br/>" . $txt["chapterTitle"] . " : <INPUT TYPE='text' size='40' name='chapter'><br/>&nbsp;"));
        $template->pparse('bookDisplaySearchResultsFooter');
				break;
      case "as3": // Add a search result to current catalog : The real add !
      	// Display display/search header
      	if (isset($_GET["id"]))
      	  $id=$_GET["id"];
				else
      	  $id=-1;
      	$selected_book=$_POST["selected_book"];
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
				$msg = $txt["booksToAddToCatalog"] . sizeof($selected_book) . "<br/>
					<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>";
				// If there is a chapter name... We add it !
        if ($_POST["chapter"]!="")
				{
					$catalog->addChapter($_POST["chapter"],"",$id);
					if ($id!=-1) $id++;
				}
				$i=0;
				while($i!=sizeof($selected_book))
				{
				  $current=$book->get($selected_book[$i]);
					$msg .= "<br />".$current["author"].", ".$current["title"];
				  $catalog->addBook($selected_book[$i],$id);
					if ($id!=-1) $id++;
				  $i++;
				}
				$msg .= "<br /><a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>";
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"], 'CATALOG_MSG' => $msg));
				$template->pparse('catalogEditHeader');
				break;
      case "at": // Add a Text to current catalog
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"], 'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
				$template->pparse('catalogEditHeader');
				// If it is a template add...
				if (isset($_GET["idcatalogtemplate"]))
				{
					$templatedata=$catalog->getTemplate($_GET["idcatalogtemplate"]);
        	$template->assign_vars(array('NAME_V'  => $templatedata[0]["name"],
								'DESCRIPTION_V'  => $templatedata[0]["content"]));
				}
				// Display form
        $template->set_filenames(array('catalogEditText' => 'catalog_edit_text.tpl'));
        $template->assign_vars(array('CATALOG_FORM' => "?m=ca&a=ed&sa=at2&type=" . $_GET["type"] . "&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'NAME'  => $txt["name"],
								'DESCRIPTION'  => $txt["description"],
								'SUBMIT'  => $txt["submit"]));
				$template->pparse('catalogEditText');
        break;
      case "at2": // Add a Text to current catalog (real add)
        // Display edit header & result
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"]));
        $txtBack="<p><a href='?m=ca&a=ed&sa=vi&idCatalog=" . $catalog->idCatalog . "'>" . $txt["returnToList"] . "</a></p>";
        if ($_GET["modify"]==1) $catalog->delData($_GET["idCatalogData"]);
        switch($_GET["type"])
        {
        case "text":
          if ($catalog->addText($_POST["name"],$_POST["description"],$_GET["id"]))
						$template->assign_vars(array('CATALOG_MSG' => $txt["addDone"].$txtBack));
					else
						$template->assign_vars(array('CATALOG_MSG' => $txt["errorInAdd"].$txtBack));
          break;
        case "title":
          if ($catalog->addTitle($_POST["name"],$_POST["description"],$_GET["id"]))
						$template->assign_vars(array('CATALOG_MSG' => $txt["addDone"].$txtBack));
					else
						$template->assign_vars(array('CATALOG_MSG' => $txt["errorInAdd"].$txtBack));
          break;
        case "chapter":
          if ($catalog->addChapter($_POST["name"],$_POST["description"],$_GET["id"]))
						$template->assign_vars(array('CATALOG_MSG' => $txt["addDone"].$txtBack));
					else
						$template->assign_vars(array('CATALOG_MSG' => $txt["errorInAdd"].$txtBack));
          break;
				}
				$template->pparse('catalogEditHeader');
        break;
      case "mt": // Modify a Text to current catalog
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
				$template->pparse('catalogEditHeader');
				// We get the data from catalogdata
				$data=$catalog->getData($_GET["idCatalogData"]);
				// Display form
        $template->set_filenames(array('catalogEditText' => 'catalog_edit_text.tpl'));
        $template->assign_vars(array('CATALOG_FORM' => "?m=ca&a=ed&sa=at2&modify=1&type=" . $_GET["type"] . "&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"] . "&idCatalogData=" . $_GET["idCatalogData"],
								'NAME'  => $txt["name"],
								'NAME_V'  => $data[0]["name"],
								'DESCRIPTION'  => $txt["description"],
								'DESCRIPTION_V'  => $data[0]["description"],
								'SUBMIT'  => $txt["submit"]));
				$template->pparse('catalogEditText');
        break;
      case "np": // Add a newpage tag to current catalog
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
				// We get the data from catalogdata
				$data=$catalog->addNewPage("",$_GET["id"]);
				// Display result
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a> <br />" . $txt["addDone"]));
				$template->pparse('catalogEditHeader');
        break;
      case "wiz": // Wizard to add a page to current catalog
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"],'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
				$template->pparse('catalogEditHeader');
				// Display Wizard form
				/////////////////////////////////////////////
				// TODO
				/////////////////////////////////////////////
        break;
      default:
        // Display edit header
        $template->set_filenames(array('catalogEditHeader' => 'catalog_edit_header.tpl'));
        $template->assign_vars(array('CATALOG_EDIT' => $txt["catalogEdit"], 'CATALOG_MSG' => "<a href='?m=ca&a=ed&sa=vi&idCatalog=" . $_GET["idCatalog"] . "'>" . $txt["returnToList"] . "</a>"));
        // Display Edit Menu
        $template->set_filenames(array('catalogEditMenu' => 'catalog_edit_menu.tpl'));
        $template->assign_vars(array('CATALOG_NB_BOOKS' => $catalog->nbBooks(),
								'CATALOG_ADD_CHAPTER' => $txt["catalogAddChapter"],
								'CATALOG_ADD_CHAPTER_URL' => "?m=ca&a=ed&sa=at&type=chapter&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'CATALOG_ADD_TITLE' => $txt["catalogAddTitle"],
								'CATALOG_ADD_TITLE_URL' => "?m=ca&a=ed&sa=at&type=title&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'CATALOG_ADD_NEWPAGE' => $txt["catalogAddNewPage"],
								'CATALOG_ADD_NEWPAGE_URL' => "?m=ca&a=ed&sa=np&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'CATALOG_ADD_SEARCH' => $txt["catalogAddSearch"],
								'CATALOG_ADD_SEARCH_URL' => "?m=ca&a=ed&sa=as&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'CATALOG_ADD_TITLEWIZARD' => $txt["catalogTitleWizard"],
								'CATALOG_ADD_TITLEWIZARD_URL' => "?m=ca&a=ed&sa=wiz&type=main&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"],
								'CATALOG_TEMPLATE' => $txt["catalogTemplatePage"]));
				// Generating template listing...
				$templatedata=$catalog->getTemplate();
				$templatetxt="";
				$i=0;
				while($i!=sizeof($templatedata))
				{
				  switch($templatedata[$i]["type"])
					{
					case "title":
						$templatetxt.="<a href='?m=ca&a=ed&sa=at&type=title&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"] . "&idcatalogtemplate=" . $templatedata[$i]["idCatalogTemplate"] . "'>".$templatedata[$i]["name"]."</a><br />";
						break;
					case "text":
						$templatetxt.="<a href='?m=ca&a=ed&sa=at&type=text&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"] . "&idcatalogtemplate=" . $templatedata[$i]["idCatalogTemplate"] . "'>".$templatedata[$i]["name"]."</a><br />";
						break;
					case "chapter":
						$templatetxt.="<a href='?m=ca&a=ed&sa=at&type=chapter&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"] . "&idcatalogtemplate=" . $templatedata[$i]["idCatalogTemplate"] . "'>".$templatedata[$i]["name"]."</a><br />";
					case "image":
					// TODO...
						$templatetxt.="<a href='?m=ca&a=ed&sa=at&type=image&idCatalog=" . $catalog->idCatalog . "&id=" . $_GET["id"] . "&idcatalogtemplate=" . $templatedata[$i]["idCatalogTemplate"] . "'>".$templatedata[$i]["name"]."</a><br />";
						break;
					}
				  $i++;
				}
        $template->assign_vars(array('TEMPLATE' => $templatetxt));
				$template->pparse('catalogEditHeader');
        $template->pparse('catalogEditMenu');
				break;
			}
			break;
    case "vi":
        switch ($sa) {
            case "mo": // Display Modify form
                if ($_POST['idCatalog'] != "") {
                    $catalog->idCatalog = $_POST['idCatalog'];
                } else {
                    $catalog->idCatalog = $_GET['idCatalog'];
                }
                if ($_POST["sub"] == "ok") { // Update the current record
                    $catalog->update($_POST["name"],$_POST["description"],$_POST["bypassnoquantity"]);
                    $template->assign_vars(array('CATALOG_MSG' => $txt["catalogModificationDone"]));
                }
                $record = $catalog->get();
                // Display display header
                $template->set_filenames(array('catalogDisplayHeader' => 'catalog_display_header.tpl'));
                // Display Modify txt header
                $template->set_filenames(array('catalogModify' => 'catalog_modify_header.tpl'));
                $template->assign_vars(array('CATALOG_MODIFY_TXT' => $txt["catalogModification"]));
                // Return to the list
                $template->assign_vars(array('TXT_RETURN_TO_LIST' => $txt["returnToList"], 'URL_CATALOG_LIST' => "?m=ca&a=vi"));
                $template->set_filenames(array('catalogCreateMask' => 'catalog_main_mask.tpl'));
                $template->assign_vars(array('CATALOG_NAME' => $txt["name"], 'CATALOG_DESCRIPTION' => $txt["description"], 'CATALOG_BYPASSNOQUANTITY' => $txt["bypassNoQuantity"],
                        'CATALOG_FORM' => "?m=ca&a=vi&sa=mo&idCatalog=".$catalog->idCatalog, // FORM Target URL <----------------------
                        'CATALOG_SUBMIT' => $txt["submit"], 'NAME_V' => $record["name"], 'DESCRIPTION_V' => $record["description"], 'BYPASSNOQUANTITY_V' => $record["bypassNoQuantity"]));
                $template->pparse('catalogDisplayHeader');
                $template->pparse('catalogModify');
                $template->pparse('catalogCreateMask');
                break;
            default: // Display default catalog list screen
                    // We reset sessions variable for start & row attributes
                    $_SESSION["start"]="";
                    $_SESSION["row"]="";

                    if ($_POST['idCatalog'] != "") {
                        $catalog->idCatalog = $_POST['idCatalog'];
                    } else {
                        $catalog->idCatalog = $_GET['idCatalog'];
                    }
                    // If we want to delete a catalog
                    if ($_GET["del"] == "1") { // Delete the record whith the id
                        if ($catalog->delete())
                            $template->assign_vars(array('CATALOG_MSG' => ($txt["catalogEraseDone"] . $record[0]["name"])));
                        else
                            $template->assign_vars(array('CATALOG_MSG' => ($txt["catalogEraseNotOk"] . $record[0]["name"])));
                    }
                    // If we want to lock a catalog
                    if ($_GET["lock"] == "1")
                      if ($catalog->lock()) // lock the catalog whith the given id
                      	$template->assign_vars(array('CATALOG_MSG' => ($txt["locked"])));
                    // If we want to unlock a catalog
                    if ($_GET["unlock"] == "1")
                      if ($catalog->unlock()) // unlock the catalog whith the given id
                      	$template->assign_vars(array('CATALOG_MSG' => ($txt["unlocked"])));
										// Get the full catalog list
                    $catList = $catalog->getList();
                    // Display display header
                    $template->set_filenames(array('catalogDisplayHeader' => 'catalog_display_header.tpl'));
                    // Display search table header
                    $template->set_filenames(array('catalogDisplaySearchResultsHeader' => 'catalog_display_search_results_header.tpl'));
                    $template->assign_vars(array('CATALOG_DATABASE' => $txt["catalogDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => (sizeof($catList))));
                    $template->assign_vars(array('CATALOG_NAME' => $txt["name"], 'CATALOG_DESCRIPTION' => $txt["description"]));
                    $template->assign_vars(array('CATALOG_MODIFY_TXT' => $txt["catalogModification"]));
                    $template->pparse('catalogDisplayHeader');
                    $template->pparse('catalogDisplaySearchResultsHeader');
                    // Display search results body
                    $template->set_filenames(array('catalogDisplaySearchResultsBody' => 'catalog_display_search_results_body.tpl'));
                    $i = 0;
                    while ($i != (sizeof($catList))) {
												if ($tableClass == "result-color-1")
          								$tableClass = "result-color-2";
												else
													$tableClass = "result-color-1";
        								$template->assign_vars(array('CLASS' => $tableClass));
        								$catalog->lockedStatus="?";
                        if ($catalog->islocked($catList[$i]["idCatalog"])) {
                          // Catalog is locked : No delete, modify or edit... Only generate
													$template->assign_vars(array('CATALOG_MODIFY' => "<img src='template/".$config["template"]."/images/modify-disable.png' alt='".$txt["modify"]."' border='0'>",
													        'CATALOG_MODIFY_URL' => "javascript:alert('".$txt["errorCatalogLocked"]."');",
													        'CATALOG_ERASE' => "<img src='template/".$config["template"]."/images/delete-disable.png' alt='".$txt["erase"]."' border='0'>",
													        'CATALOG_ERASE_URL' => "javascript:alert('".$txt["errorCatalogLocked"]."');",
													        'CATALOG_EDIT' => "<img src='template/".$config["template"]."/images/editcatalog-disable.png' alt='".$txt["edit"]."' border='0'>",
													        'CATALOG_EDIT_URL' => "javascript:alert('".$txt["errorCatalogLocked"]."');",
													        'CATALOG_GENERATE' => "<img src='template/".$config["template"]."/images/generate.png' alt='".$txt["generate"]."' border='0'>",
													        'CATALOG_GENERATE_URL' => "?m=ca&a=ge&idCatalog=".$catList[$i]["idCatalog"],
													        'CATALOG_LOCK' => "<img src='template/".$config["template"]."/images/locked.png' alt='".$txt["locked"]."' border='0'>",
													        'CATALOG_LOCK_URL' => "?m=ca&a=vi&unlock=1&idCatalog=".$catList[$i]["idCatalog"]));
												} else {
                          // Catalog isn't locked : Only delete, modify or edit... NOT generate
													$template->assign_vars(array('CATALOG_MODIFY' => "<img src='template/".$config["template"]."/images/modify.png' alt='".$txt["modify"]."' border='0'>",
													        'CATALOG_MODIFY_URL' => "?m=ca&a=vi&sa=mo&idCatalog=" . $catList[$i]["idCatalog"],
													        'CATALOG_ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='".$txt["erase"]."' border='0'>",
													        'CATALOG_ERASE_URL' => "?m=ca&a=vi&del=1&idCatalog=" . $catList[$i]["idCatalog"],
													        'CATALOG_EDIT' => "<img src='template/".$config["template"]."/images/editcatalog.png' alt='".$txt["edit"]."' border='0'>",
													        'CATALOG_EDIT_URL' => "?m=ca&a=ed&sa=vi&idCatalog=" . $catList[$i]["idCatalog"],
													        'CATALOG_GENERATE' => "<img src='template/".$config["template"]."/images/generate-disable.png' alt='".$txt["generate"]."' border='0'>",
													        'CATALOG_GENERATE_URL' => "javascript:alert('".$txt["errorCatalogNotLocked"]."');",
													        'CATALOG_LOCK' => "<img src='template/".$config["template"]."/images/unlocked.png' alt='".$txt["unlocked"]."' border='0'>",
													        'CATALOG_LOCK_URL' => "?m=ca&a=vi&lock=1&idCatalog=".$catList[$i]["idCatalog"]));
												}
                        // trunk the description
                        $description = substr($catList[$i]["description"], 0, 30) . "...";
                        $template->assign_vars(array('CATALOG_RESULT_NAME' => $catList[$i]["name"], 'CATALOG_RESULT_DESCRIPTION' => $description,
														'CATALOG_CONFIRM_ERASE' => $txt["catalogConfirmErase"] . $search[$i]["name"]));
                        $template->pparse('catalogDisplaySearchResultsBody');
                        $i++;
                    }
                    // Footer
                    $template->set_filenames(array('catalogDisplaySearchResultsFooter' => 'catalog_display_search_results_footer.tpl'));
                    $template->pparse('catalogDisplaySearchResultsFooter');
                break;
        }
        break;
    case "ge":
			if ($_POST['idCatalog'] != "")
       	$catalog->idCatalog = $_POST['idCatalog'];
      else
       	$catalog->idCatalog = $_GET['idCatalog'];
      switch($_GET["sa"])
			{
      case "htmlview":
        	echo $catalog->generate('htmllist');
        break;
      case "pdf":
        break;
			default:
    		$template->set_filenames(array('catalogHeader' => 'catalog_generate_header.tpl'));
      	$template->assign_vars(array('CATALOG_GENERATE' => $txt["generateCatalog"],
           'CATALOG_MSG' => "<a href='?m=ca&a=vi'>" . $txt["clicToReturnToMain"] . "</a>"));
      	// Display Generate Menu
      	$template->set_filenames(array('catalogMenu' => 'catalog_generate_menu.tpl'));
      	$template->assign_vars(array('CATALOG_NB_BOOKS' => $catalog->nbBooks(),
								'BOOKS_IN_CATALOG' => $txt["booksInCatalog"],
								'CATALOG_GENERATE_HTMLVIEW' => $txt["generateHtmlView"],
								'CATALOG_GENERATE_HTMLVIEW_URL' => "generate.php?t=cat&a=htmlview&idCatalog=".$catalog->idCatalog,
								'CATALOG_GENERATE_PDF' => $txt["generatePDFCatalog"],
								'CATALOG_GENERATE_PDF_URL' => "generate.php?t=cat&a=pdf&idCatalog=".$catalog->idCatalog,
                'CATALOG_GENERATE_PDF_LOCATION' => $txt["generatePDFCatalogWithLocation"],
								'CATALOG_GENERATE_PDF_LOCATION_URL' => "generate.php?t=cat&a=pdfloc&idCatalog=".$catalog->idCatalog));
      	$template->pparse('catalogHeader');
      	$template->pparse('catalogMenu');
      	break;
      }
      break;
    default:
        $template->set_filenames(array('body' => 'catalog_home.tpl'));
        $template->assign_vars(array('CATALOG' => $txt["catalogs"],
                'CATALOG_HOME' => ""));
        $template->pparse('body');
        break;
}

?>

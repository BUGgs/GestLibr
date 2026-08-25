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
        $template->assign_vars(array('BOOK_CREATE' => $txt["createBook"])); 
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
				$inCatalog.="</table>" . $txt["addToCatalog"] . " :<br /><SELECT NAME='addInCatalog'><OPTION VALUE='' SELECTED></OPTION>";
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
        $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_AUTHOR2' => $txt["author2"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_TITLE2' => $txt["title2"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'IN_CATALOG_V' => $inCatalog,
                'QUANTITY_V' => "1",
                'CATEGORY_V' => $cat,
                'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                'BOOKS_SUBMIT' => $txt["submit"]));

        if ($_POST["sub"] == "ok") { // If form is filled...
            if (($_POST["title"] AND $_POST["author"])) { // Check for correct entry
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
								}
                else $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateDbError"]));
                $template->pparse('bookCreateHeader');
            } else { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('AUTHOR_V' => $_POST["author"], 'AUTHOR2_V' => $_POST["author2"], 'TITLE_V' => $_POST["title"], 'TITLE2_V' => $_POST["title2"], 'DESCRIPTION_V' => $_POST["description"], 'PRICE_V' => $_POST["price"], 'QUANTITY_V' => $_POST["quantity"], 'NOTES_V' => $_POST["notes"],
                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"])); 
                // Display mask with error message
                $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateMissedField"]));
                $template->pparse('bookCreateHeader');
                $template->pparse('bookCreateMask');
            } 
        } else { // form not filled... New form
            // display empty mask
            $template->pparse('bookCreateHeader');
            $template->pparse('bookCreateMask');
        } 
        break;
    case "vi":
        switch ($sa) {
            case "re": // Display search results
//                if (($_POST["sub"] == "ok") OR ($_GET["sub"] == "ok")) // If form is filled...
                    if ($_POST['id'] != "") {
                        $id = $_POST['id'];
                    } else {
                        $id = $_GET['id'];
                    }
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
                    if ($_GET["del"] == "1") { // Delete the record whith the id
                      $book->idBook=$id;
                      if ($book->del())
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseDone"])));
                      else
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseNotOk"])));
                    } 

                    if (($_GET["research"] == "ok") or ($_POST["research"] == "ok"))
                    {
                        $search=$book->search("cache","",$start,$row);
								    }
                    else
                    {
                      $book->assignData($_POST);
											$search=$book->search("","",$start,$row);
										}
										$nbResult=$search["nb"];
										
										$nav= $txt["nbResultDisplayed"] . $start . " - " . ($start+$row) . " ";
										if ($start!=0)
										{
										  $nav.=" | <a href='?m=bo&a=vi&sa=re&research=ok&start=0&row=" . $row . "'><img src='template/".$config["template"]."/images/left-full.png' alt='" . $txt["previous"] . "' border='0' width=24></a> <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/left.png' alt='" . $txt["previous"] . "' border='0' width=24> " . $txt["previous"] . "</a>";
                    }
										if (($row+$start)<$nbResult)
										{
										  $nav.=" | <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start+$row) . "&row=" . $row . "'>" . $txt["next"] . " <img src='template/".$config["template"]."/images/right.png' alt='" . $txt["next"] . "' border='0' width=24></a> <a href='?m=bo&a=vi&sa=re&research=ok&start=" . ($nbResult-$row) . "&row=" . $row . "'><img src='template/".$config["template"]."/images/right-full.png' alt='" . $txt["next"] . "' border='0' width=24></a>";
                    }
// TO ADD ROW NUMBER MODIFICATION
//                    $nav.= " | " . $txt["nbPerPage"] . "<form action='?m=bo&a=vi&sa=re&research=ok&start=" . $_GET['start'] . "' method='GET'><INPUT TYPE='text' NAME='row' SIZE='3' VALUE='" . $config["maxBookRow"] . "'></form>";
                  
                    //$search = $babase->DbSelect($sqlSearch, $nbResult);
                    // Display display/search header
                    $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                    // Display search table header
                    $template->set_filenames(array('bookDisplaySearchResultsHeader' => 'book_display_search_results_header.tpl'));
                    $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
                    $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"],  'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_MODIFY' => "<img src='template/".$config["template"]."/images/edit.png' alt='" . $txt["modify"] . "' border='0' width='32'>", 'BOOKS_ERASE' => "<img src='template/".$config["template"]."/images/delete.png' alt='" . $txt["erase"] . "' border='0' width='32'>", 'NAV' => $nav));
                    $template->pparse('bookDisplayHeader');
                    $template->pparse('bookDisplaySearchResultsHeader'); 
                    // Display search results body
                    $template->set_filenames(array('bookDisplaySearchResultsBody' => 'book_display_search_results_body.tpl'));
                    $i = 0;
                    while ($i != (sizeof($search)-1)) {
												if ($tableClass == "result-color-1")
          								$tableClass = "result-color-2";
												else
													$tableClass = "result-color-1";
        								$template->assign_vars(array('CLASS' => $tableClass));
                        // trunk the description
                        $description = substr($search[$i]["description"], 0, 30) . "...";
                        $select="<input type='checkbox' name='selected_book[]'' value='".$search[$i]["idBook"]."' id='checkbox_book_".$search[$i]["idBook"]."' />";
                        $template->assign_vars(array('BOOKS_RESULT_MODIFY' => "?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($i+$start), 'BOOKS_RESULT_ERASE' => "?m=bo&a=vi&sa=re&del=1&sub=ok&research=ok&id=" . $search[$i]["idBook"],
                                'BOOKS_RESULT_AUTHOR' => "<label for='checkbox_book_".$search[$i]["idBook"]."'>".$search[$i]["author"]."</label>", 'BOOKS_RESULT_TITLE' => "<label for='checkbox_book_".$search[$i]["idBook"]."'>".$search[$i]["title"]."</label>", 'BOOKS_RESULT_DESCRIPTION' => $description, 'BOOKS_RESULT_PRICE' => $search[$i]["price"], 'BOOKS_RESULT_QUANTITY' => $search[$i]["quantity"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_RESULT_SELECT' => $select,
                                'BOOK_CONFIRM_ERASE' => $txt["bookConfirmErase"]));
                        $template->pparse('bookDisplaySearchResultsBody');
                        $i++;
                    } 
                    // Footer
                    $template->set_filenames(array('bookDisplaySearchResultsFooter' => 'book_display_search_results_footer.tpl'));
                    $template->assign_vars(array('SELECT_ALL' => $txt["selectAll"], 'UNSELECT_ALL' => $txt["unselectAll"],
												'FOR_SELECTION' => $txt["forSelection"]));
                    $template->pparse('bookDisplaySearchResultsFooter');
                    break;
            case "mo": // Display Modify form
                if ($_POST['id'] != "") {
                    $id = $_POST['id'];
                } else {
                    $id = $_GET['id'];
                }
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
                if ($_POST["sub"] == "ok") { // Update the current record
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
								$template->assign_vars(array('BOOK_MSG' => $msg));
                // Get book data
                $record=$book->get($id);
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display Modify txt header
                $template->set_filenames(array('bookModify' => 'book_modify.tpl'));
                $template->assign_vars(array('BOOKS_DATABASE' => $txt["bookDatabase"], 'BOOKS_MODIFY_TXT' => $txt["bookModification"])); 
                // Re-search cache form
                $template->assign_vars(array('BOOKS_SEARCH_CACHE_SUBMIT' => $txt["bookSearchCacheSubmit"], 'BOOKS_SEARCH_FORM' => $returnToResult));
                
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
								
								// Assign Data
								$template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
                $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_AUTHOR2' => $txt["author2"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_TITLE2' => $txt["title2"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_IN_CATALOG' => $txt["bookInCatalog"], 'BOOKS_MODIFYDATE' => $txt["bookModifyDate"],
                        'BOOKS_FORM' => "?m=bo&a=vi&sa=mo&id=$id".$fromresultsearch, // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"], 'AUTHOR_V' => $record["author"], 'AUTHOR2_V' => $record["author2"], 'TITLE_V' => $record["title"], 'TITLE2_V' => $record["title2"], 'DESCRIPTION_V' => $record["description"], 'PRICE_V' => $record["price"], 'QUANTITY_V' => $record["quantity"], 'NOTES_V' => $record["notes"], 'MODIFYDATE_V' => date("d/m/Y", $record["modifyDate"]), 'CATEGORY_V' => $cat, 'IN_CATALOG_V' => $inCatalog, 'NAV' => $nav));
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
                $template->assign_vars(array('BOOKS_AUTHOR' => $txt["author"], 'BOOKS_AUTHOR2' => $txt["author2"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_TITLE2' => $txt["title2"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'CATEGORY_V' => $cat,
                        'QUANTITY_NOT_NULL' => $txt["notNull"]." <input type='checkbox' name='quantitynotnull' value='1'>",
                        'BOOKS_FORM' => "?m=bo&a=vi&sa=re", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["search"]));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookSearchForm');
                $template->pparse('bookCreateMask');
                break;
        } 
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

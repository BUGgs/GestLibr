<?php
include "class/category.class.php";
$category=New Category();

switch ($a) {
    case "cr": 
        // Display create header
        $template->set_filenames(array('categoriesCreateHeader' => 'categories_create_header.tpl'));
        $template->assign_vars(array('CATEGORIES_CREATE' => $txt["createCategorie"])); 
        // Display empty mask
        $template->set_filenames(array('categoriesCreateMask' => 'categories_main_mask.tpl'));
        $template->assign_vars(array('CATEGORIES_NAME' => $txt["categoriesName"], 'CATEGORIES_DESCRIPTION' => $txt["categoriesDescription"],
                'CATEGORIES_FORM' => "?m=ct&a=cr", // FORM Target URL <----------------------
                'CATEGORIES_SUBMIT' => $txt["submit"]));

        if ($_POST["sub"] == "ok") { // If form is filled...
            if ($_POST["name"]!="") { // Check for correct entry
                // Ok, we're recording the new category
                if ($category->add($_POST["name"],$_POST["description"]))
								  $template->assign_vars(array('CATEGORIES_MSG' => $txt["categoriesCreateOk"]));
                $template->pparse('categoriesCreateHeader');
            } else { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('NAME_V' => $_POST["name"], 'DESCRIPTION_V' => $_POST["description"])); 
                // Display mask with error message
                $template->assign_vars(array('CATEGORIES_MSG' => $txt["categoriesCreateMissedField"]));
                $template->pparse('categoriesCreateHeader');
                $template->pparse('categoriesCreateMask');
            } 
        } else { // form not filled... New form
            // display empty mask
            $template->pparse('categoriesCreateHeader');
            $template->pparse('categoriesCreateMask');
        } 
        break;
    case "vi":
        switch ($sa) {
            case "mo": // Display Modify form
                if ($_POST['idCat'] != "") {
                    $category->idCategory = $_POST['idCat'];
                } else {
                    $category->idCategory = $_GET['idCat'];
                } 
                if ($_POST["sub"] == "ok") { // Update the current record
                    $category->update($_POST["name"],$_POST["description"]);
                    $template->assign_vars(array('CATEGORIES_MSG' => $txt["categoriesModificationDone"]));
                } 
                $record = $category->get();
                // Display display/search header
                $template->set_filenames(array('categoriesDisplayHeader' => 'categories_display_header.tpl')); 
                // Display Modify txt header
                $template->set_filenames(array('categoriesModify' => 'categories_modify.tpl'));
                $template->assign_vars(array('CATEGORIES_DATABASE' => $txt["categoriesDatabase"], 'CATEGORIES_MODIFY_TXT' => $txt["categoriesModification"])); 
                // Re-search cache form
                $template->assign_vars(array('CATEGORIES_SEARCH_CACHE_SUBMIT' => $txt["categoriesSearchCacheSubmit"], 'CATEGORIES_SEARCH_FORM' => "?m=ct&a=vi"));
                $template->set_filenames(array('categoriesCreateMask' => 'categories_main_mask.tpl'));
                $template->assign_vars(array('CATEGORIES_NAME' => $txt["categoriesName"], 'CATEGORIES_DESCRIPTION' => $txt["categoriesDescription"], 
                        'CATEGORIES_FORM' => "?m=ct&a=vi&sa=mo&idCat=".$category->idCategory, // FORM Target URL <----------------------
                        'CATEGORIES_SUBMIT' => $txt["submit"], 'NAME_V' => $record["name"], 'DESCRIPTION_V' => $record["description"]));
                $template->pparse('categoriesDisplayHeader');
                $template->pparse('categoriesModify');
                $template->pparse('categoriesCreateMask');
                break;

            default: // Display default category list screen
                    if ($_POST['idCat'] != "") {
                        $category->idCategory = $_POST['idCat'];
                    } else {
                        $category->idCategory = $_GET['idCat'];
                    }
                    // If we want to delete a category
                    if ($_GET["del"] == "1") { // Delete the record whith the id
                        if (($category->del()) == 1)
                            $template->assign_vars(array('CATEGORIES_MSG' => ($txt["categoriesEraseDone"] . $record[0]["name"])));
                        else
                            $template->assign_vars(array('CATEGORIES_MSG' => ($txt["categoriesEraseNotOk"] . $record[0]["name"])));
                    }

                    $catList = $category->getList();
                    // Display display/search header
                    $template->set_filenames(array('categoriesDisplayHeader' => 'categories_display_header.tpl'));
                    // Display search table header
                    $template->set_filenames(array('categoriesDisplaySearchResultsHeader' => 'categories_display_search_results_header.tpl'));
                    $template->assign_vars(array('CATEGORIES_DATABASE' => $txt["categoriesDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
                    $template->assign_vars(array('CATEGORIES_NAME' => $txt["categoriesName"], 'CATEGORIES_DESCRIPTION' => $txt["categoriesDescription"], 'CATEGORIES_MODIFY' => $txt["modify"], 'CATEGORIES_ERASE' => $txt["erase"]));
                    $template->pparse('categoriesDisplayHeader');
                    $template->pparse('categoriesDisplaySearchResultsHeader');
                    // Display search results body
                    $template->set_filenames(array('categoriesDisplaySearchResultsBody' => 'categories_display_search_results_body.tpl'));
                    $i = 0;
                    while ($i != (sizeof($catList))) {
                        if ($bgcolor == "result-color-1") {
                            $bgcolor = "result-color-2";
                        } else {
                            $bgcolor = "result-color-1";
                        }
                        // trunk the description
                        $description = substr($catList[$i]["description"], 0, 30) . "...";
                        $template->assign_vars(array('CATEGORIES_RESULT_MODIFY' => "?m=ct&a=vi&sa=mo&idCat=" . $catList[$i]["idCategory"], 'CATEGORIES_RESULT_ERASE' => "?m=ct&a=vi&del=1&idCat=" . $catList[$i]["idCategory"],
                                'CATEGORIES_RESULT_NAME' => $catList[$i]["name"], 'CATEGORIES_RESULT_DESCRIPTION' => $description, 'FORM_BGCOLOR' => $bgcolor, 'CATEGORIES_CONFIRM_ERASE' => $txt["categorieConfirmErase"] . $search[$i]["name"]));
                        $template->pparse('categoriesDisplaySearchResultsBody');
                        $i++;
                    }
                    // Footer
                    $template->set_filenames(array('categoriesDisplaySearchResultsFooter' => 'categories_display_search_results_footer.tpl'));
                    $template->pparse('categoriesDisplaySearchResultsFooter');
                break;
        } 
        break;
    default:
        $template->set_filenames(array('body' => 'categories_home.tpl'));
        $stats=$category->stats();
        $template->assign_vars(array('CATEGORY_DATABASE' => $txt["category"],
                'CATEGORY_HOME' => $txt["categoriesHome"],
                'TOTAL_CATEGORIES' => $txt["totalCategories"],
                'TOTAL_CATEGORIES_VALUE' => $stats["totalCategories"],
                'verProg' => $txt["verProg"]));
        $template->pparse('body');
        break;
} 

?>

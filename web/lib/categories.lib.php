<?php
include "class/category.class.php";
$category=New Category();
require_once "class/ebay.class.php";
$postedIdCat = isset($_POST['idCat']) ? $_POST['idCat'] : "";
$queryIdCat = isset($_GET['idCat']) ? $_GET['idCat'] : "";
$deleteFlag = isset($_GET["del"]) ? $_GET["del"] : "0";
$postSub = isset($_POST["sub"]) ? $_POST["sub"] : "";
$postName = isset($_POST["name"]) ? $_POST["name"] : "";
$postDescription = isset($_POST["description"]) ? $_POST["description"] : "";

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

        if ($postSub == "ok") { // If form is filled...
            if ($postName!="") { // Check for correct entry
                // Ok, we're recording the new category
                if ($category->add($postName, $postDescription))
									  $template->assign_vars(array('CATEGORIES_MSG' => $txt["categoriesCreateOk"]));
                $template->pparse('categoriesCreateHeader');
            } else { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('NAME_V' => $postName, 'DESCRIPTION_V' => $postDescription)); 
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
                if ($postedIdCat != "") {
                    $category->idCategory = $postedIdCat;
                } else {
                    $category->idCategory = $queryIdCat;
                } 
                if ($postSub == "ok") { // Update the current record
                    $category->update($postName, $postDescription);
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
                    if ($postedIdCat != "") {
                        $category->idCategory = $postedIdCat;
                    } else {
                        $category->idCategory = $queryIdCat;
                    }
                    // If we want to delete a category
                    if ($deleteFlag == "1") { // Delete the record whith the id
                        $record = $category->get();
                        $recordName = (is_array($record) && isset($record["name"])) ? $record["name"] : "";
                        if (($category->del()) == 1)
                            $template->assign_vars(array('CATEGORIES_MSG' => ($txt["categoriesEraseDone"] . $recordName)));
                        else
                            $template->assign_vars(array('CATEGORIES_MSG' => ($txt["categoriesEraseNotOk"] . $recordName)));
                    }

                    $catList = $category->getList();
                    $nbResult = is_array($catList) ? sizeof($catList) : 0;

                    // eBay store category management + mapping (REST provider only)
                    $ebayStoreBlockHtml = "";
                    $ebayStoreAction = isset($_REQUEST["ebay_store_action"]) ? trim((string)$_REQUEST["ebay_store_action"]) : "";
                    $ebayStoreActionIsAjax = (isset($_REQUEST["ajax"]) && (string)$_REQUEST["ajax"] === "1");
                    $ebayStoreActionResult = null;
                    $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
                    $storeMessage = "";
                    $isRestProvider = (ebay_get_provider_name() === "rest");
                    $storeCategories = array();
                    $storeMapByCategory = array();
                    $templateImagesBase = "template/" . $config["template"] . "/images/";

                    if ($isRestProvider)
                    {
                        if ($ebayStoreAction == "sync")
                        {
                            $ebayStoreActionResult = ebay_rest_sync_store_categories($idGroup);
                            $storeMessage = $ebayStoreActionResult["message"];
                        }
                        else if ($ebayStoreAction == "create")
                        {
                            $ebayStoreActionResult = ebay_rest_create_store_category($idGroup, array(
                                "name" => isset($_POST["store_category_name"]) ? $_POST["store_category_name"] : "",
                                "parentStoreCategoryId" => isset($_POST["store_category_parent_id"]) ? $_POST["store_category_parent_id"] : "",
                            ));
                            $storeMessage = $ebayStoreActionResult["message"];
                        }
                        else if ($ebayStoreAction == "rename")
                        {
                            $ebayStoreActionResult = ebay_rest_rename_store_category(
                                $idGroup,
                                (isset($_POST["store_category_id"]) ? $_POST["store_category_id"] : ""),
                                (isset($_POST["store_category_new_name"]) ? $_POST["store_category_new_name"] : "")
                            );
                            $storeMessage = $ebayStoreActionResult["message"];
                        }
                        else if ($ebayStoreAction == "delete")
                        {
                            $ebayStoreActionResult = ebay_rest_delete_store_category($idGroup, (isset($_POST["store_category_id"]) ? $_POST["store_category_id"] : ""));
                            $storeMessage = $ebayStoreActionResult["message"];
                        }
                        else if ($ebayStoreAction == "map_save")
                        {
                            $ebayStoreActionResult = ebay_rest_save_category_store_map(
                                $idGroup,
                                (isset($_POST["map_idCategory"]) ? $_POST["map_idCategory"] : 0),
                                (isset($_POST["map_storeCategoryId"]) ? $_POST["map_storeCategoryId"] : "")
                            );
                            $storeMessage = $ebayStoreActionResult["message"];
                        }

                        if ($ebayStoreActionIsAjax && $ebayStoreAction != "")
                        {
                            if (function_exists("ob_get_level"))
                            {
                                while (ob_get_level() > 0)
                                    @ob_end_clean();
                            }
                            header("Content-Type: application/json; charset=utf-8");
                            $ok = (is_array($ebayStoreActionResult) && isset($ebayStoreActionResult["ok"])) ? (bool)$ebayStoreActionResult["ok"] : false;
                            $message = (is_array($ebayStoreActionResult) && isset($ebayStoreActionResult["message"])) ? (string)$ebayStoreActionResult["message"] : "Action eBay non disponible.";
                            echo json_encode(array(
                                "ok" => $ok,
                                "code" => ($ok ? "ok" : "server_error"),
                                "message" => $message,
                                "data" => array(),
                            ));
                            exit;
                        }

                        $storeCategoriesResult = ebay_rest_get_store_categories($idGroup);
                        if ($storeCategoriesResult["ok"] == true && isset($storeCategoriesResult["data"]) && is_array($storeCategoriesResult["data"]))
                            $storeCategories = $storeCategoriesResult["data"];

                        $storeMapResult = ebay_rest_get_category_store_map($idGroup);
                        $storeMapRows = array();
                        if ($storeMapResult["ok"] == true && isset($storeMapResult["data"]) && is_array($storeMapResult["data"]))
                            $storeMapRows = $storeMapResult["data"];

                        $sm = 0;
                        while ($sm < sizeof($storeMapRows))
                        {
                            $cid = isset($storeMapRows[$sm]["idCategory"]) ? (int)$storeMapRows[$sm]["idCategory"] : 0;
                            if ($cid > 0)
                                $storeMapByCategory[$cid] = isset($storeMapRows[$sm]["storeCategoryId"]) ? (string)$storeMapRows[$sm]["storeCategoryId"] : "";
                            $sm++;
                        }
                    }

                    $storeOptionsForParent = "<option value=''>Racine de la boutique</option>";
                    $storeOptionsForActions = "<option value=''>Sélectionner une rubrique boutique</option>";
                    $sc = 0;
                    while ($sc < sizeof($storeCategories))
                    {
                        $scId = isset($storeCategories[$sc]["storeCategoryId"]) ? (string)$storeCategories[$sc]["storeCategoryId"] : "";
                        $scName = isset($storeCategories[$sc]["name"]) ? (string)$storeCategories[$sc]["name"] : "";
                        $scLevel = isset($storeCategories[$sc]["level"]) ? (int)$storeCategories[$sc]["level"] : 0;
                        if ($scId != "")
                        {
                            $prefix = "";
                            $lvl = 0;
                            while ($lvl < $scLevel)
                            {
                                $prefix .= "&nbsp;&nbsp;&nbsp;";
                                $lvl++;
                            }
                            $label = $prefix . htmlspecialchars($scName, ENT_QUOTES, "UTF-8") . " (" . htmlspecialchars($scId, ENT_QUOTES, "UTF-8") . ")";
                            $option = "<option value='" . htmlspecialchars($scId, ENT_QUOTES, "UTF-8") . "'>" . $label . "</option>";
                            $storeOptionsForParent .= $option;
                            $storeOptionsForActions .= $option;
                        }
                        $sc++;
                    }

                    if ($isRestProvider)
                    {
                        $ebayStoreBlockHtml .= "<h2>Rubriques boutique eBay</h2>";
                        if ($storeMessage != "")
                            $ebayStoreBlockHtml .= "<div class='info'>" . htmlspecialchars($storeMessage, ENT_QUOTES, "UTF-8") . "</div>";

                        $ebayStoreBlockHtml .= "<div class='category-ebay-actions-grid'>";
                        $ebayStoreBlockHtml .= "<form method='post' action='?m=ct&a=vi' class='category-ebay-action-form'><input type='hidden' name='ebay_store_action' value='sync'><label>Synchroniser depuis eBay</label><button type='submit' class='category-ebay-action-btn'><img class='icon-svg' src='" . $templateImagesBase . "icon-sync.svg' alt=''>Synchroniser</button></form>";
                        $ebayStoreBlockHtml .= "<form method='post' action='?m=ct&a=vi' class='category-ebay-action-form'><input type='hidden' name='ebay_store_action' value='create'><label>Créer une rubrique boutique</label><input type='text' name='store_category_name' placeholder='Nom rubrique' /><select name='store_category_parent_id'>" . $storeOptionsForParent . "</select><button type='submit' class='category-ebay-action-btn'><img class='icon-svg' src='" . $templateImagesBase . "icon-plus.svg' alt=''>Créer</button></form>";
                        $ebayStoreBlockHtml .= "<form method='post' action='?m=ct&a=vi' class='category-ebay-action-form'><input type='hidden' name='ebay_store_action' value='rename'><label>Renommer une rubrique boutique</label><select name='store_category_id'>" . $storeOptionsForActions . "</select><input type='text' name='store_category_new_name' placeholder='Nouveau nom' /><button type='submit' class='category-ebay-action-btn'><img class='icon-svg' src='" . $templateImagesBase . "icon-edit.svg' alt=''>Renommer</button></form>";
                        $ebayStoreBlockHtml .= "<form method='post' action='?m=ct&a=vi' class='category-ebay-action-form' onsubmit=\"return confirm('Supprimer cette rubrique eBay boutique ?');\"><input type='hidden' name='ebay_store_action' value='delete'><label>Supprimer une rubrique boutique</label><select name='store_category_id'>" . $storeOptionsForActions . "</select><button type='submit' class='category-ebay-action-btn category-ebay-action-btn--danger'><img class='icon-svg' src='" . $templateImagesBase . "icon-trash.svg' alt=''>Supprimer</button></form>";
                        $ebayStoreBlockHtml .= "</div>";
                        $ebayStoreBlockHtml .= "<div id='category-ebay-map-status' class='info category-ebay-map-status' style='display:none;'></div>";
                        $ebayStoreBlockHtml .= "<script>(function(){var forms=document.querySelectorAll('form.js-ebay-map-form');if(!forms||forms.length===0){return;}var status=document.getElementById('category-ebay-map-status');var showStatus=function(ok,msg){if(!status){return;}status.style.display='block';status.className=ok?'info category-ebay-map-status':'err category-ebay-map-status';status.textContent=msg||'';};var submitMap=function(form){var fd=new FormData(form);fd.append('ajax','1');fetch(form.action,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(payload){if(payload&&payload.ok){showStatus(true,payload.message||'Mapping sauvegardé.');}else{showStatus(false,(payload&&payload.message)?payload.message:'Erreur sauvegarde mapping.');}}).catch(function(){showStatus(false,'Erreur réseau pendant la sauvegarde du mapping.');});};for(var i=0;i<forms.length;i++){(function(form){var select=form.querySelector('select[name=\"map_storeCategoryId\"]');if(select){select.addEventListener('change',function(){submitMap(form);});}form.addEventListener('submit',function(ev){ev.preventDefault();submitMap(form);});})(forms[i]);}})();</script>";
                    }
                    else
                    {
                        $ebayStoreBlockHtml = "<h2>Rubriques boutique eBay</h2><p>Activez d'abord <strong>EBAY_PROVIDER=rest</strong> pour gérer le mapping des rubriques boutique.</p>";
                    }

                    // Display display/search header
                    $template->set_filenames(array('categoriesDisplayHeader' => 'categories_display_header.tpl'));
                    // Display search table header
                    $template->set_filenames(array('categoriesDisplaySearchResultsHeader' => 'categories_display_search_results_header.tpl'));
                    $template->assign_vars(array('CATEGORIES_DATABASE' => $txt["categoriesDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
                    $template->assign_vars(array('CATEGORIES_NAME' => $txt["categoriesName"], 'CATEGORIES_DESCRIPTION' => $txt["categoriesDescription"], 'CATEGORIES_MODIFY' => $txt["modify"], 'CATEGORIES_ERASE' => $txt["erase"], 'CATEGORIES_EBAY_MAPPING_LABEL' => 'Rubrique eBay boutique'));
                    $template->pparse('categoriesDisplayHeader');
                    $template->pparse('categoriesDisplaySearchResultsHeader');
                    // Display search results body
                    $template->set_filenames(array('categoriesDisplaySearchResultsBody' => 'categories_display_search_results_body.tpl'));
                    $i = 0;
                    $bgcolor = "result-color-2";
                    while ($i != (sizeof($catList))) {
                        if ($bgcolor == "result-color-1") {
                            $bgcolor = "result-color-2";
                        } else {
                            $bgcolor = "result-color-1";
                        }
                        // trunk the description
                        $description = substr((string) $catList[$i]["description"], 0, 30) . "...";
                        $mappingCellHtml = "<span class='category-ebay-map-empty'>Non disponible</span>";
                        if ($isRestProvider)
                        {
                            if (sizeof($storeCategories) <= 0)
                            {
                                $mappingCellHtml = "<span class='category-ebay-map-empty'>Aucune rubrique synchronisée</span>";
                            }
                            else
                            {
                                $cid = (int)$catList[$i]["idCategory"];
                                $selectedStoreId = isset($storeMapByCategory[$cid]) ? (string)$storeMapByCategory[$cid] : "";
                                $mapOptions = "<option value=''>-- non mappé --</option>";
                                $sc2 = 0;
                                while ($sc2 < sizeof($storeCategories))
                                {
                                    $storeId = isset($storeCategories[$sc2]["storeCategoryId"]) ? (string)$storeCategories[$sc2]["storeCategoryId"] : "";
                                    $storeName = isset($storeCategories[$sc2]["name"]) ? (string)$storeCategories[$sc2]["name"] : "";
                                    $storeLevel = isset($storeCategories[$sc2]["level"]) ? (int)$storeCategories[$sc2]["level"] : 0;
                                    if ($storeId != "")
                                    {
                                        $prefix = "";
                                        $tmpLevel = 0;
                                        while ($tmpLevel < $storeLevel)
                                        {
                                            $prefix .= "&nbsp;&nbsp;&nbsp;";
                                            $tmpLevel++;
                                        }
                                        $selectedAttr = ($storeId === $selectedStoreId) ? " selected='selected'" : "";
                                        $mapOptions .= "<option value='" . htmlspecialchars($storeId, ENT_QUOTES, "UTF-8") . "'" . $selectedAttr . ">" . $prefix . htmlspecialchars($storeName, ENT_QUOTES, "UTF-8") . " (" . htmlspecialchars($storeId, ENT_QUOTES, "UTF-8") . ")</option>";
                                    }
                                    $sc2++;
                                }

                                $mappingCellHtml = "<form method='post' action='?m=ct&a=vi' class='js-ebay-map-form category-ebay-map-form' data-ebay-map-form='1'>"
                                    . "<input type='hidden' name='ebay_store_action' value='map_save'>"
                                    . "<input type='hidden' name='map_idCategory' value='" . $cid . "'>"
                                    . "<select name='map_storeCategoryId'>"
                                    . $mapOptions
                                    . "</select>"
                                    . "<button type='submit' class='category-ebay-map-save'>OK</button>"
                                    . "</form>";
                            }
                        }

                        $template->assign_vars(array('CATEGORIES_RESULT_MODIFY' => "?m=ct&a=vi&sa=mo&idCat=" . $catList[$i]["idCategory"], 'CATEGORIES_RESULT_ERASE' => "?m=ct&a=vi&del=1&idCat=" . $catList[$i]["idCategory"],
                                'CATEGORIES_RESULT_NAME' => $catList[$i]["name"], 'CATEGORIES_RESULT_DESCRIPTION' => $description, 'CATEGORIES_RESULT_EBAY_MAPPING' => $mappingCellHtml, 'FORM_BGCOLOR' => $bgcolor, 'CATEGORIES_CONFIRM_ERASE' => $txt["categoriesConfirmErase"] . $catList[$i]["name"]));
                        $template->pparse('categoriesDisplaySearchResultsBody');
                        $i++;
                    }
                    // Footer
                    $template->set_filenames(array('categoriesDisplaySearchResultsFooter' => 'categories_display_search_results_footer.tpl'));
                    $template->assign_vars(array('CATEGORIES_EBAY_STORE_BLOCK' => $ebayStoreBlockHtml));
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
                'verProg' => $var["verProg"]));
        $template->pparse('body');
        break;
} 

?>

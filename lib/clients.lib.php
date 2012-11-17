<?php
include "class/customer.class.php";
$customer = new Customer();
include "class/category.class.php";
$category = new Category();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/order.class.php";
$order = new Order();


switch ($a) {
    case "cr": 
        // Display create header
        $template->set_filenames(array('clientCreateHeader' => 'client_create_header.tpl'));
        $template->assign_vars(array('CLIENTS_CREATE' => $txt["createClient"])); 
        // Display empty mask
        $template->set_filenames(array('clientCreateMask' => 'client_main_mask.tpl'));
        $template->assign_vars(array('CLIENTS_CIVILITY' => $txt["civility"], 'CLIENTS_ORGANISATION' => $txt["organisation"], 'CLIENTS_NAME' => $txt["name"], 'CLIENTS_FIRSTNAME' => $txt["firstname"], 'CLIENTS_ADDRESS' => $txt["address"], 'CLIENTS_POSTCODE' => $txt["postCode"], 'CLIENTS_CITY' => $txt["city"], 'CLIENTS_COUNTRY' => $txt["country"], 'CLIENTS_ENTRYDATE' => $txt["entryDate"], 'CLIENTS_NOTES' => $txt["notes"], 'CLIENTS_TELEPHONE' => $txt["telephone"], 'CLIENTS_TELEPHONE2' => $txt["telephone2"], 'CLIENTS_MOBILE' => $txt["mobile"], 'CLIENTS_FAX' => $txt["fax"], 'CLIENTS_EMAIL' => $txt["email"], 'CLIENTS_ACTIVE' => $txt["active"],
                'CLIENTS_FORM' => "?m=cl&a=cr", // FORM Target URL <----------------------
                'CLIENTS_SUBMIT' => $txt["submit"], 'CLIENTS_' => $txt[""]));

        if ($_POST["sub"] == "ok") { // If form is filled...
            if (($_POST["name"]) OR ($_POST["organisation"])) { // Check for correct entry
                // Ok, we're recording the new customer
                $customer->assignData($_POST);
                if (($customer->add()) > -1) {
                    $template->assign_vars(array('CLIENTS_MSG' => $txt["clientCreateOk"]));
                } else $template->assign_vars(array('CLIENTS_MSG' => $txt["clientCreateDbError"]));
                $template->pparse('clientCreateHeader');
            } else { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('CIVILITY_V' => $_POST["civility"], 'ORGANISATION_V' => $_POST["organisation"], 'NAME_V' => $_POST["name"], 'FIRSTNAME_V' => $_POST["firstname"], 'ADDRESS_V' => $_POST["address"], 'POSTCODE_V' => $_POST["postCode"], 'CITY_V' => $_POST["city"], 'COUNTRY_V' => $_POST["country"], 'ENTRYDATE_V' => $_POST["entryDate"], 'NOTES_V' => $_POST["notes"], 'TELEPHONE_V' => $_POST["telephone"], 'TELEPHONE2_V' => $_POST["telephone2"], 'MOBILE_V' => $_POST["mobile"], 'FAX_V' => $_POST["fax"], 'EMAIL_V' => $_POST["email"],
                        'CLIENTS_FORM' => "?m=cl&a=cr", // FORM Target URL <----------------------
                        'CLIENTS_SUBMIT' => $txt["submit"], 'CLIENTS_' => $txt[""]));
                if ($_POST["active"] == "on") $template->assign_vars(array('ACTIVE_V' => "checked")); 
                // Display mask with error message
                $template->assign_vars(array('CLIENTS_MSG' => $txt["clientCreateMissedField"]));
                $template->pparse('clientCreateHeader');
                $template->pparse('clientCreateMask');
            } 
        } else { // form not filled... New form
            $template->assign_vars(array('ACTIVE_V' => "checked")); 
            // display empty mask
            $template->pparse('clientCreateHeader');
            $template->pparse('clientCreateMask');
        } 
        break;
    case "vi":
        switch ($sa) {
            case "re": // Display search results
//              if (($_POST["sub"] == "ok") OR ($_GET["sub"] == "ok")) // If form is filled...
		if ($_POST['id'] != "")
		    $id = $_POST['id'];
		else
                    $id = $_GET['id'];
		if ($_GET["del"] == "1")
		{ // Delete the record whith the id
		    $customer->idCustomer=$id;
		    if ($customer->del())
			$template->assign_vars(array('CLIENTS_MSG' => ($txt["clientEraseDone"])));
		    else
			$template->assign_vars(array('CLIENTS_MSG' => ($txt["clientEraseNotOk"])));
		} 

		if (($_GET["research"] == "ok") or ($_POST["research"] == "ok"))
		    $search=$customer->search("cache");
		else
		{
		    $customer->assignData($_POST);
		    $search=$customer->search();
		}
		$nbResult=sizeof($search);
		
		// Display display/search header
		$template->set_filenames(array('clientDisplayHeader' => 'client_display_header.tpl'));
		// Display search table header
		$template->set_filenames(array('clientDisplaySearchResultsHeader' => 'client_display_search_results_header.tpl'));
		$template->assign_vars(array('CLIENTS_DATABASE' => $txt["clientDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
		$template->assign_vars(array('CLIENTS_ORGANISATION' => $txt["organisation"], 'CLIENTS_NAME' => $txt["name"], 'CLIENTS_FIRSTNAME' => $txt["firstname"], 'CLIENTS_CITY' => $txt["city"], 'CLIENTS_ACTIVE' => $txt["active"], 'CLIENTS_MODIFY' => $txt["modify"], 'CLIENTS_ERASE' => $txt["erase"]));
		$template->pparse('clientDisplayHeader');
		$template->pparse('clientDisplaySearchResultsHeader');
		// Display search results body
		$template->set_filenames(array('clientDisplaySearchResultsBody' => 'client_display_search_results_body.tpl'));
		$i = 0;
		while ($i != $nbResult)
		{
		    if ($search[$i]["active"]==1)
			$active = '<img src="' . $template_path . $template_name . '/images/check-ok.png" alt="'.$search[$i]["active"].'">';
		    else
			$active = "";
		    $template->assign_vars(array('CLIENTS_RESULT_MODIFY' => "?m=cl&a=vi&sa=mo&id=" . $search[$i]["idCustomer"], 'CLIENTS_RESULT_ERASE' => "?m=cl&a=vi&sa=re&del=1&research=ok&id=" . $search[$i]["idCustomer"], 'CLIENTS_RESULT_NAME' => $search[$i]["name"], 'CLIENTS_RESULT_FIRSTNAME' => $search[$i]["firstname"], 'CLIENTS_RESULT_CITY' => $search[$i]["city"], 'CLIENTS_RESULT_ACTIVE' => $active));
		    $template->pparse('clientDisplaySearchResultsBody');
		    $i++;
		} 
		// Footer
		$template->set_filenames(array('clientDisplaySearchResultsFooter' => 'client_display_search_results_footer.tpl'));
		$template->pparse('clientDisplaySearchResultsFooter');
		break;
            case "mo": // Display Modify form
                if ($_POST['id'] != "") {
                    $id = $_POST['id'];
                } else {
                    $id = $_GET['id'];
                } 
                $customer->idCustomer=$id;
                if ($_POST["sub"] == "ok") { // Update the current record
								    $customer->assignData($_POST);
										$customer->update();
                    $template->assign_vars(array('CLIENTS_MSG' => $txt["clientModificationDone"]));
                		if ($_POST["createorder"]!="")
                		{
                		  $catalog->idCatalog=$_POST["createorder"];
                		  $catalogInfo=$catalog->get();
                		  $idOrder=$order->create($id,"N/A",$catalogInfo["name"]);
                		  $order->idOrder=$idOrder;
                      $order->addCatalogDetail($_POST["createorder"],0,0);
                      $order->close();
                	 		$msg.=$txt["createOrderDone"]."<br />";
                	 	}
                	 	$msg.=$txt["clientModification"];
                } 
                $record = $customer->get($id);
								if ($record==-1) die("Erreur: fiche non trouvée"); 
 
                // Display display/search header
                $template->set_filenames(array('clientDisplayHeader' => 'client_display_header.tpl')); 
                // Display Modify txt header
                $template->set_filenames(array('clientModify' => 'client_modify.tpl'));
                $template->assign_vars(array('CLIENTS_DATABASE' => $txt["clientDatabase"], 'CLIENTS_MODIFY_TXT' => $msg)); 
                // Re-search cache form
                $template->assign_vars(array('CLIENTS_SEARCH_CACHE_SUBMIT' => $txt["clientSearchCacheSubmit"], 'CLIENTS_SEARCH_FORM' => "?m=cl&a=vi&sa=re"));
                $template->set_filenames(array('clientCreateMask' => 'client_main_mask.tpl'));
                if ($record["active"] == "1") {
                    $active = "CHECKED";
                } else {
                    $active = "";
                }

                // Fetch Order data
                $orderList=$customer->listOrder();
                $orderTxt="<table border=1>";
                $i=0;
//                while (($i!=sizeof($orderList)) AND ($i<3))
                while ($i!=sizeof($orderList))
                {
                  $orderTxt.="<tr><td><a href='#'>" . $orderList[$i]["source"] . "</a></td><td>" . $orderList[$i]["idOrder"] . " (" . date("d/m/Y",$orderList[$i]["date"]) . ")</td></tr>";
								  $i++;
								}
								$orderTxt.="</table>".$txt["createOrder"]."<br />";
								// To add a new order...
								$catList=$catalog->getList();
								$orderTxt.="<SELECT NAME='createorder' onchange=\"this.form.submit();\"><OPTION VALUE='' SELECTED></OPTION>";
        				$i=0;
								while($i!=(sizeof($catList)))
        				{
        					if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
          					else $name=$catList[$i]["name"];
          				$orderTxt.="<OPTION VALUE='".$catList[$i]["idCatalog"]."'>".$name."</OPTION>";
          			$i++;
								}
        				$orderTxt.="</SELECT>";


                $template->assign_vars(array('CLIENTS_CIVILITY' => $txt["civility"], 'CLIENTS_ORGANISATION' => $txt["organisation"], 'CLIENTS_NAME' => $txt["name"], 'CLIENTS_FIRSTNAME' => $txt["firstname"], 'CLIENTS_ADDRESS' => $txt["address"], 'CLIENTS_POSTCODE' => $txt["postCode"], 'CLIENTS_CITY' => $txt["city"], 'CLIENTS_COUNTRY' => $txt["country"], 'CLIENTS_ENTRYDATE' => $txt["entryDate"], 'CLIENTS_NOTES' => $txt["notes"], 'CLIENTS_TELEPHONE' => $txt["telephone"], 'CLIENTS_TELEPHONE2' => $txt["telephone2"], 'CLIENTS_MOBILE' => $txt["mobile"], 'CLIENTS_FAX' => $txt["fax"], 'CLIENTS_EMAIL' => $txt["email"], 'CLIENTS_ACTIVE' => $txt["active"], 'CLIENTS_ORDER' => $txt["order"],
                        'CLIENTS_FORM' => "?m=cl&a=vi&sa=mo&id=$id", // FORM Target URL <----------------------
                        'CLIENTS_SUBMIT' => $txt["submit"], 'CIVILITY_V' => $record["civility"], 'ORGANISATION_V' => $record["organisation"], 'NAME_V' => $record["name"], 'FIRSTNAME_V' => $record["firstname"], 'ADDRESS_V' => $record["address"], 'POSTCODE_V' => $record["postCode"], 'CITY_V' => $record["city"], 'COUNTRY_V' => $record["country"], 'ENTRYDATE_V' => date("d/m/Y", $record["entryDate"]), 'NOTES_V' => $record["notes"], 'TELEPHONE_V' => $record["telephone"], 'TELEPHONE2_V' => $record["telephone2"], 'MOBILE_V' => $record["mobile"], 'FAX_V' => $record["fax"], 'EMAIL_V' => $record["email"], 'ACTIVE_V' => $active, 'ORDER_V' => $orderTxt));
                $template->pparse('clientDisplayHeader');
                $template->pparse('clientModify');
                $template->pparse('clientCreateMask');
                break;
            default: // Display default search screen
                // Display display/search header
                $template->set_filenames(array('clientDisplayHeader' => 'client_display_header.tpl')); 
                // Display search txt
                $template->assign_vars(array('CLIENTS_DATABASE' => $txt["clientDatabase"]));

                $template->set_filenames(array('clientCreateMask' => 'client_main_mask.tpl'));
                $template->assign_vars(array('CLIENTS_CIVILITY' => $txt["civility"], 'CLIENTS_ORGANISATION' => $txt["organisation"], 'CLIENTS_NAME' => $txt["name"], 'CLIENTS_FIRSTNAME' => $txt["firstname"], 'CLIENTS_ADDRESS' => $txt["address"], 'CLIENTS_POSTCODE' => $txt["postCode"], 'CLIENTS_CITY' => $txt["city"], 'CLIENTS_COUNTRY' => $txt["country"], 'CLIENTS_ENTRYDATE' => $txt["entryDate"], 'CLIENTS_NOTES' => $txt["notes"], 'CLIENTS_TELEPHONE' => $txt["telephone"], 'CLIENTS_TELEPHONE2' => $txt["telephone2"], 'CLIENTS_MOBILE' => $txt["mobile"], 'CLIENTS_FAX' => $txt["fax"], 'CLIENTS_EMAIL' => $txt["email"], 'CLIENTS_ACTIVE' => $txt["active"],
                        'CLIENTS_FORM' => "?m=cl&a=vi&sa=re", // FORM Target URL <----------------------
                        'CLIENTS_SUBMIT' => $txt["search"], 'ACTIVE_V' => "CHECKED", 'CLIENTS_' => $txt[""]));

                $template->pparse('clientDisplayHeader');
                $template->pparse('clientCreateMask');
                break;
        } 
        break;
    default:
        $template->set_filenames(array('body' => 'clients_home.tpl'));
        $template->assign_vars(array('CLIENTS' => $txt["clients"],
                'CLIENTS_HOME' => $txt["clientsHome"],
                'verProg' => $var["verProg"]));
        $template->pparse('body');
        break;
} 

?>

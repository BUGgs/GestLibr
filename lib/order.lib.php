<?php
/**
 * * * *      GestLibr: order.lib.php      			*
 * * * *           Order Lib               			*
 * * * *  Romain DUCHENE, jan 2006 - ?				 	*
 */


include "class/book.class.php";
$book = new Book();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/category.class.php";
$category=New Category();
include "class/order.class.php";
$order=New Order();

switch ($a) {
    case "cr":
        // Display create header
        $template->set_filenames(array('orderCreate' => 'order_create.tpl'));
        $template->assign_vars(array('ORDER' => $txt["orders"],
                'CATALOG_FORM' => "?m=ca&a=cr", // FORM Target URL <----------------------
                'CATALOG_SUBMIT' => $txt["submit"]));
        $template->pparse('orderCreate');

      break;
    case "in":
      if (isset($_GET["aa"]))
        $order->updateInternet($_GET["id"], $_GET["aa"]);
        // Display Internet Orders
        $template->set_filenames(array('orderInternetHeader' => 'order_internet_header.tpl'));
        $template->assign_vars(array('ORDER' => $txt["orders"], 'INTERNET' => $txt["internet"]));
        $template->pparse('orderInternetHeader');
        // Loop to display all Internet orders
        $template->set_filenames(array('orderInternetBody' => 'order_internet_body.tpl'));
        $o = $order->getInternet();
        $i=0;
        while ($i!=sizeof($o))
        {
          if ($o[$i]["datePaiement"]==0)
            $datePayement = "<a href='?m=or&a=in&aa=payement&id=" . $o[$i]["id"] . "'>VALIDER</a>";
          else
            $datePayement = date("d/m/Y",$o[$i]["datePaiement"]);
          if ($o[$i]["dateValidate"]==0)
            $dateValidate = "<a href='?m=or&a=in&aa=validate&id=" . $o[$i]["id"] . "'>VALIDER</a>";
          else
            $dateValidate = date("d/m/Y",$o[$i]["dateValidate"]);
          if ($o[$i]["dateShipping"]==0)
            $dateShipping = "<a href='?m=or&a=in&aa=shipping&id=" . $o[$i]["id"] . "'>VALIDER</a>";
          else
            $dateShipping = date("d/m/Y",$o[$i]["dateShipping"]);
          if ($o[$i]["dateReceiving"]==0)
            $dateReceiving = "&nbsp;";
          else
            $dateReceiving = date("d/m/Y",$o[$i]["dateReceiving"]);
          if ($o[$i]["payementType"]=="CB")
          {
            if ($o[$i]["status"]=="terminé")
              $payement = "CB OK";
            else
              $payement = "Err CB";            
          }
          else
            $payement = $o[$i]["payementType"];
          $orderPrint = "<a href='generate.php?t=invoice&type=internet&id=" . $o[$i]["id"] . "' target='_blank'>FACTURE</a> | <a href='generate.php?t=label&a=internet&id=" . $o[$i]["id"] . "' target='_blank'>ETIQUETTE</a>";
          $template->assign_vars(array('ID' => "INT-".$o[$i]["id"], 'ORDER_DATE' => (date("d/m/Y",$o[$i]["dateCreation"])), 'ORDER_MAIL' => $o[$i]["email"], 'ORDER_SHIP_PRICE' => $o[$i]["shipPrice"], 'ORDER_TOTAL_PRICE' => $o[$i]["totalPrice"], 'ORDER_PAYEMENT_TYPE' => $payement, 'ORDER_SHIP_METHOD' => $o[$i]["shipMethod"], 'ORDER_DATE_PAYEMENT' => $datePayement, 'ORDER_DATE_VALIDATE' => $dateValidate, 'ORDER_DATE_SHIPPING' => $dateShipping, 'ORDER_DATE_RECEIVED' => $dateReceiving, 'ORDER_PRINT' => $orderPrint));
          $template->pparse('orderInternetBody');
          $i++;
        }
        $template->set_filenames(array('orderInternetFooter' => 'order_internet_footer.tpl'));
        $template->pparse('orderInternetFooter');

      break;
    default:
        $template->set_filenames(array('body' => 'order_home.tpl'));
        $template->assign_vars(array('ORDER' => $txt["orders"],
                'ORDER_HOME' => ""));
        $template->pparse('body');
        break;
}

?>

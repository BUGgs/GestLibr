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
    case "eb":
      require_once "class/ebay.class.php";
      $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
      $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 25;
      if ($limit <= 0) $limit = 25;
      if ($limit > 200) $limit = 200;
      $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
      if ($page <= 0) $page = 1;
      $status = isset($_GET["status"]) ? trim((string)$_GET["status"]) : "";
      $dateFrom = isset($_GET["date_from"]) ? trim((string)$_GET["date_from"]) : "";
      $dateTo = isset($_GET["date_to"]) ? trim((string)$_GET["date_to"]) : "";
      $validated = (isset($_GET["validated"]) && (string)$_GET["validated"] === "1") ? "1" : "0";

      $syncMessage = "";
      $syncError = "";
      if (isset($_GET["order_sync"]) && (string)$_GET["order_sync"] === "1")
      {
          $syncResult = $order->syncEbayOrders($idGroup, array("limit" => 100, "maxPages" => 500));
          if (is_array($syncResult) && !empty($syncResult["ok"]))
              $syncMessage = (string)$syncResult["message"];
          else if (is_array($syncResult) && isset($syncResult["message"]))
              $syncError = (string)$syncResult["message"];
          else
              $syncError = "Synchro eBay impossible.";
      }

      $ordersData = $order->getEbayOrdersList($idGroup, array(
          "status" => $status,
          "date_from" => $dateFrom,
          "date_to" => $dateTo,
          "validated" => $validated,
      ), $page, $limit);

      $orders = (isset($ordersData["rows"]) && is_array($ordersData["rows"])) ? $ordersData["rows"] : array();
      $total = isset($ordersData["total"]) ? (int)$ordersData["total"] : 0;
      $itemsByOrder = (isset($ordersData["itemsByOrder"]) && is_array($ordersData["itemsByOrder"])) ? $ordersData["itemsByOrder"] : array();

      $offset = ($page - 1) * $limit;
      $querySuffix = "&limit=" . $limit
          . "&status=" . urlencode($status)
          . "&validated=" . urlencode($validated)
          . "&date_from=" . urlencode($dateFrom)
          . "&date_to=" . urlencode($dateTo);
      $prevUrl = ($page > 1) ? ("?m=or&a=eb&page=" . ($page - 1) . $querySuffix) : "";
      $nextUrl = ((($offset + sizeof($orders)) < $total) ? ("?m=or&a=eb&page=" . ($page + 1) . $querySuffix) : "");
      $prevHtml = ($prevUrl != "") ? ("<a class='result-nav__link' href='" . htmlspecialchars($prevUrl, ENT_QUOTES, "UTF-8") . "'>Précédent</a>") : "<span class='result-nav__link is-disabled'>Précédent</span>";
      $nextHtml = ($nextUrl != "") ? ("<a class='result-nav__link' href='" . htmlspecialchars($nextUrl, ENT_QUOTES, "UTF-8") . "'>Suivant</a>") : "<span class='result-nav__link is-disabled'>Suivant</span>";
      $lastSyncRun = $order->getLastEbayOrderSyncRun($idGroup);
      $lastSyncLabel = "Jamais";
      if (is_array($lastSyncRun) && isset($lastSyncRun["endedAtTs"]) && (int)$lastSyncRun["endedAtTs"] > 0)
          $lastSyncLabel = date("d/m/Y H:i:s", (int)$lastSyncRun["endedAtTs"]);
      $lastSyncStatus = (is_array($lastSyncRun) && isset($lastSyncRun["status"])) ? (string)$lastSyncRun["status"] : "n/a";
      $lastSyncStats = "";
      if (is_array($lastSyncRun) && isset($lastSyncRun["nbFetchedOrders"]))
          $lastSyncStats = (int)$lastSyncRun["nbFetchedOrders"] . " cmd, " . (int)$lastSyncRun["nbUpsertItems"] . " ligne(s)";

      $template->set_filenames(array('orderEbay' => 'order_ebay.tpl'));
      $template->assign_vars(array(
          'ORDER' => $txt["orders"],
          'ORDER_EBAY_TITLE' => "Commandes eBay",
          'ORDER_EBAY_FILTER_FORM' => "?m=or&a=eb",
          'ORDER_EBAY_DATE_FROM' => htmlspecialchars(isset($_GET["date_from"]) ? $_GET["date_from"] : "", ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_DATE_TO' => htmlspecialchars(isset($_GET["date_to"]) ? $_GET["date_to"] : "", ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_STATUS' => htmlspecialchars($status, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_VALIDATED' => htmlspecialchars($validated, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_STATUS_ALL_SELECTED' => ($status == "" ? " selected='selected'" : ""),
          'ORDER_EBAY_STATUS_NOT_STARTED_SELECTED' => ($status == "NOT_STARTED" ? " selected='selected'" : ""),
          'ORDER_EBAY_STATUS_IN_PROGRESS_SELECTED' => ($status == "IN_PROGRESS" ? " selected='selected'" : ""),
          'ORDER_EBAY_STATUS_FULFILLED_SELECTED' => ($status == "FULFILLED" ? " selected='selected'" : ""),
          'ORDER_EBAY_STATUS_CANCELLED_SELECTED' => ($status == "CANCELLED" ? " selected='selected'" : ""),
          'ORDER_EBAY_VALIDATED_ALL_SELECTED' => ($validated == "0" ? " selected='selected'" : ""),
          'ORDER_EBAY_VALIDATED_ONLY_SELECTED' => ($validated == "1" ? " selected='selected'" : ""),
          'ORDER_EBAY_LIMIT' => $limit,
          'ORDER_EBAY_TOTAL' => $total,
          'ORDER_EBAY_PAGE' => $page,
          'ORDER_EBAY_PREV' => $prevUrl,
          'ORDER_EBAY_NEXT' => $nextUrl,
          'ORDER_EBAY_PREV_HTML' => $prevHtml,
          'ORDER_EBAY_NEXT_HTML' => $nextHtml,
          'ORDER_EBAY_ERROR' => htmlspecialchars($syncError, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_SYNC_MESSAGE' => htmlspecialchars($syncMessage, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_LAST_SYNC' => htmlspecialchars($lastSyncLabel, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_LAST_SYNC_STATUS' => htmlspecialchars($lastSyncStatus, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_LAST_SYNC_STATS' => htmlspecialchars($lastSyncStats, ENT_QUOTES, "UTF-8"),
          'ORDER_EBAY_SELLER_HUB_URL' => "https://www.ebay.com/sh/ord",
      ));

      $i = 0;
      while ($i < sizeof($orders))
      {
          $row = $orders[$i];
          $orderId = isset($row["ebayOrderId"]) ? $row["ebayOrderId"] : "";
          $creationDate = isset($row["creationDate"]) ? $row["creationDate"] : "";
          $creationDateTs = ($creationDate != "") ? strtotime($creationDate) : false;
          if ($creationDateTs !== false)
              $creationDate = date("d/m/Y H:i", $creationDateTs);
          $fulfillmentStatus = isset($row["orderFulfillmentStatus"]) ? $row["orderFulfillmentStatus"] : "";
          $paymentStatus = isset($row["orderPaymentStatus"]) ? $row["orderPaymentStatus"] : "";
          $buyer = isset($row["buyerUsername"]) ? $row["buyerUsername"] : "";
          $price = isset($row["totalValue"]) ? $row["totalValue"] : "";
          $currency = isset($row["currency"]) ? $row["currency"] : "";
          $orderItems = isset($itemsByOrder[$orderId]) ? $itemsByOrder[$orderId] : array();
          $lineItemsNb = is_array($orderItems) ? sizeof($orderItems) : 0;
          $validatedLabel = ((isset($row["isValidated"]) && (int)$row["isValidated"] === 1) ? "Oui" : "Non");

          $detailsHtml = "<details class='order-ebay-details'><summary>Détails</summary>";
          $detailsHtml .= "<div class='order-ebay-details__body'>";
          if (is_array($orderItems) && sizeof($orderItems) > 0)
          {
              $detailsHtml .= "<table class='bordered'><tr><th>SKU / idBook</th><th>Item eBay</th><th>Titre</th><th>Qté</th><th>Prix unitaire</th></tr>";
              $li = 0;
              while ($li < sizeof($orderItems))
              {
                  $line = $orderItems[$li];
                  $lineSku = isset($line["sku"]) ? $line["sku"] : "";
                  $lineTitle = isset($line["titleSnapshot"]) ? $line["titleSnapshot"] : "";
                  $lineQty = isset($line["quantity"]) ? $line["quantity"] : "";
                  $lineVal = isset($line["unitPrice"]) ? $line["unitPrice"] : "";
                  $lineCur = isset($line["currency"]) ? $line["currency"] : "";
                  $lineUnitPrice = trim($lineVal . " " . $lineCur);
                  $lineIdBook = isset($line["idBook"]) ? (int)$line["idBook"] : 0;
                  $lineItemId = isset($line["ebayItemId"]) ? trim((string)$line["ebayItemId"]) : "";
                  if ($lineIdBook > 0)
                      $lineSkuHtml = "<a target='_blank' href='?m=bo&a=vi&sa=mo&id=" . (int)$lineIdBook . "'>" . htmlspecialchars((string)$lineIdBook, ENT_QUOTES, "UTF-8") . "</a>";
                  else
                      $lineSkuHtml = htmlspecialchars($lineSku, ENT_QUOTES, "UTF-8");
                  if ($lineItemId !== "")
                      $lineItemHtml = "<a target='_blank' href='https://www.ebay.fr/itm/" . rawurlencode($lineItemId) . "'>" . htmlspecialchars($lineItemId, ENT_QUOTES, "UTF-8") . "</a>";
                  else
                      $lineItemHtml = "—";

                  $detailsHtml .= "<tr>"
                      . "<td>" . $lineSkuHtml . "</td>"
                      . "<td>" . $lineItemHtml . "</td>"
                      . "<td>" . htmlspecialchars($lineTitle, ENT_QUOTES, "UTF-8") . "</td>"
                      . "<td>" . htmlspecialchars((string)$lineQty, ENT_QUOTES, "UTF-8") . "</td>"
                      . "<td>" . htmlspecialchars($lineUnitPrice, ENT_QUOTES, "UTF-8") . "</td>"
                      . "</tr>";
                  $li++;
              }
              $detailsHtml .= "</table>";
          }
          else
              $detailsHtml .= "<p>Aucun détail article retourné.</p>";
          $detailsHtml .= "</div></details>";

          $template->assign_block_vars('orderEbayRow', array(
              'ORDER_ID' => htmlspecialchars($orderId, ENT_QUOTES, "UTF-8"),
              'ORDER_DATE' => htmlspecialchars($creationDate, ENT_QUOTES, "UTF-8"),
              'ORDER_VALIDATED' => htmlspecialchars($validatedLabel, ENT_QUOTES, "UTF-8"),
              'ORDER_FULFILLMENT_STATUS' => htmlspecialchars($fulfillmentStatus, ENT_QUOTES, "UTF-8"),
              'ORDER_PAYMENT_STATUS' => htmlspecialchars($paymentStatus, ENT_QUOTES, "UTF-8"),
              'ORDER_BUYER' => htmlspecialchars($buyer, ENT_QUOTES, "UTF-8"),
              'ORDER_TOTAL' => htmlspecialchars(trim($price . " " . $currency), ENT_QUOTES, "UTF-8"),
              'ORDER_LINE_ITEMS_NB' => (int)$lineItemsNb,
              'ORDER_DETAILS_HTML' => $detailsHtml,
          ));
          $i++;
      }

      $template->pparse('orderEbay');
      break;
    default:
        $template->set_filenames(array('body' => 'order_home.tpl'));
        $template->assign_vars(array('ORDER' => $txt["orders"],
                'ORDER_HOME' => ""));
        $template->pparse('body');
        break;
}

?>

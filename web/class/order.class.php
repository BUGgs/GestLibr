<?php

/* Order Class
* v 1.0
* Romain DUCHENE
* 04 november 2005 -
*/

Class Order {

	var $babase;
	var $idOrder;
	var $err_msg = "";
	var $error = 0;
	var $tableExistsCache = array();
		
	function __construct()
	{
		$this->Order();
	}

	function Order()
	{
		$this->babase = new DataBase();
		$this->babase->DbConnect();
		$this->idOrder = -1;
	}

	function secureData($data)
	{
		return(addslashes($data));
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

	function isExisting()
	{
		// Check if idOrder exists
		// Return true if idOrder exists, false if not
		settype($this->idOrder, 'integer');
		$nul=$this->babase->DbSelect("SELECT `idOrder` FROM `order` WHERE `idGroup`='".$_SESSION["idGroup"]."' AND `idOrder`='".$this->idOrder."';",$nb);
		if ($nb==1) return(true);
		else return(false);
	}

	function changeStatus($status,$description="")
	{
		// Change the order status
		// $status : The new status = creation, addDetail, validate, paid, shipped, received, cancelled, closed
		// Return true if ok
		settype($this->idOrder, 'integer');
		$status=$this->secureData($status);
		$description=$this->secureData($description);
		if ($this->idOrder<1)
		  return(false);
		if ($this->isExisting())
		{
			if ($this->babase->DbInsert("INSERT INTO `orderstatus` ( `idOrderStatus` , `idOrder` , `status` , `description` , `idUser` ) VALUES ('', '".$this->idOrder."', '$status', '$description', '".$_SESSION["idUser"]."');",1)>0)
			{
			  $this->babase->DbQuery("UPDATE `order` SET `status` = '$status' WHERE `idOrder` = '".$this->idOrder."' LIMIT 1 ;");
			  return(true);
			}
			else // trouble during orderstatus add...
		  	return(false);
		}
		else // idOrder not found
			return(false);
	}

	function status()
	{
		// Return current status of current order
		// Return his status, false if not found
		if ($this->isExisting())
		{
		  $tmp=$this->babase->DbSelect("SELECT `status` FROM `order` WHERE `idOrder`='".$this->idOrder."';",$nb);
		  return($tmp[0]["status"]);
		}
		else return(false);
	}

	function statusHistory()
	{
		// Return full status history of current order
		// Return his status history, false if not found
		settype($this->idOrder, 'integer');
		// TODO
	}

	function create($idCustomer,$refOrder,$source)
	{
		// Creating a new Order in the database
		// $idCustomer : The new catalog name
		// $refOrder : Reference Order
		// $source : Source of the order (web, phone...)
		// Return the id of the new order just created
		settype($idCustomer, 'integer');
		$refOrder=$this->secureData($refOrder);
		$source=$this->secureData($source);
	  $this->idOrder=$this->babase->DbInsert("INSERT INTO `order` ( `idOrder` , `refOrder` , `source` , `date` , `idCustomer` , `price` , `vtaPrice` , `totalPrice` , `status` , `idUser`, `idGroup` ) VALUES ('', '$refOrder', '$source', '".time()."', '$idCustomer', '0.00', '0.00', '0.00', '', '".$_SESSION["idUser"]."', '".$_SESSION["idGroup"]."');",1);
	  $this->changeStatus("creation");
	  return($this->idOrder);
	}

	function addDetail($type,$id1,$id2,$name,$price,$vta,$quantity,$description="")
	{
		// Generic function to add some new detail to current order
		// $type : type of source of detail (catalog, directId, ...)
		// $id1 : param 1
		// $id2 : param 2
		// $name : name of this detailorder
		// $price : price without vta
		// $vta : vta to add
		// $description : More description about this detailorder
		// Return the id of the new orderdetail just created
		$type=$this->secureData($type);
		$id1=$this->secureData($id1);
		$id2=$this->secureData($id2);
		$name=$this->secureData($name);
		settype($quantity, 'int');
		settype($price, 'float');
		settype($vta, 'float');
		$description=$this->secureData($description);
		if ($this->isExisting())
		{
		  $totalPrice=$quantity*($price+(($vta*$price)/100));
			$id=$this->babase->DbInsert("INSERT INTO `orderdetails` ( `idOrderDetails` , `idOrder` , `type` , `id1` , `id2` , `name` , `description` , `price` , `vta` , `quantity` , `totalPrice` ) VALUES ('', '" . $this->idOrder . "', '$type', '$id1', '$id2', '$name', '$description', '$price', '$vta', '$quantity', '$totalPrice');",1);
		}
		else return(false);
		$this->checkTotal();
	  $this->changeStatus("addDetail",$id);
		return($id);
	}

	function checkTotal()
	{
		// Check & claculate the big total of current order
		// Return true if total is correctly caculated, false if not
		if ($this->isExisting())
		{
		  $tmp=$this->babase->DbSelect("SELECT * FROM `orderdetails` WHERE `idOrder`='".$this->idOrder."';",$nb);
		  $orderPrice=0;
			settype($orderPrice, 'float');
		  $orderVtaPrice=0;
			settype($orderVtaPrice, 'float');
		  $orderTotalPrice=0;
			settype($orderTotalPrice, 'float');
		  $i=0;
		  while($i!=$nb)
		  {
		    $orderPrice+=$tmp[$i]["quantity"]*$tmp[$i]["price"];
		    $orderVta+=$tmp[$i]["quantity"]*(($tmp[$i]["vta"]*$tmp[$i]["price"])/100);
		    $orderTotalPrice+=($tmp[$i]["quantity"]*$tmp[$i]["price"])+$tmp[$i]["quantity"]*(($tmp[$i]["vta"]*$tmp[$i]["price"])/100);
		    $i++;
			}
			$this->babase->DbQuery("UPDATE `order` SET `price`='$orderPrice', `vtaPrice`='$orderVta', `totalPrice`='$orderTotalPrice' WHERE `idOrder` = '" . $this->idOrder . "' LIMIT 1;");
			return(true);
		}
		else return(false);
	}

	function addCatalogDetail($idCatalog,$idCatalogData,$quantity)
	{
		// To add some new detail from a catalog to current order
		// $idCatalog : the idCatalog to find the item
		// $idCatalogData : the idCatalogData
		// $quantity : quantity to buy
		// Return the id of the new orderdetail just created, and false if creation failed
		
		///////////////////////
		// TODO in conf file... Not in hard...
		$vta = "5.5";
		///////////////////////
		settype($idCatalog, 'integer');
		settype($idCatalogData, 'integer');
		settype($quantity, 'integer');
		$catalog=$this->babase->DbSelect("SELECT * FROM catalog WHERE `idGroup`='".$_SESSION["idGroup"]."' AND idCatalog='$idCatalog';",$nbCatalog);
		$catalogData=$this->babase->DbSelect("SELECT * FROM `catalogdata` WHERE `idCatalog`='$idCatalog' AND `idCatalogData`;",$nbCatalogData);
		if ((($nbCatalog!=1) OR ($nbCatalogData!=1)) AND $idCatalogData!=0) return(false);
		// TODO : remove 1 from book quantity
		//$book=$this->babase->DbSelect("SELECT * FROM `book` WHERE `idBook` = '" . $catalogData[0]["name"] . "';",$nul);
		//if ($nul!=1) return(false);
		if ($idCatalogData==0)
		{
			$name = $catalog[0]["name"];
			$description = "Inconnu";
		}
		else
		{
			$name = $catalog[0]["name"]. " - N° ". $catalogData[0]["number"];
			$description = explode("\n",$catalogData[0]["description"]);
			$description = $description[0];
		}
		return($this->addDetail("catalog",$idCatalog,$idCatalogData,$name,$catalogData[0]["price"],$vta,$quantity,$description));
	}

	function close()
	{
		// Closing the active order
		// $this->idOrder : order id to close
		// Return true if closing is ok, false if not
		if ($this->isExisting())
		{
	  	$this->changeStatus("closed");
	  	return(true);
		}
		else return(false);
	}

	function delete()
	{
		// Deleting the active order in the database
		// $this->idOrder : order id to delete
		// Return true if deleting is ok, false if not
	}

	function getInternet($id=-1)
	{
		// Get the list or all Internet Order, directly online
		// $idCustomer : The id of the specific order (if empty, all orders are returned)
		// Return all infos for the specific order, or all the list
		settype($id, 'integer');
		$babase_internet = new DataBase();
		$babase_internet->DbInit(config("order_internet_username"), config("order_internet_password"), config("order_internet_server"), config("order_internet_database"));
		$babase_internet->DbConnect();
		if ($id==-1) // We want all the list
		  $sql = $babase_internet->DbSelect("SELECT c.*, o.*, p.status FROM `order` as o, `customer` as c, `paiement` as p WHERE o.idCustomer=c.id AND ((o.payementType='CHQ' AND p.idPaiement='9') OR (p.description=o.id AND p.origine='webshop')) ORDER BY o.dateCreation DESC",$nul);
		  //$sql = $babase_internet->DbSelect("SELECT c.*, o.*, p.status FROM `order` as o, `customer` as c, `paiement` as p WHERE o.idCustomer=c.id AND p.description=o.id AND p.origine='webshop' ORDER BY o.dateCreation DESC",$nul);
		else // We want only 1 specific order
		  $sql = $babase_internet->DbSelect("SELECT o.*, c.*, p.status FROM `order` as o, `customer` as c WHERE o.idCustomer=c.id  AND id='$id' AND p.description=o.id AND p.origine='webshop'",$nul);		
//var_dump($sql);
		$babase = new DataBase();
		$babase->DbConnect();
	  return($sql);
	}

	function updateInternet($id=-1,$type="")
	{
		// Update one Internet Order, directly online
		// $idCustomer : The id of the specific order (if empty, all orders are returned)
		// Return all infos for the specific order, or all the list
		settype($id, 'integer');
		$babase_internet = new DataBase();
		$babase_internet->DbInit(config("order_internet_username"), config("order_internet_password"), config("order_internet_server"), config("order_internet_database"));
		$babase_internet->DbConnect();
		if ($id==-1) // Error
		  return(false);
		else // We want to update
		  switch($type)
		  {
		  case "payement":
		    $sql = "datePaiement = '" . time() . "'";
		    break;
		  case "validate":
		    $sql = "dateValidate = '" . time() . "'";
		    break;
		  case "shipping":
		    $sql = "dateShipping = '" . time() . "'";
		    break;
		  case "receiving":
		    $sql = "dateReceived = '" . time() . "'";
		    break;
		  default:
		    return(false);
		    break;
      }
		$babase_internet->DbQuery("UPDATE `order` SET $sql WHERE id='$id'");		
		$babase = new DataBase();
		$babase->DbConnect();
	  return(true);
	}

	function ensureEbayOrderSchema()
	{
		$this->babase->DbQuery("CREATE TABLE IF NOT EXISTS `ebay_order` (
			`id` BIGINT NOT NULL AUTO_INCREMENT,
			`idGroup` INT NOT NULL,
			`ebayOrderId` VARCHAR(80) NOT NULL,
			`creationDate` VARCHAR(64) NOT NULL DEFAULT '',
			`creationDateTs` INT NOT NULL DEFAULT 0,
			`lastModifiedDate` VARCHAR(64) NOT NULL DEFAULT '',
			`lastModifiedDateTs` INT NOT NULL DEFAULT 0,
			`orderPaymentStatus` VARCHAR(64) NOT NULL DEFAULT '',
			`orderFulfillmentStatus` VARCHAR(64) NOT NULL DEFAULT '',
			`buyerUsername` VARCHAR(255) NOT NULL DEFAULT '',
			`totalValue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			`currency` VARCHAR(8) NOT NULL DEFAULT '',
			`isValidated` TINYINT(1) NOT NULL DEFAULT 0,
			`rawJson` LONGTEXT,
			`firstSeenTs` INT NOT NULL DEFAULT 0,
			`lastSeenTs` INT NOT NULL DEFAULT 0,
			`createdAtTs` INT NOT NULL DEFAULT 0,
			`updatedAtTs` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_group_order` (`idGroup`,`ebayOrderId`),
			KEY `idx_group_creation_ts` (`idGroup`,`creationDateTs`),
			KEY `idx_group_updated_ts` (`idGroup`,`updatedAtTs`),
			KEY `idx_group_validated` (`idGroup`,`isValidated`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$this->babase->DbQuery("CREATE TABLE IF NOT EXISTS `ebay_order_item` (
			`id` BIGINT NOT NULL AUTO_INCREMENT,
			`idGroup` INT NOT NULL,
			`ebayOrderId` VARCHAR(80) NOT NULL,
			`lineItemId` VARCHAR(120) NOT NULL,
			`sku` VARCHAR(120) NOT NULL DEFAULT '',
			`idBook` INT NOT NULL DEFAULT 0,
			`ebayItemId` VARCHAR(64) NOT NULL DEFAULT '',
			`listingRef` VARCHAR(128) NOT NULL DEFAULT '',
			`titleSnapshot` VARCHAR(512) NOT NULL DEFAULT '',
			`quantity` INT NOT NULL DEFAULT 0,
			`stockAppliedQty` INT NOT NULL DEFAULT 0,
			`stockAppliedAtTs` INT NOT NULL DEFAULT 0,
			`stockAppliedRunId` INT NOT NULL DEFAULT 0,
			`unitPrice` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			`currency` VARCHAR(8) NOT NULL DEFAULT '',
			`rawJson` LONGTEXT,
			`createdAtTs` INT NOT NULL DEFAULT 0,
			`updatedAtTs` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_group_line_item` (`idGroup`,`lineItemId`),
			KEY `idx_group_order` (`idGroup`,`ebayOrderId`),
			KEY `idx_group_book` (`idGroup`,`idBook`),
			KEY `idx_group_ebay_item` (`idGroup`,`ebayItemId`),
			KEY `idx_group_stock_applied` (`idGroup`,`stockAppliedQty`,`quantity`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$this->babase->DbQuery("CREATE TABLE IF NOT EXISTS `ebay_order_sync_run` (
			`id` BIGINT NOT NULL AUTO_INCREMENT,
			`idGroup` INT NOT NULL,
			`startedAtTs` INT NOT NULL DEFAULT 0,
			`endedAtTs` INT NOT NULL DEFAULT 0,
			`status` VARCHAR(32) NOT NULL DEFAULT '',
			`message` MEDIUMTEXT,
			`nbFetchedOrders` INT NOT NULL DEFAULT 0,
			`nbUpsertOrders` INT NOT NULL DEFAULT 0,
			`nbUpsertItems` INT NOT NULL DEFAULT 0,
			`nbErrors` INT NOT NULL DEFAULT 0,
			`syncFromTs` INT NOT NULL DEFAULT 0,
			`syncToTs` INT NOT NULL DEFAULT 0,
			`createdAtTs` INT NOT NULL DEFAULT 0,
			`updatedAtTs` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			KEY `idx_group_started` (`idGroup`,`startedAtTs`),
			KEY `idx_group_status` (`idGroup`,`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
	}

	function syncEbayOrders($idGroup, $options=array())
	{
		$idGroup = (int)$idGroup;
		if ($idGroup <= 0 && isset($_SESSION["idGroup"]))
			$idGroup = (int)$_SESSION["idGroup"];
		if ($idGroup <= 0)
			return array("ok" => false, "message" => "Groupe invalide.");

		$this->ensureEbayOrderSchema();
		require_once __DIR__ . '/ebay.class.php';

		$dryRun = (isset($options["dryRun"]) && (int)$options["dryRun"] === 1);
		$limit = isset($options["limit"]) ? (int)$options["limit"] : 100;
		if ($limit <= 0) $limit = 100;
		if ($limit > 200) $limit = 200;
		$maxPages = isset($options["maxPages"]) ? (int)$options["maxPages"] : 200;
		if ($maxPages <= 0) $maxPages = 200;

		$syncFromTs = $this->getLastSuccessfulEbayOrderSyncTs($idGroup);
		$syncToTs = time();
		$minAllowedTs = max(0, $syncToTs - (730 * 24 * 3600) + 60);
		$fromTsCandidate = ($syncFromTs > 0) ? ($syncFromTs - 300) : $minAllowedTs;
		$fromTs = max(0, min($syncToTs, max($minAllowedTs, $fromTsCandidate)));
		$fromIso = gmdate("Y-m-d\\TH:i:s.000\\Z", $fromTs);
		$toIso = gmdate("Y-m-d\\TH:i:s.000\\Z", $syncToTs);
		$filter = "lastmodifieddate:[".$fromIso."..".$toIso."]";
		$useFilter = true;
		$fallbackNoFilter = false;

		$runId = 0;
		if (!$dryRun)
			$runId = $this->startEbayOrderSyncRun($idGroup, $syncFromTs, $syncToTs);
		$offset = 0;
		$pageCount = 0;
		$totalRemote = -1;
		$fetchedOrders = 0;
		$upsertOrders = 0;
		$upsertItems = 0;
		$validatedLinesById = array();
		$errors = array();

		while ($pageCount < $maxPages)
		{
			$requestParams = array(
				"idGroup" => $idGroup,
				"limit" => $limit,
				"offset" => $offset,
			);
			if ($useFilter)
				$requestParams["filter"] = $filter;

			$response = ebay_GetOrders($requestParams);

			if (!is_array($response))
			{
				$errors[] = "Appel API eBay impossible (offset ".$offset.").";
				break;
			}
			if (isset($response["__error"]))
			{
				$errorText = trim((string)$response["__error"]);
				if ($errorText == "")
					$errorText = "Erreur inconnue API eBay.";

				$lowerError = strtolower($errorText);
				$canFallbackNoFilter = (
					(strpos($lowerError, "30830") !== false) ||
					(strpos($lowerError, "30850") !== false) ||
					(strpos($lowerError, "start date") !== false) ||
					(strpos($lowerError, "end date") !== false) ||
					(strpos($lowerError, "filter") !== false)
				);

				if ($useFilter && $canFallbackNoFilter)
				{
					$useFilter = false;
					$fallbackNoFilter = true;
					$errors[] = "Filtre Fulfillment non supporté, fallback sans filtre: " . $errorText;
					continue;
				}

				$errors[] = "Erreur API eBay (offset ".$offset."): " . $errorText;
				break;
			}

			$orders = (isset($response["orders"]) && is_array($response["orders"])) ? $response["orders"] : array();
			if ($totalRemote < 0)
				$totalRemote = isset($response["total"]) ? (int)$response["total"] : -1;

			$countPage = sizeof($orders);
			if ($countPage <= 0)
				break;

			$fetchedOrders += $countPage;
			$i = 0;
			while ($i < $countPage)
			{
				$orderPayload = is_array($orders[$i]) ? $orders[$i] : array();
				$validatedLines = $this->extractValidatedOrderLinesFromPayload($orderPayload);
				$v = 0;
				while ($v < sizeof($validatedLines))
				{
					$line = $validatedLines[$v];
					$lineItemId = isset($line["lineItemId"]) ? trim((string)$line["lineItemId"]) : "";
					if ($lineItemId != "")
						$validatedLinesById[$lineItemId] = $line;
					$v++;
				}

				if ($dryRun)
				{
					$upsertOrders += 1;
					$upsertItems += $this->countOrderLineItems($orderPayload);
				}
				else
				{
					$stats = $this->upsertEbayOrderPayload($idGroup, $orderPayload);
					$upsertOrders += (int)$stats["orders"];
					$upsertItems += (int)$stats["items"];
				}
				$i++;
			}

			$offset += $countPage;
			$pageCount++;
			if ($totalRemote >= 0 && $offset >= $totalRemote)
				break;
			if ($countPage < $limit)
				break;
		}

		$status = (sizeof($errors) > 0) ? "done_with_errors" : "done";
		$message = "Synchro commandes eBay " . ($dryRun ? "simulée" : "terminee") . ". " . $fetchedOrders . " commande(s) lue(s), "
			. $upsertItems . " ligne(s) synchronisee(s).";
		if ($fallbackNoFilter)
			$message .= " Mode fallback actif: récupération sans filtre date.";
		if (sizeof($errors) > 0)
			$message .= " Erreurs: " . implode(" | ", $errors);

		if (!$dryRun)
			$this->finishEbayOrderSyncRun($runId, $status, $message, $fetchedOrders, $upsertOrders, $upsertItems, sizeof($errors));

		return array(
			"ok" => (sizeof($errors) === 0),
			"message" => $message,
			"runId" => (int)$runId,
			"dryRun" => ($dryRun ? 1 : 0),
			"validatedLines" => array_values($validatedLinesById),
			"stats" => array(
				"fetchedOrders" => (int)$fetchedOrders,
				"upsertOrders" => (int)$upsertOrders,
				"upsertItems" => (int)$upsertItems,
				"errors" => (int)sizeof($errors),
				"syncFromTs" => (int)$syncFromTs,
				"syncToTs" => (int)$syncToTs,
				"validatedLines" => sizeof($validatedLinesById),
			),
		);
	}

	function countOrderLineItems($orderPayload)
	{
		$orderPayload = is_array($orderPayload) ? $orderPayload : array();
		$lineItems = $this->arrayValue($orderPayload, "lineItems", array());
		if (!is_array($lineItems))
			return 0;
		return sizeof($lineItems);
	}

	function extractValidatedOrderLinesFromPayload($orderPayload)
	{
		$orderPayload = is_array($orderPayload) ? $orderPayload : array();
		$orderPaymentStatus = trim((string)$this->arrayValue($orderPayload, "orderPaymentStatus", ""));
		$orderFulfillmentStatus = trim((string)$this->arrayValue($orderPayload, "orderFulfillmentStatus", ""));
		if ($orderPaymentStatus !== "PAID" || $orderFulfillmentStatus !== "FULFILLED")
			return array();

		$orderId = trim((string)$this->arrayValue($orderPayload, "orderId", ""));
		$lineItems = $this->arrayValue($orderPayload, "lineItems", array());
		if (!is_array($lineItems) || sizeof($lineItems) === 0)
			return array();

		$out = array();
		$i = 0;
		while ($i < sizeof($lineItems))
		{
			$line = is_array($lineItems[$i]) ? $lineItems[$i] : array();
			$lineItemId = trim((string)$this->arrayValue($line, "lineItemId", ""));
			if ($lineItemId == "")
				$lineItemId = $orderId . "#" . ($i + 1);
			$qty = (int)$this->arrayValue($line, "quantity", 0);
			if ($qty <= 0)
			{
				$i++;
				continue;
			}
			$sku = trim((string)$this->arrayValue($line, "sku", ""));
			$idBook = (ctype_digit($sku) ? (int)$sku : 0);
			$ebayItemId = trim((string)$this->arrayValue($line, "legacyItemId", ""));
			if ($ebayItemId == "")
				$ebayItemId = trim((string)$this->arrayValue($line, "itemId", ""));
			if ($ebayItemId == "")
				$ebayItemId = trim((string)$this->arrayValue($line, "itemLegacyId", ""));
			$out[] = array(
				"ebayOrderId" => $orderId,
				"lineItemId" => $lineItemId,
				"sku" => $sku,
				"idBook" => $idBook,
				"quantity" => $qty,
				"ebayItemId" => $ebayItemId,
			);
			$i++;
		}
		return $out;
	}

	function getEbayOrdersList($idGroup, $filters=array(), $page=1, $limit=25)
	{
		$idGroup = (int)$idGroup;
		$page = (int)$page;
		$limit = (int)$limit;
		if ($idGroup <= 0) $idGroup = (isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0);
		if ($page <= 0) $page = 1;
		if ($limit <= 0) $limit = 25;
		if ($limit > 200) $limit = 200;

		$this->ensureEbayOrderSchema();
		$offset = ($page - 1) * $limit;
		$where = array();
		$where[] = "`idGroup`='" . (int)$idGroup . "'";
		$status = isset($filters["status"]) ? trim((string)$filters["status"]) : "";
		if ($status != "")
			$where[] = "`orderFulfillmentStatus`='" . $this->escapeSql($status) . "'";
		$validatedOnly = (isset($filters["validated"]) && (string)$filters["validated"] === "1");
		if ($validatedOnly)
			$where[] = "`isValidated`='1'";

		$dateFrom = isset($filters["date_from"]) ? trim((string)$filters["date_from"]) : "";
		$dateTo = isset($filters["date_to"]) ? trim((string)$filters["date_to"]) : "";
		if ($dateFrom != "")
		{
			$fromTs = strtotime($dateFrom . " 00:00:00");
			if ($fromTs !== false)
				$where[] = "`creationDateTs`>='" . (int)$fromTs . "'";
		}
		if ($dateTo != "")
		{
			$toTs = strtotime($dateTo . " 23:59:59");
			if ($toTs !== false)
				$where[] = "`creationDateTs`<='" . (int)$toTs . "'";
		}
		$whereSql = implode(" AND ", $where);

		$countRows = $this->babase->DbSelect("SELECT COUNT(*) AS nb FROM `ebay_order` WHERE " . $whereSql, $nbCount);
		$total = 0;
		if (is_array($countRows) && isset($countRows[0]["nb"]))
			$total = (int)$countRows[0]["nb"];

		$rows = $this->babase->DbSelect(
			"SELECT * FROM `ebay_order` WHERE " . $whereSql . " ORDER BY `creationDateTs` DESC, `id` DESC LIMIT " . (int)$offset . "," . (int)$limit,
			$nbRows
		);
		if (!is_array($rows))
			$rows = array();

		$orderIds = array();
		$i = 0;
		while ($i < sizeof($rows))
		{
			$orderIds[] = (string)$rows[$i]["ebayOrderId"];
			$i++;
		}

		return array(
			"rows" => $rows,
			"total" => (int)$total,
			"itemsByOrder" => $this->getEbayOrderItemsByOrderIds($idGroup, $orderIds),
		);
	}

	function getLastEbayOrderSyncRun($idGroup)
	{
		$idGroup = (int)$idGroup;
		if ($idGroup <= 0) $idGroup = (isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0);
		$this->ensureEbayOrderSchema();
		$rows = $this->babase->DbSelect(
			"SELECT * FROM `ebay_order_sync_run` WHERE `idGroup`='" . (int)$idGroup . "' ORDER BY `id` DESC LIMIT 1",
			$nb
		);
		if (is_array($rows) && $nb > 0)
			return $rows[0];
		return array();
	}

	function getBookEbaySalesHistory($idGroup, $idBook, $limit=100)
	{
		$idGroup = (int)$idGroup;
		$idBook = (int)$idBook;
		$limit = (int)$limit;
		if ($idGroup <= 0) $idGroup = (isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0);
		if ($idBook <= 0) return array();
		if ($limit <= 0) $limit = 100;
		if ($limit > 500) $limit = 500;

		$this->ensureEbayOrderSchema();
		$sql = "SELECT i.*, o.`creationDate`, o.`creationDateTs`, o.`orderPaymentStatus`, o.`orderFulfillmentStatus`, o.`buyerUsername`, o.`isValidated`
			FROM `ebay_order_item` i
			LEFT JOIN `ebay_order` o ON o.`idGroup`=i.`idGroup` AND o.`ebayOrderId`=i.`ebayOrderId`
			WHERE i.`idGroup`='" . (int)$idGroup . "' AND i.`idBook`='" . (int)$idBook . "'
			ORDER BY o.`creationDateTs` DESC, i.`id` DESC
			LIMIT " . (int)$limit;
		$rows = $this->babase->DbSelect($sql, $nb);
		if (!is_array($rows))
			$rows = array();
		if (sizeof($rows) == 0)
		{
			// Fallback defensive: idBook is globally unique; if group context drifts
			// (admin cross-group view), keep sales history visible on the book page.
			$sqlAnyGroup = "SELECT i.*, o.`creationDate`, o.`creationDateTs`, o.`orderPaymentStatus`, o.`orderFulfillmentStatus`, o.`buyerUsername`, o.`isValidated`
				FROM `ebay_order_item` i
				LEFT JOIN `ebay_order` o ON o.`idGroup`=i.`idGroup` AND o.`ebayOrderId`=i.`ebayOrderId`
				WHERE i.`idBook`='" . (int)$idBook . "'
				ORDER BY o.`creationDateTs` DESC, i.`id` DESC
				LIMIT " . (int)$limit;
			$fallbackRows = $this->babase->DbSelect($sqlAnyGroup, $nbFallback);
			if (is_array($fallbackRows))
				$rows = $fallbackRows;
		}
		return $rows;
	}

	function getEbayOrderItemsByOrderIds($idGroup, $orderIds)
	{
		$idGroup = (int)$idGroup;
		$out = array();
		if ($idGroup <= 0 || !is_array($orderIds) || sizeof($orderIds) == 0)
			return $out;

		$escaped = array();
		$i = 0;
		while ($i < sizeof($orderIds))
		{
			$orderId = trim((string)$orderIds[$i]);
			if ($orderId != "")
				$escaped[] = "'" . $this->escapeSql($orderId) . "'";
			$i++;
		}
		if (sizeof($escaped) == 0)
			return $out;

		$sql = "SELECT * FROM `ebay_order_item` WHERE `idGroup`='" . (int)$idGroup . "' AND `ebayOrderId` IN (" . implode(",", $escaped) . ") ORDER BY `id` ASC";
		$rows = $this->babase->DbSelect($sql, $nb);
		if (!is_array($rows))
			return $out;

		$j = 0;
		while ($j < sizeof($rows))
		{
			$key = (string)$rows[$j]["ebayOrderId"];
			if (!isset($out[$key]) || !is_array($out[$key]))
				$out[$key] = array();
			$out[$key][] = $rows[$j];
			$j++;
		}
		return $out;
	}

	function upsertEbayOrderPayload($idGroup, $orderPayload)
	{
		$idGroup = (int)$idGroup;
		if (!is_array($orderPayload))
			return array("orders" => 0, "items" => 0);

		$orderId = trim((string)$this->arrayValue($orderPayload, "orderId", ""));
		if ($orderId == "")
			return array("orders" => 0, "items" => 0);

		$creationDate = trim((string)$this->arrayValue($orderPayload, "creationDate", ""));
		$lastModifiedDate = trim((string)$this->arrayValue($orderPayload, "lastModifiedDate", ""));
		$creationDateTs = $this->toTimestamp($creationDate);
		$lastModifiedDateTs = $this->toTimestamp($lastModifiedDate);
		$orderPaymentStatus = trim((string)$this->arrayValue($orderPayload, "orderPaymentStatus", ""));
		$orderFulfillmentStatus = trim((string)$this->arrayValue($orderPayload, "orderFulfillmentStatus", ""));
		$buyer = (array)$this->arrayValue($orderPayload, "buyer", array());
		$buyerUsername = trim((string)$this->arrayValue($buyer, "username", ""));
		$pricingSummary = (array)$this->arrayValue($orderPayload, "pricingSummary", array());
		$total = (array)$this->arrayValue($pricingSummary, "total", array());
		$totalValue = $this->toFloat($this->arrayValue($total, "value", 0));
		$currency = trim((string)$this->arrayValue($total, "currency", ""));
		$isValidated = (($orderPaymentStatus === "PAID") && ($orderFulfillmentStatus === "FULFILLED")) ? 1 : 0;
		$now = time();

		$sqlOrder = "INSERT INTO `ebay_order` (`idGroup`,`ebayOrderId`,`creationDate`,`creationDateTs`,`lastModifiedDate`,`lastModifiedDateTs`,`orderPaymentStatus`,`orderFulfillmentStatus`,`buyerUsername`,`totalValue`,`currency`,`isValidated`,`rawJson`,`firstSeenTs`,`lastSeenTs`,`createdAtTs`,`updatedAtTs`) VALUES ('"
			. (int)$idGroup . "','"
			. $this->escapeSql($orderId) . "','"
			. $this->escapeSql($creationDate) . "','"
			. (int)$creationDateTs . "','"
			. $this->escapeSql($lastModifiedDate) . "','"
			. (int)$lastModifiedDateTs . "','"
			. $this->escapeSql($orderPaymentStatus) . "','"
			. $this->escapeSql($orderFulfillmentStatus) . "','"
			. $this->escapeSql($buyerUsername) . "','"
			. number_format($totalValue, 2, ".", "") . "','"
			. $this->escapeSql($currency) . "','"
			. (int)$isValidated . "','"
			. $this->escapeSql($this->jsonEncode($orderPayload)) . "','"
			. (int)$now . "','"
			. (int)$now . "','"
			. (int)$now . "','"
			. (int)$now . "') ON DUPLICATE KEY UPDATE "
			. "`creationDate`=VALUES(`creationDate`),"
			. "`creationDateTs`=VALUES(`creationDateTs`),"
			. "`lastModifiedDate`=VALUES(`lastModifiedDate`),"
			. "`lastModifiedDateTs`=VALUES(`lastModifiedDateTs`),"
			. "`orderPaymentStatus`=VALUES(`orderPaymentStatus`),"
			. "`orderFulfillmentStatus`=VALUES(`orderFulfillmentStatus`),"
			. "`buyerUsername`=VALUES(`buyerUsername`),"
			. "`totalValue`=VALUES(`totalValue`),"
			. "`currency`=VALUES(`currency`),"
			. "`isValidated`=VALUES(`isValidated`),"
			. "`rawJson`=VALUES(`rawJson`),"
			. "`lastSeenTs`=VALUES(`lastSeenTs`),"
			. "`updatedAtTs`=VALUES(`updatedAtTs`)";
		$this->babase->DbQuery($sqlOrder);

		$lineItems = $this->arrayValue($orderPayload, "lineItems", array());
		$lineCount = 0;
		if (is_array($lineItems))
		{
			$i = 0;
			while ($i < sizeof($lineItems))
			{
				$line = is_array($lineItems[$i]) ? $lineItems[$i] : array();
				$lineItemId = trim((string)$this->arrayValue($line, "lineItemId", ""));
				if ($lineItemId == "")
					$lineItemId = $orderId . "#" . ($i + 1);
				$sku = trim((string)$this->arrayValue($line, "sku", ""));
				$idBook = (ctype_digit($sku) ? (int)$sku : 0);
				$lineQty = (int)$this->arrayValue($line, "quantity", 0);
				$lineItemCost = (array)$this->arrayValue($line, "lineItemCost", array());
				$linePrice = (array)$this->arrayValue($line, "lineItemPrice", array());
				$unitPrice = $this->toFloat($this->arrayValue($linePrice, "value", $this->arrayValue($lineItemCost, "value", 0)));
				$itemCurrency = trim((string)$this->arrayValue($linePrice, "currency", $this->arrayValue($lineItemCost, "currency", $currency)));
				$lineTitle = trim((string)$this->arrayValue($line, "title", ""));
				$ebayItemId = trim((string)$this->arrayValue($line, "legacyItemId", ""));
				if ($ebayItemId == "")
					$ebayItemId = trim((string)$this->arrayValue($line, "itemId", ""));
				if ($ebayItemId == "")
					$ebayItemId = trim((string)$this->arrayValue($line, "itemLegacyId", ""));
				$listingRef = trim((string)$this->arrayValue($line, "listingReference", ""));
				if ($listingRef == "")
					$listingRef = trim((string)$this->arrayValue($line, "legacyVariationId", ""));

				$sqlItem = "INSERT INTO `ebay_order_item` (`idGroup`,`ebayOrderId`,`lineItemId`,`sku`,`idBook`,`ebayItemId`,`listingRef`,`titleSnapshot`,`quantity`,`unitPrice`,`currency`,`rawJson`,`createdAtTs`,`updatedAtTs`) VALUES ('"
					. (int)$idGroup . "','"
					. $this->escapeSql($orderId) . "','"
					. $this->escapeSql($lineItemId) . "','"
					. $this->escapeSql($sku) . "','"
					. (int)$idBook . "','"
					. $this->escapeSql($ebayItemId) . "','"
					. $this->escapeSql($listingRef) . "','"
					. $this->escapeSql(substr($lineTitle, 0, 510)) . "','"
					. (int)$lineQty . "','"
					. number_format($unitPrice, 2, ".", "") . "','"
					. $this->escapeSql($itemCurrency) . "','"
					. $this->escapeSql($this->jsonEncode($line)) . "','"
					. (int)$now . "','"
					. (int)$now . "') ON DUPLICATE KEY UPDATE "
					. "`ebayOrderId`=VALUES(`ebayOrderId`),"
					. "`sku`=VALUES(`sku`),"
					. "`idBook`=VALUES(`idBook`),"
					. "`ebayItemId`=VALUES(`ebayItemId`),"
					. "`listingRef`=VALUES(`listingRef`),"
					. "`titleSnapshot`=VALUES(`titleSnapshot`),"
					. "`quantity`=VALUES(`quantity`),"
					. "`unitPrice`=VALUES(`unitPrice`),"
					. "`currency`=VALUES(`currency`),"
					. "`rawJson`=VALUES(`rawJson`),"
					. "`updatedAtTs`=VALUES(`updatedAtTs`)";
				$this->babase->DbQuery($sqlItem);
				$lineCount++;
				$i++;
			}
		}

		return array("orders" => 1, "items" => $lineCount);
	}

	function startEbayOrderSyncRun($idGroup, $syncFromTs, $syncToTs)
	{
		$now = time();
		return (int)$this->babase->DbInsert(
			"INSERT INTO `ebay_order_sync_run` (`idGroup`,`startedAtTs`,`endedAtTs`,`status`,`message`,`nbFetchedOrders`,`nbUpsertOrders`,`nbUpsertItems`,`nbErrors`,`syncFromTs`,`syncToTs`,`createdAtTs`,`updatedAtTs`) VALUES ('"
			. (int)$idGroup . "','" . (int)$now . "','0','running','',0,0,0,0,'"
			. (int)$syncFromTs . "','" . (int)$syncToTs . "','" . (int)$now . "','" . (int)$now . "')",
			1
		);
	}

	function finishEbayOrderSyncRun($runId, $status, $message, $nbFetchedOrders, $nbUpsertOrders, $nbUpsertItems, $nbErrors)
	{
		$runId = (int)$runId;
		if ($runId <= 0) return;
		$now = time();
		$this->babase->DbQuery("UPDATE `ebay_order_sync_run` SET `endedAtTs`='" . (int)$now . "', `status`='" . $this->escapeSql($status) . "', `message`='" . $this->escapeSql($message) . "', `nbFetchedOrders`='" . (int)$nbFetchedOrders . "', `nbUpsertOrders`='" . (int)$nbUpsertOrders . "', `nbUpsertItems`='" . (int)$nbUpsertItems . "', `nbErrors`='" . (int)$nbErrors . "', `updatedAtTs`='" . (int)$now . "' WHERE `id`='" . (int)$runId . "' LIMIT 1");
	}

	function getLastSuccessfulEbayOrderSyncTs($idGroup)
	{
		$idGroup = (int)$idGroup;
		$rows = $this->babase->DbSelect("SELECT `syncToTs` FROM `ebay_order_sync_run` WHERE `idGroup`='" . (int)$idGroup . "' AND `status`='done' ORDER BY `id` DESC LIMIT 1", $nb);
		if (is_array($rows) && $nb > 0)
			return (int)$rows[0]["syncToTs"];
		return 0;
	}

	function toTimestamp($dateValue)
	{
		$dateValue = trim((string)$dateValue);
		if ($dateValue == "")
			return 0;
		$ts = strtotime($dateValue);
		if ($ts === false)
			return 0;
		return (int)$ts;
	}

	function toFloat($value)
	{
		if (is_string($value))
			$value = str_replace(",", ".", $value);
		return (float)$value;
	}

	function jsonEncode($value)
	{
		$json = json_encode($value, JSON_UNESCAPED_UNICODE);
		if (!is_string($json))
			$json = "";
		return $json;
	}

	function escapeSql($value)
	{
		if (!isset($this->babase->DB_CONN) || !$this->babase->DB_CONN)
			return addslashes((string)$value);
		return mysqli_real_escape_string($this->babase->DB_CONN, (string)$value);
	}

	function arrayValue($array, $key, $default="")
	{
		if (!is_array($array))
			return $default;
		if (!array_key_exists($key, $array))
			return $default;
		return $array[$key];
	}

}

?>

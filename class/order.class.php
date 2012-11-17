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
		  $sql = $babase_internet->DbSelect("SELECT c.*, o.*, p.status FROM `order` as o, `customer` as c, `paiement` as p WHERE o.idCustomer=c.id AND p.description=o.id AND p.origine='webshop' ORDER BY o.dateCreation DESC",$nul);
		else // We want only 1 specific order
		  $sql = $babase_internet->DbSelect("SELECT o.*, c.*, p.status FROM `order` as o, `customer` as c WHERE o.idCustomer=c.id  AND id='$id' AND p.description=o.id AND p.origine='webshop'",$nul);		
		$babase = new DataBase();
		$babase->DbConnect();
	  return($sql);
	}

	function updateInternet($id=-1,$type)
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
	  return($true);
	}

}

?>
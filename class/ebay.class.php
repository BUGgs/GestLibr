<?php
/**
 * * * *      GestLibr: ebay.class.php      			*
 * * * *           Ebay Class               			*
 * * * *  Romain DUCHENE, feb 2012 - ?				*
 */

require_once 'EbatNs/AddItemRequestType.php';
require_once 'EbatNs/GetItemRequestType.php';
require_once 'EbatNs/EndItemRequestType.php';
require_once 'EbatNs/GetOrdersRequestType.php';
require_once 'EbatNs/ReviseItemRequestType.php';
require_once 'EbatNs/EbatNs_Environment.php';

class ebay_GetItem extends EbatNs_Environment
{
    /**
     * ebay_GetItem::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new GetItemRequestType();
        $req->setItemID($params['ItemID']);
        
        $res = $this->proxy->GetItem($req);
        if ($this->testValid($res)) {
            $this->dumpObject($res);
            return (true);
        } else {
            $this->dumpObject($res);
            return (false);
        }
    }
}

class ebay_EndItem extends EbatNs_Environment
{
    /**
     * ebay_EndItem::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call - EndingReason : Incorrect / LostOrBroken / NotAvailable / OtherListingError
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new EndItemRequestType();
        $req->setItemID($params['ItemID']);
        $req->setEndingReason($params['EndingReason']);
        
        $res = $this->proxy->EndItem($req);
        if ($this->testValid($res)) {
            //            $this->dumpObject($res);
            return (true);
        } else {
            $this->dumpObject($res);
            return (false);
        }
    }
}

class ebay_AddItem extends EbatNs_Environment
{
    /**
     * ebay_AddItem::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new AddItemRequestType();
        
        $item = new ItemType();
        $item->setTitle($params['title80']);
        $item->setQuantity($params['quantity']);
        $item->setStartPrice($params['price']);
        $item->setSKU($params['idBook']);
        $item->setDescription("<i><p><font face=Times color=#ad001f size=7><strong>" . $params['title'] . " <br/>par " . $params['author'] . "</strong></font></p><p><font face=Times size=6>" . $params['description'] . "</font></p><p><font face=Times size=5>(Réf: " . $params['idBook'] . ")<br/>&nbsp;<br/>La Librairie est spécialisée en histoire XX° siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales.<br/>Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empêcher que ces tragédies humaines ne se reproduisent.<br/>&nbsp;<br/>Photos supplémentaires sur demande.<br/>&nbsp;<br/>Les ouvrages sont expédiés en envoi simple.<br/>Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande.<br/>&nbsp;</br>Envoi rapide et soigné par un professionnel du livre depuis 30 ans.</font></p></i>");
        
        $item->setCurrency("EUR");
        $item->setCountry("FR");
        $item->setListingDuration("GTC");
        $item->setLocation("Toulouse");
        $item->setListingType("FixedPriceItem");
        $item->Site = 'France';
        $item->setConditionID(5000);
        
        $shipToLocation[] = "FR";
        $shipToLocation[] = "Europe";
        $shipToLocation[] = "Worldwide";
        $item->setShipToLocations($shipToLocation);
        $item->setDispatchTimeMax(3);
        
        // Shipping
        $ShippingServiceOptions = new ShippingServiceOptionsType();
        $cost                   = new AmountType();
        $cost->setTypeValue($amount);
        $cost->setTypeAttribute('currencyID', 'EUR');
        $ShippingServiceOptions->setShippingService(config("ebay_shipping_service_name"));
        $ShippingServiceOptions->setShippingServiceCost($params['ebayShippingCost']);
        $ShippingServiceOptions->setShippingServicePriority(1);
        $ShippingServiceOptions->setShippingTimeMin(2);
        $ShippingServiceOptions->setShippingTimeMax(3);
        $shipping[]                          = $ShippingServiceOptions;
        $InternationalShippingServiceOptions = new InternationalShippingServiceOptionsType();
        $cost                                = new AmountType();
        $cost->setTypeValue($amount);
        $cost->setTypeAttribute('currencyID', 'EUR');
        $InternationalShippingServiceOptions->setShippingService(config("ebay_shipping_service_international_name"));
        $InternationalShippingServiceOptions->setShippingServiceCost($params['ebayShippingCostInternational']);
        $InternationalShippingServiceOptions->setShippingServicePriority(1);
        $InternationalShippingServiceOptions->setShipToLocation('Worldwide');
        $shippingInternational[]   = $InternationalShippingServiceOptions;
        $shippingObj               = new ShippingDetailsType();
        $shippingObj->ShippingType = 'Flat';
        $shippingObj->setPaymentInstructions(config("ebay_payment_instructions"));
        $shippingObj->setShippingServiceOptions($shipping);
        $shippingObj->setInternationalShippingServiceOption($InternationalShippingServiceOptions);
        
        // Payment
        $item->setPaymentMethods("PersonalCheck", 0);
        $item->setPaymentMethods("PayPal", 1);
        $item->setPayPalEmailAddress(config("ebay_paypal_email"));
        
        $item->setShippingDetails($shippingObj);
        
        // Category
        $primaryCategory = new CategoryType();
        $primaryCategory->setCategoryID($params['ebayCategoryId']);
        $item->setPrimaryCategory($primaryCategory);
        
        // Optionnal Secondary Category
        if ($params['ebayCategoryIdSecondary'] > 0) {
            $secondaryCategory = new CategoryType();
            $secondaryCategory->setCategoryID($params['ebayCategoryIdSecondary']);
            $item->setSecondaryCategory($secondaryCategory);
        }
        
        // Return Policy        
        $ReturnPolicy                        = new ReturnPolicyType();
        $ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
        $ReturnPolicy->ReturnsAccepted       = 'ReturnsAccepted';
        $ReturnPolicy->Description           = config("ebay_return_policy");
        $item->setReturnPolicy($ReturnPolicy);
        
        // Book specific attributeSet
        $attSet = new AttributeSetType();
        $attSet->setTypeAttribute('attributeSetID', 1458);
        
        
        // Attribute: Author
        $att = new AttributeType();
        $att->setTypeAttribute('attributeID', 25000);
        $val = new ValType();
        $val->setValueLiteral($params['author']);
        $att->setValue($val, 0);
        $attSet->setAttribute($att, 1);
        //	$attSet->Attribute[] = $att;
        
        if ($params['isbn'] != "") {
            // Attribute: ISBN
            $att = new AttributeType();
            $att->setTypeAttribute('attributeID', 25001);
            $val = new ValType();
            $val->setValueLiteral($params['isbn']);
            $att->setValue($val, 0);
            $attSet->setAttribute($att, 2);
            //	    $attSet->Attribute[] = $att;
        }
        
        // Finilize attributeSet
        $atts = new AttributeSetArrayType();
        $atts->setAttributeSet($attSet, 0);
        $item->setAttributeSetArray($atts);
        
        $req->setItem($item);
        
        $res = $this->proxy->AddItem($req);
        if ($this->testValid($res)) {
            //            echo "<br>VENTE OK !<br>";
            //            $this->dumpObject($res);
            //            $this->dumpObject($req);
            return ($res->ItemID);
        } else {
            //            echo "<br>ERREUR !!!!!<br>";
            //            $this->dumpObject($res);
            //            $this->dumpObject($req);
            return ($res->ItemID);
        }
    }
}

/**
 * sample_GetOrders
 * 
 * Ebay call for GetOrders
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_GetOrders.php,v 1.90 2011-12-29 14:03:00 michaelcoslar Exp $
 * @access public 
 */
class ebay_GetOrders extends EbatNs_Environment
{
    /**
     * sample_GetOrders::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new GetOrdersRequestType();
        $req->setCreateTimeFrom($params['CreateTimeFrom']);
        $req->setCreateTimeTo($params['CreateTimeTo']);
        $req->setOrderRole($params['OrderRole']);
        $req->setOrderStatus($params['OrderStatus']);
        
        $res = $this->proxy->GetOrders($req);
        if ($this->testValid($res)) {
            $this->dumpObject($res);
            return (true);
        } else {
            return (false);
        }
    }
}

/**
 * ebay_ReviseItem
 * 
 * Ebay call for ReviseItem
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_ReviseItem.php,v 1.90 2011-12-29 14:03:02 michaelcoslar Exp $
 * @access public
 */
class ebay_ReviseItem extends EbatNs_Environment
{
    /**
     * ebay_ReviseItem::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new ReviseItemRequestType();
        
        $item = new ItemType();
        $item->setItemID($params['ebayItemId']);
        $item->setTitle($params['title80']);
        $item->setQuantity($params['quantity']);
        $item->setStartPrice($params['price']);
        $item->setSKU($params['idBook']);
        $item->setDescription("<i><p><font face=Times color=#ad001f size=7><strong>" . $params['title'] . " <br/>par " . $params['author'] . "</strong></font></p><p><font face=Times size=6>" . $params['description'] . "</font></p><p><font face=Times size=5>(Réf: " . $params['idBook'] . ")<br/>&nbsp;<br/>La Librairie est spécialisée en histoire XX° siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales.<br/>Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empêcher que ces tragédies humaines ne se reproduisent.<br/>&nbsp;<br/>Photos supplémentaires sur demande.<br/>&nbsp;<br/>Les ouvrages sont expédiés en envoi simple.<br/>Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande.<br/>&nbsp;</br>Envoi rapide et soigné par un professionnel du livre depuis 30 ans.</font></p></i>");
        
        $item->setCurrency("EUR");
        $item->setCountry("FR");
        $item->setListingDuration("GTC");
        $item->setLocation("Toulouse");
        $item->setListingType("FixedPriceItem");
        $item->Site = 'France';
        $item->setConditionID(5000);
        
        $shipToLocation[] = "FR";
        $shipToLocation[] = "Europe";
        $shipToLocation[] = "Worldwide";
        $item->setShipToLocations($shipToLocation);
        $item->setDispatchTimeMax(3);
        
        // Shipping
        $ShippingServiceOptions = new ShippingServiceOptionsType();
        $cost                   = new AmountType();
        $cost->setTypeValue($amount);
        $cost->setTypeAttribute('currencyID', 'EUR');
        $ShippingServiceOptions->setShippingService(config("ebay_shipping_service_name"));
        $ShippingServiceOptions->setShippingServiceCost($params['ebayShippingCost']);
        $ShippingServiceOptions->setShippingServicePriority(1);
        $ShippingServiceOptions->setShippingTimeMin(2);
        $ShippingServiceOptions->setShippingTimeMax(3);
        $shipping[]                          = $ShippingServiceOptions;
        $InternationalShippingServiceOptions = new InternationalShippingServiceOptionsType();
        $cost                                = new AmountType();
        $cost->setTypeValue($amount);
        $cost->setTypeAttribute('currencyID', 'EUR');
        $InternationalShippingServiceOptions->setShippingService(config("ebay_shipping_service_international_name"));
        $InternationalShippingServiceOptions->setShippingServiceCost($params['ebayShippingCostInternational']);
        $InternationalShippingServiceOptions->setShippingServicePriority(1);
        $InternationalShippingServiceOptions->setShipToLocation('Worldwide');
        $shippingInternational[]   = $InternationalShippingServiceOptions;
        $shippingObj               = new ShippingDetailsType();
        $shippingObj->ShippingType = 'Flat';
        $shippingObj->setPaymentInstructions(config("ebay_payment_instructions"));
        $shippingObj->setShippingServiceOptions($shipping);
        $shippingObj->setInternationalShippingServiceOption($InternationalShippingServiceOptions);
        
        // Payment
        $item->setPaymentMethods("PersonalCheck", 0);
        $item->setPaymentMethods("PayPal", 1);
        $item->setPayPalEmailAddress(config("ebay_paypal_email"));
        
        $item->setShippingDetails($shippingObj);
        
        // Category
        $primaryCategory = new CategoryType();
        $primaryCategory->setCategoryID($params['ebayCategoryId']);
        $item->setPrimaryCategory($primaryCategory);
        
        // Optionnal Secondary Category
        if ($params['ebayCategoryIdSecondary'] > 0) {
            $secondaryCategory = new CategoryType();
            $secondaryCategory->setCategoryID($params['ebayCategoryIdSecondary']);
            $item->setSecondaryCategory($secondaryCategory);
        }
        
        // Return Policy        
        $ReturnPolicy                        = new ReturnPolicyType();
        $ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
        $ReturnPolicy->ReturnsAccepted       = 'ReturnsAccepted';
        $ReturnPolicy->Description           = config("ebay_return_policy");
        $item->setReturnPolicy($ReturnPolicy);
        
        // Book specific attributeSet
        $attSet = new AttributeSetType();
        $attSet->setTypeAttribute('attributeSetID', 1458);
        
        
        // Attribute: Author
        $att = new AttributeType();
        $att->setTypeAttribute('attributeID', 25000);
        $val = new ValType();
        $val->setValueLiteral($params['author']);
        $att->setValue($val, 0);
        $attSet->setAttribute($att, 1);
        //	$attSet->Attribute[] = $att;
        
        if ($params['isbn'] != "") {
            // Attribute: ISBN
            $att = new AttributeType();
            $att->setTypeAttribute('attributeID', 25001);
            $val = new ValType();
            $val->setValueLiteral($params['isbn']);
            $att->setValue($val, 0);
            $attSet->setAttribute($att, 2);
            //	    $attSet->Attribute[] = $att;
        }
        
        // Finilize attributeSet
        $atts = new AttributeSetArrayType();
        $atts->setAttributeSet($attSet, 0);
        $item->setAttributeSetArray($atts);
        
        $req->setItem($item);
        
        $res = $this->proxy->ReviseItem($req);
        if ($this->testValid($res)) {
            //            $this->dumpObject($res);
            return ($res->ItemID);
        } else {
            //            $this->dumpObject($res);
            return ($res->ItemID);
        }
    }
}

?>
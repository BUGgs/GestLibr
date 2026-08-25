<?php

/*
 * * * *      GestLibr: ebay.class.php      			*
 * * * *           Ebay Class
 * * * *  Romain DUCHENE, feb 2012 - 2016
 * * * *
 */

/**
 * Include the SDK by using the autoloader from Composer.
 */
require __DIR__ . '/../vendor/autoload.php';
$config_ebay = require __DIR__ . '/../include/ebay.config.php';

use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading;
use \DTS\eBaySDK\FileTransfer;
use \DTS\eBaySDK\Shopping;

class EchoLogger extends Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        if ($message === 'Request') {
            /**
             * When debugging a request the following values are available
             *
             * url     - The API endpoint.
             * headers - Associative array of HTTP headers and values.
             * body    - The XML request.
             */
            printf("URL: %s\n", $context['url']);

            foreach($context['headers'] as $header => $value) {
                printf("Header %s : %s\n", $header, $value);
            }

            printf("Request XML: %s\n", $context['body']);

        } elseif ($message === 'Response') {
            /**
             * When debugging the response only the body is available.
             */
            printf("Response XML: %s\n", $context['body']);
        }
    }
}

function ebay_GetCategory()
{
    global $config_ebay;
    $service                                      = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR
    ));
    $request                                      = new Trading\Types\GetCategoriesRequestType();
    $request->RequesterCredentials                = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];
    /**
     * By specifying 'ReturnAll' we are telling the API return the full category hierarchy.
     */
    $request->DetailLevel                         = array(
        'ReturnAll'
    );
    $request->OutputSelector                      = array(
        'CategoryArray.Category.CategoryID',
        'CategoryArray.Category.CategoryParentID',
        'CategoryArray.Category.CategoryLevel',
        'CategoryArray.Category.CategoryName'
    );
    $response                                     = $service->getCategories($request);
    if ($response->Ack !== 'Success') {
        if (isset($response->Errors)) {
            foreach ($response->Errors as $error) {
                printf("Error: %s\n", $error->ShortMessage);
            }
        }
    } else {
        /**
         * For the US site this will output approximately 18,000 categories.      
         */
        //foreach ($response->CategoryArray->Category as $category) {
        //    printf("Level %s : %s (%s) : Parent ID %s\n", $category->CategoryLevel, $category->CategoryName, $category->CategoryID, $category->CategoryParentID[0]);
        //}
        return (json_decode(json_encode($response->CategoryArray), true));
    }
}


function ebay_GetItem($itemID)
{
    global $config_ebay;
    /**
     * get Item Info from ebay
     */
    $service                  = new Shopping\Services\ShoppingService(array(
        'apiVersion' => $config_ebay['shoppingApiVersion'],
        'appId' => $config_ebay['production']['appId']
    ));
    /**
     * Create the request object.
     *
     * For more information about creating a request object, see:
     * http://devbay.net/sdk/guides/getting-started/#request-object
     */
    $request                  = new Shopping\Types\GetSingleItemRequestType();
    /**
     * Specify the item ID of the listing.
     */
    $request->ItemID          = $sku;
    /**
     * Specify that additional fields need to be returned in the response.
     */
    $request->IncludeSelector = 'ItemSpecifics,Variations,Compatibility,Details';
    /**
     * Send the request to the GetSingleItem service operation.
     *
     * For more information about calling a service operation, see:
     * http://devbay.net/sdk/guides/getting-started/#service-operation
     */
    $response                 = $service->getSingleItem($request);
    /**
     * Output the result of calling the service operation.
     *
     * For more information about working with the service response object, see:
     * http://devbay.net/sdk/guides/getting-started/#response-object
     */
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n", $error->SeverityCode === Shopping\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning', $error->ShortMessage, $error->LongMessage);
        }
    }
    if ($response->Ack !== 'Failure') {
        $item = $response->Item;
        print("$item->Title\n");
        printf("Quantity sold %s, quantiy available %s\n", $item->QuantitySold, $item->Quantity - $item->QuantitySold);
        if (isset($item->ItemSpecifics)) {
            print("\nThis item has the following item specifics:\n\n");
            foreach ($item->ItemSpecifics->NameValueList as $nameValues) {
                printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
            }
        }
        if (isset($item->Variations)) {
            print("\nThis item has the following variations:\n");
            foreach ($item->Variations->Variation as $variation) {
                printf("\nSKU: %s\nStart Price: %s\n", $variation->SKU, $variation->StartPrice->value);
                printf("Quantity sold %s, quantiy available %s\n", $variation->SellingStatus->QuantitySold, $variation->Quantity - $variation->SellingStatus->QuantitySold);
                foreach ($variation->VariationSpecifics as $specific) {
                    foreach ($specific->NameValueList as $nameValues) {
                        printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
                    }
                }
            }
        }
        if (isset($item->ItemCompatibilityCount)) {
            printf("\nThis item is compatible with %s vehicles:\n\n", $item->ItemCompatibilityCount);
            // Only show the first 3.
            $limit = min($item->ItemCompatibilityCount, 3);
            for ($x = 0; $x < $limit; $x++) {
                $compatibility = $item->ItemCompatibilityList->Compatibility[$x];
                foreach ($compatibility->NameValueList as $nameValues) {
                    printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
                }
                printf("Notes: %s \n", $compatibility->CompatibilityNotes);
            }
        }
    }
}

function ebay_GetItemFromSku($sku)
{
    global $config_ebay;
    /**
     * get Item Info from ebay
     */
    $service                  = new Shopping\Services\ShoppingService(array(
        'apiVersion' => $config_ebay['shoppingApiVersion'],
        'appId' => $config_ebay['production']['appId']
    ));
    /**
     * Create the request object.
     *
     * For more information about creating a request object, see:
     * http://devbay.net/sdk/guides/getting-started/#request-object
     */
    $request                  = new Shopping\Types\GetSingleItemRequestType();
    /**
     * Specify the item ID of the listing.
     */
    $request->ItemID          = $itemID;
    /**
     * Specify that additional fields need to be returned in the response.
     */
    $request->IncludeSelector = 'ItemSpecifics,Variations,Compatibility,Details';
    /**
     * Send the request to the GetSingleItem service operation.
     *
     * For more information about calling a service operation, see:
     * http://devbay.net/sdk/guides/getting-started/#service-operation
     */
    $response                 = $service->getSingleItem($request);
    /**
     * Output the result of calling the service operation.
     *
     * For more information about working with the service response object, see:
     * http://devbay.net/sdk/guides/getting-started/#response-object
     */
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n", $error->SeverityCode === Shopping\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning', $error->ShortMessage, $error->LongMessage);
        }
    }
    if ($response->Ack !== 'Failure') {
        $item = $response->Item;
        print("$item->Title\n");
        printf("Quantity sold %s, quantiy available %s\n", $item->QuantitySold, $item->Quantity - $item->QuantitySold);
        if (isset($item->ItemSpecifics)) {
            print("\nThis item has the following item specifics:\n\n");
            foreach ($item->ItemSpecifics->NameValueList as $nameValues) {
                printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
            }
        }
        if (isset($item->Variations)) {
            print("\nThis item has the following variations:\n");
            foreach ($item->Variations->Variation as $variation) {
                printf("\nSKU: %s\nStart Price: %s\n", $variation->SKU, $variation->StartPrice->value);
                printf("Quantity sold %s, quantiy available %s\n", $variation->SellingStatus->QuantitySold, $variation->Quantity - $variation->SellingStatus->QuantitySold);
                foreach ($variation->VariationSpecifics as $specific) {
                    foreach ($specific->NameValueList as $nameValues) {
                        printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
                    }
                }
            }
        }
        if (isset($item->ItemCompatibilityCount)) {
            printf("\nThis item is compatible with %s vehicles:\n\n", $item->ItemCompatibilityCount);
            // Only show the first 3.
            $limit = min($item->ItemCompatibilityCount, 3);
            for ($x = 0; $x < $limit; $x++) {
                $compatibility = $item->ItemCompatibilityList->Compatibility[$x];
                foreach ($compatibility->NameValueList as $nameValues) {
                    printf("%s: %s\n", $nameValues->Name, implode(', ', iterator_to_array($nameValues->Value)));
                }
                printf("Notes: %s \n", $compatibility->CompatibilityNotes);
            }
        }
    }
}

function legacy_ebay_EndItem($params)
{
    global $config_ebay;
    $service = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR,
      'authToken' => $config_ebay['production']['userToken'],
      'devId' => $config_ebay['production']['devId'],
      'appId' => $config_ebay['production']['appId'],
      'certId' => $config_ebay['production']['certId']
    ));
    $service->logger(new EchoLogger());

    // Ending the Sell by ItemId

    $request = new Trading\Types\EndFixedPriceItemRequestType();
    $request->RequesterCredentials = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];
    
    $request->ItemID = $params['ItemID'];
//    $request->SKU = $params['SKU'];
    $request->EndingReason = $params['EndingReason'];
        
    // Sending request    
    $response = $service->EndFixedPriceItem($request);
    
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n",
                $error->SeverityCode === Trading\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                $error->ShortMessage,
                $error->LongMessage
            );
        }
    }
    if ($response->Ack !== 'Failure')
        return($response->EndTime);
    else
        // True anyway because book can be sold already on ebay...
        // TODO: check if ShortMessage is "Les enchères sont terminées." to confirm or not
        return(true);
//        return(false);

}

function legacy_ebay_AddItem($params)
{
    global $config_ebay;
    $service = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR,
      'authToken' => $config_ebay['production']['userToken'],
      'devId' => $config_ebay['production']['devId'],
      'appId' => $config_ebay['production']['appId'],
      'certId' => $config_ebay['production']['certId']
    ));
    $request = new Trading\Types\AddFixedPriceItemRequestType();
    $request->RequesterCredentials = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];
    $item = new Trading\Types\ItemType();
    
    // Details
    $item->Title = htmlspecialchars($params['title80'],ENT_NOQUOTES);
    $item->Quantity = (int)$params['quantity'];
    $item->SKU = $params['idBook'];
//    $item->Description = htmlspecialchars("<i><p><font face='Times' color='#ad001f' size='7'><strong>") . htmlspecialchars($params['title']) . htmlspecialchars(" <br/>par ") . htmlspecialchars($params['author']) . htmlspecialchars("</strong></font></p><p><font face='Times' size='6'>") . htmlspecialchars($params['description']) . htmlspecialchars("</font></p><p><font face='Times' size='5'>(Ref: " . $params['idBook'] . ")<br/>") . "La Librairie est spécialisée en histoire XXe siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales".htmlspecialchars("<br/>")."Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empècher que ces tragédies humaines ne se reproduisent".htmlspecialchars("<br/>")."Photos supplémentaires sur demande".htmlspecialchars("<br/>")."Les ouvrages sont expédiés en envoi simple sous enveloppe à bulle".htmlspecialchars("<br/>")."Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande".htmlspecialchars("</br>")."Envoi rapide et soigné par un professionnel du livre depuis 30 ans".htmlspecialchars("</font></p></i>");
    $item->Description = htmlspecialchars("<i><p><font face='Times' color='#ad001f' size='7'><strong>" . $params['title'] . " <br/>par " . $params['author'] . "</strong></font></p><p><font face='Times' size='6'>" . $params['description'] . "</font></p><p><font face='Times' size='5'>(Ref: " . $params['idBook'] . ")<br/>La Librairie est spécialisée en histoire XXe siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales<br/>Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empècher que ces tragédies humaines ne se reproduisent<br/>Photos supplémentaires sur demande<br/>Les ouvrages sont expédiés en envoi simple sous enveloppe à bulle<br/>Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande</br>Envoi rapide et soigné par un professionnel du livre depuis 30 ans</font></p></i>",ENT_NOQUOTES);
    $item->StartPrice = new Trading\Types\AmountType(array('value' => (double)$params['price']));
    
    // Picture
    if (!empty($params['photo']))
    {
        $item->PictureDetails = new Trading\Types\PictureDetailsType();
        $item->PictureDetails->GalleryType = Trading\Enums\GalleryTypeCodeType::C_GALLERY;
        $item->PictureDetails->PictureURL = array($params['photo']);
    }
    
    $item->Site = Trading\Enums\SiteCodeType::C_FRANCE;
    $item->Country = Trading\Enums\CountryCodeType::C_FR;
    $item->Location = Trading\Enums\PreferredLocationCodeType::C_LOCATED_IN_COUNTRY_IMPLIED;
    $item->Currency = Trading\Enums\CurrencyCodeType::C_EUR;
//    $item->InventoryTrackingMethod = Trading\Enums\InventoryTrackingMethodCodeType::C_SKU;
    
    $item->ListingType = Trading\Enums\ListingTypeCodeType::C_FIXED_PRICE_ITEM;
    $item->ListingDuration = Trading\Enums\ListingDurationCodeType::C_GTC;

    $item->ConditionID = 5000;
        
    $item->ShipToLocations[] = 'FR';
    $item->ShipToLocations[] = 'Europe';
    $item->ShipToLocations[] = 'Worldwide';

    $item->DispatchTimeMax = 3;
    
    // Category
    $item->PrimaryCategory = new Trading\Types\CategoryType();
    $item->PrimaryCategory->CategoryID = $params['ebayCategoryId'];
        
    // Optionnal Secondary Category
    if ($params['ebayCategoryIdSecondary'] > 0) {
        $item->SecondaryCategory = new Trading\Types\CategoryType();
        $item->SecondaryCategory->CategoryID = $params['ebayCategoryIdSecondary'];
    }

    // Payment
    $item->PaymentMethods = array('PayPal');
    $item->PayPalEmailAddress = config("ebay_paypal_email");
    
    // Shipping
    $item->ShippingDetails = new Trading\Types\ShippingDetailsType();
    $item->ShippingDetails->ShippingType = Trading\Enums\ShippingTypeCodeType::C_FLAT;
    $item->ShippingDetails->PaymentInstructions = config("ebay_payment_instructions");
    
    $shippingService = new Trading\Types\ShippingServiceOptionsType();
    $shippingService->ShippingServicePriority = 1;
    $shippingService->ShippingService = config("ebay_shipping_service_name");
    $shippingService->ShippingServiceCost = new Trading\Types\AmountType(array('value' => (double)$params['ebayShippingCost']));
    //$shippingService->ShippingServiceAdditionalCost = new Trading\Types\AmountType(array('value' => 1.00));
    $item->ShippingDetails->ShippingServiceOptions[] = $shippingService;

    $shippingService = new Trading\Types\InternationalShippingServiceOptionsType();
    $shippingService->ShippingServicePriority = 1;
    $shippingService->ShippingService = config("ebay_shipping_service_international_name");
    $shippingService->ShippingServiceCost = new Trading\Types\AmountType(array('value' => (double)$params['ebayShippingCostInternational']));
    //$shippingService->ShippingServiceAdditionalCost = new Trading\Types\AmountType(array('value' => 3.00));
    $shippingService->ShipToLocation = array('WorldWide');
    $item->ShippingDetails->InternationalShippingServiceOption[] = $shippingService;

    // Return Policy
    $item->ReturnPolicy = new Trading\Types\ReturnPolicyType();
    $item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
//    $item->ReturnPolicy->RefundOption = 'MoneyBack';
    $item->ReturnPolicy->ReturnsWithinOption = 'Days_14';
    $item->ReturnPolicy->ShippingCostPaidByOption = 'Buyer';
    $item->ReturnPolicy->Description = config("ebay_return_policy");


    // Book specific attibutes
    $item->ItemSpecifics = new Trading\Types\NameValueListArrayType();
    // Attribute Author (25000)
    $specific = new Trading\Types\NameValueListType();
    $specific->Name = 'Auteur';
    $specific->Value[] = htmlspecialchars(substr($params['author'], 0, 62));
    $item->ItemSpecifics->NameValueList[] = $specific;
    // Attribute Titre
    $specific = new Trading\Types\NameValueListType();
    $specific->Name = 'Titre';
    $specific->Value[] = htmlspecialchars(substr($params['title'], 0, 62));
    $item->ItemSpecifics->NameValueList[] = $specific;
    // Attribute Isbn (25001)
    if ($params['isbn'] != "") {
        $specific = new Trading\Types\NameValueListType();
        $specific->Name = 'Isbn';
        $specific->Value[] = htmlspecialchars(substr($params['isbn'], 0, 62));
        $item->ItemSpecifics->NameValueList[] = $specific;
    }


    // Sending request    
    $request->Item = $item;
    $response = $service->addFixedPriceItem($request);
    
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n",
                $error->SeverityCode === Trading\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                $error->ShortMessage,
                $error->LongMessage
            );
        }
    }
    if ($response->Ack == 'Failure') {
//        var_dump($item);
        return(false);
    }
    else
        // In case of success/warnings...
        return($response->ItemID);
}

function legacy_ebay_GetSellerList($params)
{
    global $config_ebay;
    $service = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR,
      'authToken' => $config_ebay['production']['userToken'],
      'devId' => $config_ebay['production']['devId'],
      'appId' => $config_ebay['production']['appId'],
      'certId' => $config_ebay['production']['certId']
    ));
    $service->logger(new EchoLogger());

    // Ending the Sell by ItemId

    $request = new Trading\Types\GetSellerListRequestType();
    $request->RequesterCredentials = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];
    
    $request->Pagination =  new Trading\Types\PaginationType();
    $request->Pagination->PageNumber = $params["PageNumber"];
    $request->Pagination->EntriesPerPage = $params["EntriesPerPage"];
    $request->EndTimeFrom = $params["EndTimeFrom"];
    $request->EndTimeTo = $params["EndTimeTo"];
    $request->GranularityLevel = Trading\Enums\GranularityLevelCodeType::C_COARSE;
            
    // Sending request    
    $response = $service->GetSellerList($request);
    
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n",
                $error->SeverityCode === Trading\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                $error->ShortMessage,
                $error->LongMessage
            );
        }
        return(false);
    }
    if ($response->Ack !== 'Failure') {
        return($response);
    }

}



    /**
     * ebay_GetOrders()
     * 
     * Get Ebay Order list
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
function legacy_ebay_GetOrders($params)
{
    global $config_ebay;
    $service = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR
    ));
    $request = new Trading\Types\GetOrdersRequestType();
    $request->RequesterCredentials = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];

    $request->CreateTimeFrom = $params['CreateTimeFrom'];
    $request->CreateTimeTo = $params['CreateTimeTo'];
    $request->OrderRole = $params['OrderRole'];
    $request->OrderStatus = $params['OrderStatus'];
    
    $response = $service->getOrders($request);
    
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n",
                $error->SeverityCode === Trading\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                $error->ShortMessage,
                $error->LongMessage
            );
            return(false);
        }
    }
    
    if ($response->Ack !== 'Failure') {
        return($response->OrderArray);
    }    
}

/**
 * ebay_ReviseItem
 * 
 * Ebay call for ReviseItem
 * 
 */
function legacy_ebay_ReviseItem($params)
{
    global $config_ebay;
    $service = new Trading\Services\TradingService(array(
        'apiVersion' => $config_ebay['tradingApiVersion'],
        'siteId' => Constants\SiteIds::FR,
      'authToken' => $config_ebay['production']['userToken'],
      'devId' => $config_ebay['production']['devId'],
      'appId' => $config_ebay['production']['appId'],
      'certId' => $config_ebay['production']['certId']
    ));
    $request = new Trading\Types\ReviseFixedPriceItemRequestType();
    $request->RequesterCredentials = new Trading\Types\CustomSecurityHeaderType();
    $request->RequesterCredentials->eBayAuthToken = $config_ebay['production']['userToken'];
    $item = new Trading\Types\ItemType();
    
    // Details
    $item->ItemID = $params['ebayItemId'];
    $item->Title = htmlspecialchars($params['title80'],ENT_NOQUOTES);
    $item->Quantity = (int)$params['quantity'];
    $item->SKU = $params['idBook'];
//    $item->Description = htmlspecialchars("<i><p><font face='Times' color='#ad001f' size='7'><strong>") . htmlspecialchars($params['title']) . htmlspecialchars(" <br/>par ") . htmlspecialchars($params['author']) . htmlspecialchars("</strong></font></p><p><font face='Times' size='6'>") . htmlspecialchars($params['description']) . htmlspecialchars("</font></p><p><font face='Times' size='5'>(Ref: " . $params['idBook'] . ")<br/>") . "La Librairie est spécialisée en histoire XXe siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales".htmlspecialchars("<br/>")."Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empècher que ces tragédies humaines ne se reproduisent".htmlspecialchars("<br/>")."Photos supplémentaires sur demande".htmlspecialchars("<br/>")."Les ouvrages sont expédiés en envoi simple sous enveloppe à bulle".htmlspecialchars("<br/>")."Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande".htmlspecialchars("</br>")."Envoi rapide et soigné par un professionnel du livre depuis 30 ans".htmlspecialchars("</font></p></i>");
    $item->Description = htmlspecialchars("<i><p><font face='Times' color='#ad001f' size='7'><strong>" . $params['title'] . " <br/>par " . $params['author'] . "</strong></font></p><p><font face='Times' size='6'>" . $params['description'] . "</font></p><p><font face='Times' size='5'>(Ref: " . $params['idBook'] . ")<br/>La Librairie est spécialisée en histoire XXe siècle depuis 30 ans et donc essentiellement sur les 2 Guerres mondiales<br/>Les livres proposés abordent et étudient des sujets douloureux et violents de cette époque sans jamais contenir d'éléments de propagande ou faire l'apologie de la haine raciale. Ils contribuent à empècher que ces tragédies humaines ne se reproduisent<br/>Photos supplémentaires sur demande<br/>Les ouvrages sont expédiés en envoi simple sous enveloppe à bulle<br/>Pour tout envoi recommandé ou suivi, le montant sera communiqué au client sur simple demande</br>Envoi rapide et soigné par un professionnel du livre depuis 30 ans</font></p></i>",ENT_NOQUOTES);
    $item->StartPrice = new Trading\Types\AmountType(array('value' => (double)$params['price']));
    
    // Picture
    if (!empty($params['photo']))
    {
        $item->PictureDetails = new Trading\Types\PictureDetailsType();
        $item->PictureDetails->GalleryType = Trading\Enums\GalleryTypeCodeType::C_GALLERY;
        $item->PictureDetails->PictureURL = array($params['photo']);
    }
    
    $item->Site = Trading\Enums\SiteCodeType::C_FRANCE;
    $item->Country = Trading\Enums\CountryCodeType::C_FR;
    $item->Location = Trading\Enums\PreferredLocationCodeType::C_LOCATED_IN_COUNTRY_IMPLIED;
    $item->Currency = Trading\Enums\CurrencyCodeType::C_EUR;
//    $item->InventoryTrackingMethod = Trading\Enums\InventoryTrackingMethodCodeType::C_SKU;
    
    $item->ListingType = Trading\Enums\ListingTypeCodeType::C_FIXED_PRICE_ITEM;
    $item->ListingDuration = Trading\Enums\ListingDurationCodeType::C_GTC;

    $item->ConditionID = 5000;
        
    $item->ShipToLocations[] = 'FR';
    $item->ShipToLocations[] = 'Europe';
    $item->ShipToLocations[] = 'Worldwide';

    $item->DispatchTimeMax = 3;
    
    // Category
    $item->PrimaryCategory = new Trading\Types\CategoryType();
    $item->PrimaryCategory->CategoryID = $params['ebayCategoryId'];
        
    // Optionnal Secondary Category
    if ($params['ebayCategoryIdSecondary'] > 0) {
        $item->SecondaryCategory = new Trading\Types\CategoryType();
        $item->SecondaryCategory->CategoryID = $params['ebayCategoryIdSecondary'];
    }

    // Payment
    $item->PaymentMethods = array('PayPal');
    $item->PayPalEmailAddress = config("ebay_paypal_email");
    
    // Shipping
    $item->ShippingDetails = new Trading\Types\ShippingDetailsType();
    $item->ShippingDetails->ShippingType = Trading\Enums\ShippingTypeCodeType::C_FLAT;
    $item->ShippingDetails->PaymentInstructions = config("ebay_payment_instructions");
    
    $shippingService = new Trading\Types\ShippingServiceOptionsType();
    $shippingService->ShippingServicePriority = 1;
    $shippingService->ShippingService = config("ebay_shipping_service_name");
    $shippingService->ShippingServiceCost = new Trading\Types\AmountType(array('value' => (double)$params['ebayShippingCost']));
    //$shippingService->ShippingServiceAdditionalCost = new Trading\Types\AmountType(array('value' => 1.00));
    $item->ShippingDetails->ShippingServiceOptions[] = $shippingService;

    $shippingService = new Trading\Types\InternationalShippingServiceOptionsType();
    $shippingService->ShippingServicePriority = 1;
    $shippingService->ShippingService = config("ebay_shipping_service_international_name");
    $shippingService->ShippingServiceCost = new Trading\Types\AmountType(array('value' => (double)$params['ebayShippingCostInternational']));
    //$shippingService->ShippingServiceAdditionalCost = new Trading\Types\AmountType(array('value' => 3.00));
    $shippingService->ShipToLocation = array('WorldWide');
    $item->ShippingDetails->InternationalShippingServiceOption[] = $shippingService;

    // Return Policy
    $item->ReturnPolicy = new Trading\Types\ReturnPolicyType();
    $item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
//    $item->ReturnPolicy->RefundOption = 'MoneyBack';
    $item->ReturnPolicy->ReturnsWithinOption = 'Days_14';
    $item->ReturnPolicy->ShippingCostPaidByOption = 'Buyer';
    $item->ReturnPolicy->Description = config("ebay_return_policy");


    // Book specific attibutes
    $item->ItemSpecifics = new Trading\Types\NameValueListArrayType();
    // Attribute Author (25000)
    $specific = new Trading\Types\NameValueListType();
    $specific->Name = 'Auteur';
    $specific->Value[] = htmlspecialchars(substr($params['author'], 0, 62));
    $item->ItemSpecifics->NameValueList[] = $specific;
    // Attribute Titre
    $specific = new Trading\Types\NameValueListType();
    $specific->Name = 'Titre';
    $specific->Value[] = htmlspecialchars(substr($params['title'], 0, 62));
    $item->ItemSpecifics->NameValueList[] = $specific;
    // Attribute Isbn (25001)
    if ($params['isbn'] != "") {
        $specific = new Trading\Types\NameValueListType();
        $specific->Name = 'Isbn';
        $specific->Value[] = htmlspecialchars(substr($params['isbn'], 0, 62));
        $item->ItemSpecifics->NameValueList[] = $specific;
    }


    // Sending request    
    $request->Item = $item;
    $response = $service->reviseFixedPriceItem($request);
    
    if (isset($response->Errors)) {
        foreach ($response->Errors as $error) {
            printf("%s: %s\n%s\n\n",
                $error->SeverityCode === Trading\Enums\SeverityCodeType::C_ERROR ? 'Error' : 'Warning',
                $error->ShortMessage,
                $error->LongMessage
            );
        }
    }
    if ($response->Ack == 'Failure') {
        return(false);
    }
    else
        // In case of success/warnings...
        return($response->ItemID);
}

/**
 * ebay_GetCategorySpecifics
 * 
 * Ebay call for GetCategorySpecifics
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_ReviseItem.php,v 1.90 2011-12-29 14:03:02 michaelcoslar Exp $
 * @access public
 */
class ebay_GetCategorySpecifics
{
    
    /**
     * sample_GetCategorySpecifics::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new GetCategorySpecificsRequestType();
        $req->setCategoryID($params['CategoryID']);
        
        $res = $this->proxy->GetCategorySpecifics($req);
        if ($this->testValid($res)) {
            $this->dumpObject($res);
            return (true);
        } else {
            return (false);
        }
    }
}

/**
 * ebay_GetCategory2CS
 * 
 * Sample call for GetCategory2CS
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_GetCategory2CS.php,v 1.90 2011-12-29 14:03:00 michaelcoslar Exp $
 * @access public 
 */
class ebay_GetCategory2CS
{
    
    /**
     * sample_GetCategory2CS::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall($params)
    {
        $req = new GetCategory2CSRequestType();
        $req->setCategoryID($params['CategoryID']);
        $req->setDetailLevel($params['DetailLevel']);
        
        $res = $this->proxy->GetCategory2CS($req);
        if ($this->testValid($res)) {
            $this->dumpObject($res);
            return (true);
        } else {
            return (false);
        }
    }
}

?>

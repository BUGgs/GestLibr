<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebServiceSellers
 *  @copyright   Copyright 2009 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2011-07-01
 */
/******************************************************************************* 
 *  
 *  Marketplace Web Service Sellers PHP5 Library
 *  Generated: Tue Jul 05 17:50:53 GMT 2011
 * 
 */

/**
 * List Marketplace Participations By Next Token  Sample
 */

include_once ('config.inc.php'); 

/************************************************************************
 * Instantiate Implementation of MarketplaceWebServiceSellers
 * 
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants 
 * are defined in the .config.inc.php located in the same 
 * directory as this sample
 ***********************************************************************/
 $service = new MarketplaceWebServiceSellers_Client(AWS_ACCESS_KEY_ID, 
                                       AWS_SECRET_ACCESS_KEY);
 
/************************************************************************
 * Uncomment to try out Mock Service that simulates MarketplaceWebServiceSellers
 * responses without calling MarketplaceWebServiceSellers service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under MarketplaceWebServiceSellers/Mock tree
 *
 ***********************************************************************/
 // $service = new MarketplaceWebServiceSellers_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out 
 * sample for List Marketplace Participations By Next Token Action
 ***********************************************************************/
 // @TODO: set request. Action can be passed as MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest
 // object or array of parameters
 // invokeListMarketplaceParticipationsByNextToken($service, $request);

                            
/**
  * List Marketplace Participations By Next Token Action Sample
  * If ListMarketplaces cannot return all the marketplaces in one go, it will
  * provide a nextToken.  That nextToken can be used with this operation to
  * retrieve the next batch of Marketplaces for that SellerId.
  *   
  * @param MarketplaceWebServiceSellers_Interface $service instance of MarketplaceWebServiceSellers_Interface
  * @param mixed $request MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextToken or array of parameters
  */
  function invokeListMarketplaceParticipationsByNextToken(MarketplaceWebServiceSellers_Interface $service, $request) 
  {
      try {
              $response = $service->listMarketplaceParticipationsByNextToken($request);
              
                echo ("Service Response\n");
                echo ("=============================================================================\n");

                echo("        ListMarketplaceParticipationsByNextTokenResponse\n");
                if ($response->isSetListMarketplaceParticipationsByNextTokenResult()) { 
                    echo("            ListMarketplaceParticipationsByNextTokenResult\n");
                    $listMarketplaceParticipationsByNextTokenResult = $response->getListMarketplaceParticipationsByNextTokenResult();
                    if ($listMarketplaceParticipationsByNextTokenResult->isSetNextToken()) 
                    {
                        echo("                NextToken\n");
                        echo("                    " . $listMarketplaceParticipationsByNextTokenResult->getNextToken() . "\n");
                    }
                    if ($listMarketplaceParticipationsByNextTokenResult->isSetListParticipations()) { 
                        echo("                ListParticipations\n");
                        $listParticipations = $listMarketplaceParticipationsByNextTokenResult->getListParticipations();
                        $participationList = $listParticipations->getParticipation();
                        foreach ($participationList as $participation) {
                            echo("                    Participation\n");
                            if ($participation->isSetMarketplaceId()) 
                            {
                                echo("                        MarketplaceId\n");
                                echo("                            " . $participation->getMarketplaceId() . "\n");
                            }
                            if ($participation->isSetSellerId()) 
                            {
                                echo("                        SellerId\n");
                                echo("                            " . $participation->getSellerId() . "\n");
                            }
                            if ($participation->isSetHasSellerSuspendedListings()) 
                            {
                                echo("                        HasSellerSuspendedListings\n");
                                echo("                            " . $participation->getHasSellerSuspendedListings() . "\n");
                            }
                        }
                    } 
                    if ($listMarketplaceParticipationsByNextTokenResult->isSetListMarketplaces()) { 
                        echo("                ListMarketplaces\n");
                        $listMarketplaces = $listMarketplaceParticipationsByNextTokenResult->getListMarketplaces();
                        $marketplaceList = $listMarketplaces->getMarketplace();
                        foreach ($marketplaceList as $marketplace) {
                            echo("                    Marketplace\n");
                            if ($marketplace->isSetMarketplaceId()) 
                            {
                                echo("                        MarketplaceId\n");
                                echo("                            " . $marketplace->getMarketplaceId() . "\n");
                            }
                            if ($marketplace->isSetName()) 
                            {
                                echo("                        Name\n");
                                echo("                            " . $marketplace->getName() . "\n");
                            }
                            if ($marketplace->isSetDefaultLanguageCode()) 
                            {
                                echo("                        DefaultLanguageCode\n");
                                echo("                            " . $marketplace->getDefaultLanguageCode() . "\n");
                            }
                            if ($marketplace->isSetDefaultCountryCode()) 
                            {
                                echo("                        DefaultCountryCode\n");
                                echo("                            " . $marketplace->getDefaultCountryCode() . "\n");
                            }
                            if ($marketplace->isSetDefaultCurrencyCode()) 
                            {
                                echo("                        DefaultCurrencyCode\n");
                                echo("                            " . $marketplace->getDefaultCurrencyCode() . "\n");
                            }
                            if ($marketplace->isSetDomainName()) 
                            {
                                echo("                        DomainName\n");
                                echo("                            " . $marketplace->getDomainName() . "\n");
                            }
                        }
                    } 
                } 
                if ($response->isSetResponseMetadata()) { 
                    echo("            ResponseMetadata\n");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId()) 
                    {
                        echo("                RequestId\n");
                        echo("                    " . $responseMetadata->getRequestId() . "\n");
                    }
                } 

     } catch (MarketplaceWebServiceSellers_Exception $ex) {
         echo("Caught Exception: " . $ex->getMessage() . "\n");
         echo("Response Status Code: " . $ex->getStatusCode() . "\n");
         echo("Error Code: " . $ex->getErrorCode() . "\n");
         echo("Error Type: " . $ex->getErrorType() . "\n");
         echo("Request ID: " . $ex->getRequestId() . "\n");
         echo("XML: " . $ex->getXML() . "\n");
     }
 }
        

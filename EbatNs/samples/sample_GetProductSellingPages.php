<?php
/**
 * sources
 */
require_once 'setincludepath.php';
require_once 'GetProductSellingPagesRequestType.php';
require_once 'EbatNs_Environment.php';

/**
 * sample_GetProductSellingPages
 * 
 * Sample call for GetProductSellingPages
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_GetProductSellingPages.php,v 1.90 2011-12-29 14:03:01 michaelcoslar Exp $
 * @access public 
 */
class sample_GetProductSellingPages extends EbatNs_Environment
{

   /**
     * sample_GetProductSellingPages::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
	public function dispatchCall ($params)
    {
        $req = new GetProductSellingPagesRequestType();
        $req->setUseCase($params['UseCase']);
        
        $Product = new ProductType();
        $Product->setTypeAttribute('productID', $params['productID']);
        $CharacteristicsSet = new CharacteristicsSetType();
        $CharacteristicsSet->setAttributeSetID($params['AttributeSetID']);
        $Product->setCharacteristicsSet($CharacteristicsSet);
        $req->setProduct($Product);
        
        $res = $this->proxy->GetProductSellingPages($req);
        if ($this->testValid($res))
        {
            $this->dumpObject($res);
            return (true);
        }
        else 
        {
            return (false);
        }
    }
}

$x = new sample_GetProductSellingPages();
$x->dispatchCall
(
	array
	(
		'UseCase' => 'RelistItem', 
		'AttributeSetID' => '1301',
		'productID' => '3854178'
	)
);

?>
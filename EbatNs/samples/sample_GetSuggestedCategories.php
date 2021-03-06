<?php
/**
 * sources
 */
require_once 'setincludepath.php';
require_once 'GetSuggestedCategoriesRequestType.php';
require_once 'EbatNs_Environment.php';

/**
 * sample_GetSuggestedCategories
 * 
 * Sample call for GetSuggestedCategories
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_GetSuggestedCategories.php,v 1.90 2011-12-29 14:03:01 michaelcoslar Exp $
 * @access public
 */
class sample_GetSuggestedCategories extends EbatNs_Environment
{

   /**
     * sample_GetSuggestedCategories::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
    public function dispatchCall ($params)
    {
        $req = new GetSuggestedCategoriesRequestType();
        $req->setQuery($params['QueryKeywords']);
		
        $res = $this->proxy->GetSuggestedCategories($req);
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

$x = new sample_GetSuggestedCategories();
$x->dispatchCall
(
	array
	(
		'QueryKeywords' => 'ipod'
	)
);
?>
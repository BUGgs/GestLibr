<?php
/**
 * sources
 */
require_once 'setincludepath.php';
require_once 'GetSellerEventsRequestType.php';
require_once 'EbatNs_Environment.php';

/**
 * sample_GetSellerEvents
 * 
 * Sample call for GetSellerEvents
 * 
 * @package ebatns
 * @subpackage samples_trading
 * @author johann 
 * @copyright Copyright (c) 2008
 * @version $Id: sample_GetSellerEvents.php,v 1.90 2011-12-29 14:03:01 michaelcoslar Exp $
 * @access public
 */
class sample_GetSellerEvents extends EbatNs_Environment
{

   /**
     * sample_GetSellerEvents::dispatchCall()
     * 
     * Dispatch the call
     *
     * @param array $params array of parameters for the eBay API call
     * 
     * @return boolean success
     */
	public function dispatchCall ($params)
    {
        $req = new GetSellerEventsRequestType();
        
        $res = $this->proxy->GetSellerEvents($req);
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

$x = new sample_GetSellerEvents(1);
$x->dispatchCall(array());

?>
<?php
// autogenerated file 29.12.2011 15:00
// $Id: $
// $Log: $
//
require_once 'EbatNs_FacetType.php';

/**
 * Specifies whether a listing feature is available for the site specified in the 
 * request. 
 *
 * @link http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/types/BorderCodeType.html
 *
 * @property string Enabled
 * @property string Disabled
 * @property string CustomCode
 */
class BorderCodeType extends EbatNs_FacetType
{
	const CodeType_Enabled = 'Enabled';
	const CodeType_Disabled = 'Disabled';
	const CodeType_CustomCode = 'CustomCode';

	/**
	 * @return 
	 */
	function __construct()
	{
		parent::__construct('BorderCodeType', 'urn:ebay:apis:eBLBaseComponents');

	}
}

$Facet_BorderCodeType = new BorderCodeType();

?>

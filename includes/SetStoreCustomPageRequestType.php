<?php
/* Generated on 6/26/15 3:23 AM by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'AbstractRequestType.php';
require_once 'StoreCustomPageType.php';

/**
  *       Creates or updates a custom page on a user's eBay Store.
  *     
 **/

class SetStoreCustomPageRequestType extends AbstractRequestType
{
	/**
	* @var StoreCustomPageType
	**/
	protected $CustomPage;


	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('SetStoreCustomPageRequestType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
				'CustomPage' =>
				array(
					'required' => false,
					'type' => 'StoreCustomPageType',
					'nsURI' => 'urn:ebay:apis:eBLBaseComponents',
					'array' => false,
					'cardinality' => '0..1'
				)));
		}
		$this->_attributes = array_merge($this->_attributes,
		array(
));
	}

	/**
	 * @return StoreCustomPageType
	 **/
	function getCustomPage()
	{
		return $this->CustomPage;
	}

	/**
	 * @return void
	 **/
	function setCustomPage($value)
	{
		$this->CustomPage = $value;
	}

}
?>

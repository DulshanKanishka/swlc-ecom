<?php
/**
 *
 * SM CartQuickPro - Version 1.1.0
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: http://www.magentech.com
 */
 
namespace Sm\CartQuickPro\Block;

class CartQuickPro extends \Magento\Framework\View\Element\Template
{
	protected $_config = null;
	protected $_storeManager;
    protected $_scopeConfig;
	protected $_storeId;
	protected $_storeCode;
	protected $_request ;

	/**
	 * Class constructor
	 *
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Framework\App\ResourceConnection $resourceConnection
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param \Magento\Eav\Model\Config $eavConfig
	 * @param string|null $scope
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		array $data = [],
		$attr = null
	)
	{
		$this->_storeManager = $context->getStoreManager();
        $this->_scopeConfig = $context->getScopeConfig();
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();
		$this->_request = $context->getRequest();
		$this->_config = $this->_getCfg($attr, $data);
		parent::__construct($context, $data);
	}
	public function _getCfg($attr = null , $data = null)
	{
		$defaults = [];
		$_cfg_xml = $this->_scopeConfig->getValue('cartquickpro',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
		if (empty($_cfg_xml)) return;
		$groups = [];
		foreach ($_cfg_xml as $def_key => $def_cfg) {
			$groups[] = $def_key;
			foreach ($def_cfg as $_def_key => $cfg) {
				$defaults[$_def_key] = $cfg;
			}
		}
		
		if (empty($groups)) return;
		$cfgs = [];
		foreach ($groups as $group) {
			$_cfgs = $this->_scopeConfig->getValue('cartquickpro/'.$group.'',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
			foreach ($_cfgs as $_key => $_cfg) {
				$cfgs[$_key] = $_cfg;
			}
		}

		if (empty($defaults)) return;
		$configs = [];
		foreach ($defaults as $key => $def) {
			if (isset($defaults[$key])) {
				$configs[$key] = $cfgs[$key];
			} else {
				unset($cfgs[$key]);
			}
		}
		$cf = ($attr != null) ? array_merge($configs, $attr) : $configs;
		$this->_config = ($data != null) ? array_merge($cf, $data) : $cf;
		return $this->_config;
	}

	public function _getConfig($name = null, $value_def = null)
	{
		if (is_null($this->_config)) $this->_getCfg();
		if (!is_null($name)) {
			$value_def = isset($this->_config[$name]) ? $this->_config[$name] : $value_def;
			return $value_def;
		}
		return $this->_config;
	}
	

	public function _setConfig($name, $value = null)
	{

		if (is_null($this->_config)) $this->_getCfg();
		if (is_array($name)) {
			$this->_config = array_merge($this->_config, $name);

			return;
		}
		if (!empty($name) && isset($this->_config[$name])) {
			$this->_config[$name] = $value;
		}
		return true;
	}
	
	public function _isProductView(){
		$_action_name = ['cartquickpro_wishlist_index_configure', 'cartquickpro_catalog_product_view' ,'cartquickpro_catalog_product_options', 'cartquickpro_cart_configure'];
        if (in_array($this->_request->getFullActionName(), $_action_name )) {
			return true;
		}
		return false;
	}
	
	public function _isConfigure(){
		if ($this->_request->getFullActionName() == 'cartquickpro_cart_configure' ) {
			return true;
		}
		return false;
	}
	
	public function _isCompareIndex(){
		if ($this->_request->getFullActionName() == 'catalog_product_compare_index' ) {
			return true;
		}
		return false;
	}
	
	public function _isWishlistIndex(){
		if ($this->_request->getFullActionName() == 'wishlist_index_index' ) {
			return true;
		}
		return false;
	}
	
	public function _isWishlistIndexConfigure(){
		if ($this->_request->getFullActionName() == 'cartquickpro_wishlist_index_configure' ) {
			return true;
		}
		return false;
	}
	
	public function _isPageCheckout(){
		if ($this->_request->getFullActionName() == 'checkout_cart_index' ) {
			return true;
		}
		return false;
	}
	
	public function _isLoggedIn(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->create('Magento\Customer\Model\Session');
		if($customerSession->isLoggedIn()){
		   return true;
		}
		return false;
	}
	
	public function _urlLogin(){
		return $this->getUrl('customer/account/login');
	}
	
	public function getCurrentUrl() {
		return $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
	}
	
	public function _isAjaxCart () {
		if($this->_getConfig('isenabled', 1) && ($this->_getConfig('select_type', 1) == 'both' || $this->_getConfig('select_type', 1) == 'ajaxcart')){
			return true;
		}
		return false;
	}
	
	public function _isQuickView () {
		if($this->_getConfig('isenabled', 1) && ($this->_getConfig('select_type', 1) == 'both' || $this->_getConfig('select_type', 1) == 'quickview')){
			return true;
		}
		return false;
	}
}
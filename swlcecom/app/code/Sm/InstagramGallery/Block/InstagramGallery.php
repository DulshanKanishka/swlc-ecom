<?php
/*------------------------------------------------------------------------
# SM Instagram Gallery - Version 2.0.1
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\InstagramGallery\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Sm\InstagramGallery\Block\Cache\Lite;
class InstagramGallery extends \Magento\Catalog\Block\Product\AbstractProduct
{
	protected $_config = null;
	protected $_storeId = null;
	protected $_storeManager;
	protected $_objectManager;
	protected $_scopeConfigInterface;
	protected $_directory;

	public function __construct(
		ObjectManagerInterface $objectManager,
		Context $context,
		array $data = [],
		$attr = null
	)
	{
		$this->_objectManager = $objectManager;
		$this->_storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$this->_scopeConfigInterface = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
		$this->_directory =  $this->_objectManager->get('\Magento\Framework\Filesystem');
		$this->_storeId = (int)$this->_storeManager->getStore()->getId();
		$this->_config = $this->_getCfg($attr, $data);
		parent::__construct($context, $data);
	}
	
	public function _prepareLayout()
	{
		return parent::_prepareLayout();
	}

	public function _helper()
	{
		return $this->_objectManager->get('\Sm\InstagramGallery\Helper\Data');
	}


	public function _getCfg($attr = null, $data = null)
	{
		// get default config.xml
		$defaults = [];
		$collection = $this->_scopeConfigInterface->getValue('instagramgallery',\Magento\Store\Model\ScopeInterface::SCOPE_STORES,$this->_storeId);

		if (empty($collection)) return;
		$groups = [];
		foreach ($collection as $def_key => $def_cfg) {
			$groups[] = $def_key;
			foreach ($def_cfg as $_def_key => $cfg) {
				$defaults[$_def_key] = $cfg;
			}
		}

		// get configs after change
		$_configs = $this->_scopeConfigInterface->getValue('instagramgallery',\Magento\Store\Model\ScopeInterface::SCOPE_STORES,$this->_storeId);
		if (empty($_configs)) return;
		$cfgs = [];

		foreach ($groups as $group) {
			$_cfgs = $this->_scopeConfigInterface->getValue('instagramgallery/'.$group.'',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeId);
			foreach ($_cfgs as $_key => $_cfg) {
				$cfgs[$_key] = $_cfg;
			}
		}

		// get output config
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

	protected function _toHtml()
	{

		if (!$this->_getConfig('active', 1)) return;
		$lib_dir = $this->_directory->getDirectoryWrite(DirectoryList::APP)->getAbsolutePath();
		$cache_dir =  $this->_directory->getDirectoryWrite(DirectoryList::CACHE)->getAbsolutePath();

		$use_cache = (int)$this->_getConfig('use_cache');
		$cache_time = (int)$this->_getConfig('cache_time');
		$folder_cache = $this->_directory->getDirectoryWrite(DirectoryList::CACHE)->getAbsolutePath();
		$folder_cache = $folder_cache.'Sm/InstagramGallery/';
		if(!file_exists($folder_cache))
			mkdir ($folder_cache, 0777, true);
		
		$options = array(
			'cacheDir' => $folder_cache,
			'lifeTime' => $cache_time
		);
		$Cache_Lite = new \Sm\InstagramGallery\Block\Cache\Lite($options);
		if ($use_cache){
			$hash = md5( serialize([$this->_getConfig(), $this->_storeId ,$this->_storeManager->getStore()->getCurrentCurrencyCode()]) );	
			if ($data = $Cache_Lite->get($hash)) {
				return  $data;
			} else { 
				$template_file = $this->getTemplate();
				$template_file = (!empty($template_file)) ? $template_file : "Sm_InstagramGallery::default.phtml";
				$this->setTemplate($template_file);
				$data = parent::_toHtml();
				$Cache_Lite->save($data);
			}
		}else{
			if(file_exists($folder_cache))
			$Cache_Lite->_cleanDir($folder_cache);
			$template_file = $this->getTemplate();
			$template_file = (!empty($template_file)) ? $template_file : "Sm_InstagramGallery::default.phtml";
			$this->setTemplate($template_file);
		}
		
        return parent::_toHtml();
		
	}
}
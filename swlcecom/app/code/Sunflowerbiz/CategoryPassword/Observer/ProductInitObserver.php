<?php

namespace Sunflowerbiz\CategoryPassword\Observer;

//use \Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductInitObserver implements ObserverInterface 
 {
	 protected $customerSession;
	  
    public function __construct()
    {
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');
		$base  =  $directory->getRoot();
		
		$Product = $observer->getProduct();  
		$error_msg='';
		$category_password='';
		$productId =$Product->getId(); 
		
		$scopeConfig=  $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
		$usecategorypassword= $scopeConfig->getValue('se_categorypassword/categorypassword/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	
		if($usecategorypassword){
			$protected = true;
			$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
			$core_write = $resource->getConnection();
			$tableName = $resource->getTableName('category_password');
			$catalog_category_product_index = $resource->getTableName('catalog_category_product_index');
			$password = "";
			$category_password = "";
			$categories = array();
			if (!isset($this->customerSession)) $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
			$customerSession = $this->customerSession;
			$session_passed_category = $customerSession->getData('passed_category');
			if (!isset($session_passed_category)) $session_passed_category = array();
			$product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
			// Fetch the 'category_ids' attribute from the Data Model.
			if ($categoryIds = $product->getCustomAttribute('category_ids')) {
				foreach ($categoryIds->getValue() as $categoryId) {
					$categories[] = $categoryId;
				}
			}
			foreach ($categories as $category_id) {
				$category_password = "";
				$selectsql = "select * from `" . $tableName . "` where category_id='" . $category_id . "'";
				$category_passwordfeach = $core_write->fetchAll($selectsql);
				if (count($category_passwordfeach) > 0) {
					foreach ($category_passwordfeach as $categorypassword) $category_password = $categorypassword['password'];
				}
				if ($category_password == '' || in_array($category_id, $session_passed_category)) {
					$protected = false;
					break;
				}
			}
		
			if ($protected) {
				foreach ($categories as $category_id) {
					$selectsql = "select * from `" . $tableName . "` where category_id='" . $category_id . "'";
					$category_passwordfeach = $core_write->fetchAll($selectsql);
					if (count($category_passwordfeach) > 0) {
						foreach ($category_passwordfeach as $categorypassword) $category_password = $categorypassword['password'];
					}
					if ($category_password != '' && !in_array($category_id, $session_passed_category)) {
						$category = $objectManager->create('Magento\Catalog\Model\Category')->load($category_id);
						$redirect = $objectManager->create('Magento\Framework\App\Response\RedirectInterfaceFactory')->create();
						$controller = $observer->getControllerAction();
						$RedirectUrl = $category->getUrl();
						$redirect->redirect($controller->getResponse() , $RedirectUrl);
					}
				}
			}
		}
		
	
		return;
		
	}
        
     
	
}
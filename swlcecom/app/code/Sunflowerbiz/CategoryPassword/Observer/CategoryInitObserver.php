<?php

namespace Sunflowerbiz\CategoryPassword\Observer;

//use \Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CategoryInitObserver implements ObserverInterface 
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
		
		$Category = $observer->getCategory();  
		$error_msg='';
		$category_password='';
		$category_id =$Category->getId(); 
		
		
		
		$scopeConfig=  $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
		$usecategorypassword= $scopeConfig->getValue('se_categorypassword/categorypassword/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	
		if($usecategorypassword){
			$protected = false;
			$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
			$core_write = $resource->getConnection();
			$tableName = $resource->getTableName('category_password');
			$password = "";
			$selectsql = "select * from `" . $tableName . "` where category_id='" . $category_id . "'";
			$category_passwordfeach = $core_write->fetchAll($selectsql);
			if (count($category_passwordfeach) > 0) {
				foreach ($category_passwordfeach as $categorypassword) $category_password = $categorypassword['password'];
			}
			if (!isset($this->customerSession)) $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
			$customerSession = $this->customerSession;
			$session_passed_category = $customerSession->getData('passed_category');
			if (!isset($session_passed_category)) $session_passed_category = array();
			if ($category_password != '' && !in_array($category_id, $session_passed_category)) {
				$protected = true;
			}
			if ($protected) {
				$customerSession->setData('redirect_password_category_id', $category_id);
				$customerSession->setData('redirect_password_category', $_SERVER['REQUEST_URI']);
				$redirect = $objectManager->create('Magento\Framework\App\Response\RedirectInterfaceFactory')->create();
				$controller = $observer->getControllerAction();
				$RedirectUrl = $urlInterface->getUrl('categorypassword/redirect/redirect');
				$redirect->redirect($controller->getResponse() , $RedirectUrl);
			}
			
	}
		
	
		return;
		
	}
        
     
	
}
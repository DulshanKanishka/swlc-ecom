<?php

namespace Sunflowerbiz\CategoryPassword\Model;

//use \Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CategoryPasswordObserver implements ObserverInterface 
 {

    public function __construct()
    {
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
	$core_write = $resource->getConnection();
	$tableName = $resource->getTableName('category_password');
	$catalog_category_entity = $resource->getTableName('catalog_category_entity');
	$password="";
	
$postData = $observer->getEvent()->getRequest()->getPost();

		if(isset($postData['category_password'])){
		if(isset($postData['entity_id']) && isset($postData['category_password_changed']) && $postData['category_password_changed']!="")
		$category_id=$postData['entity_id'];
		else{
				$maxsql= "select entity_id from `".$catalog_category_entity."` order by entity_id desc limit 1";
				$maxentity_id=0;
			$category_passwordfeach=$core_write->fetchAll($maxsql);
			if(count($category_passwordfeach)>0){			
					foreach($category_passwordfeach as $categorypassword)
						$maxentity_id=$categorypassword['entity_id'];			
			}
			$category_id=$maxentity_id+1;
		}
		
		
		 $category_password = $postData['category_password'];
		 
		$deletesql= "delete from `".$tableName."` where category_id='".$category_id."'";
		$core_write->query($deletesql);
		
		if($category_password!=''){
			$insertsql= "insert into `".$tableName."` (category_id,password) values ('".$category_id."','".$category_password."')";
//	$selectsql= "UPDATE  `".$tableName."` set password='".$_POST['category_password']."' where category_id='".$category_id."'";
			$core_write->query($insertsql);
		}
	}

		
	}
        
     
	
}
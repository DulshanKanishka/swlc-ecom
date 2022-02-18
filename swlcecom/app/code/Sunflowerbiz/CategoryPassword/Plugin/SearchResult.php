<?php

namespace Sunflowerbiz\CategoryPassword\Plugin;

class SearchResult
{
    protected $customerSession;
    public function beforeGetProductListHtml($subject)
    {
        $ids=[];
        foreach ($subject->getListBlock()->getLoadedProductCollection() as $product) {
            $pid=$product->getId();
            if (!$this->protectedPid($pid)) {
                $ids[]= $pid;
            }
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $productCollection->addFieldToSelect(['name', 'thumbnail', 'price', 'special_price', 'sku', 'image', 'small_image']);
        $productCollection->addFieldToFilter(
             'entity_id',
             [
                                        'in' => $ids]
         );

        $subject->getListBlock()->setCollection($productCollection);

        return [$subject];
    }
    public function protectedPid($productId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');
        $base  =  $directory->getRoot();

        $error_msg='';
        $category_password='';

        $scopeConfig=  $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $usecategorypassword= $scopeConfig->getValue('se_categorypassword/categorypassword/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($usecategorypassword) {
            $protected = true;
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $core_write = $resource->getConnection();
            $tableName = $resource->getTableName('category_password');
            $catalog_category_product_index = $resource->getTableName('catalog_category_product_index');
            $password = "";
            $category_password = "";
            $categories = [];
            if (!isset($this->customerSession)) {
                $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
            }
            $customerSession = $this->customerSession;
            $session_passed_category = $customerSession->getData('passed_category');
            if (!isset($session_passed_category)) {
                $session_passed_category = [];
            }
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
                    foreach ($category_passwordfeach as $categorypassword) {
                        $category_password = $categorypassword['password'];
                    }
                }
                if ($category_password == '' || in_array($category_id, $session_passed_category)) {
                    $protected = false;
                    break;
                }
            }

            return ($protected);
        } else {
            return false;
        }
    }
}

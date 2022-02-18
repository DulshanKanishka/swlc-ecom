<?php
namespace Magebees\Promotionsnotification\Block;

use Magento\Store\Model\ScopeInterface;

class View extends \Magebees\Promotionsnotification\Block\Promotionsnotification
{
    
    public function getNotificationCollection($mode = "bar")
    {
        $notification_collection = $this->_notificationFactory->create()->getCollection();
        $notification_collection->addFieldToFilter('status', 1);
        $notification_collection->addFieldToFilter('notification_style', $mode);
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        
        $notification_collection->addFieldToFilter('from_date', ['lt' => $now]);
        $notification_collection->addFieldToFilter('to_date', ['gt' => $now]);
        
        if ($this->_request->getFullActionName() == 'catalog_category_view') {
            $category_id = $this->_coreRegistry->registry('current_category')->getId();
            $notification_collection->categoryFilter($category_id);
        } elseif ($this->_request->getFullActionName() == 'catalog_product_view') {
            $productid = $this->_coreRegistry->registry('current_product')->getId();
            $notification_collection->productFilter($productid);
        } elseif ($this->_request->getFullActionName() == 'cms_page_view' || $this->_request->getFullActionName() == 'cms_index_index') {
            if ($this->_request->getFullActionName() != 'cms_noroute_index') {
                $pageId = $this->_cmsPage->getPage()->getPageId();
                $notification_collection->pageFilter($pageId);
            }
        } elseif ($this->_request->getFullActionName() == 'checkout_cart_index') {
            $notification_collection->addFieldToFilter('cart_page', 1);
        } else {
            if ($this->_request->getFullActionName() == 'cms_noroute_index') {
                return $notification_collection;
            }
        }
        
        //store filter
        $store_id = $this->_storeManager->getStore()->getId();
        if (!$this->_storeManager->isSingleStoreMode()) {
            $notification_collection->storeFilter($store_id);
        }
        
        //customer group filter
        $customer_id = $this->_customerSession->getCustomerGroupId();
        $notification_collection->customerFilter($customer_id);
        
        //set sort order
        if ($mode=="bar") {
            $order = $this->getBarOrder();
        } else {
            $order = $this->getPopupOrder();
        }
        if ($order=="sort_order") {
            $notification_collection->setOrder('sort_order', 'ASC')
            ->setOrder('notification_id', 'ASC');
        } else {
            $notification_collection->getSelect()->orderRand();
        }
        
        return $notification_collection;
    }
}

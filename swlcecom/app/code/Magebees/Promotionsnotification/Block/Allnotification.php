<?php
namespace Magebees\Promotionsnotification\Block;

use Magento\Store\Model\ScopeInterface;

class Allnotification extends \Magento\Framework\View\Element\Template
{
    protected $_notificationFactory;
    protected $_customerSession;
    protected $_date;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magebees\Promotionsnotification\Model\PromotionsnotificationFactory $notificationFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->_notificationFactory = $notificationFactory;
        $this->_customerSession = $customerSession;
        $this->_date = $date;
        $this->timezone = $timezone;
            
        //Set Configuration values
        $this->setBarEnabled($this->_scopeConfig->getValue('promotions/notification_bar/enabled', ScopeInterface::SCOPE_STORE));
        $this->setPopupEnabled($this->_scopeConfig->getValue('promotions/notification_popup/enabled', ScopeInterface::SCOPE_STORE));
    }
    
    public function getCurrentNotificationCollection()
    {
        $notification_collection = $this->_notificationFactory->create()->getCollection();
        $notification_collection->addFieldToFilter('status', 1);
        //$now = $this->_date->gmtDate();
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $notification_collection->addFieldToFilter('from_date', ['lt' => $now]);
        $notification_collection->addFieldToFilter('to_date', ['gt' => $now]);
        
        if (!$this->getBarEnabled()) {
            $notification_collection->addFieldToFilter('notification_style', ['neq' => "bar"]);
        }
        if (!$this->getPopupEnabled()) {
            $notification_collection->addFieldToFilter('notification_style', ['neq' => "popup"]);
        }
        
        //store filter
        $store_id = $this->_storeManager->getStore()->getId();
        if (!$this->_storeManager->isSingleStoreMode()) {
            $notification_collection->storeFilter($store_id);
        }
        
        //customer group filter
        $customer_id = $this->_customerSession->getCustomerGroupId();
        $notification_collection->customerFilter($customer_id);
                
        return $notification_collection;
    }
    
    public function getUpcomingNotificationCollection()
    {
        $notification_collection = $this->_notificationFactory->create()->getCollection();
        $notification_collection->addFieldToFilter('status', 1);
        //$now = $this->_date->gmtDate();
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $notification_collection->addFieldToFilter('from_date', ['gt' => $now]);
        
        if (!$this->getBarEnabled()) {
            $notification_collection->addFieldToFilter('notification_style', ['neq' => "bar"]);
        }
        if (!$this->getPopupEnabled()) {
            $notification_collection->addFieldToFilter('notification_style', ['neq' => "popup"]);
        }
    
        //store filter
        $store_id = $this->_storeManager->getStore()->getId();
        if (!$this->_storeManager->isSingleStoreMode()) {
            $notification_collection->storeFilter($store_id);
        }
        
        //customer group filter
        $customer_id = $this->_customerSession->getCustomerGroupId();
        $notification_collection->customerFilter($customer_id);
                
        return $notification_collection;
    }
}

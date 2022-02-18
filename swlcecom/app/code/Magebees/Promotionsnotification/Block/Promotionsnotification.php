<?php
namespace Magebees\Promotionsnotification\Block;

use Magento\Store\Model\ScopeInterface;

class Promotionsnotification extends \Magento\Framework\View\Element\Template
{
    protected $_notificationFactory;
    protected $_coreRegistry;
    protected $_cmsPage;
    protected $_request;
    protected $_customerSession;
    protected $_date;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magebees\Promotionsnotification\Model\PromotionsnotificationFactory $notificationFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Block\Page $cmsPage,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
        
    ) {
        parent::__construct($context);
        $this->_notificationFactory = $notificationFactory;
        $this->_request = $request;
        $this->_coreRegistry = $coreRegistry;
        $this->_cmsPage = $cmsPage;
        $this->_customerSession = $customerSession;
        $this->_date = $date;
        $this->timezone = $timezone;
        $this->_cookieManager = $cookieManager;
        
            
        //Set Configuration values
        $this->setEnabled($this->_scopeConfig->getValue('promotions/general/enabled', ScopeInterface::SCOPE_STORE));
                
        //Bar configuration settings
        $this->setBarEnabled($this->_scopeConfig->getValue('promotions/notification_bar/enabled', ScopeInterface::SCOPE_STORE));
        $this->setDisplayPosition($this->_scopeConfig->getValue('promotions/notification_bar/position', ScopeInterface::SCOPE_STORE));
        $this->setBarAfterTimer($this->_scopeConfig->getValue('promotions/notification_bar/display_after', ScopeInterface::SCOPE_STORE));
        $this->setBarHeight($this->_scopeConfig->getValue('promotions/notification_bar/height', ScopeInterface::SCOPE_STORE));
        $this->setBarAllOrOne($this->_scopeConfig->getValue('promotions/notification_bar/all_or_one', ScopeInterface::SCOPE_STORE));
        $this->setBarAutoClose($this->_scopeConfig->getValue('promotions/notification_bar/auto_close', ScopeInterface::SCOPE_STORE));
        $this->setBarAutoCloseTime($this->_scopeConfig->getValue('promotions/notification_bar/close_after', ScopeInterface::SCOPE_STORE));
        $this->setBarOneTimePerUser($this->_scopeConfig->getValue('promotions/notification_bar/onetime_per_user', ScopeInterface::SCOPE_STORE));
        $this->setBarOrder($this->_scopeConfig->getValue('promotions/notification_bar/order', ScopeInterface::SCOPE_STORE));
        $this->setBarShowInMobile($this->_scopeConfig->getValue('promotions/notification_bar/show_in_mobile', ScopeInterface::SCOPE_STORE));
        
        //Popup configuration settings
        $this->setPopupEnabled($this->_scopeConfig->getValue('promotions/notification_popup/enabled', ScopeInterface::SCOPE_STORE));
        $this->setPopupAfterTimer($this->_scopeConfig->getValue('promotions/notification_popup/display_after', ScopeInterface::SCOPE_STORE));
        $this->setPopupAllOrOne($this->_scopeConfig->getValue('promotions/notification_popup/all_or_one', ScopeInterface::SCOPE_STORE));
        $this->setPopupAutoClose($this->_scopeConfig->getValue('promotions/notification_popup/auto_close', ScopeInterface::SCOPE_STORE));
        $this->setPopupAutoCloseTime($this->_scopeConfig->getValue('promotions/notification_popup/close_after', ScopeInterface::SCOPE_STORE));
        $this->setPopupOneTimePerUser($this->_scopeConfig->getValue('promotions/notification_popup/onetime_per_user', ScopeInterface::SCOPE_STORE));
        $this->setPopupOrder($this->_scopeConfig->getValue('promotions/notification_popup/order', ScopeInterface::SCOPE_STORE));
        $this->setPopupHeight($this->_scopeConfig->getValue('promotions/notification_popup/height', ScopeInterface::SCOPE_STORE));
        $this->setPopupWidth($this->_scopeConfig->getValue('promotions/notification_popup/width', ScopeInterface::SCOPE_STORE));
        $this->setPopupShowInMobile($this->_scopeConfig->getValue('promotions/notification_popup/show_in_mobile', ScopeInterface::SCOPE_STORE));
    }
    
    public function getNotificationCollection($notification_id)
    {
        
        $notification_collection = $this->_notificationFactory->create()->getCollection();
        $notification_collection = $this->_notificationFactory->create()->getCollection()->addFieldToFilter('status', 1);
      
             
        //for posh theme changes
        if($this->getNotificationId()){
            $notification_collection->addFieldToFilter('main_table.notification_id',$this->getNotificationId());
        }else{
            $unique_code = $this->getUniqueCode();
            $notification_collection->addFieldToFilter('unique_code',$unique_code);
        }
        //for posh theme changes
        
        if($this->getNotificationId()){
            $now = $this->timezone->date()->format('Y-m-d H:i:s');
            $notification_collection->addFieldToFilter('from_date',array('lt' => $now));
            $notification_collection->addFieldToFilter('to_date',array('gt' => $now));
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
       
    public function getNotificationType($element)
    {
        return $element['notification_style'];
    }
}

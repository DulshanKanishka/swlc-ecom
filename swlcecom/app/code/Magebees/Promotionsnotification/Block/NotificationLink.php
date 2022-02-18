<?php
namespace Magebees\Promotionsnotification\Block;

use Magento\Store\Model\ScopeInterface;

class NotificationLink extends \Magento\Framework\View\Element\Html\Link
{

   
    public function getLabel()
    {
        return $this->_scopeConfig->getValue('promotions/general/link_label', ScopeInterface::SCOPE_STORE);
    }
    
    public function isEnable()
    {
        return $this->_scopeConfig->getValue('promotions/general/enable_link', ScopeInterface::SCOPE_STORE);
    }
}

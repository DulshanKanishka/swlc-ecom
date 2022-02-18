<?php
namespace Magebees\Promotionsnotification\Block;

use Magento\Store\Model\ScopeInterface;

class GetPreview extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setBarHeight($this->_scopeConfig->getValue('promotions/notification_bar/height', ScopeInterface::SCOPE_STORE));
        $this->setPopupHeight($this->_scopeConfig->getValue('promotions/notification_popup/height', ScopeInterface::SCOPE_STORE));
        $this->setPopupWidth($this->_scopeConfig->getValue('promotions/notification_popup/width', ScopeInterface::SCOPE_STORE));
    }
                
    public function getFomrData()
    {
        return $this->getRequest()->getPost()->toArray();
    }
}

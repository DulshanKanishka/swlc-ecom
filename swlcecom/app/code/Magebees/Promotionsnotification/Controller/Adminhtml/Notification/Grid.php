<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class Grid extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->getResponse()->setBody($this->_view->getLayout()->createBlock('Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Grid')->toHtml());
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Promotionsnotification::promotions_content');
    }
}

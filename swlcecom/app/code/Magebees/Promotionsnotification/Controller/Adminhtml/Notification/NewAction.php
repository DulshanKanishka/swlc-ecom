<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class NewAction extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->_forward('edit');
    }
}

<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class Delete extends \Magento\Backend\App\Action
{
    protected $_notificationFactory;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magebees\Promotionsnotification\Model\PromotionsnotificationFactory $notificationFactory
    ) {
        parent::__construct($context);
        $this->_notificationFactory = $notificationFactory;
    }

    public function execute()
    {
        $notificationId = $this->getRequest()->getParam('id');
        try {
            $notification = $this->_notificationFactory->create()->load($notificationId);
            $notification->delete();
            $this->messageManager->addSuccess(
                __('Notification Deleted successfully !')
            );
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_redirect('*/*/');
    }
}

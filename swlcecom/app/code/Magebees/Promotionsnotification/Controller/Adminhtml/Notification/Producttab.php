<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class Producttab extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }
    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('promotionsnotification_promotionsnotification_edit_tab_product')
            ->setProductNotification($this->getRequest()->getPost('product_notification', null));
        return $resultLayout;
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Promotionsnotification::promotions_content');
    }
}

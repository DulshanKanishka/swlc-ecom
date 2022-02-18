<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class Index extends \Magento\Backend\App\Action
{
    //const ADMIN_RESOURCE = 'Magebees_Promotionsnotification::finder';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magebees_Promotionsnotification::promotions_content');
        $resultPage->addBreadcrumb(__('Promtotions Notification Pro'), __('Promtotions Notification Pro'));
        $resultPage->addBreadcrumb(__('Manage Notifications'), __('Manage Notifications'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Notifications'));
        
        return $resultPage;
    }
         
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Promotionsnotification::promotions_content');
    }
}

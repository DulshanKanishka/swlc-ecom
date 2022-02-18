<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magenest_Xero::xero');
        $resultPage->addBreadcrumb(__('Queue'), __('Queue'));
        $resultPage->addBreadcrumb(__('Manage Queue'), __('Manage Queue'));
        $resultPage->getConfig()->getTitle()->prepend(__('Queue'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::queue');
    }
}

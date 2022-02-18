<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

class Import extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->addBreadcrumb(__('Import Black List'), __('Import Black List'));
        $resultPage->getConfig()->getTitle()->prepend(__('Import Black List'));
        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_AbandonedCart::blacklist');
    }
}
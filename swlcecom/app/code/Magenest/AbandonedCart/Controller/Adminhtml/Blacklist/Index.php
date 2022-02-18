<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

class Index extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->addBreadcrumb(__('Blacklist'), __('Blacklist'));
        $resultPage->getConfig()->getTitle()->prepend(__('Blacklist'));
        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_AbandonedCart::blacklist');
    }
}

<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Unsubscribe;

class Index extends \Magenest\AbandonedCart\Controller\Adminhtml\Unsubscribe
{

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $pageResult */
        $pageResult = $this->_resultPageFactory->create();
        $pageResult->addBreadcrumb(__('Manage Unsubscribe'), __('Manage Unsubscribe'));
        $pageResult->getConfig()->getTitle()->prepend(__('Manage Unsubscribe'));
        return $pageResult;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_AbandonedCart::unsubscribe');
    }
}
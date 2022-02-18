<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

class Index extends Rule
{
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Meetanshi_PaymentRestriction::rule');
        $resultPage->getConfig()->getTitle()->prepend(__('Payment Restrictions'));
        $resultPage->addBreadcrumb(__('Payment Restrictions'), __('Payment Restrictions'));
        return $resultPage;
    }
}

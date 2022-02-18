<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

use Magenest\AbandonedCart\Model\Cron;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Collect extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
{

    public function execute()
    {
        $this->_cronJob->collectAbandonedCarts();
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
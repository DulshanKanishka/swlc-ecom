<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Queue;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractQueue;
use Magenest\QuickBooksOnline\Helper\Synchronization;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magenest\QuickBooksOnline\Model\Config;

/**
 * Class Sync
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Queue
 */
class Sync extends AbstractQueue
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Sync constructor.
     * @param Context $context
     * @param QueueFactory $queueFactory
     * @param Config $config
     */
    public function __construct(
        Context $context,
        QueueFactory $queueFactory,
        Config $config
    ) {
        parent::__construct($context, $queueFactory);
        $this->config = $config;
    }

    /**
     * Execute the action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $connect = $this->config->getConnected();
        if ($connect && $connect == 1) {
            /** @var Synchronization $cronjob */
            $cronJob = $this->_objectManager->create(Synchronization::class);
            $cronJob->execute();
        } else {
            $this->messageManager->addErrorMessage(__('You\'re not connected to QuickBooks Online. Please go to Configuration to finish the connection.'));
        }

        return $this->_redirect('*/*/index');
    }
}

<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Queue;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractQueue;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magenest\QuickBooksOnline\Model\QueueFactory;

/**
 * Class Invoice
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Queue
 */
class Invoice extends AbstractQueue
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Order constructor.
     *
     * @param Context $context
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $queueFactory);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $from = $this->getRequest()->getParam('from');
        $to   = $this->getRequest()->getParam('to');

        try {
            /**
             * remove invoice duplicate to display in queue
             */
            try {
                $qbOnlineQueueItemCollection = $this->_objectManager->create('Magenest\QuickBooksOnline\Model\ResourceModel\Queue\Collection')
                    ->addFieldToFilter('type', 'invoice');
                $qbOnlineQueueItemCollection->walk('delete');
            } catch (\Exception $e) {
            }
            $i = 0;
            if (empty($from)) {
                $from = '2000-01-01';
            }
            if (empty($to)) {
                $to = '2099-01-01';
            }

            $from = $from . ' 00:00:00';
            $to   = $to . ' 23:59:59';

            $collections = $this->collectionFactory->create()
                ->addFieldToFilter('created_at', ['gteq' => $from])
                ->addFieldToFilter('created_at', ['lteq' => $to]);

            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            foreach ($collections as $invoice) {
                $this->addToQueue($invoice->getIncrementId(), 'invoice');
                $i++;
            }
            $this->messageManager->addSuccessMessage(__('%1 invoice(s) have been added to the queue.', $i));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while adding invoices to queue. Please try again later.'));
        }

        return;
    }
}

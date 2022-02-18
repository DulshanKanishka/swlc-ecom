<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Queue;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractQueue;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\App\Action\Context;

/**
 * Class Customer
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Queue
 */
class Item extends AbstractQueue
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Customer constructor.
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
             * remove product duplicate to display in queue
             */
            try {
                $qbOnlineQueueItemCollection = $this->_objectManager->create('Magenest\QuickBooksOnline\Model\ResourceModel\Queue\Collection')
                    ->addFieldToFilter('type', 'item');
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
                ->addFieldToFilter('updated_at', ['gteq' => $from])
                ->addFieldToFilter('updated_at', ['lteq' => $to]);


            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($collections as $product) {
                if ($product->getTypeId() != 'grouped') {
                    $this->addToQueue($product->getId(), 'item');
                    $i++;
                }
            }
            $this->messageManager->addSuccessMessage(__('%1 products have been added to the queue', $i));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while adding products to queue. Please try again later.'));
        }

        return;
    }
}

<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Helper;

use Magenest\QuickBooksOnline\Model\ResourceModel\Queue\CollectionFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\Order;
use Magenest\QuickBooksOnline\Model\Synchronization\Invoice;
use Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class Synchronization
 *
 * @package Magenest\QuickBooksOnline\Cron
 */
class Synchronization
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * Synchronization constructor.
     * @param CollectionFactory $collectionFactory
     * @param Customer $customer
     * @param Item $item
     * @param Order $order
     * @param Invoice $invoice
     * @param Creditmemo $creditmemo
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Customer $customer,
        Item $item,
        Order $order,
        Invoice $invoice,
        Creditmemo $creditmemo,
        ManagerInterface $messageManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->customer = $customer;
        $this->item = $item;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->messageManager = $messageManager;
    }

    /**
     *
     */
    public function execute()
    {
        //execute
        try {
            /**
             * @var \Magenest\QuickBooksOnline\Model\Config $config
             */
            $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
            $count = 0;
            if (count($this->getQueueCollection())) {
                if ($config->isEnableByType('customer')){
                    $count += $this->syncCustomer();
                }
                if ($config->isEnableByType('item')){
                    $count += $this->syncItem();
                }
                if ($config->isEnableByType('order')){
                    $count += $this->syncOrder();
                }
                if ($config->isEnableByType('invoice')) {
                    $count += $this->syncInvoice();
                }
                if ($config->isEnableByType('creditmemo')) {
                    $count += $this->syncCreditmemo();
                }
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) are processed.', $count));
            } else $this->messageManager->addNoticeMessage(__('Nothing in queues to sync'));

        } catch (\Exception $e) {
            $details = '';
            $message = $e->getMessage();
            try {
                $parser = new \Magento\Framework\Xml\Parser();
                $parser->loadXML($e->getMessage());
                if ($err = $parser->getDom()->getElementsByTagName('Message')->item(0)) {
                    $message = $err->textContent;
                    $details .= $message;
                }
            } catch (\Exception $e) {
                $details = $message;
            }
            $this->messageManager->addErrorMessage('Error Syncing Data to QuickbookOnlines. Details: ' . $details);
        }
    }

    /**
     * @return mixed
     */
    public function getQueueCollection()
    {
        return $this->collectionFactory->create();
    }


    public function syncCustomer()
    {
        $countCustomer = 0;
        $collection = $this->getQueueCollection();
        $collection->addFieldToFilter('type', 'customer');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $queue */
            foreach ($collection as $queue) {
                try {
                    $queue->delete();
                    $this->customer->sync($queue->getTypeId(), true);
                    $countCustomer++;
                } catch (\Exception $exception) {
                    ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Logger\Logger::class)->debug('Manual Sync:'.$exception->getMessage());
                }
            }
        }
        return $countCustomer;
    }

    public function syncItem()
    {
        $countItem = 0;
        $collection = $this->getQueueCollection();
        $collection->addFieldToFilter('type', 'item');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $queue */
            foreach ($collection as $queue) {
                try {
                    $queue->delete();
                    $this->item->sync($queue->getTypeId(), true);
                    $countItem++;
                } catch (\Exception $exception) {
                    ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Logger\Logger::class)->debug('Manual Sync:'.$exception->getMessage());
                }
            }
        }
        return $countItem;
    }

    public function syncOrder()
    {
        $countOrder = 0;
        $collection = $this->getQueueCollection();
        $collection->addFieldToFilter('type', 'order');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $queue */
            foreach ($collection as $queue) {
                try {
                    $queue->delete();
                    $qboId = $this->order->sync($queue->getTypeId());
                    if ($qboId) {
                        $countOrder++;
                    }
                } catch (\Exception $exception) {
                    ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Logger\Logger::class)->debug('Manual Sync:'.$exception->getMessage());
                }
            }
        }
        return $countOrder;
    }

    public function syncInvoice()
    {
        $countInvoice = 0;
        $collection = $this->getQueueCollection();
        $collection->addFieldToFilter('type', 'invoice');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $queue */
            foreach ($collection as $queue) {
                try {
                    $queue->delete();
                    $qboId = $this->invoice->sync($queue->getTypeId());
                    if ($qboId) {
                        $countInvoice++;
                    }
                } catch (\Exception $exception) {
                    ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Logger\Logger::class)->debug('Manual Sync:'.$exception->getMessage());
                }
            }
        }
        return $countInvoice;
    }

    /**
     * sync credit memo
     */
    public function syncCreditmemo()
    {
        $countCreditMemo = 0;
        $collection = $this->getQueueCollection();
        $collection->addFieldToFilter('type', 'creditmemo');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $queue */
            foreach ($collection as $queue) {
                try {
                    $queue->delete();
                    $qboId = $this->creditmemo->sync($queue->getTypeId());
                    if ($qboId) {
                        $countCreditMemo++;
                    }
                } catch (\Exception $exception) {
                    ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Logger\Logger::class)->debug('Manual Sync:'.$exception->getMessage());
                }
            }
        }
        return $countCreditMemo;
    }
}

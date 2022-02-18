<?php

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Queue;

use Magenest\QuickBooksOnline\Controller\Adminhtml\Queue\MassDelete;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer as CustomerSync;
use Magenest\QuickBooksOnline\Model\Synchronization\Item as ItemSync;
use Magenest\QuickBooksOnline\Model\Synchronization\Order as OrderSync;
use Magenest\QuickBooksOnline\Model\Synchronization\Invoice as InvoiceSync;
use Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo as CreditmemoSync;


class MassSync extends MassDelete
{
    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\Customer
     */
    protected $customer;

    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\Item
     */
    protected $item;

    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\Order
     */
    protected $order;

    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\Invoice
     */
    protected $invoice;

    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo
     */
    protected $creditmemo;


    /**
     * MassSync constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magenest\QuickBooksOnline\Model\QueueFactory $queueFactory
     * @param \Magenest\QuickBooksOnline\Model\Synchronization\Customer $customer
     * @param \Magenest\QuickBooksOnline\Model\Synchronization\Item $item
     * @param \Magenest\QuickBooksOnline\Model\Synchronization\Order $order
     * @param \Magenest\QuickBooksOnline\Model\Synchronization\Invoice $invoice
     * @param \Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo $creditmemo
     */
    public function __construct(
        Context $context,
        Filter $filter,
        QueueFactory $queueFactory,
        CustomerSync $customer,
        ItemSync $item,
        OrderSync $order,
        InvoiceSync $invoice,
        CreditmemoSync $creditmemo
    ) {
        parent::__construct($context, $filter, $queueFactory);
        $this->customer = $customer;
        $this->item = $item;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
    }

    public function execute()
    {
        $queueCollection = $this->queueFactory->create()->getCollection();
        $collection = $this->filter->getCollection($queueCollection);
        $queueSync = 0;
        /**
         * @var \Magenest\QuickBooksOnline\Model\Config $config
         */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        foreach ($collection->getItems() as $queue) {
            try {
                $typeSync = $queue->getType();
                if ($typeSync == 'customer' && $config->isEnableByType('customer')) {
                    $queue->delete();
                    $this->customer->sync($queue->getTypeId(), true);
                    $queueSync++;
                } elseif ($typeSync == 'item' && $config->isEnableByType('item')) {
                    $queue->delete();
                    $this->item->sync($queue->getTypeId(), true);
                    $queueSync++;
                } elseif ($typeSync == 'order' && $config->isEnableByType('order')) {
                    $queue->delete();
                    $this->order->sync($queue->getTypeId());
                    $queueSync++;
                } elseif ($typeSync == 'invoice' && $config->isEnableByType('invoice')) {
                    $queue->delete();
                    $this->invoice->sync($queue->getTypeId());
                    $queueSync++;
                } elseif ($typeSync == 'creditmemo' && $config->isEnableByType('creditmemo')) {
                    $queue->delete();
                    $this->creditmemo->sync($queue->getTypeId());
                    $queueSync++;
                }
            } catch (\Exception $exception) {
            }
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) are processed.', $queueSync)
        );

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}
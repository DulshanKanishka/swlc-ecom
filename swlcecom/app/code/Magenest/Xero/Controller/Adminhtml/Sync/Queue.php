<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Queue
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Queue extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\Customer
     */
    protected $_customer;

    /**
     * @var Synchronization\Item
     */
    protected $_item;

    /**
     * @var Synchronization\Order
     */
    protected $_order;

    /**
     * @var Synchronization\Invoice
     */
    protected $_invoice;

    /**
     * @var Synchronization\CreditNote
     */
    protected $_creditNote;

    /**
     * Queue constructor.
     * @param Context $context
     * @param Synchronization\Customer $customer
     * @param Synchronization\Item $item
     * @param Synchronization\Order $order
     * @param Synchronization\Invoice $invoice
     * @param Synchronization\CreditNote $creditNote
     */
    public function __construct(
        Context $context,
        Synchronization\Customer $customer,
        Synchronization\Item $item,
        Synchronization\Order $order,
        Synchronization\Invoice $invoice,
        Synchronization\CreditNote $creditNote
    ) {
        $this->_customer = $customer;
        $this->_item = $item;
        $this->_order = $order;
        $this->_invoice = $invoice;
        $this->_creditNote = $creditNote;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        try {
            if (
                /** Sync all customer in queue */
                !$this->_customer->syncCronJobMode() ||
                /** Sync all item in queue */
                !$this->_item->syncCronJobMode() ||
                /** Sync all order in queue */
                !$this->_order->syncCronJobMode() ||
                /** Sync all invoice in queue */
                !$this->_invoice->syncCronJobMode() ||
                /** Sync all creditnote in queue */
                !$this->_creditNote->syncCronJobMode()
            ) {
                return $resultRedirect;
            }
            $this->messageManager->addSuccess(
                __('All items in queue are synced, check out Logs for result.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('Something happen during syncing process. Detail: ' . $e->getMessage())
            );
        }

        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }
}

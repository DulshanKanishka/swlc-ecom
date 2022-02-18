<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magenest\Xero\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\InvoiceFactory;

/**
 * Class Invoice
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class Invoice extends \Magento\Backend\App\Action
{
    /**
     * @var InvoiceFactory
     */
    protected $invoiceFactory;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var string
     */
    protected $type = 'InvoiceToInvoice';

    /**
     * Invoice constructor.
     * @param Context $context
     * @param InvoiceFactory $invoiceFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        InvoiceFactory $invoiceFactory,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->invoiceFactory = $invoiceFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('id');
        $invoices = $this->invoiceFactory->create()->getCollection();
        $connection = $this->invoiceFactory->create()->getResource()->getConnection();
        $records = [];
        $count = 0;
        $queueModel = $this->queueFactory->create();
        $queueTable = $queueModel->getResource()->getMainTable();

        if ($invoiceId == ''){
            $connection->delete($queueTable, 'type like "%Invoice%"');
        } else {
            $connection->delete($queueTable, 'type like "%Invoice%" AND entity_id = "'.$invoiceId.'"');
            $invoices->getSelect()->where("increment_id = ".$invoiceId);
        }

        $lastId = $invoices->getLastItem()->getId();

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        foreach ($invoices as $invoice) {
            $records[] = [
                'type' => $this->type,
                'entity_id' => $invoice->getIncrementId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $count++;
            if ($count > 5000 || $invoice->getId() == $lastId) {
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        if ($invoiceId != '') {
            $this->messageManager->addSuccess(
                __('Invoice has been added to queue.')
            );
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        } else {
            $response = ['message' => __('All Invoices have been added to queue, <a href="'.$this->getUrl('*/*/index').'">click here</a> to go to check out sync queue')->__toString()];
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultRedirect->setData($response);

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

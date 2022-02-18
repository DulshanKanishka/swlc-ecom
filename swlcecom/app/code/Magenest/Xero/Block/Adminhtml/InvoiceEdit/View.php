<?php
namespace Magenest\Xero\Block\Adminhtml\InvoiceEdit;

use Magenest\Xero\Model\Log\Status;

/**
 * Class View
 * @package Magenest\Xero\Block\Adminhtml\InvoiceEdit
 */
class View extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magenest\Xero\Model\LogFactory $logFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magenest\Xero\Model\LogFactory $logFactory,
        array $data = []
    ) {
        $this->logFactory = $logFactory;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getInvoice()
    {
        return $this->_coreRegistry->registry('current_invoice');
    }

    /**
     * @return string
     */
    public function getInvoiceIncrementId()
    {
        return $this->getInvoice()->getIncrementId();
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'InvoiceToInvoice')
            ->addFieldToFilter('entity_id', $this->getInvoiceIncrementId())
            ->getFirstItem();

        return $log->getData('dequeue_time') ? : 'Never';
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getLastUpdatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'InvoiceToInvoice')
            ->addFieldToFilter('entity_id', $this->getInvoiceIncrementId())
            ->getLastItem();

        return $log->getData('dequeue_time') ? : 'Never';
    }

    /**
     * @return string
     */
    public function getXeroId()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'InvoiceToInvoice')
            ->addFieldToFilter('entity_id', $this->getInvoiceIncrementId())
            ->addFieldToFilter('status', Status::SUCCESS_STATUS)
            ->getLastItem();

        return $log->getData('xero_id');
    }

    /**
     * @return string
     */
    public function getXeroUrl()
    {
        return 'https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID='.$this->getXeroId();
    }

    /**
     * @return mixed
     */
    public function getSyncLog()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'InvoiceToInvoice')
            ->addFieldToFilter('entity_id', $this->getInvoiceIncrementId())
            ->addOrder('dequeue_time', 'DESC')
            ->setPageSize(10);

        return $log;
    }

    /**
     * @return string
     */
    public function getSyncButtonUrl()
    {
        return $this->getUrl('xero/sync/invoice', ['id'=>$this->getInvoiceIncrementId()]);
    }

    public function getAddToQueueButtonUrl()
    {
        return $this->getUrl('xero/queue/invoice', ['id'=>$this->getInvoiceIncrementId()]);
    }

//    public function _toHtml()
//    {
//        if ($this->_scopeConfig->isSetFlag('magenest_xero_config/xero_order/order_invoice_enabled')
//            || !$this->_scopeConfig->isSetFlag('magenest_xero_config/xero_order/enabled')
//        ) {
//            return '';
//        }
//        return parent::_toHtml();
//    }
}

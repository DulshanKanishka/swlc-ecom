<?php
namespace Magenest\Xero\Block\Adminhtml\CreditMemoView;

use Magento\Sales\Model\Order\Creditmemo;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * Creditmemo
     *
     * @var Creditmemo|null
     */
    protected $_creditmemo;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $logFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magenest\Xero\Model\LogFactory $logFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->logFactory = $logFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return Creditmemo
     */
    public function getCreditmemo()
    {
        if ($this->_creditmemo === null) {
            if ($this->hasData('creditmemo')) {
                $this->_creditmemo = $this->_getData('creditmemo');
            } elseif ($this->_coreRegistry->registry('current_creditmemo')) {
                $this->_creditmemo = $this->_coreRegistry->registry('current_creditmemo');
            } elseif ($this->getParentBlock() && $this->getParentBlock()->getCreditmemo()) {
                $this->_creditmemo = $this->getParentBlock()->getCreditmemo();
            }
        }
        return $this->_creditmemo;
    }

    public function getCreditmemoId()
    {
        return 'C'.$this->getCreditmemo()->getIncrementId();
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function ever()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'CreditNote')
            ->addFieldToFilter('entity_id', $this->getCreditmemoId())
            ->getFirstItem();

        return $this->formatDate($log->getData('dequeue_time'), \IntlDateFormatter::MEDIUM, true);
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getLastUpdatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'CreditNote')
            ->addFieldToFilter('entity_id', $this->getCreditmemoId())
            ->getLastItem();

        return $log->getData('dequeue_time') ? : 'N';
    }

    /**
     * @return string
     */
    public function getXeroId()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'CreditNote')
            ->addFieldToFilter('entity_id', $this->getCreditmemoId());
        foreach ($log as $v) {
            if ($v->getData('xero_id')) {
                return $v->getData('xero_id');
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getXeroUrl()
    {
        return 'https://go.xero.com/AccountsReceivable/ViewCreditNote.aspx?creditNoteID='.$this->getXeroId();
    }

    /**
     * @return mixed
     */
    public function getSyncLog()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'CreditNote')
            ->addFieldToFilter('entity_id', $this->getCreditmemoId())
            ->addOrder('dequeue_time', 'DESC')
            ->setPageSize(10);

        return $log;
    }

    /**
     * @return string
     */
    public function getSyncButtonUrl()
    {
        return $this->getUrl('xero/sync/credit', ['id'=>$this->getCreditmemo()->getId()]);
    }

    /**
     * @return string
     */
    public function getAddToQueueButtonUrl()
    {
        return $this->getUrl('xero/queue/credit', ['id'=>$this->getCreditmemo()->getId()]);
    }
}

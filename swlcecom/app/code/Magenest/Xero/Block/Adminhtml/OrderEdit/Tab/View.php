<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\Xero\Block\Adminhtml\OrderEdit\Tab;

use Magenest\Xero\Model\Log\Status;

/**
 * Class View
 * @package Magenest\Xero\Block\Adminhtml\OrderEdit\Tab
 */
class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'tab/view/xero_order_info.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;

    /**
     * @var string
     */
    protected $type = 'OrderToInvoice';


    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magenest\Xero\Model\LogFactory $logFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logFactory = $logFactory;
        $this->_coreRegistry = $registry;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Xero Integration');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Sync History');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('entity_id', $this->getOrderIncrementId())
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
            ->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('entity_id', $this->getOrderIncrementId())
            ->getLastItem();

        return $log->getData('dequeue_time') ? : 'Never';
    }

    /**
     * @return string
     */
    public function getXeroId()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('entity_id', $this->getOrderIncrementId())
            ->addFieldToFilter('status', Status::SUCCESS_STATUS)
            ->getLastItem();

        return $log->getData('xero_id');
    }

    /**
     * @return string
     */
    public function getXeroUrl()
    {
        $url = 'https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID='.$this->getXeroId();
        return $url;
    }

    /**
     * @return mixed
     */
    public function getSyncLog()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('entity_id', $this->getOrderIncrementId())
            ->addOrder('dequeue_time', 'DESC')
            ->setPageSize(10);

        return $log;
    }

    /**
     * @return string
     */
    public function getSyncButtonUrl()
    {
        return $this->getUrl('xero/sync/order', ['id'=>$this->getOrderIncrementId()]);
    }

    /**
     * @return string
     */
    public function getAddToQueueButtonUrl()
    {
        return $this->getUrl('xero/queue/order', ['id'=>$this->getOrderIncrementId()]);
    }
}

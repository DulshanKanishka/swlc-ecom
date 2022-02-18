<?php
namespace Magenest\Xero\Block\Adminhtml\ProductEdit\Tab\View;

use Magenest\Xero\Model\Log\Status;

/**
 * Class XeroCustomerInfo
 * @package Magenest\Xero\Block\Adminhtml\Edit\Tab\View
 */
class XeroItemInfo extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;

    /**
     * XeroItemInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magenest\Xero\Model\LogFactory $logFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magenest\Xero\Model\LogFactory $logFactory,
        array $data = []
    ) {
        $this->logFactory = $logFactory;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve currently edited product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('current_product');
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * @return int
     */
    public function getSku()
    {
        return $this->getProduct()->getSku();
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
                    ->addFieldToFilter('type', 'Item')
                    ->addFieldToFilter('entity_id', $this->getSku())
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
            ->addFieldToFilter('type', 'Item')
            ->addFieldToFilter('entity_id', $this->getSku())
            ->getLastItem();

        return $log->getData('dequeue_time') ? : 'Never';
    }

    /**
     * @return string
     */
    public function getXeroId()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'Item')
            ->addFieldToFilter('entity_id', $this->getSku())
            ->addFieldToFilter('status', Status::SUCCESS_STATUS)
            ->getLastItem();

        return $log->getData('xero_id');
    }

    /**
     * @return string
     */
    public function getXeroUrl()
    {
        return 'https://go.xero.com/Accounts/Inventory/'.$this->getXeroId();
    }

    /**
     * @return mixed
     */
    public function getSyncLog()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'Item')
            ->addFieldToFilter('entity_id', $this->getSku())
            ->addOrder('dequeue_time', 'DESC')
            ->setPageSize(10);

        return $log;
    }

    /**
     * @return string
     */
    public function getSyncButtonUrl()
    {
        return $this->getUrl('xero/sync/product', ['id'=>$this->getProductId()]);
    }

    /**
     * @return string
     */
    public function getAddToQueueButtonUrl()
    {
        return $this->getUrl('xero/queue/product', ['id'=>$this->getProductId()]);
    }
}

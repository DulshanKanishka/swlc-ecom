<?php

namespace Magenest\Xero\Block\Adminhtml\CustomerEdit\Tab\View;

use Magenest\Xero\Model\Log\Status;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\Customer;

/**
 * Class XeroCustomerInfo
 * @package Magenest\Xero\Block\Adminhtml\Edit\Tab\View
 */
class XeroCustomerInfo extends \Magento\Backend\Block\Template
{
    /**
     * Customer
     *
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $customer;

    /**
     * Customer registry
     *
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;


    /**
     * Customer data factory
     *
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Data object helper
     *
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;

    /**
     * XeroCustomerInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magenest\Xero\Model\LogFactory $logFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magenest\Xero\Model\LogFactory $logFactory,
        array $data = []
    ) {
        $this->logFactory = $logFactory;
        $this->coreRegistry = $registry;
        $this->customerDataFactory = $customerDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $data);
    }

    /**
     * Set customer registry
     *
     * @param \Magento\Framework\Registry $coreRegistry
     * @return void
     * @deprecated
     */
    public function setCustomerRegistry(\Magento\Customer\Model\CustomerRegistry $customerRegistry)
    {
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Get customer registry
     *
     * @return \Magento\Customer\Model\CustomerRegistry
     * @deprecated
     */
    public function getCustomerRegistry()
    {
        if (!($this->customerRegistry instanceof \Magento\Customer\Model\CustomerRegistry)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\CustomerRegistry');
        } else {
            return $this->customerRegistry;
        }
    }

    /**
     * Retrieve customer object
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        if (!$this->customer) {
            $this->customer = $this->customerDataFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $this->customer,
                $this->_backendSession->getCustomerData()['account'],
                '\Magento\Customer\Api\Data\CustomerInterface'
            );
        }

        return $this->customer;
    }

    /**
     * Retrieve customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get customer creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        $log = $this->logFactory->create()->getCollection()
                    ->addFieldToFilter('type', 'Contact')
                    ->addFieldToFilter('entity_id', $this->getCustomerId())
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
            ->addFieldToFilter('type', 'Contact')
            ->addFieldToFilter('entity_id', $this->getCustomerId())
            ->getLastItem();

        return $log->getData('dequeue_time') ? : 'Never';
    }

    /**
     * @return string
     */
    public function getXeroId()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'Contact')
            ->addFieldToFilter('entity_id', $this->getCustomerId())
            ->addFieldToFilter('status', Status::SUCCESS_STATUS)
            ->getLastItem();

        return $log->getData('xero_id');
    }

    /**
     * Link to view contacts
     *
     * @return string
     */
    public function getXeroUrl()
    {
        return 'https://go.xero.com/Contacts/View/'.$this->getXeroId();
    }

    /**
     * @return mixed
     */
    public function getSyncLog()
    {
        $log = $this->logFactory->create()->getCollection()
            ->addFieldToFilter('type', 'Contact')
            ->addFieldToFilter('entity_id', $this->getCustomerId())
            ->addOrder('dequeue_time', 'DESC');

        return $log;
    }

    /**
     * @return string
     */
    public function getSyncButtonUrl()
    {
        return $this->getUrl('xero/sync/customer', ['id'=>$this->getCustomerId()]);
    }

    /**
     * @return string
     */
    public function getAddToQueueButtonUrl()
    {
        return $this->getUrl('xero/queue/customer', ['id'=>$this->getCustomerId()]);
    }
}

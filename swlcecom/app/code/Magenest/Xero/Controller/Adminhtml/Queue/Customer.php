<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magenest\Xero\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Config\Model\ResourceModel\Config;

/**
 * Class Customer
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class Customer extends \Magento\Backend\App\Action
{
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_configInterface;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var string
     */
    protected $type = 'Contact';

    /**
     * Customer constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param Config $config
     * @param ScopeConfigInterface $configInterface
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Config $config,
        ScopeConfigInterface $configInterface,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->_config = $config;
        $this->_configInterface = $configInterface;
        $this->customerFactory = $customerFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $customerId = $this->getRequest()->getParam('id');
        $queueModel = $this->queueFactory->create();
        $queueTable = $queueModel->getResource()->getMainTable();
        $connection = $this->customerFactory->create()->getResource()->getConnection();
        $customers = $this->customerFactory->create()->getCollection();

        if ($customerId == ''){
            $connection->delete($queueTable, 'type = "'.$this->type.'"');
        } else {
            $connection->delete($queueTable, 'type = "'.$this->type.'" AND entity_id = "'.$customerId.'"');
            $customers->getSelect()->where("entity_id = ".$customerId);

        }
        $records = [];
        $count = 0;
        $lastId = $customers->getLastItem()->getId();

        /** @var \Magento\Customer\Model\Customer $customer */
        foreach ($customers as $customer) {
            $records[] = [
                'type' => $this->type,
                'entity_id' => $customer->getId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $count++;
            if ($count > 5000 || $customer->getId() == $lastId) {
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        if ($customerId != '') {
            $this->messageManager->addSuccess(
                __('Customer has been added to queue.')
            );
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        } else {
            $response = ['message' => __('All Customers have been added to queue, <a href="'.$this->getUrl('*/*/index').'">click here</a> to go to check out sync queue')->__toString()];
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

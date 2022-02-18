<?php
/**
 * Copyright © 2018 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\Xero\Controller\Adminhtml\MassQueue;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magenest\Xero\Model\QueueFactory;

class Customer extends AbstractMassQueue
{
    protected $_collectionFactory;

    protected $_queueFactory;

    protected $_enable = "magenest_xero_config/xero_contact/enabled";

    protected $_type = "Contact";

    public function __construct(
        Context $context,
        ScopeConfigInterface $config,
        Filter $filter,
        CollectionFactory $collectionFactory,
        QueueFactory $queueFactory
    ){
        $this->_collectionFactory = $collectionFactory;
        $this->_queueFactory = $queueFactory;
        parent::__construct($context, $config, $filter);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());

        $queueModel = $this->_queueFactory->create();
        $connection = $queueModel->getResource()->getConnection();
        $queueTable = $queueModel->getResource()->getMainTable();

        $records = [];
        $count = 0;
        $lastId = $collection->getLastItem()->getId();

        $deleteIds = [];
        /** @var \Magento\Customer\Model\Customer $customer */
        foreach ($collection as $customer) {
            $records[] = [
                'type' => $this->_type,
                'entity_id' => $customer->getId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $deleteIds[] = $customer->getId();
            $count++;
            if ($count > 5000 || $customer->getId() == $lastId) {
                $idsString = $connection->quoteInto('entity_id IN (?)', $deleteIds);
                $connection->delete($queueTable, "{$idsString} AND type like 'Contact'");
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been add to queue.', $collection->getSize()));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('customer/index/');
    }
}

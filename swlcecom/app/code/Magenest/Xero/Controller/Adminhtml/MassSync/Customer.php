<?php
/**
 * Copyright © 2018 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\Xero\Controller\Adminhtml\MassSync;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magenest\Xero\Model\Synchronization\Customer as SyncModel;
use Magenest\Xero\Model\QueueFactory;
use Magenest\Xero\Model\Helper;

class Customer extends AbstractMassSync
{
    protected $_collectionFactory;

    protected $_syncModel;

    protected $_queueFactory;

    protected $_enable = "magenest_xero_config/xero_contact/enabled";

    public function __construct(
        Context $context,
        ScopeConfigInterface $config,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SyncModel $customer,
        QueueFactory $queueFactory,
        Helper $helper
    ){
        $this->_collectionFactory = $collectionFactory;
        $this->_syncModel = $customer;
        $this->_queueFactory = $queueFactory;
        parent::__construct($context, $config, $filter, $helper);
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
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $ids = [];
        foreach ($collection as $customer) {
            $ids[] = $customer->getId();
            if (count($ids) > 1000) {
                if (!$this->_helper->isXeroConnectedByIds($ids, $this->_collectionFactory, 'entity_id')) {
                    $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                    return $resultRedirect->setPath('customer/index/');
                };
                if ($this->_helper->isMultipleWebsiteEnable()) {
                    $this->_syncModel->addRecordsForMultipleWebsite($ids, $connection, $queueModel);
                } else {
                    $this->_syncModel->addRecords($ids, $connection, $queueModel);
                }
                $ids = [];
            }
        }

        if (count($ids) > 0) {
            if (!$this->_helper->isXeroConnectedByIds($ids, $this->_collectionFactory, 'entity_id')) {
                $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                return $resultRedirect->setPath('customer/index/');
            };
            if ($this->_helper->isMultipleWebsiteEnable()) {
                $this->_syncModel->addRecordsForMultipleWebsite($ids, $connection, $queueModel);
            } else {
                $this->_syncModel->addRecords($ids, $connection, $queueModel);
            }
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been synced.', $collection->getSize()));
        return $resultRedirect->setPath('customer/index/');
    }
}

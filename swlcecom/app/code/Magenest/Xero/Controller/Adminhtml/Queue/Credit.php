<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magenest\Xero\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;

/**
 * Class Credit
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class Credit extends \Magento\Backend\App\Action
{
    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var string
     */
    protected $type = 'CreditNote';

    /**
     * Credit constructor.
     * @param Context $context
     * @param CreditmemoFactory $creditmemoFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        CreditmemoFactory $creditmemoFactory,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $creditId = $this->getRequest()->getParam('id');
        $connection = $this->_objectManager->create('\Magento\Sales\Model\Order\Creditmemo')->getResource()->getConnection();
        $queueModel = $this->queueFactory->create();
        $queueTable = $queueModel->getResource()->getMainTable();
        $creditmemos = $this->_objectManager->create('\Magento\Sales\Model\Order\Creditmemo')->getCollection();

        if ($creditId == ''){
            $connection->delete($queueTable, 'type = "'.$this->type.'"');
        } else {
            $creditmemos->getSelect()->where("entity_id = ".$creditId);
            $connection->delete($queueTable, 'type = "'.$this->type.'" AND entity_id = "'.$creditmemos->getLastItem()->getIncrementId().'"');
        }
        $records = [];
        $count = 0;
        $lastId = $creditmemos->getLastItem()->getIncrementId();

        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        foreach ($creditmemos as $creditmemo) {
            $records[] = [
                'type' => $this->type,
                'entity_id' => $creditmemo->getIncrementId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $count++;
            if ($count > 5000 || $creditmemo->getIncrementId() == $lastId) {
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        if ($creditId != '') {
            $this->messageManager->addSuccess(
                __('Credit Memo has been added to queue.')
            );
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        } else {
            $response = ['message' => __('All Credit Memos have been added to queue, <a href="'.$this->getUrl('*/*/index').'">click here</a> to go to check out sync queue')->__toString()];
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

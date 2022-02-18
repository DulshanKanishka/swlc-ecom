<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magenest\Xero\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Order
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Order extends \Magento\Backend\App\Action
{
    /**
     * @var
     */
    protected $orderFactory;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var string
     */
    protected $type = 'OrderToInvoice';

    /**
     * Order constructor.
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfigInterface,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('id');
        $connection = $this->orderFactory->create()->getResource()->getConnection();
        $queueModel = $this->queueFactory->create();
        $queueTable = $queueModel->getResource()->getMainTable();
        $orders = $this->orderFactory->create()->getCollection();

        if ($orderId == ''){
            $connection->delete($queueTable, 'type like "%Invoice%"');
        } else {
            $connection->delete($queueTable, 'type like "%Invoice%" AND entity_id = "'.$orderId.'"');
            $orders->getSelect()->where("increment_id = ".$orderId);
        }

        $records = [];
        $count = 0;
        $lastId = $orders->getLastItem()->getId();

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $records[] = [
                'type' => $this->type,
                'entity_id' => $order->getIncrementId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $count++;
            if ($count > 5000 || $order->getId() == $lastId) {
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        if ($orderId != '') {
            $this->messageManager->addSuccess(
                __('Order has been added to queue.')
            );
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        } else {
            $response = ['message' => __('All Orders have been added to queue, <a href="'.$this->getUrl('*/*/index').'">click here</a> to go to check out sync queue')->__toString()];
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

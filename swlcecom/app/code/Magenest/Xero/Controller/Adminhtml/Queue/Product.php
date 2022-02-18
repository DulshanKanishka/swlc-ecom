<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magenest\Xero\Model\QueueFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Product
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class Product extends \Magento\Backend\App\Action
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var string
     */
    protected $type = 'Item';

    /**
     * Order constructor.
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('id');

        $products = $this->productFactory->create()->getCollection();
        $connection = $this->productFactory->create()->getResource()->getConnection();
        $records = [];
        $count = 0;

        $queueModel = $this->queueFactory->create();
        $queueTable = $queueModel->getResource()->getMainTable();
        if ($productId == ''){
            $connection->delete($queueTable, 'type = "'.$this->type.'"');
        } else {
            $connection->delete($queueTable, 'type = "'.$this->type.'" AND entity_id = "'.$productId.'"');
            $products->getSelect()->where("entity_id = ".$productId);
        }
        $lastId = $products->getLastItem()->getId();
        /** @var \Magento\Customer\Model\Customer $customer */
        foreach ($products as $product) {
            $records[] = [
                'type' => $this->type,
                'entity_id' => $product->getId(),
                'enqueue_time' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'priority' => 1
            ];
            $count++;
            if ($count > 5000 || $product->getId() == $lastId) {
                $connection->insertMultiple($queueTable, $records);
                $records = [];
                $count = 0;
            }
        }
        if ($productId != '') {
            $this->messageManager->addSuccess(
                __('Product has been added to queue.')
            );
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        } else {
            $response = ['message' => __('All Products have been added to queue, <a href="'.$this->getUrl('*/*/index').'">click here</a> to go to check out sync queue')->__toString()];
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

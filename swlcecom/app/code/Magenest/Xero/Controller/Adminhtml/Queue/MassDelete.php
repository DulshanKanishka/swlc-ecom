<?php
namespace Magenest\Xero\Controller\Adminhtml\Queue;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magenest\Xero\Model\ResourceModel\Queue\CollectionFactory;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 * @package Magenest\Xero\Controller\Adminhtml\Queue
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        QueueFactory $queueFactory
    ) {
        $this->queueFactory = $queueFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $queueModel = $this->queueFactory->create();
            $ids = [];
            $count = 0;
            $affectedRows = 0;
            $lastItemId = $collection->getLastItem()->getId();
            foreach ($collection as $item) {
                $ids[] = $item->getId();
                $count++;
                if ($count >= 5000 || $item->getId() == $lastItemId) {
                    $idsString = implode(',', $ids);
                    $affectedRows += $queueModel->getResource()->getConnection()->delete($queueModel->getResource()->getMainTable(), 'id IN ('.$idsString.')');
                    $count = 0;
                    $ids = [];
                }
            }

            $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', $affectedRows));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*');

        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::queue');
    }
}

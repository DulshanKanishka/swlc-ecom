<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

class MassDelete extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{
    /** @var  \Magento\Ui\Component\MassAction\Filter $_filer */
    protected $_filer;

    /** @var  \Magenest\AbandonedCart\Model\ResourceModel\BlackList\CollectionFactory $_collectionFactory */
    protected $_collectionFactory;

    /**
     * MassDelete constructor.
     *
     * @param \Magenest\AbandonedCart\Model\ResourceModel\BlackList\CollectionFactory $collectionFactory
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\ResourceModel\BlackList\CollectionFactory $collectionFactory,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_filer             = $filter;
        parent::__construct($blacklistFactory, $logger, $registry, $pageFactory, $context);
    }

    public function execute()
    {
        try {
            $collection = $this->_filer->getCollection($this->_collectionFactory->create());
            $count      = 0;
            $ids        = [];
            foreach ($collection->getItems() as $item) {
                $ids[] = $item->getId();
                $count++;
            }
            $blacklistModel = $this->_blacklistFactory->create();
            $blacklistModel->deleteMultiple($ids);
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $count)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
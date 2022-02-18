<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml;

abstract class Unsubscribe extends \Magento\Backend\App\Action
{

    protected $_unsubscribeFactory;

    /** @var  \Magento\Ui\Component\MassAction\Filter $_filer */
    protected $_filer;

    /** @var  \Magenest\AbandonedCart\Model\ResourceModel\Unsubscribe\CollectionFactory $_collectionFactory */
    protected $_collectionFactory;

    /** @var  \Magenest\AbandonedCart\Model\LogContentFactory $_logContent */
    protected $_logContentFactory;

    /** @var  \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var \Magento\Framework\View\Result\PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /**
     * Unsubscribe constructor.
     *
     * @param \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magenest\AbandonedCart\Model\ResourceModel\Unsubscribe\CollectionFactory $collectionFactory
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContent
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magenest\AbandonedCart\Model\ResourceModel\Unsubscribe\CollectionFactory $collectionFactory,
        \Magenest\AbandonedCart\Model\LogContentFactory $logContent,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_unsubscribeFactory = $unsubscribeFactory;
        $this->_filer              = $filter;
        $this->_collectionFactory  = $collectionFactory;
        $this->_logContentFactory  = $logContent;
        $this->_logger             = $logger;
        $this->_coreRegistry       = $coreRegistry;
        $this->_resultPageFactory  = $resultPageFactory;
        parent::__construct($context);
    }
}
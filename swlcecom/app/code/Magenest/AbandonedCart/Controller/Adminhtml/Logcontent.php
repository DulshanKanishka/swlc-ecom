<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml;

abstract class Logcontent extends \Magento\Backend\App\Action
{
    /** @var \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var \Magento\Framework\View\Result\PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /**
     * Logcontent constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_logger            = $logger;
        $this->_logContentFactory = $contentFactory;
        $this->_coreRegistry      = $registry;
        $this->_resultPageFactory = $pageFactory;
        parent::__construct($context);
    }
}
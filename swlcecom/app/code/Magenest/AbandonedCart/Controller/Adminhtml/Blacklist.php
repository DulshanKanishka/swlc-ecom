<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

abstract class Blacklist extends \Magento\Backend\App\Action
{
    /** @var \Magenest\AbandonedCart\Model\BlackListFactory $_blacklistFactory */
    protected $_blacklistFactory;

    /** @var \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var \Magento\Framework\View\Result\PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /**
     * Blacklist constructor.
     *
     * @param \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param PageFactory $pageFactory
     * @param Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_blacklistFactory  = $blacklistFactory;
        $this->_logger            = $logger;
        $this->_coreRegistry      = $registry;
        $this->_resultPageFactory = $pageFactory;
        parent::__construct($context);
    }

}
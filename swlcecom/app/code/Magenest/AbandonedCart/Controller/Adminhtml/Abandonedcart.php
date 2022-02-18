<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

abstract class Abandonedcart extends \Magento\Backend\App\Action
{

    /** @var  \Magenest\AbandonedCart\Model\Cron $_cronJob */
    protected $_cronJob;

    /** @var \Magenest\AbandonedCart\Model\AbandonedCartFactory $_abandonedCartFactory */
    protected $_abandonedCartFactory;

    /** @var \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart $_abandonedCartResource */
    protected $_abandonedCartResource;

    /** @var \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart\CollectionFactory $_collectionFactory */
    protected $_collectionFactory;

    /** @var  \Magenest\AbandonedCart\Model\LogContentFactory $_logContent */
    protected $_logContentFactory;

    /** @var  \Magenest\AbandonedCart\Helper\SendMail $_sendMailHelper */
    protected $_sendMailHelper;

    /** @var \Magenest\AbandonedCart\Helper\MandrillConnector $_mandrillConnector */
    protected $_mandrillConnector;

    /** @var  \Magenest\AbandonedCart\Helper\SendSms $_sendSmsHelper */
    protected $_sendSmsHelper;

    /** @var  \Magento\Ui\Component\MassAction\Filter $_filer */
    protected $_filter;

    /** @var  \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var \Magento\Framework\View\Result\PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /**
     * Abandonedcart constructor.
     *
     * @param \Magenest\AbandonedCart\Model\Cron $cron
     * @param \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
     * @param \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart\CollectionFactory $collectionFactory
     * @param \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart $abandonedCartResource
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory
     * @param \Magenest\AbandonedCart\Helper\SendMail $sendMail
     * @param \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector
     * @param \Magenest\AbandonedCart\Helper\SendSms $sendSms
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param LoggerInterface $logger
     * @param Registry $coreRegistry
     * @param PageFactory $pageFactory
     * @param Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\Cron $cron,
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory,
        \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart\CollectionFactory $collectionFactory,
        \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart $abandonedCartResource,
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magenest\AbandonedCart\Helper\SendMail $sendMail,
        \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector,
        \Magenest\AbandonedCart\Helper\SendSms $sendSms,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_cronJob               = $cron;
        $this->_abandonedCartFactory  = $abandonedCartFactory;
        $this->_collectionFactory     = $collectionFactory;
        $this->_abandonedCartResource = $abandonedCartResource;
        $this->_logContentFactory     = $contentFactory;
        $this->_sendMailHelper        = $sendMail;
        $this->_mandrillConnector     = $mandrillConnector;
        $this->_sendSmsHelper         = $sendSms;
        $this->_filter                = $filter;
        $this->_logger                = $logger;
        $this->_coreRegistry          = $coreRegistry;
        $this->_resultPageFactory     = $pageFactory;
        parent::__construct($context);
    }
}
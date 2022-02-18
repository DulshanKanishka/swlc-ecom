<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml;

abstract class Rule extends \Magento\Backend\App\Action
{

    /** @var \Magenest\AbandonedCart\Model\RuleFactory $_ruleFactory */
    protected $_ruleFactory;

    /** @var  \Magenest\AbandonedCart\Model\ResourceModel\Rule\CollectionFactory $_collectionFactory */
    protected $_collectionFactory;

    /** @var  \Magenest\AbandonedCart\Model\Cron $_cronJob */
    protected $_cronJob;

    /** @var  \Magenest\AbandonedCart\Model\TestCampaignFactory $_testCampaignFactory */
    protected $_testCampaignFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime $_dateTime */
    protected $_dateTime;

    /** @var \Magenest\AbandonedCart\Helper\Data $_helperData */
    protected $_helperData;

    /** @var  \Magenest\AbandonedCart\Model\LogContentFactory $_logContent */
    protected $_logContentFactory;

    /** @var  \Magenest\AbandonedCart\Helper\SendMail $_sendMailHelper */
    protected $_sendMailHelper;

    /** @var \Magenest\AbandonedCart\Helper\MandrillConnector $_mandrillConnector */
    protected $_mandrillConnector;

    /** @var  \Magento\Framework\Controller\Result\RawFactory $rawResultFactory */
    protected $resultRawFactory;

    /** @var  \Magento\Ui\Component\MassAction\Filter $_filer */
    protected $_filer;

    /** @var  \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var \Magento\Framework\View\Result\PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Rule constructor.
     *
     * @param \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory
     * @param \Magenest\AbandonedCart\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magenest\AbandonedCart\Model\Cron $cron
     * @param \Magenest\AbandonedCart\Model\TestCampaignFactory $testCampaignFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magenest\AbandonedCart\Helper\Data $helperData
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory
     * @param \Magenest\AbandonedCart\Helper\SendMail $sendMail
     * @param \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
        \Magenest\AbandonedCart\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Magenest\AbandonedCart\Model\Cron $cron,
        \Magenest\AbandonedCart\Model\TestCampaignFactory $testCampaignFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magenest\AbandonedCart\Helper\Data $helperData,
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magenest\AbandonedCart\Helper\SendMail $sendMail,
        \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector,
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_ruleFactory         = $ruleFactory;
        $this->_collectionFactory   = $collectionFactory;
        $this->_cronJob             = $cron;
        $this->_testCampaignFactory = $testCampaignFactory;
        $this->_dateTime            = $dateTime;
        $this->_helperData          = $helperData;
        $this->_logContentFactory   = $contentFactory;
        $this->_sendMailHelper      = $sendMail;
        $this->_mandrillConnector   = $mandrillConnector;
        $this->resultRawFactory     = $rawResultFactory;
        $this->_filer               = $filter;
        $this->_logger              = $logger;
        $this->_coreRegistry        = $coreRegistry;
        $this->_resultPageFactory   = $resultPageFactory;
        $this->_localeDate          = $timezone;
        $this->resultLayoutFactory  = $resultLayoutFactory;
        $this->resultJsonFactory    = $resultJsonFactory;
        parent::__construct($context);
    }

    public function geNotiLogId($ruleId)
    {
        $flag = false;
        try {
            $collection = $this->_logContentFactory->create()
                ->addFieldToFilter('rule_id', $ruleId)
                ->getFirstItem();
            if ($collection->getRuleId()) {
                $flag = true;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
        return $flag;
    }
}
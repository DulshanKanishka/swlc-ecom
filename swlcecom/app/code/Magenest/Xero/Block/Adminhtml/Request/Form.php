<?php
namespace Magenest\Xero\Block\Adminhtml\Request;

use Magento\Sales\Model\Order;

/**
 * Class Form
 * @package Magenest\Xero\Block\Adminhtml\Request
 */
class Form extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;

    /**
     * @var \Magenest\Xero\Model\RequestLogFactory
     */
    protected $requestLogFactory;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Reader
     */
    protected $configReader;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magenest\Xero\Model\LogFactory $logFactory,
        \Magenest\Xero\Model\RequestLogFactory $requestLogFactory,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        array $data = []
    ) {
        $this->configReader = $configReader;
        $this->requestLogFactory = $requestLogFactory;
        $this->logFactory = $logFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getTodayRequest()
    {
        $requestLog = $this->requestLogFactory->create()->getCollection()
            ->addFieldToFilter('date', date('Y-m-d'))
            ->getFirstItem();

        return $requestLog->getData('request');
    }

    /**
     * @return bool
     */
    public function getTodayCreditNoteRequest()
    {
        $log = $this->logFactory->create()->getCollection();
        $log
            ->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d')])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type')
            ->having('type="CreditNote"');
        foreach ($log as $result) {
            return $result->getData('count');
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getTodayItemRequest()
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d')])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type')
            ->having('type="Item"');
        foreach ($log as $result) {
            return $result->getData('count');
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getTodayContactRequest()
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d')])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type')
            ->having('type="Contact"');
        foreach ($log as $result) {
            return $result->getData('count');
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getTodayOrderRequest()
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d')])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type')
            ->having('type="OrderToInvoice"');
        foreach ($log as $result) {
            return $result->getData('count');
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getTodayInvoiceRequest()
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d')])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type')
            ->having('type="InvoiceToInvoice"');
        foreach ($log as $result) {
            return $result->getData('count');
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getHighestRequestDay()
    {
        $requestLog = $this->requestLogFactory->create()->getCollection()
            ->addOrder('request', 'DESC')
            ->getFirstItem();

        return $requestLog->getData('date');
    }

    /**
     * @return mixed
     */
    public function getHighestRequest()
    {
        $requestLog = $this->requestLogFactory->create()->getCollection()
            ->addOrder('request', 'DESC')
            ->getFirstItem();

        return $requestLog->getData('request');
    }

    /**
     * @return mixed
     */
    public function getLowestRequestDay()
    {
        $requestLog = $this->requestLogFactory->create()->getCollection()
            ->addOrder('request', 'ASC')
            ->getFirstItem();

        return $requestLog->getData('date');
    }

    /**
     * @return mixed
     */
    public function getLowestRequest()
    {
        $requestLog = $this->requestLogFactory->create()->getCollection()
            ->addOrder('request', 'ASC')
            ->getFirstItem();

        return $requestLog->getData('request');
    }
}

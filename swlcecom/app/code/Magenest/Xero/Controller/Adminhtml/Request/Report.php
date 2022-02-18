<?php
namespace Magenest\Xero\Controller\Adminhtml\Request;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Report
 * @package Magenest\Xero\Controller\Adminhtml\Request
 */
class Report extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magenest\Xero\Model\RequestLogFactory
     */
    protected $requestLogFactory;

    /**
     * @var \Magenest\Xero\Model\LogFactory
     */
    protected $logFactory;
    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magenest\Xero\Model\LogFactory $logFactory,
        \Magenest\Xero\Model\RequestLogFactory $requestLogFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->requestLogFactory = $requestLogFactory;
        $this->logFactory = $logFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $startDate = '';
        $endDate = '';
        $params = $this->getRequest()->getParams();
        foreach ($params as $key => $param) {
            if ($key == 'start_date') {
                $startDate = $param;
            }
            if ($key == 'end_date') {
                $endDate = $param;
            }
        }
        $requestLog = $this->getRequestLog($startDate, $endDate);
        $log = $this->mergeLog($this->getLog($startDate, $endDate), $this->getFailedLog($startDate, $endDate));
        $requestLog['items'] = array_merge($log, $requestLog['items']);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultPage->setData($requestLog);

        return $resultPage;
    }

    /**
     * get request log from $startDate to $endDate
     *
     * @param $startDate
     * @param $endDate
     * @return array
     */
    protected function getRequestLog($startDate, $endDate)
    {
        return $requestLog = $this->requestLogFactory->create()
            ->getCollection()
            ->addFieldToFilter('date', ['gteq' => date('Y-m-d', strtotime($startDate))])
            ->addFieldToFilter('date', ['lt' => date('Y-m-d', strtotime($endDate."+1 days"))])
            ->toArray();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     */
    protected function getLog($startDate, $endDate)
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d', strtotime($startDate))])
            ->addFieldToFilter('dequeue_time', ['lt' => date('Y-m-d', strtotime($endDate."+1 days"))])
            ->getSelect()
            ->columns(['COUNT(id) as count'])
            ->group('type');

        return $log->getData();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     */
    protected function getFailedLog($startDate, $endDate)
    {
        $log = $this->logFactory->create()->getCollection();
        $log->addFieldToFilter('dequeue_time', ['gteq' => date('Y-m-d', strtotime($startDate))])
            ->addFieldToFilter('dequeue_time', ['lt' => date('Y-m-d', strtotime($endDate."+1 days"))])
            ->getSelect()
            ->columns(['COUNT(id) as count_failed'])
            ->group('type')
            ->group('status')
            ->having('status = 2');

        return $log->getData();
    }

    /**
     * @param $logs
     * @param $failedLogs
     * @return mixed
     */
    protected function mergeLog($logs, $failedLogs)
    {
        foreach ($logs as &$log) {
            foreach ($failedLogs as $failedLog) {
                if (isset($log['type']) && isset($failedLog['type']) && $log['type'] == $failedLog['type']) {
                    $log = array_merge($log, $failedLog);
                }
            }
        }

        return $logs;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::queue');
    }
}

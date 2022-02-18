<?php

namespace Magenest\AbandonedCart\Ui\DataProvider\LogContent;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class LogContentDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * LogContentDataProvider constructor.
     *
     * @param LoggerInterface $logger
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->_logger            = $logger;
        $this->_logContentFactory = $logContentFactory;
        $this->collection         = $this->_logContentFactory->create()->getCollection()->addFieldToFilter(
            'type',
            ['neq' => 'Campaign']
        );
        $this->request            = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function getSearchResult()
    {
        $result = parent::getSearchResult();
        $result->getSelect()->where("`type` != 'Campaign'");
        return $result;
    }
}
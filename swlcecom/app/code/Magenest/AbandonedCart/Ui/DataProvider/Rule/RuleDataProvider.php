<?php

namespace Magenest\AbandonedCart\Ui\DataProvider\Rule;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\DB\Select;
use Magenest\AbandonedCart\Model\Config\Source\Mail;

class RuleDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /** @var \Magenest\AbandonedCart\Model\RuleFactory $_ruleFactory */
    protected $_ruleFactory;

    /** @var \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var RequestInterface $request */
    protected $request;

    /**
     * RuleDataProvider constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory
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
        \Psr\Log\LoggerInterface $logger,
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
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
        $this->_logger      = $logger;
        $this->_ruleFactory = $ruleFactory;
        $this->collection   = $this->_ruleFactory->create()->getCollection();
        $this->request      = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function getSearchResult()
    {
        $result = parent::getSearchResult();
        $result->getSelect()
            ->joinLeft(
                ['log_emails' => $this->getEmailCountSelect()],
                'log_emails.id = main_table.id',
                'log_emails.emails'
            )->joinLeft(
                ['log_sent' => $this->getEmailSentSelect()],
                'log_sent.id = main_table.id',
                'log_sent.sent'
            )->joinLeft(
                ['log_opened' => $this->getEmailOpenedSelect()],
                'log_opened.id = main_table.id',
                'log_opened.opened'
            )->joinLeft(
                ['log_clicks' => $this->getClickCountSelect()],
                'log_clicks.id = main_table.id',
                'log_clicks.clicks'
            )->joinLeft(
                ['log_restore' => $this->countCartRestore()],
                'log_restore.id = main_table.id',
                'log_restore.restore'
            );
        return $result;
    }

    private function getClickCountSelect()
    {
        $logClicks = parent::getSearchResult();
        $logClicks->getSelect()
            ->reset(Select::COLUMNS)
            ->joinLeft(
                ['log_clicks' => $logClicks->getTable('magenest_abacar_log')],
                'log_clicks.rule_id = main_table.id',
                'SUM(log_clicks.clicks) as clicks'
            )->columns('main_table.id')
            ->group('main_table.id');
        return $logClicks->getSelect();
    }

    private function getEmailCountSelect()
    {
        $logEmails = parent::getSearchResult();
        $logEmails->getSelect()
            ->reset(Select::COLUMNS)
            ->joinLeft(
                ['abacar_log' => $logEmails->getTable('magenest_abacar_log')],
                'abacar_log.rule_id = main_table.id',
                'COUNT(abacar_log.id) as emails'
            )->columns('main_table.id')
            ->group('main_table.id');
        return $logEmails->getSelect();
    }

    private function getEmailSentSelect()
    {
        $logSent = parent::getSearchResult();
        $logSent->getSelect()
            ->reset(Select::COLUMNS)
            ->joinLeft(
                ['abacar_log' => $logSent->getTable('magenest_abacar_log')],
                'abacar_log.rule_id = main_table.id AND abacar_log.status=' . Mail::STATUS_SENT,
                'COUNT(abacar_log.id) as sent'
            )->columns('main_table.id')
            ->group('main_table.id');
        return $logSent->getSelect();
    }

    private function getEmailOpenedSelect()
    {
        $logOpened = parent::getSearchResult();
        $logOpened->getSelect()
            ->reset(Select::COLUMNS)
            ->joinLeft(
                ['log_opened' => $logOpened->getTable('magenest_abacar_log')],
                'log_opened.rule_id = main_table.id AND log_opened.opened > 0',
                'COUNT(log_opened.id) as opened'
            )->columns('main_table.id')
            ->group('main_table.id');
        return $logOpened->getSelect();
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $fields = ['id', 'created_at', 'status', 'product_id'];
        if (in_array($filter->getField(), $fields)) {
            $filter->setField("main_table.{$filter->getField()}");
        }
        parent::addFilter($filter); // TODO: Change the autogenerated stub
    }

    private function countCartRestore()
    {
        $logEmails = parent::getSearchResult();
        $logEmails->getSelect()
            ->reset(Select::COLUMNS)
            ->joinLeft(
                ['abacar_log' => $logEmails->getTable('magenest_abacar_log')],
                'abacar_log.rule_id = main_table.id AND abacar_log.is_restore > 0',
                'COUNT(abacar_log.is_restore) as restore'
            )->columns('main_table.id')
            ->group('main_table.id');
        return $logEmails->getSelect();
    }
}
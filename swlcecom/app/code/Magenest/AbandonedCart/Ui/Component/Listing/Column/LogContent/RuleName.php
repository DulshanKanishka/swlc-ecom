<?php

namespace Magenest\AbandonedCart\Ui\Component\Listing\Column\LogContent;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class RuleName extends \Magento\Ui\Component\Listing\Columns\Column
{

    /** @var \Magenest\AbandonedCart\Model\RuleFactory $_ruleFactory */
    protected $_ruleFactory;

    /**
     * RuleName constructor.
     *
     * @param \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->_ruleFactory = $ruleFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $ruleId    = $item['rule_id'];
                $ruleModel = $this->_ruleFactory->create()->load($ruleId);
                if ($ruleModel->getId()) {
                    $item['rule_id'] = $ruleModel->getName();
                }
            }
        }
        return $dataSource;
    }
}
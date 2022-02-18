<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab\TestCampaign\Renderer;

use Magenest\AbandonedCart\Model\LogContentFactory;
use Magenest\AbandonedCart\Model\ResourceModel\LogContent\Collection;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var LogContentFactory $_logContent */
    protected $_logContent;

    /** @var \Magento\Framework\Registry|null $_coreRegistry */
    protected $_coreRegistry = null;

    /**
     * Action constructor.
     *
     * @param LogContentFactory $logContentFactory
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_logContent   = $logContentFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $actions              = [];
        $ruleModel            = $this->_coreRegistry->registry('abandonedcart_rule');
        $contentLogCollection = $this->_logContent->create()->load($row->getData('id'));
        $onclick              = "setLocation('" . $this->getUrl('*/*/sendEmail', ['id' => $row->getId(), 'rule_id' => $ruleModel->getId(), 'log_id' => $contentLogCollection->getId()]) . "')";
        $actions[]            = [
            '@' => [
                'onclick' => $onclick,
                'caption' => __('Send Mail'),
            ],
            '#' => __('Send Mail'),
        ];
        return $this->_actionsToHtml($actions);
    }

    protected function _actionsToHtml(array $actions)
    {
        $html             = [];
        $attributesObject = new \Magento\Framework\DataObject();
        foreach ($actions as $action) {
            $attributesObject->setData($action['@']);
            $html[] = '<a ' . $attributesObject->serialize() . '>' . $action['#'] . '</a>';
        }
        return implode('<span class="separator">&nbsp;<br/>&nbsp;</span>', $html);
    }
}
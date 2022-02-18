<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class TestCampaign extends Generic implements TabInterface
{
    /** @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $_rendererFieldset */
    protected $_rendererFieldset;

    /**
     * TestCampaign constructor.
     *
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->_rendererFieldset = $rendererFieldset;
        $this->_rendererFieldset->setData('testCampaignClass', $this);
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $renderer = $this->_rendererFieldset->setTemplate(
            'Magenest_AbandonedCart::rule/testcampaign.phtml'
        )->setNewChildUrl(
            $this->getUrl('abandonedcart/*/testCampaignGrid', ['_current' => true])
        );
        $form->addFieldset(
            'test_campaign',
            [
                'legend' => ''
            ]
        )->setRenderer(
            $renderer
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Test Campaign');
    }

    public function getTabTitle()
    {
        return __('Test Campaign');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
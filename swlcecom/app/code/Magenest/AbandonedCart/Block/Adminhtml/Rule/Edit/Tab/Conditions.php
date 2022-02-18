<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab;

use Magenest\AbandonedCart\Helper\Data;

class Conditions extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /** @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $_rendererFieldset */
    protected $_rendererFieldset;

    /** @var \Magento\Rule\Block\Conditions $_conditions */
    protected $_conditions;

    /** @var Data $_helperData */
    protected $_helperData;

    /**
     * Conditions constructor.
     *
     * @param Data $helperData
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Helper\Data $helperData,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->_helperData       = $helperData;
        $this->_rendererFieldset = $rendererFieldset;
        $this->_conditions       = $conditions;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _prepareForm()
    {
        $ruleModel     = $this->_coreRegistry->registry('abandonedcart_rule');
        $saleRuleModel = $this->_coreRegistry->registry('current_promo_sale_rule');
        $newChildUrl   = $this->getUrl(
            'sales_rule/promo_quote/newConditionHtml/form/abandonedcart_rule_fieldset'
        );
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $renderer = $this->_rendererFieldset->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $newChildUrl
        )->setFieldSetId(
            'abandonedcart_rule_fieldset'
        );

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            [
                'legend' => __(
                    'Apply the rule only if the following conditions are met (leave blank for all products).'
                ),
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name'  => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
            ]
        )->setRule(
            $saleRuleModel
        )->setRenderer(
            $this->_conditions
        );

        if ($this->getRequest()->getParam('id')) {
            $editData = $ruleModel->getData();
            if ($editData['stores_view']) {
                $editData['stores_view'] = json_decode($editData['stores_view'], true);
            }

            if ($editData['customer_group']) {
                $editData['customer_group'] = json_decode($editData['customer_group'], true);
            }

            if ($editData['conditions_serialized']) {
                $versionMagento = $this->_helperData->getVersionMagento();
                if (version_compare($versionMagento, '2.2.0') < 0) {
                    $editData['conditions_serialized'] = unserialize($editData['conditions_serialized']);
                } else {
                    $editData['conditions_serialized'] = json_decode($editData['conditions_serialized'], true);
                }
            }
            $editData['id'] = $this->getRequest()->getParam('id');
            $form->setValues($editData);
        }
        $this->setForm($form);

        return parent::_prepareForm(); // TODO: Change the autogenerated stub
    }

    public function getTabLabel()
    {
        return __('Conditions');
    }

    public function getTabTitle()
    {
        return __('Conditions');
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
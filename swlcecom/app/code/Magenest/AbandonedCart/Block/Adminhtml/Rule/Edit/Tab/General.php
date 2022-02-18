<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab;

class General extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /** @var \Magenest\AbandonedCart\Model\RuleFactory $_ruleFactory */
    protected $_ruleFactory;

    /** @var \Magento\Store\Model\System\Store $_systemStore */
    protected $_systemStore;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface $groupRepository */
    protected $groupRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder */
    protected $_searchCriteriaBuilder;

    /**
     * General constructor.
     *
     * @param \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->_ruleFactory           = $ruleFactory;
        $this->_systemStore           = $systemStore;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _prepareForm()
    {
        $ruleModel = $this->_coreRegistry->registry('abandonedcart_rule');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');
        $fieldset = $form->addFieldset(
            'setting_fieldset',
            [
                'legend' => __('General Setting'),
                'class'  => 'fieldset-wide'
            ]
        );
        if ($ruleModel->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                [
                    'name'  => 'rule_id',
                    'label' => __('Id'),
                    'title' => __('Id')
                ]
            );
        }
        $fieldset->addField(
            'name',
            'text',
            [
                'name'     => 'name',
                'label'    => __('Rule Name'),
                'title'    => __('Rule Name'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'description',
            'textarea',
            [
                'name'  => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
            ]
        );
        $fieldset->addField(
            'status',
            'select',
            [
                'name'     => 'status',
                'label'    => __('Status'),
                'title'    => __('Status'),
                'values'   => [
                    [
                        'label' => __('Inactive'),
                        'value' => '0'
                    ],
                    [
                        'label' => __('Active'),
                        'value' => '1'
                    ],
                ],
                'required' => true
            ]
        );
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'from_date',
            'date',
            [
                'name'         => 'from_date',
                'label'        => __('From'),
                'title'        => __('From'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format'  => $dateFormat
            ]
        );

        $fieldset->addField(
            'to_date',
            'date',
            [
                'name'         => 'to_date',
                'label'        => __('To'),
                'title'        => __('To'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format'  => $dateFormat,
            ]
        );
        if ($this->_storeManager->isSingleStoreMode()) {
            $storeId = $this->_storeManager->getStore(true)->getStoreId();
            $fieldset->addField('stores_view', 'hidden', ['name' => 'stores_view[]', 'value' => $storeId]);
        } else {
            $field    = $fieldset->addField(
                'stores_view',
                'multiselect',
                [
                    'name'     => 'stores_view[]',
                    'label'    => __('Stores View'),
                    'title'    => __('Stores View'),
                    'values'   => $this->_systemStore->getStoreValuesForForm(false, true),
                    'required' => true,
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $groupOptions  = $objectManager->get('\Magento\Customer\Model\ResourceModel\Group\Collection')->toOptionArray();
        $fieldset->addField(
            'customer_group',
            'multiselect',
            [
                'name'     => 'customer_group[]',
                'label'    => __('Customer Groups'),
                'title'    => __('Customer Groups'),
                'required' => true,
                'values'   => $groupOptions
            ]
        );
        $fieldset->addField(
            'discard_subsequent',
            'select',
            [
                'name'     => 'discard_subsequent',
                'label'    => __('Discard Subsequent Rule'),
                'title'    => __('Discard Subsequent Rule'),
                'values'   => [
                    [
                        'label' => __('No'),
                        'value' => '0'
                    ],
                    [
                        'label' => __('Yes'),
                        'value' => '1'
                    ],
                ],
                'required' => true
            ]
        );
        $fieldset->addField(
            'priority',
            'text',
            [
                'name'     => 'priority',
                'label'    => __('Priority'),
                'title'    => __('Priority'),
                'class'    => 'validate-digits',
                'required' => true
            ]
        );
        $fieldset->addField(
            'cancel_rule_when',
            'multiselect',
            [
                'name'   => 'cancel_rule_when[]',
                'label'  => __('Cancel Condition When'),
                'title'  => __('Cancel Condition When'),
                'values' => [
                    [
                        'label' => __('Select Condition'),
                        'value' => '',
                    ],
                    [
                        'label' => __('Link from Email Clicked'),
                        'value' => '3'
                    ],
                    [
                        'label' => __('Any product went out of stock'),
                        'value' => '1'
                    ],
                    [
                        'label' => __('All products went out of stock'),
                        'value' => '2'
                    ]
                ],
            ]
        );
        if ($this->getRequest()->getParam('id')) {
            $editData = $ruleModel->getData();
            if ($editData['stores_view']) {
                $editData['stores_view'] = json_decode($editData['stores_view'], true);
            }

            if ($editData['customer_group']) {
                $editData['customer_group'] = json_decode($editData['customer_group'], true);
            }
            if ($editData['cancel_rule_when']) {
                $editData['cancel_rule_when'] = json_decode($editData['cancel_rule_when'], true);
            }
            $editData['id'] = $this->getRequest()->getParam('id');
            $form->setValues($editData);
        }
//        $form->setValues($ruleModel->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('General Setting');
    }

    public function getTabTitle()
    {
        return __('General Setting');
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
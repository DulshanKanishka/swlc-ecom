<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{

    /** @var  \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Magenest_AbandonedCart';
        $this->_controller = 'adminhtml_rule';
        parent::_construct();

        /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
        $ruleModel = $this->_coreRegistry->registry('abandonedcart_rule');
        if ($ruleModel->getId()) {
            $this->buttonList->update('save', 'label', __('Save'));
        } else {
            $this->buttonList->remove('save', 'label', __('Save'));
        }

        $backUrl = $this->getUrl('*/*/index');
        $this->buttonList->update('back', 'onclick', "setLocation('{$backUrl}')");
        $this->buttonList->add(
            'save-and-continue',
            [
                'label'          => __('Save and Continue Edit'),
                'class'          => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event'  => 'saveAndContinueEdit',
                            'target' => '#edit_form'
                        ]
                    ]
                ]
            ],
            -100
        );
    }

    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl(
            '*/*/save',
            [
                '_current'   => true,
                'back'       => 'edit',
                'active_tab' => '{{tab_id}}'
            ]
        );
    }
}
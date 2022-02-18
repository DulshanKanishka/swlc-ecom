<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab;

class SMS extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_template = 'rule/sms.phtml';

    /**
     * SMS constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getSmsCollection()
    {
        /** @var  $rule \Magenest\AbandonedCart\Model\Rule $rule */
        $rule    = $this->_coreRegistry->registry('abandonedcart_rule');
        $smsData = $rule->getSMSData();
        return $smsData;
    }

    public function getAvailableCouponRules()
    {
        $saleRuleCollection = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\SalesRule\Model\ResourceModel\Rule\Collection');
        $saleRuleCollection
            ->addFieldToFilter('coupon_type', \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC)
            ->addFieldToFilter('use_auto_generation', 1);
        $data = array();
        foreach ($saleRuleCollection->getItems() as $item) {
            $data[] = [
                'value' => $item->getRuleId(),
                'label' => $item->getName()
            ];
        }
        return $data;
    }

    public function getSMS()
    {
        $ruleModel                                                      = $this->_coreRegistry->registry('abandonedcart_rule');
        $emailChain                                                     = json_decode($ruleModel->getData('sms_chain'), true);
        $this->jsLayout['components']['abandonedcart_sms']['component'] = "Magenest_AbandonedCart/js/rule/smschain";
        $this->jsLayout['components']['abandonedcart_sms']['template']  = "Magenest_AbandonedCart/rule/smsChain";
        if (is_array($emailChain)) {
            $this->jsLayout['components']['abandonedcart_sms']['config']['sms_chain'] = $emailChain;
        } else {
            $this->jsLayout['components']['abandonedcart_sms']['config']['sms_chain'] = null;
        }
        $this->jsLayout['components']['abandonedcart_sms']['config']['promotion_rule'] = $this->getAvailableCouponRules();
        return json_encode($this->jsLayout);
    }

    public function getTabLabel()
    {
        return __('SMS');
    }

    public function getTabTitle()
    {
        return __('SMS');
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
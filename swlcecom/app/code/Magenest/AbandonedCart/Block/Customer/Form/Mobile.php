<?php

namespace Magenest\AbandonedCart\Block\Customer\Form;

use Magenest\AbandonedCart\Helper\Data;

class Mobile extends \Magento\Framework\View\Element\Template
{

    /** @var \Magenest\AbandonedCart\Helper\Data $_helperData */
    protected $_helperData;

    /**
     * Mobile constructor.
     *
     * @param Data $helperData
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Helper\Data $helperData,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->_helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function isMobileRequired()
    {
        return $this->_helperData->getConfig('abandonedcart/nexmo/is_required');
    }

    public function phoneConfig()
    {
        $config = [
            "nationalMode"       => false,
            "utilsScript"        => $this->getViewFileUrl('MaxMage_InternationalTelephoneInput::js/utils.js'),
            "preferredCountries" => []
        ];

        return json_encode($config);
    }
}
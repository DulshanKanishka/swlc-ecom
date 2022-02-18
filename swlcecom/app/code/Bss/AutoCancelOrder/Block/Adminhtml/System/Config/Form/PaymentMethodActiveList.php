<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Block\Adminhtml\System\Config\Form;

class PaymentMethodActiveList extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentModelConfig;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Payment\Model\Config $paymentModelConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Payment\Model\Config $paymentModelConfig,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig  = $context->getScopeConfig();
        $this->paymentModelConfig = $paymentModelConfig;
    }

    /**
     * Return options in array format
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (count($this->getOptions()) == 0) {
            $payments = $this->paymentModelConfig->getActiveMethods();
            $cOptions = [];

            foreach (array_keys($payments) as $paymentCode) {
                $paymentTitle = $this->scopeConfig
                    ->getValue('payment/'.$paymentCode.'/title');
                $cOptions[] = [
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }
            $cOptions[] = [
                'label' => __('All Payment Method'),
                'value' => 'all_payment_method'
            ];
            $this->_options = $cOptions;
        }

        return $this->_options;
    }

    /**
     * Return template html
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->toOptionArray();
        return parent::_toHtml();
    }

    /**
     * Set input name
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}

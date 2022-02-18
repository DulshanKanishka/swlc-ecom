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
namespace Bss\AutoCancelOrder\Model\Config\Backend\Serialized;
 
class PaymentMethodGroupConditionCheck extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    /**
     * Validate group before save
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     * @return \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
     */
    public function save()
    {
        $methods = [];
        $options = $this->getValue();
        if (!empty($options)) {
            foreach ($options as $option) {
                if (is_array($option)) {
                    if (isset($option['payment_method_group_id'])) {
                        $methods[]= $option['payment_method_group_id'];
                    }
                }
            }
        }

        foreach (array_count_values($methods) as $count) {
            if ($count > 1) {
                throw new \Magento\Framework\Exception\LocalizedException(__("Make sure that each payment method is set with only 1 duration for cancelation!"));
            }
        }

        return parent::save();
    }
}

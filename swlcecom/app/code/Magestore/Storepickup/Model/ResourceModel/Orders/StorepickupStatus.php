<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Model\ResourceModel\Orders;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class StorepickupStatus implements \Magento\Framework\Data\OptionSourceInterface, \Magestore\Storepickup\Model\Data\Option\OptionHashInterface
{
    const STOREPICUP_PENDING = 0;
    const STOREPICUP_PREPARE = 1;
    const STOREPICUP_WAIT_FOR_PICKUP = 2;
    const STOREPICUP_RECEIVED = 3;

    /**
     * Return array of options as value-label pairs.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Pending'), 'value' => self::STOREPICUP_PENDING],
            ['label' => __('Prepare'), 'value' => self::STOREPICUP_PREPARE],
            ['label' => __('Wait for Pickup'), 'value' => self::STOREPICUP_WAIT_FOR_PICKUP],
            ['label' => __('Received'), 'value' => self::STOREPICUP_RECEIVED],
        ];
    }

    /**
     * Return array of options as key-value pairs.
     *
     * @return array Format: array('<key>' => '<value>', '<key>' => '<value>', ...)
     */
    public function toOptionHash()
    {
        return [
            self::STOREPICUP_PENDING => __('Pending'),
            self::STOREPICUP_PREPARE => __('Prepare'),
            self::STOREPICUP_WAIT_FOR_PICKUP => __('Wait for Pickup'),
            self::STOREPICUP_RECEIVED => __('Received'),
        ];
    }
}

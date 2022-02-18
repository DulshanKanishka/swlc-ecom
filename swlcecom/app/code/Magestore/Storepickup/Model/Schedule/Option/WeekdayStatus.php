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

namespace Magestore\Storepickup\Model\Schedule\Option;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class WeekdayStatus implements \Magento\Framework\Data\OptionSourceInterface, \Magestore\Storepickup\Model\Data\Option\OptionHashInterface
{
    const WEEKDAY_STATUS_OPEN = 1;
    const WEEKDAY_STATUS_CLOSE = 2;

    /**
     * Return array of options as value-label pairs.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Open'), 'value' => self::WEEKDAY_STATUS_OPEN],
            ['label' => __('Close'), 'value' => self::WEEKDAY_STATUS_CLOSE],
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
            self::WEEKDAY_STATUS_OPEN => __('Open'),
            self::WEEKDAY_STATUS_CLOSE => __('Close'),
        ];
    }
}

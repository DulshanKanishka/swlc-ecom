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

namespace Magestore\Storepickup\Model\Config\Source;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class OrderTypeStore implements \Magento\Framework\Option\ArrayInterface
{
    const SORT_BY_DEFAULT = 0;
    const SORT_BY_DISTANCE = 1;
    const SORT_BY_ALPHABETICAL = 2;

    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SORT_BY_DEFAULT, 'label' => __('Default')],
            ['value' => self::SORT_BY_DISTANCE, 'label' => __('Distance')],
            ['value' => self::SORT_BY_ALPHABETICAL, 'label' => __('Alphabetical order')],
        ];
    }

    /**
     * Get options in "key-value" format.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::SORT_BY_DEFAULT => __('Default'),
            self::SORT_BY_DISTANCE => __('Distance'),
            self::SORT_BY_ALPHABETICAL => __('Alphabetical order'),
        ];
    }
}

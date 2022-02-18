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
class Specificcountry implements \Magento\Framework\Option\ArrayInterface
{
    const Australia = 'au';
    const Brazil = 'br';
    const Canada = 'ca';
    const France = 'fr';
    const Germany = 'de';
    const Mexico = 'mx';
    const New_Zealand = 'nz';
    const Italy = 'it';
    const South_Africa = 'za';
    const South_Spain = 'es';
    const South_Portugal = 'pt';
    const U_S_A = 'us';
    const United_Kingdom = 'uk';

    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::Australia, 'label' => __('Australia')],
            ['value' => self::Brazil, 'label' => __('Brazil')],
            ['value' => self::Canada, 'label' => __('Canada')],
            ['value' => self::France, 'label' => __('France')],
            ['value' => self::Germany, 'label' => __('Germany')],
            ['value' => self::Mexico, 'label' => __('Mexico')],
            ['value' => self::New_Zealand, 'label' => __('New Zealand')],
            ['value' => self::Italy, 'label' => __('Italy')],
            ['value' => self::South_Africa, 'label' => __('South Africa')],
            ['value' => self::South_Spain, 'label' => __('South Spain')],
            ['value' => self::South_Portugal, 'label' => __('South Portugal')],
            ['value' => self::U_S_A, 'label' => __('U S A')],
            ['value' => self::United_Kingdom, 'label' => __('United Kingdom')],
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
            self::SEARCH_CRITERIA_NONE => __('None'),
            self::SEARCH_CRITERIA_STORE_NAME => __('Store Name'),
            self::SEARCH_CRITERIA_COUNTRY => __('Country'),
            self::SEARCH_CRITERIA_STATE => __('State/ Province'),
            self::SEARCH_CRITERIA_CITY => __('City'),
            self::SEARCH_CRITERIA_ZIPCODE => __('Zip Code'),
        ];
    }
}

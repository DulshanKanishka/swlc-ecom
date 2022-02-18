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

namespace Magestore\Storepickup\Model\Store\Option;

/**
 * Country Options for Stores.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Country implements \Magento\Framework\Data\OptionSourceInterface, \Magestore\Storepickup\Model\Data\Option\OptionHashInterface
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * Return array of options as value-label pairs.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $option = [];
        /** @var \Magento\Directory\Model\ResourceModel\Country\Collection $collection */
        $collection = $this->_countryCollectionFactory->create()->loadByStore();

        foreach ($collection as $item) {
            $option[] = ['label' => $item->getName(), 'value' => $item->getId()];
        }

        return $option;
    }

    /**
     * Return array of options as key-value pairs.
     *
     * @return array Format: array('<key>' => '<value>', '<key>' => '<value>', ...)
     */
    public function toOptionHash()
    {
        $option = [];
        /** @var \Magento\Directory\Model\ResourceModel\Country\Collection $collection */
        $collection = $this->_countryCollectionFactory->create()->loadByStore();

        foreach ($collection as $item) {
            $option[$item->getId()] = $item->getName();
        }

        return $option;
    }
}

<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_MassOrderActions
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\MassOrderActions\Model\Config\Source\System;

use Magento\Framework\Option\ArrayInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class Actions
 * @package Mageplaza\MassOrderActions\Model\Config\Source\System
 */
class OrderStatus implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_orderStatusColFact;

    /**
     * OrderStatus constructor.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->_orderStatusColFact = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->_orderStatusColFact->create()->toOptionArray();
        array_unshift($options, [
            'value' => '',
            'label' => __('-- Please Select --'),
        ]);

        return $options;
    }
}

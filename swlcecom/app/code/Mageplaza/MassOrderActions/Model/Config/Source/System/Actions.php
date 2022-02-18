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

/**
 * Class Actions
 * @package Mageplaza\MassOrderActions\Model\Config\Source\System
 */
class Actions implements ArrayInterface
{
    const CREATE_INVOICE            = 1;
    const CREATE_SHIPMENT           = 2;
    const INVOICE_AND_SHIPMENT      = 3;
    const ADD_ORDER_COMMENT         = 4;
    const CHANGE_ORDER_STATUS       = 5;
    const SEND_TRACKING_INFORMATION = 6;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label['label'],
                'type'  => $label['type']
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CREATE_INVOICE => [
                'label' => __('Create Invoice'),
                'type'  => 'mp_create_invoice'
            ],
            self::CREATE_SHIPMENT => [
                'label' => __('Create Shipment'),
                'type'  => 'mp_create_shipment'
            ],
            self::INVOICE_AND_SHIPMENT => [
                'label' => __('Create Invoice and Shipment'),
                'type'  => 'mp_invoice_shipment'
            ],
            self::ADD_ORDER_COMMENT => [
                'label' => __('Add Order Comments'),
                'type'  => 'mp_order_comment'
            ],
            self::CHANGE_ORDER_STATUS => [
                'label' => __('Change Order Status'),
                'type'  => 'mp_status'
            ],
            self::SEND_TRACKING_INFORMATION  => [
                'label' => __('Send Tracking Information'),
                'type'  => 'mp_send_tracking_information'
            ],
        ];
    }
}

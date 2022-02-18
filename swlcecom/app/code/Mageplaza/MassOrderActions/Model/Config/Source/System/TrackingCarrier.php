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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Shipping\Model\Config;

/**
 * Class TrackingCarrier
 * @package Mageplaza\MassOrderActions\Model\Config\Source\System
 */
class TrackingCarrier implements ArrayInterface
{
    /**
     * @var Config
     */
    protected $shipConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * TrackingCarrier constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $shipConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $shipConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->shipConfig  = $shipConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options           = [];
        $options['custom'] = [ 'value' => 'custom', 'label' => __('Custom Value')];
        $carriers          = $this->shipConfig->getAllCarriers();

        foreach ($carriers as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $options[] = [
                    'value' => $code,
                    'label' => $carrier->getConfigData('title')
                ];
            }
        }

        return $options;
    }
}

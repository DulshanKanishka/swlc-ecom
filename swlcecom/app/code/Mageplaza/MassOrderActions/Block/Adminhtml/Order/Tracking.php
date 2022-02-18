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

namespace Mageplaza\MassOrderActions\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentColFactory;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Config;
use Mageplaza\MassOrderActions\Helper\Data;

/**
 * Class Tracking
 *
 * @package Mageplaza\MassOrderActions\Block\Adminhtml\Order
 * @method Tracking setOrderCollection(Collection $collection)
 * @method Tracking setOrderShipments(array $shipments)
 * @method Tracking setIsAllowedAction(bool $isAllowedAction)
 * @method Collection getOrderCollection()
 * @method array getOrderShipments()
 * @method bool getIsAllowedAction()
 */
class Tracking extends Template
{
    /**
     * @var Config
     */
    protected $_shippingConfig;

    /**
     * @var ShipmentColFactory
     */
    protected $_shipmentColFactory;

    /**
     * @var ShipmentLoader
     */
    protected $_shipmentLoader;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Tracking constructor.
     *
     * @param Context $context
     * @param Config $shippingConfig
     * @param ShipmentColFactory $shipmentColFactory
     * @param ShipmentLoader $shipmentLoader
     * @param Registry $coreRegistry
     * @param Data $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $shippingConfig,
        ShipmentColFactory $shipmentColFactory,
        ShipmentLoader $shipmentLoader,
        Registry $coreRegistry,
        Data $helperData,
        array $data = []
    ) {
        $this->_shippingConfig     = $shippingConfig;
        $this->_shipmentColFactory = $shipmentColFactory;
        $this->_shipmentLoader     = $shipmentLoader;
        $this->_coreRegistry       = $coreRegistry;
        $this->helperData          = $helperData;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve carriers
     *
     * @param Shipment $shipment
     *
     * @return array
     */
    public function getCarriers($shipment)
    {
        $carriers           = [];
        $carrierInstances   = $this->_getCarriersInstances($shipment);
        $carriers['custom'] = __('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }

        return $carriers;
    }

    /**
     * Get Shipment collection by Order ID
     *
     * @param int $orderId
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipmentsByOrderId($orderId)
    {
        return $this->_shipmentColFactory->create()->addFieldToFilter('order_id', $orderId);
    }

    /**
     * @param int $shipmentId
     *
     * @return bool|Shipment
     * @throws LocalizedException
     */
    public function loadShipmentById($shipmentId)
    {
        $this->_shipmentLoader->setShipmentId($shipmentId);
        $this->_coreRegistry->unregister('current_shipment');

        return $this->_shipmentLoader->load();
    }

    /**
     * @param array $tracks
     *
     * @return array|string
     */
    public function getCarrier($tracks)
    {
        $trackingTitle = [];
        foreach ($tracks as $track) {
            /** @var Track $track */
            $trackingTitle[] = $track->isCustom() ? __('Custom') : $track->getTitle();
        }
        $trackingTitle = implode(',', $trackingTitle);

        return $trackingTitle;
    }

    /**
     * @param array $tracks
     *
     * @return array|string
     */
    public function getTrackingNumber($tracks)
    {
        $trackingNumber = [];
        foreach ($tracks as $track) {
            /** @var Track $track */
            $trackingNumber[] = $track->getTrackNumber();
        }
        $trackingNumber = implode(',', $trackingNumber);

        return $trackingNumber;
    }

    /**
     * @param Shipment $shipment
     *
     * @return array
     */
    protected function _getCarriersInstances($shipment)
    {
        return $this->_shippingConfig->getAllCarriers($shipment->getStoreId());
    }

    /**
     * @return array|mixed
     */
    public function getTrackingCarrierDefault()
    {
        return $this->helperData->getTrackingCarrierDefault();
    }
}

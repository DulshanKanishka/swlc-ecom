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

namespace Mageplaza\MassOrderActions\Ui\Component\Listing\Column;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackingCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mageplaza\MassOrderActions\Helper\Data;
use Mageplaza\MassOrderActions\Model\Config\Source\System\TrackingCarrier;

/**
 * Class TrackingInformation
 * @package Mageplaza\MassOrderActions\Ui\Component\Listing\Column
 */
class TrackingInformation extends Column
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var CollectionFactory
     */
    protected $trackingCollectionFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var TrackingCarrier
     */
    protected $trackingCarrier;

    /**
     * TrackingInformation constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepository $orderRepository
     * @param CarrierFactory $carrierFactory
     * @param CollectionFactory $trackingCollectionFactory
     * @param UrlInterface $urlBuilder
     * @param Data $helperData
     * @param TrackingCarrier $trackingCarrier
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepository $orderRepository,
        CarrierFactory $carrierFactory,
        CollectionFactory $trackingCollectionFactory,
        UrlInterface $urlBuilder,
        Data $helperData,
        TrackingCarrier $trackingCarrier,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository             = $orderRepository;
        $this->carrierFactory              = $carrierFactory;
        $this->trackingCollectionFactory   = $trackingCollectionFactory;
        $this->urlBuilder                  = $urlBuilder;
        $this->helperData                  = $helperData;
        $this->trackingCarrier             = $trackingCarrier;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id']) && $this->helperData->isEnabled()) {
                    /** @var Order $order */
                    $order               = $this->orderRepository->get($item['entity_id']);
                    $shipmentsCollection = $order->getShipmentsCollection();
                    $tracksCollection    = $this->getTracksCollection($order);
                    if ($shipmentsCollection->getSize() >= 2 && $tracksCollection->getSize()) {
                        $item[$fieldName] = $this->getTrackingInformationHtml($shipmentsCollection);
                    } elseif ($shipmentsCollection->getSize() >= 2 && !$tracksCollection->getSize()) {
                        $item[$fieldName] = __('The order has a shipment, you can go to the order\'s shipment to create a tracking number.');
                    } else {
                        $item[$fieldName] = $this->addTrackingInformation(
                            $shipmentsCollection,
                            $tracksCollection,
                            $item['entity_id']
                        );
                    }
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param string $orderId
     *
     * @return string
     */
    protected function addTrackingInformation($shipmentsCollection, $tracksCollection, $orderId)
    {
        $title           = '';
        $trackingDefault = $this->helperData->getTrackingCarrierDefault();

        $html  = '<form id="add-tracking-information-' . $orderId . '" action="' . $this->getTrackingInformationUrl($orderId). '">';
        $html .= '<table class="data-table admin__control-table" id="mp_shipment_tracking_info">';
        $html .= '<tr class="headings">';
        if ($tracksCollection->getSize()) {
            $html .= '<th class="col-carrier">' . __('Shipment ID') . '</th>';
        }
        $html .= '<th class="col-carrier">' . __('Carrier') . '</th>';
        $html .= '<th class="col-carrier">' . __('Title') . '</th>';
        $html .= '<th class="col-carrier">' . __('Number') . '</th>';
        $html .= '<th class="col-carrier">' . __('Action') . '</th>';
        $html .= '</tr>';
        $html .= '<tbody>';

        foreach ($shipmentsCollection->getItems() as $shipment) {
            $row = $shipment->getTracksCollection()->getSize() + $tracksCollection->getSize();

            foreach ($shipment->getTracksCollection()->getItems() as $track) {
                $html .= '<tr class="old">';
                if ($row === $shipment->getTracksCollection()->getSize() + $tracksCollection->getSize()) {
                    $html .= '<td rowspan="' . $row . '" class="col-shipment-id">' . $shipment->getIncrementId() . '</td>';
                }

                $html .= '<td class="col-carrier">' . $this->getCarrierTitle($track->getCarrierCode()) . '</td>';
                $html .= '<td class="col-title">' . $track->getTitle() . '</td>';
                $html .= '<td class="col-number">' . $track->getTrackNumber() . '</td>';
                $html .= '<td class="col-action"></td>';
                $html .= '</tr>';
                $row--;
            }
        }

        $html .= '<tr>';
        $html .= '<td class="col-carrier">';
        $html .= '<select onchange="mpTrackingInformation.changeCarrier(event)" name="track[' . $orderId . '][carrier_code]" class="select admin__control-select carrier">';
        foreach ($this->trackingCarrier->toOptionArray() as $option) {
            $selected = $option['value'] === $trackingDefault ? 'selected="selected"' : '';
            if (!$title) {
                $title    = $option['value'] === $trackingDefault ? $option['label'] : '';
            }
            $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['label'] . '</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        $html .= '<td class="col-title"><input name="track[' . $orderId . '][title]" type="text" value="' . $title . '" class="input-text admin__control-text number-title"></td>';
        $html .= '<td class="col-number"><input name="track[' . $orderId . '][number]" type="text" class="input-text admin__control-text required-entry"></td>';
        $html .= '<td class="col-add"><button onclick="mpTrackingInformation.addTrackingInformation(event)" id="add-tracking" type="button" title="' . __('Add') . '" class="action-default scalable save"><span>' . __('Add'). '</span></button></td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<input type="hidden" name="orderId" value="' . $orderId . '">';
        $html .= "<input type='hidden' name='mp-tracking-carrier-" . $orderId . "' value='" .
            Data::jsonEncode($this->trackingCarrier->toOptionArray()) . "'>";
        $html .= '</form>';

        return $html;
    }

    /**
     * @param ShipmentCollection $shipmentsCollection
     *
     * @return string
     */
    protected function getTrackingInformationHtml($shipmentsCollection)
    {
        $html  = '<table class="data-table admin__control-table" id="mp_shipment_tracking_info">';
        $html .= '<tr class="headings">';
        $html .= '<th class="col-carrier">' . __('Shipment ID') . '</th>';
        $html .= '<th class="col-carrier">' . __('Carrier') . '</th>';
        $html .= '<th class="col-carrier">' . __('Title') . '</th>';
        $html .= '<th class="col-carrier">' . __('Number') . '</th>';
        $html .= '</tr>';
        $html .= '<tbody>';

        foreach ($shipmentsCollection->getItems() as $shipment) {
            $row = $shipment->getTracksCollection()->getSize();

            foreach ($shipment->getTracksCollection()->getItems() as $track) {
                $html .= '<tr class="old">';
                if ($row === $shipment->getTracksCollection()->getSize()) {
                    $html .= '<td rowspan="' . $row . '" class="col-shipment-id">' . $shipment->getIncrementId() . '</td>';
                }

                $html .= '<td class="col-carrier">' . $this->getCarrierTitle($track->getCarrierCode()) . '</td>';
                $html .= '<td class="col-title">' . $track->getTitle() . '</td>';
                $html .= '<td class="col-number">' . $track->getTrackNumber() . '</td>';
                $html .= '</tr>';
                $row--;
            }
        }

        $html .= '</tbody>';
        $html .= '</<table>';

        return $html;
    }

    /**
     * @param int $orderId
     *
     * @return string
     */
    public function getTrackingInformationUrl($orderId)
    {
        return $this->urlBuilder->getUrl(
            'mpmassorderactions/order_shipment/addTrack/',
            ['order_id' => $orderId]
        );
    }

    /**
     * Get carrier title
     *
     * @param string $code
     *
     * @return Phrase|string|bool
     */
    public function getCarrierTitle($code)
    {
        $carrier = $this->carrierFactory->create($code);

        return $carrier ? $carrier->getConfigData('title') : __('Custom Value');
    }

    /**
     * @param Order $order
     * @return TrackingCollection
     */
    protected function getTracksCollection($order)
    {
        $collection = $this->trackingCollectionFactory->create()
            ->setOrderFilter($order)->setOrder('parent_id', 'ASC');

        return $collection->load();
    }
}

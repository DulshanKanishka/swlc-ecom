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

namespace Mageplaza\MassOrderActions\Controller\Adminhtml\Order\Shipment;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Layout;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order as ModelOrder;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\OrderRepository;
use Mageplaza\MassOrderActions\Block\Adminhtml\Result;

/**
 * Class AddTrack
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order\Shipment
 */
class AddTrack extends Action
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Json
     */
    protected $_resultJson;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * @var Order
     */
    protected $convertOrder;

    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var ShipmentSender
     */
    protected $shipmentSender;

    /**
     * AddTrack constructor.
     *
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param Order $convertOrder
     * @param TrackFactory $trackFactory
     * @param ShipmentSender $shipmentSender
     * @param Json $resultJson
     * @param Layout $layout
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        Order $convertOrder,
        TrackFactory $trackFactory,
        ShipmentSender $shipmentSender,
        Json $resultJson,
        Layout $layout
    ) {
        $this->orderRepository = $orderRepository;
        $this->convertOrder    = $convertOrder;
        $this->_resultJson     = $resultJson;
        $this->layout          = $layout;
        $this->trackFactory    = $trackFactory;
        $this->shipmentSender  = $shipmentSender;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        /** @var Result $resultBlock */
        $resultBlock = $this->layout
            ->createBlock(Result::class)
            ->setTemplate('Mageplaza_MassOrderActions::result.phtml');

        $orderId     = $this->getRequest()->getParam('order_id');
        $trackNumber = $this->getRequest()->getParam('track');
        if ($orderId && $trackNumber) {
            try {
                /** @var  ModelOrder $order */
                $order               = $this->orderRepository->get($orderId);
                $shipmentsCollection = $order->getShipmentsCollection();

                if ($shipmentsCollection->getSize() === 1) {
                    /** @var Shipment $shipment */
                    foreach ($shipmentsCollection->getItems() as $shipment) {
                        $this->updateTrackingInformation($order, $shipment, $trackNumber);
                        $resultBlock->addSuccess(__('The tracking number of the shipment has been updated.'));
                    }
                } elseif ($order->canShip() && !$order->getForcedShipmentWithInvoice()) {
                    $this->createShipment($order, $trackNumber);
                    $resultBlock->addSuccess(__('The order has been created shipment.'));
                }
            } catch (Exception $e) {
                $resultBlock->addError($e->getMessage());
            }
        }

        return $this->_resultJson->setData($this->_addAjaxResult($resultBlock));
    }

    /**
     * @param Result $resultBlock
     *
     * @return array
     */
    protected function _addAjaxResult($resultBlock)
    {
        return [
            'status'      => true,
            'result_html' => $resultBlock->toHtml()
        ];
    }

    /**
     * @param ModelOrder $order
     * @param array $trackNumber
     *
     * @throws LocalizedException
     */
    protected function createShipment($order, $trackNumber)
    {
        $shipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $qtyShipped   = $orderItem->getQtyToShip();
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setCustomerNoteNotify(true);
        $shipment->getOrder()->setIsInProcess(true);
        $this->updateTrackingInformation($order, $shipment, $trackNumber);
        $this->shipmentSender->send($shipment);
    }

    /**
     * @param ModelOrder $order
     * @param Shipment $shipment
     * @param array $trackNumber
     */
    protected function updateTrackingInformation($order, $shipment, $trackNumber)
    {
        $track = $this->trackFactory->create()->addData($trackNumber[$order->getId()]);
        $shipment->addTrack($track)->save();
        $shipment->getOrder()->save();
    }
}

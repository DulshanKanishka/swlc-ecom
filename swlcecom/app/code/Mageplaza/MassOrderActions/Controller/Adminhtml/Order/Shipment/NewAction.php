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
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Mageplaza\MassOrderActions\Block\Adminhtml\Order\Tracking;
use Mageplaza\MassOrderActions\Controller\Adminhtml\Order\AbstractMassAction;

/**
 * Class NewAction
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order\Shipment
 */
class NewAction extends AbstractMassAction
{
    /**
     * @return ResponseInterface|ResultInterface|mixed
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        if (!$request->isAjax()) {
            return $this->_resultFwFactory->create()->forward('noroute');
        }
        if (!$request->isPost()) {
            throw new NotFoundException(__('Page not found.'));
        }

        return parent::execute();
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return $this|mixed
     */
    protected function massAction($collection)
    {
        $shipments = [];
        foreach ($collection as $order) {
            try {
                /** @var Order $order */
                if ($order->canShip() && !$order->getForcedShipmentWithInvoice()) {
                    $this->_shipmentLoader->setOrderId($order->getId());
                    $shipment = $this->_shipmentLoader->load();
                    $this->_coreRegistry->unregister('current_shipment');
                    $shipments[$order->getId()] = $shipment;
                }
            } catch (Exception $e) {
                $result = [
                    'status' => false,
                    'tracking_html' => $e->getMessage()
                ];

                return $this->_resultJson->setData($result);
            }
        }

        $trackingBlock = $this->_layout
            ->createBlock(Tracking::class)
            ->setOrderCollection($collection)
            ->setOrderShipments($shipments)
            ->setTemplate('Mageplaza_MassOrderActions::order/tracking.phtml');

        $result = [
            'status' => true,
            'tracking_html' => $trackingBlock->toHtml()
        ];

        return $this->_resultJson->setData($result);
    }
}

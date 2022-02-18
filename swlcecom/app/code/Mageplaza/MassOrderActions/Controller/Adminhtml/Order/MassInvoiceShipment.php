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

namespace Mageplaza\MassOrderActions\Controller\Adminhtml\Order;

use Exception;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Mageplaza\MassOrderActions\Block\Adminhtml\Result;

/**
 * Class MassInvoiceShipment
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order
 */
class MassInvoiceShipment extends AbstractMassAction
{
    /**
     * @param AbstractCollection $collection
     *
     * @return $this|mixed
     * @throws Exception
     */
    protected function massAction($collection)
    {
        $params = $this->getRequest()->getParams();
        $dataInvoice = $params['invoice'];
        $dataShipment = $params['shipment'];
        $trackingNumbers = $this->getRequest()->getParam('tracking');
        $dataInvoice['status'] = $params['status'];
        $dataShipment['status'] = $params['status'];
        /** @var Result $resultBlock */
        $resultBlock = $this->_layout
            ->createBlock(Result::class)
            ->setTemplate('Mageplaza_MassOrderActions::result.phtml');

        foreach ($collection->getItems() as $order) {
            /** @var Order $order */
            if ($this->getRequest()->getParam('order_id') !== null) {
                $_params = $this->_request->getParams();
                unset($_params['order_id']);
            }
            $this->getRequest()->setParam('order_id', $order->getId());
            $isInvoiced = false;
            if ($order->canInvoice()) {
                $this->_massInvoiceAction($order, $dataInvoice, $resultBlock);
                $isInvoiced = true;
            } else {
                $this->_orderNonInvoiced++;
            }

            if ($order->canShip()
                && !$order->getForcedShipmentWithInvoice()) {
                $this->_massShipmentAction($order, $dataShipment, $trackingNumbers, $resultBlock, $isInvoiced);
            } else {
                $this->_orderNonShipment++;
            }
        }

        /** Print invoice + shipment as pdf */
        if (isset($params['is_print']) && $params['is_print']) {
            return $this->_printInvoiceShipment($collection, $params['print_type']);
        }

        return $this->_resultJson->setData($this->_addAjaxResult($resultBlock));
    }
}

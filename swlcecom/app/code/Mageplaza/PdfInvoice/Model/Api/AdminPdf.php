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
 * @package     Mageplaza_PdfInvoice
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PdfInvoice\Model\Api;

use Magento\Sales\Model\Order;
use Mageplaza\PdfInvoice\Api\AdminPdfInterface;
use Mageplaza\PdfInvoice\Model\Source\Type;

/**
 * Class PdfDocument
 * @package Mageplaza\PdfInvoice\Model\Api
 */
class AdminPdf extends AbstractDocument implements AdminPdfInterface
{
    /**
     * @inheritDoc
     */
    public function getPdfOrder($id)
    {
        $order = $this->getOrderById($id);

        return $this->setType(Type::ORDER)->streamPdfDocument($order->getEntityId());
    }

    /**
     * @inheritDoc
     */
    public function getPdfInvoicesByOrder($id)
    {
        /** @var Order $order */
        $order = $this->getOrderById($id);

        return $this->setType(Type::INVOICE)->streamPdfDocument($this->getInvoicesByOrder($order));
    }

    /**
     * @inheritDoc
     */
    public function getPdfShipmentsByOrder($id)
    {
        /** @var Order $order */
        $order = $this->getOrderById($id);

        return $this->setType(Type::SHIPMENT)->streamPdfDocument($this->getShipmentsByOrder($order));
    }

    /**
     * @inheritDoc
     */
    public function getPdfCreditmemosByOrder($id)
    {
        /** @var Order $order */
        $order = $this->getOrderById($id);

        return $this->setType(Type::CREDIT_MEMO)->streamPdfDocument($this->getCreditmemosByOrder($order));
    }

    /**
     * @inheritDoc
     */
    public function getPdfInvoice($id)
    {
        $invoice = $this->getInvoiceById($id);

        return $this->setType(Type::INVOICE)->streamPdfDocument($invoice->getEntityId());
    }

    /**
     * @inheritDoc
     */
    public function getPdfShipment($id)
    {
        $shipment = $this->getShipmentById($id);

        return $this->setType(Type::SHIPMENT)->streamPdfDocument($shipment->getEntityId());
    }

    /**
     * @inheritDoc
     */
    public function getPdfCreditmemo($id)
    {
        $creditmemo = $this->getCreditmemoById($id);

        return $this->setType(Type::CREDIT_MEMO)->streamPdfDocument($creditmemo->getEntityId());
    }
}

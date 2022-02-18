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

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mageplaza\PdfInvoice\Api\AdminPdfInterface;
use Mageplaza\PdfInvoice\Api\CustomerPdfInterface;
use Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface;
use Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterfaceFactory;
use Mageplaza\PdfInvoice\Api\PdfDocumentInterfaceFactory;
use Mageplaza\PdfInvoice\Helper\Data as HelperData;
use Mageplaza\PdfInvoice\Helper\PrintProcess;
use Mageplaza\PdfInvoice\Model\Source\Type;

/**
 * Class CustomerPdfDocument
 * @package Mageplaza\PdfInvoice\Model\Api
 */
class CustomerPdf extends AbstractDocument implements CustomerPdfInterface
{
    /**
     * @var AdminPdfInterface
     */
    protected $_adminPdf;

    /**
     * CustomerPdf constructor.
     *
     * @param PrintProcess $printProcess
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param ErrorResponseInterfaceFactory $errorResponse
     * @param OrderCollectionFactory $orderCollection
     * @param HelperData $helperData
     * @param AdminPdfInterface $adminPdf
     */
    public function __construct(
        PrintProcess $printProcess,
        OrderRepositoryInterface $orderRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ErrorResponseInterfaceFactory $errorResponse,
        OrderCollectionFactory $orderCollection,
        HelperData $helperData,
        AdminPdfInterface $adminPdf
    ) {
        $this->_adminPdf   = $adminPdf;
        $this->_helperData = $helperData;

        parent::__construct(
            $printProcess,
            $orderRepository,
            $invoiceRepository,
            $shipmentRepository,
            $creditmemoRepository,
            $errorResponse,
            $orderCollection,
            $helperData
        );
    }

    /**
     * @inheritDoc
     */
    public function getPdfOrder($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::ORDER)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfOrder($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfInvoicesByOrder($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::ORDER)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfInvoicesByOrder($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfShipmentsByOrder($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::ORDER)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfShipmentsByOrder($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfCreditmemosByOrder($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::ORDER)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfCreditmemosByOrder($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfInvoice($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::INVOICE)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfInvoice($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfShipment($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::SHIPMENT)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfShipment($id);
    }

    /**
     * @inheritDoc
     */
    public function getPdfCreditmemo($id, $customerId)
    {
        $validateCustomer = $this->setType(Type::CREDIT_MEMO)->validateCustomer($id, $customerId);
        if ($validateCustomer instanceof ErrorResponseInterface) {
            return $validateCustomer;
        }

        return $this->_adminPdf->getPdfCreditmemo($id);
    }
}

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

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface;
use Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterfaceFactory;
use Mageplaza\PdfInvoice\Helper\Data as HelperData;
use Mageplaza\PdfInvoice\Helper\PrintProcess;
use Mageplaza\PdfInvoice\Model\Source\Type;

/**
 * Class AbstractDocument
 * @package Mageplaza\PdfInvoice\Model\Api
 */
abstract class AbstractDocument
{
    /**
     * @var string
     */
    protected $_type;

    /**
     * @var PrintProcess
     */
    protected $_printProcess;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $_invoiceRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $_creditmemoRepository;

    /**
     * @var ErrorResponseInterfaceFactory
     */
    protected $_errorResponse;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollection;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * AbstractDocument constructor.
     *
     * @param PrintProcess $printProcess
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param ErrorResponseInterfaceFactory $errorResponse
     * @param OrderCollectionFactory $orderCollection
     * @param HelperData $helperData
     */
    public function __construct(
        PrintProcess $printProcess,
        OrderRepositoryInterface $orderRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ErrorResponseInterfaceFactory $errorResponse,
        OrderCollectionFactory $orderCollection,
        HelperData $helperData
    ) {
        $this->_printProcess         = $printProcess;
        $this->_orderRepository      = $orderRepository;
        $this->_invoiceRepository    = $invoiceRepository;
        $this->_shipmentRepository   = $shipmentRepository;
        $this->_creditmemoRepository = $creditmemoRepository;
        $this->_errorResponse        = $errorResponse;
        $this->_orderCollection      = $orderCollection;
        $this->_helperData           = $helperData;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return OrderInterface
     */
    public function getOrderById($id)
    {
        return $this->_orderRepository->get($id);
    }

    /**
     * @param string $id
     *
     * @return InvoiceInterface
     */
    public function getInvoiceById($id)
    {
        return $this->_invoiceRepository->get($id);
    }

    /**
     * @param string $id
     *
     * @return ShipmentInterface
     */
    public function getShipmentById($id)
    {
        return $this->_shipmentRepository->get($id);
    }

    /**
     * @param string $id
     *
     * @return CreditmemoInterface
     */
    public function getCreditmemoById($id)
    {
        return $this->_creditmemoRepository->get($id);
    }

    /**
     * @return ErrorResponseInterface
     */
    public function getErrorResponse()
    {
        return $this->_errorResponse->create();
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getInvoicesByOrder($order)
    {
        $invoiceIds = [];
        $storeId    = $order->getStoreId();
        if ($order->hasInvoices()) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceIds[$storeId][] = $invoice->getId();
            }
        }

        return $invoiceIds;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getShipmentsByOrder($order)
    {
        $shipmentIds = [];
        $storeId     = $order->getStoreId();
        if ($order->hasShipments()) {
            foreach ($order->getShipmentsCollection() as $shipment) {
                $shipmentIds[$storeId][] = $shipment->getId();
            }
        }

        return $shipmentIds;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getCreditmemosByOrder($order)
    {
        $creditmemoIds = [];
        $storeId       = $order->getStoreId();
        if ($order->hasCreditmemos()) {
            foreach ($order->getCreditmemosCollection() as $credit) {
                $creditmemoIds[$storeId][] = $credit->getId();
            }
        }

        return $creditmemoIds;
    }

    /**
     * @param string|array $id
     *
     * @return ErrorResponseInterface|string|void
     */
    public function streamPdfDocument($id)
    {
        $errorResponse = $this->getErrorResponse();
        if (!$this->_helperData->isEnabled()) {
            return $errorResponse->setMessage('Mageplaza Pdf Invoice module has been disabled.');
        }

        if (is_array($id) && count($id) === 0) {
            return $errorResponse->setMessage('There are no printable documents related to selected order.');
        }

        try {
            return is_array($id)
                ? $this->_printProcess->printAllPdf($this->getType(), $id)
                : $this->_printProcess->printPdf($this->getType(), $id);
        } catch (Exception $e) {
            return $errorResponse->setMessage($e->getMessage());
        }
    }

    /**
     * @param string $id
     * @param string $customerId
     *
     * @return bool|ErrorResponseInterface
     */
    public function validateCustomer($id, $customerId)
    {
        $errorResponse   = $this->getErrorResponse();
        $orderCollection = $this->_orderCollection->create();
        $orderId         = $this->getCustomerOrderId($id, $this->getType());
        $customerOrderId = $orderCollection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('entity_id', $orderId)
            ->getFirstItem()
            ->getId();

        try {
            if (!$orderId) {
                throw new NoSuchEntityException(__('The document order id doesn\'t exist.'));
            }
            if (!$customerOrderId) {
                throw new NoSuchEntityException(__('The customer isn\'t authorized to access this document.'));
            }

            return true;
        } catch (Exception $e) {
            return $errorResponse->setMessage($e->getMessage());
        }
    }

    /**
     * @param string $id
     * @param string $type
     *
     * @return int
     */
    public function getCustomerOrderId($id, $type)
    {
        switch ($type) {
            case Type::ORDER:
                $orderId = (int) $id;
                break;
            case Type::INVOICE:
                $orderId = $this->getInvoiceById($id)->getOrderId();
                break;
            case Type::SHIPMENT:
                $orderId = $this->getShipmentById($id)->getOrderId();
                break;
            default:
                $orderId = $this->getCreditmemoById($id)->getOrderId();
                break;
        }

        return $orderId;
    }
}

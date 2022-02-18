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

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Layout;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Pdf\Invoice as PdfInvoice;
use Magento\Sales\Model\Order\Pdf\Shipment as PdfShipment;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceColFact;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentColFact;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Ui\Component\MassAction\Filter;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;

/**
 * Class MassSendTracking
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order
 */
class MassSendTracking extends AbstractMassAction
{
    /**
     * @var ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * MassSendTracking constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param ShipmentResource $shipmentResource
     * @param OrderResource $orderResource
     * @param HistoryResource $historyResource
     * @param CollectionFactory $collectionFactory
     * @param InvoiceColFact $invoiceColFact
     * @param ShipmentColFact $shipmentColFact
     * @param Registry $coreRegistry
     * @param Layout $layout
     * @param Json $resultJson
     * @param ForwardFactory $resultForwardFactory
     * @param FileFactory $fileFactory
     * @param DateTime $dateTime
     * @param OrderCommentSender $orderCommentSender
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param PdfInvoice $pdfInvoice
     * @param PdfShipment $pdfShipment
     * @param ShipmentLoader $shipmentLoader
     * @param ShipmentSender $shipmentSender
     * @param LabelGenerator $labelGenerator
     * @param Transaction $transaction
     * @param HelperData $helperData
     * @param Order $orderModel
     * @param ShipmentNotifier $shipmentNotifier
     */
    public function __construct(
        Context $context,
        Filter $filter,
        ShipmentResource $shipmentResource,
        OrderResource $orderResource,
        HistoryResource $historyResource,
        CollectionFactory $collectionFactory,
        InvoiceColFact $invoiceColFact,
        ShipmentColFact $shipmentColFact,
        Registry $coreRegistry,
        Layout $layout,
        Json $resultJson,
        ForwardFactory $resultForwardFactory,
        FileFactory $fileFactory,
        DateTime $dateTime,
        OrderCommentSender $orderCommentSender,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        PdfInvoice $pdfInvoice,
        PdfShipment $pdfShipment,
        ShipmentLoader $shipmentLoader,
        ShipmentSender $shipmentSender,
        LabelGenerator $labelGenerator,
        Transaction $transaction,
        HelperData $helperData,
        Order $orderModel,
        ShipmentNotifier $shipmentNotifier
    ) {
        $this->shipmentNotifier = $shipmentNotifier;

        parent::__construct(
            $context,
            $filter,
            $shipmentResource,
            $orderResource,
            $historyResource,
            $collectionFactory,
            $invoiceColFact,
            $shipmentColFact,
            $coreRegistry,
            $layout,
            $resultJson,
            $resultForwardFactory,
            $fileFactory,
            $dateTime,
            $orderCommentSender,
            $invoiceService,
            $invoiceSender,
            $pdfInvoice,
            $pdfShipment,
            $shipmentLoader,
            $shipmentSender,
            $labelGenerator,
            $transaction,
            $helperData,
            $orderModel
        );
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return Redirect|mixed
     * @throws MailException
     */
    protected function massAction($collection)
    {
        $count = 0;

        /** @var Order $order */
        foreach ($collection->getItems() as $order) {
            /** @var Shipment $shipment */
            foreach ($order->getShipmentsCollection()->getItems() as $shipment) {
                $this->shipmentNotifier->notify($shipment);
            }
            if ($order->getShipmentsCollection()->getSize()) {
                $count++;
            }
        }
        $countNon = $collection->count() - $count;

        if ($countNon && $count) {
            $this->messageManager->addErrorMessage(__('The tracking information of %1 order(s) cannot be sent.', $countNon));
        } elseif ($countNon) {
            $this->messageManager->addErrorMessage(__('You cannot send tracking information.'));
        }

        if ($count) {
            $this->messageManager->addSuccessMessage(__('The tracking information of %1 order(s) have been sent successfully.', $count));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/*/');

        return $resultRedirect;
    }
}

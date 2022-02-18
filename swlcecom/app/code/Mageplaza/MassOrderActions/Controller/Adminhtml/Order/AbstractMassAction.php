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
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
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
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceColFact;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentColFact;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Ui\Component\MassAction\Filter;
use Mageplaza\MassOrderActions\Block\Adminhtml\Result;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use Mageplaza\PdfInvoice\Helper\PrintProcess;
use Mpdf\MpdfException;
use Zend_Pdf_Exception;

/**
 * Class AbstractMassAction
 *
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order
 */
abstract class AbstractMassAction extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Sales::actions_edit';

    /**
     * @var Filter
     */
    protected $_filter;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var HistoryResource
     */
    protected $_historyResource;

    /**
     * @var ShipmentResource
     */
    protected $_shipmentResource;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var InvoiceColFact
     */
    protected $_invoiceColFact;

    /**
     * @var ShipmentColFact
     */
    protected $_shipmentColFact;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Layout
     */
    protected $_layout;

    /**
     * @var Json
     */
    protected $_resultJson;

    /**
     * @var ForwardFactory
     */
    protected $_resultFwFactory;

    /**
     * @var FileFactory
     */
    protected $_fileFactory;

    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var OrderCommentSender
     */
    protected $_orderCommentSender;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var PdfInvoice
     */
    protected $_pdfInvoice;

    /**
     * @var PdfShipment
     */
    protected $_pdfShipment;

    /**
     * @var ShipmentLoader
     */
    protected $_shipmentLoader;

    /**
     * @var ShipmentSender
     */
    protected $_shipmentSender;

    /**
     * @var LabelGenerator
     */
    protected $_labelGenerator;

    /**
     * @var Transaction
     */
    protected $_transaction;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Order
     */
    protected $_orderModel;

    /**
     * @var int
     */
    protected $_orderInvoiced = 0;

    /**
     * @var int
     */
    protected $_orderNonInvoiced = 0;

    /**
     * @var int
     */
    protected $_orderShipment = 0;

    /**
     * @var int
     */
    protected $_orderNonShipment = 0;

    /**
     * @var int
     */
    protected $_orderComment = 0;

    /**
     * @var int
     */
    protected $_orderNonComment = 0;

    /**
     * @var int
     */
    protected $_statusUpdated = 0;

    /**
     * @var int
     */
    protected $_statusNonUpdated = 0;

    /**
     * AbstractMassAction constructor.
     *
     * @param Context            $context
     * @param Filter             $filter
     * @param ShipmentResource   $shipmentResource
     * @param OrderResource      $orderResource
     * @param HistoryResource    $historyResource
     * @param CollectionFactory  $collectionFactory
     * @param InvoiceColFact     $invoiceColFact
     * @param ShipmentColFact    $shipmentColFact
     * @param Registry           $coreRegistry
     * @param Layout             $layout
     * @param Json               $resultJson
     * @param ForwardFactory     $resultForwardFactory
     * @param FileFactory        $fileFactory
     * @param DateTime           $dateTime
     * @param OrderCommentSender $orderCommentSender
     * @param InvoiceService     $invoiceService
     * @param InvoiceSender      $invoiceSender
     * @param PdfInvoice         $pdfInvoice
     * @param PdfShipment        $pdfShipment
     * @param ShipmentLoader     $shipmentLoader
     * @param ShipmentSender     $shipmentSender
     * @param LabelGenerator     $labelGenerator
     * @param Transaction        $transaction
     * @param HelperData         $helperData
     * @param Order              $orderModel
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
        Order $orderModel
    ) {
        $this->_filter             = $filter;
        $this->_shipmentResource   = $shipmentResource;
        $this->_orderResource      = $orderResource;
        $this->_historyResource    = $historyResource;
        $this->_collectionFactory  = $collectionFactory;
        $this->_invoiceColFact     = $invoiceColFact;
        $this->_shipmentColFact    = $shipmentColFact;
        $this->_coreRegistry       = $coreRegistry;
        $this->_layout             = $layout;
        $this->_resultJson         = $resultJson;
        $this->_resultFwFactory    = $resultForwardFactory;
        $this->_fileFactory        = $fileFactory;
        $this->_dateTime           = $dateTime;
        $this->_orderCommentSender = $orderCommentSender;
        $this->_invoiceService     = $invoiceService;
        $this->_invoiceSender      = $invoiceSender;
        $this->_pdfInvoice         = $pdfInvoice;
        $this->_pdfShipment        = $pdfShipment;
        $this->_shipmentLoader     = $shipmentLoader;
        $this->_shipmentSender     = $shipmentSender;
        $this->_labelGenerator     = $labelGenerator;
        $this->_transaction        = $transaction;
        $this->_helperData         = $helperData;
        $this->_orderModel         = $orderModel;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|mixed
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        if (empty($collection->getItems())) {
            /** @var Result $resultBlock */
            $resultBlock = $this->_layout
                ->createBlock(Result::class)
                ->setTemplate('Mageplaza_MassOrderActions::result.phtml');
            $resultBlock->addError('The selected order(s) does not exist.');
            $result = [
                'status'      => true,
                'result_html' => $resultBlock->toHtml()
            ];

            return $this->_resultJson->setData($result);
        }

        return $this->massAction($collection);
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return mixed
     */
    abstract protected function massAction($collection);

    /**
     * Mass order invoice action
     *
     * @param Order  $order
     * @param array  $data
     * @param Result $resultBlock
     */
    protected function _massInvoiceAction($order, $data, $resultBlock)
    {
        try {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            if (!empty($data['comment_text'])) {
                $invoice->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );

                $invoice->setCustomerNote($data['comment_text']);
                $invoice->setCustomerNoteNotify(isset($data['comment_customer_notify']));
            }

            $invoice->register();

            $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->_transaction
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionSave->save();
            if ($data['status']) {
                $orderStatus = $this->_getOrderStatus($order, $data['status']);
                if ($orderStatus && $orderStatus !== $order->getDataByKey('status')) {
                    $order->setStatus($orderStatus);
                    $this->_orderResource->save($order);
                } else {
                    $this->_statusNonUpdated++;
                }
            }

            $this->_orderInvoiced++;
            /** Send invoice emails */
            try {
                if (!empty($data['send_email'])) {
                    $this->_invoiceSender->send($invoice);
                }
            } catch (Exception $e) {
                $resultBlock->addError(__('We can\'t send the invoice email right now.'));
            }
        } catch (Exception $e) {
            $this->_orderNonInvoiced++;
            $resultBlock->addError($e->getMessage());
        }
    }

    /**
     * @param Order  $order
     * @param array  $data
     * @param array  $trackingNumbers
     * @param Result $resultBlock
     * @param bool   $isInvoiced
     */
    protected function _massShipmentAction($order, $data, $trackingNumbers, $resultBlock, $isInvoiced = false)
    {
        try {
            $trackingNumber = isset($trackingNumbers[$order->getId()]) ? $trackingNumbers[$order->getId()] : null;
            $this->_shipmentLoader->setOrderId($order->getId());
            $this->_shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->_shipmentLoader->setShipment($data);
            $this->_shipmentLoader->setTracking($trackingNumber);
            $shipment = $this->_shipmentLoader->load();
            $this->_coreRegistry->unregister('current_shipment');

            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );

                $shipment->setCustomerNote($data['comment_text']);
                $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
            }
            $shipment->register();

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

            $this->_saveShipment($shipment);

            /** Send shipment emails */
            try {
                if (!empty($data['send_email'])) {
                    $this->_shipmentSender->send($shipment);
                }
            } catch (Exception $e) {
                $resultBlock->addError(__('We can\'t send the shipment email right now.'));
            }

            if ($data['status']) {
                $orderStatus = $this->_getOrderStatus($shipment->getOrder(), $data['status']);

                if ($orderStatus && $orderStatus !== $shipment->getOrder()->getDataByKey('status')) {
                    $shipment->getOrder()->setStatus($orderStatus);
                    $this->_orderResource->save($shipment->getOrder());
                    if ($isInvoiced) {
                        $this->_statusNonUpdated--;
                    }
                } elseif (!$isInvoiced) {
                    $this->_statusNonUpdated++;
                }
            }

            $this->_orderShipment++;
        } catch (Exception $e) {
            $this->_orderNonShipment++;
            $resultBlock->addError($e->getMessage());
        }
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Shipment $shipment
     *
     * @return $this
     * @throws Exception
     */
    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $this->_transaction->addObject(
            $shipment
        )->addObject(
            $shipment->getOrder()
        )->save();

        return $this;
    }

    /**
     * @param Order       $order
     * @param string|bool $status
     *
     * @return string
     */
    protected function _getOrderStatus($order, $status)
    {
        $isDifferentState = $this->_helperData->getConfigGeneral('different_state');
        if ($isDifferentState && !in_array($status, $this->_helperData->getStatusByState($order->getState()), true)) {
            $status = false;
        }

        return $status;
    }

    /**
     * Add ajax result
     *
     * @param Result $resultBlock
     *
     * @return array
     */
    protected function _addAjaxResult($resultBlock)
    {
        if ($this->_orderShipment) {
            $resultBlock->addSuccess(__('A total of %1 order(s) have been created shipment.', $this->_orderShipment));
        }
        if ($this->_orderNonShipment) {
            $resultBlock->addError(__(
                'A total of %1 order(s) does not allow an shipment to be created.',
                $this->_orderNonShipment
            ));
        }
        if ($this->_orderComment) {
            $resultBlock->addSuccess(__('A total of %1 order(s) have been updated.', $this->_orderComment));
        }
        if ($this->_orderNonComment) {
            $resultBlock
                ->addError(__('%1 order(s) cannot be changed status and add comment.', $this->_orderNonComment));
        }
        if ($this->_orderInvoiced) {
            $resultBlock->addSuccess(__('A total of %1 order(s) have been invoiced.', $this->_orderInvoiced));
        }

        if ($this->_statusNonUpdated
            && !$this->_orderComment
            && $this->getRequest()->getFullActionName() === 'mpmassorderactions_order_massComment'
        ) {
            $resultBlock->addError(__('%1 order(s) cannot be changed status.', $this->_statusNonUpdated));
        }
        if ($this->_orderNonInvoiced) {
            $resultBlock->addError(__(
                'A total of %1 order(s) does not allow an invoice to be created.',
                $this->_orderNonInvoiced
            ));
        }

        $result = [
            'status'      => true,
            'result_html' => $resultBlock->toHtml()
        ];

        return $result;
    }

    /**
     * Magento default print invoices
     *
     * @param AbstractCollection $collection
     *
     * @return ResponseInterface|Redirect
     * @throws Exception
     * @throws Zend_Pdf_Exception
     */
    protected function _printInvoices($collection)
    {
        /** @var Collection $invoicesCollection */
        $invoicesCollection = $this->_invoiceColFact->create()->setOrderFilter(['in' => $collection->getAllIds()]);
        if (!$invoicesCollection->getSize()) {
            $this->messageManager
                ->addErrorMessage(__('There are no printable documents related to selected orders.'));

            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }
        $pdf = $this->_pdfInvoice->getPdf($invoicesCollection->getItems());

        return $this->_fileFactory->create(
            sprintf('invoice%s.pdf', $this->_dateTime->date('Y-m-d_H-i-s')),
            $pdf->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }

    /**
     * Compatible with PDF invoice print
     *
     * @param AbstractCollection $collection
     *
     * @return Redirect
     * @throws LocalizedException
     * @throws MpdfException
     */
    protected function _printPdfInvoices($collection)
    {
        $ids     = [];
        $storeId = '';
        $type    = 'invoice';
        foreach ($collection as $order) {
            /** @var Order $order */
            $currentStoreId = $order->getStoreId();
            if ($order->hasInvoices()) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $ids[$currentStoreId][] = $invoice->getId();
                }
            }
        }
        /** If $ids is null, go back*/
        if (!$ids) {
            $this->messageManager
                ->addErrorMessage(__('There are no printable documents related to selected orders.'));

            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }
        /** @var PrintProcess $pdfInvoiceHelper */
        $pdfInvoiceHelper = $this->_objectManager->create(PrintProcess::class);

        return $pdfInvoiceHelper->printAllPdf($type, $ids, $storeId);
    }

    /**
     * Magento default print shipments
     *
     * @param AbstractCollection $collection
     * @param string             $type
     *
     * @return ResponseInterface|Redirect
     * @throws Exception
     * @throws Zend_Pdf_Exception
     */
    protected function _printShipments($collection, $type)
    {
        if ($type === 'pdf') {
            /** @var ShipmentResource\Collection $shipmentsCollection */
            $shipmentsCollection = $this->_shipmentColFact
                ->create()
                ->setOrderFilter(['in' => $collection->getAllIds()]);
            if (!$shipmentsCollection->getSize()) {
                $this->messageManager
                    ->addErrorMessage(__('There are no printable documents related to selected orders.'));

                return $this->resultRedirectFactory->create()->setPath('sales/*/');
            }
            $pdf = $this->_pdfShipment->getPdf($shipmentsCollection->getItems());

            return $this->_fileFactory->create(
                sprintf('packingslip%s.pdf', $this->_dateTime->date('Y-m-d_H-i-s')),
                $pdf->render(),
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        }
        $labelsContent = [];
        $shipments     = $this->_shipmentColFact
            ->create()->setOrderFilter(['in' => $collection->getAllIds()]);

        if ($shipments->getSize()) {
            /** @var Shipment $shipment */
            foreach ($shipments as $shipment) {
                $labelContent = $shipment->getShippingLabel();
                if ($labelContent) {
                    $labelsContent[] = $labelContent;
                }
            }
        }
        if (!empty($labelsContent)) {
            $outputPdf = $this->_labelGenerator->combineLabelsPdf($labelsContent);

            return $this->_fileFactory->create(
                'ShippingLabels.pdf',
                $outputPdf->render(),
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        }

        $this->messageManager->addErrorMessage(__('There are no shipping labels related to selected orders.'));

        return $this->resultRedirectFactory->create()->setPath('sales/order/');
    }

    /**
     * Compatible with PDF shipment print
     *
     * @param AbstractCollection $collection
     *
     * @return Redirect
     * @throws LocalizedException
     * @throws MpdfException
     */
    protected function _printPdfShipments($collection)
    {
        $ids     = [];
        $storeId = '';
        $type    = 'shipment';
        foreach ($collection as $order) {
            /** @var Order $order */
            $currentStoreId = $order->getStoreId();
            if ($order->hasShipments() && $shipments = $order->getShipmentsCollection()) {
                foreach ($shipments as $shipment) {
                    $ids[$currentStoreId][] = $shipment->getId();
                }
            }
        }
        /** If $ids is null, go back*/
        if (!$ids) {
            $this->messageManager
                ->addErrorMessage(__('There are no printable documents related to selected orders.'));

            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }
        /** @var PrintProcess $pdfInvoiceHelper */
        $pdfInvoiceHelper = $this->_objectManager->create(PrintProcess::class);

        return $pdfInvoiceHelper->printAllPdf($type, $ids, $storeId);
    }

    /**
     * Magento default print invoices+shipments
     *
     * @param AbstractCollection $collection
     * @param string             $type
     *
     * @return ResponseInterface|Redirect|AbstractMassAction
     * @throws Exception
     */
    protected function _printInvoiceShipment($collection, $type)
    {
        $documents = [];
        $orderIds  = $collection->getAllIds();
        /** @var ShipmentResource\Collection $shipments */
        $shipments = $this->_shipmentColFact->create()->setOrderFilter(['in' => $orderIds]);
        /** @var Collection $invoices */
        $invoices = $this->_invoiceColFact->create()->setOrderFilter(['in' => $orderIds]);
        if ($invoices->getSize()) {
            $documents[] = $this->_pdfInvoice->getPdf($invoices);
        }
        if ($shipments->getSize()) {
            if ($type === 'pdf') {
                $documents[] = $this->_pdfShipment->getPdf($shipments);
            } else {
                /** @var Shipment $shipment */
                foreach ($shipments as $shipment) {
                    $labelContent = $shipment->getShippingLabel();
                    if ($labelContent) {
                        $labelsContent[] = $labelContent;
                    }
                }
                if (!empty($labelsContent)) {
                    $documents[] = $this->_labelGenerator->combineLabelsPdf($labelsContent);
                }
            }
        }

        if (empty($documents)) {
            $this->messageManager->addErrorMessage(__('There are no printable documents related to selected orders.'));

            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }

        $pdf          = array_shift($documents);
        $pagesOptions = [$pdf->pages];
        foreach ($documents as $document) {
            $pagesOptions[] = $document->pages;
        }
        $pdf->pages = array_merge(...$pagesOptions);

        return $this->_fileFactory->create(
            sprintf('docs%s.pdf', $this->_dateTime->date('Y-m-d_H-i-s')),
            $pdf->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}

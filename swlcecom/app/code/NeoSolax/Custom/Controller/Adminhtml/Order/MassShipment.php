<?php

namespace NeoSolax\Custom\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;

//use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Layout;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Pdf\Invoice as PdfInvoice;
use Magento\Sales\Model\Order\Pdf\Shipment as PdfShipment;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceColFact;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentColFact;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Ui\Component\MassAction\Filter;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;

//use Zend\Mvc\Controller\Plugin\Service\ForwardFactory;

class MassShipment extends \Mageplaza\MassOrderActions\Controller\Adminhtml\Order\MassShipment
{
    protected $_orderNonShipmentError = 0;

    public function __construct
    (
        Context            $context,
        Filter             $filter,
        ShipmentResource   $shipmentResource,
        OrderResource      $orderResource,
        HistoryResource    $historyResource,
        CollectionFactory  $collectionFactory,
        InvoiceColFact     $invoiceColFact,
        ShipmentColFact    $shipmentColFact,
        Registry           $coreRegistry,
        Layout             $layout,
        Json               $resultJson,
        ForwardFactory     $resultForwardFactory,
        FileFactory        $fileFactory,
        DateTime           $dateTime,
        OrderCommentSender $orderCommentSender,
        InvoiceService     $invoiceService,
        InvoiceSender      $invoiceSender,
        PdfInvoice         $pdfInvoice,
        PdfShipment        $pdfShipment,
        ShipmentLoader     $shipmentLoader,
        ShipmentSender     $shipmentSender,
        LabelGenerator     $labelGenerator,
        Transaction        $transaction,
        HelperData         $helperData,
        Order              $orderModel
    )
    {
        parent::__construct
        (
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
            try {
                $this->_saveShipment($shipment);
            } catch (\Exception $e) {
                $this->_orderNonShipmentError++;
                return $this->messageManager->addErrorMessage($e->getMessage());
            }

            if ($data['status']) {
                $orderStatus = $this->_getOrderStatus($shipment->getOrder(), $data['status']);

                if ($orderStatus && $orderStatus !== $shipment->getOrder()->getDataByKey('status')) {
                    $this->_shipmentResource->save($shipment);
                    if ($isInvoiced) {
                        $this->_statusNonUpdated--;
                    }
                } elseif (!$isInvoiced) {
//                    $this->_statusNonUpdated++;
                }
            }

            $this->_orderShipment++;
            /** Send shipment emails */
            try {
                if (!empty($data['send_email'])) {
                    $this->_shipmentSender->send($shipment);
                }
            } catch (Exception $e) {
                $resultBlock->addError(__('We can\'t send the shipment email right now.'));
            }
        } catch (Exception $e) {
            $this->_orderNonShipment++;
            $resultBlock->addError($e->getMessage());
        }
    }

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
        if ($this->_orderNonShipmentError) {
            $resultBlock->addError(__(
                'Not all of your products are available in the requested quantity.',
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
        if ($this->_statusNonUpdated && !$this->_orderComment) {
            $resultBlock->addError(__('%1 order(s) cannot be changed status.', $this->_statusNonUpdated));
        }
        if ($this->_orderNonInvoiced) {
            $resultBlock->addError(__(
                'A total of %1 order(s) does not allow an invoice to be created.',
                $this->_orderNonInvoiced
            ));
        }

        $result = [
            'status' => true,
            'result_html' => $resultBlock->toHtml()
        ];

        return $result;
    }
}

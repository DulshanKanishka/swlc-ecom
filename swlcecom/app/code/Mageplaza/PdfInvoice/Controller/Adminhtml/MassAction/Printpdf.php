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
 * @category   Mageplaza
 * @package    Mageplaza_PdfInvoice
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PdfInvoice\Controller\Adminhtml\MassAction;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Mageplaza\PdfInvoice\Helper\Data;
use Mageplaza\PdfInvoice\Helper\PrintProcess as HelperData;
use Mageplaza\PdfInvoice\Model\Source\Type;

/**
 * Class Printpdf
 * @package Mageplaza\PdfInvoice\Controller\Adminhtml\MassAction
 */
class Printpdf extends Action
{
    /**
     * @var string
     */
    protected $redirectUrl = 'sales/order/index';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $invoiceCollectionFactory;

    /**
     * @var ShipmentCollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var CreditmemoCollectionFactory
     */
    protected $creditmemoCollectionFactory;

    /**
     * @var $collectionFactory
     */
    protected $collectionFactory;

    /**
     * Printpdf constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param HelperData $helperData
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param CreditmemoCollectionFactory $creditmemoCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        HelperData $helperData,
        OrderCollectionFactory $orderCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        CreditmemoCollectionFactory $creditmemoCollectionFactory
    ) {
        parent::__construct($context);

        $this->filter                      = $filter;
        $this->helperData                  = $helperData;
        $this->orderCollectionFactory      = $orderCollectionFactory;
        $this->invoiceCollectionFactory    = $invoiceCollectionFactory;
        $this->shipmentCollectionFactory   = $shipmentCollectionFactory;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
    }

    /**
     * Execute action
     *
     * @return $this|ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $type           = $this->getRequest()->getParam('type');
        $subtype        = $this->getRequest()->getParam('subType');
        $this->setTypeCollection($type);

        try {
            $collection = $this->filter->getCollection($this->collectionFactory);
            if ($type === Type::ORDER) {
                switch ($subtype) {
                    case Type::INVOICE:
                        $ids  = $this->getSubtypeInvoiceIds($collection);
                        $type = Type::INVOICE;
                        break;
                    case Type::SHIPMENT:
                        $ids  = $this->getSubtypeShipmentIds($collection);
                        $type = Type::SHIPMENT;
                        break;
                    case Type::CREDIT_MEMO:
                        $ids  = $this->getSubtypeCreditmemoIds($collection);
                        $type = Type::CREDIT_MEMO;
                        break;
                    default:
                        $ids = $this->getOrderIds($collection);
                        break;
                }
            } else {
                $ids = $this->getOrderIds($collection);
            }

            if (empty($ids)) {
                $this->messageManager->addError(__('There are no printable documents related to selected orders.'));

                return $resultRedirect->setPath($this->redirectUrl);
            }

            $this->helperData->printAllPdf($type, $ids);
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());

            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Set type collection
     *
     * @param string $type
     *
     * @return Collection
     */
    public function setTypeCollection($type)
    {
        if (!$this->collectionFactory) {
            switch ($type) {
                case Type::CREDIT_MEMO:
                    $collection        = $this->creditmemoCollectionFactory;
                    $this->redirectUrl = 'sales/creditmemo/index';
                    break;
                case Type::ORDER:
                    $collection = $this->orderCollectionFactory;
                    break;
                case Type::SHIPMENT:
                    $collection        = $this->shipmentCollectionFactory;
                    $this->redirectUrl = 'sales/shipment/index';
                    break;
                default:
                    $collection        = $this->invoiceCollectionFactory;
                    $this->redirectUrl = 'sales/invoice/index';
            }
            $this->collectionFactory = $collection->create();
        }

        return $this->collectionFactory;
    }

    /**
     * @param AbstractDb $collection
     *
     * @return array
     */
    public function getSubtypeInvoiceIds($collection)
    {
        $invoiceIds = [];
        foreach ($collection as $order) {
            $storeId = $order->getStoreId();
            if ($order->hasInvoices()) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoiceIds[$storeId][] = $invoice->getId();
                }
            }
        }

        return $invoiceIds;
    }

    /**
     * @param AbstractDb $collection
     *
     * @return array
     */
    public function getSubtypeShipmentIds($collection)
    {
        $shipmentIds = [];
        foreach ($collection as $order) {
            $storeId = $order->getStoreId();
            if ($order->hasShipments()) {
                foreach ($order->getShipmentsCollection() as $shipment) {
                    $shipmentIds[$storeId][] = $shipment->getId();
                }
            }
        }

        return $shipmentIds;
    }

    /**
     * @param AbstractDb $collection
     *
     * @return array
     */
    public function getSubtypeCreditmemoIds($collection)
    {
        $creditMemoIds = [];
        foreach ($collection as $order) {
            $storeId = $order->getStoreId();
            if ($order->hasCreditmemos()) {
                foreach ($order->getCreditmemosCollection() as $creditMemo) {
                    $creditMemoIds[$storeId][] = $creditMemo->getId();
                }
            }
        }

        return $creditMemoIds;
    }

    /**
     * @param AbstractDb $collection
     *
     * @return array
     */
    public function getOrderIds($collection)
    {
        $orderIds = [];
        foreach ($collection as $data) {
            $storeId              = $data->getStoreId();
            $orderIds[$storeId][] = $data->getId();
        }

        return $orderIds;
    }
}

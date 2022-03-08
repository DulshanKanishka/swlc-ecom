<?php
declare(strict_types=1);

namespace Dulshan\QuickBooksOnline\Model\ReturnProcessor;

use Magento\InventorySales\Model\ReturnProcessor\GetSalesChannelForOrder;
use Magento\InventorySales\Model\ReturnProcessor\ProcessRefundItems;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\GetSourceDeductedOrderItemsInterface;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

class ProcessRefundItem extends ProcessRefundItems
{
    private $websiteRepository;
    private $salesChannelFactory;
    private $salesEventFactory;
    private $itemsToSellFactory;
    private $placeReservationsForSalesEvent;
    private $getSourceDeductedOrderItems;
    private $itemToDeductFactory;
    private $sourceDeductionRequestFactory;
    private $sourceDeductionService;
    private $salesEventExtensionFactory;
    private $getSalesChannelForOrder;

    public function __construct(
        SalesChannelInterfaceFactory $salesChannelFactory,
        WebsiteRepositoryInterface $websiteRepository,
        SalesEventInterfaceFactory $salesEventFactory,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        GetSourceDeductedOrderItemsInterface $getSourceDeductedOrderItems,
        ItemToDeductFactory $itemToDeductFactory,
        SourceDeductionRequestFactory $sourceDeductionRequestFactory,
        SourceDeductionService $sourceDeductionService,
        SalesEventExtensionFactory $salesEventExtensionFactory,
        GetSalesChannelForOrder $getSalesChannelForOrder
    )
    {
        $this->salesChannelFactory = $salesChannelFactory;
        $this->websiteRepository = $websiteRepository;
        $this->salesEventFactory = $salesEventFactory;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->getSourceDeductedOrderItems = $getSourceDeductedOrderItems;
        $this->itemToDeductFactory = $itemToDeductFactory;
        $this->sourceDeductionRequestFactory = $sourceDeductionRequestFactory;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
        $this->getSalesChannelForOrder = $getSalesChannelForOrder;
        parent::__construct($salesEventFactory, $itemsToSellFactory, $placeReservationsForSalesEvent, $getSourceDeductedOrderItems, $itemToDeductFactory, $sourceDeductionRequestFactory, $sourceDeductionService, $salesEventExtensionFactory, $getSalesChannelForOrder);
    }

    public function execute(
        OrderInterface $order,
        array $itemsToRefund,
        array $returnToStockItems
    ) {
        $salesChannel = $this->getSalesChannelForOrder($order);
        $deductedItems = $this->getSourceDeductedOrderItems->execute($order, $returnToStockItems);
        $itemToSell = $backItemsPerSource = [];
        $backQty = 0;

        foreach ($itemsToRefund as $item) {
            $sku = $item->getSku();
            $oderItems = $order->getItems();

            $totalDeductedQty = $this->getTotalDeductedQty($item, $deductedItems);
            $processedQty = $item->getProcessedQuantity() - $totalDeductedQty;
            $qtyBackToSource = ($processedQty > 0) ? $item->getQuantity() - $processedQty : $item->getQuantity();
            $qtyBackToStock = $item->getQuantity();
            $invoicedQty = 0;
            $canResevation = false;
            $addReservation = 0;

            foreach ($deductedItems as $deductedItemResult) {
                $sourceCode = $deductedItemResult->getSourceCode();
                foreach ($deductedItemResult->getItems() as $deductedItem) {
                    if ($sku != $deductedItem->getSku()) {
                        continue;
                    }

                    foreach ($oderItems as $oderItem) {
                        if ($oderItem->getSku() == $sku) {
                            $refundededQty = $oderItem->getQtyBack() - $oderItem->getQtyBackres();
                            $shipingQty = $oderItem->getQtyShipped();
                            if ($oderItem->getParentItem()) {
                                $parent = $oderItem->getParentItem();
                                if ($oderItem->getParentItem()->getProductType() == 'bundle' && $parent->getQtyShipped() > 0) {
                                    $bundle_options = $parent->getProductOptions()['bundle_options'];
                                    $name = $oderItem->getName();
                                    foreach ($bundle_options as $bundle_option) {
                                        foreach ($bundle_option['value'] as $value) {
                                            if ($name == $value['title']) {
                                                $qty = $value['qty'];
                                                break;
                                            }
                                        }
                                    }
                                    $shipingQty = $parent->getQtyShipped() * $qty;
                                }
                            }
                            $invoicedQty = $oderItem->getQtyInvoiced();
                            $canRefund = $shipingQty - $refundededQty;
                            break;
                        }
                    }

                    if ($canRefund >= $item->getQuantity()) {
                        $backQty = $item->getQuantity();
                    } else {
                        if ($canRefund >= 0) {
                            $backQty = $canRefund;
                            if ($invoicedQty > $item->getQuantity()) {
                                $canResevation = true;
                                if ($item->getQuantity() > $backQty) {
                                    $addReservation = $item->getQuantity()-$backQty;
                                } else {
                                    $addReservation = $backQty;
                                }
                            }
                            if ($invoicedQty <= $item->getQuantity()) {
                                $canResevation = true;
                                $addReservation = $invoicedQty - $backQty;
                            }
                        }
                    }

                    $backItemsPerSource[$sourceCode][] = $this->itemToDeductFactory->create([
                        'sku' => $deductedItem->getSku(),
                        'qty' => -$backQty
                    ]);
                    $qtyBackToSource -= $backQty;
                }
            }
            if ($backQty == '0' || $canResevation) {
                if ($canResevation) {
                    if ($qtyBackToStock > 0) {
                        $itemToSell[] = $this->itemsToSellFactory->create([
                            'sku' => $sku,
                            'qty' => (float)$addReservation
                        ]);
                    }
                } else {
                    if ($qtyBackToStock > 0) {
                        $itemToSell[] = $this->itemsToSellFactory->create([
                            'sku' => $sku,
                            'qty' => (float)$qtyBackToStock
                        ]);
                    }
                }
            }
        }

        $salesEvent = $this->salesEventFactory->create([
            'type' => SalesEventInterface::EVENT_CREDITMEMO_CREATED,
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$order->getEntityId()
        ]);

        foreach ($backItemsPerSource as $sourceCode => $items) {
            $sourceDeductionRequest = $this->sourceDeductionRequestFactory->create([
                'sourceCode' => $sourceCode,
                'items' => $items,
                'salesChannel' => $salesChannel,
                'salesEvent' => $salesEvent
            ]);
            $this->sourceDeductionService->execute($sourceDeductionRequest);
        }
        if ($backQty == '0' || $canResevation) {
            $this->placeReservationsForSalesEvent->execute($itemToSell, $salesChannel, $salesEvent);
        }
    }

    private function getSalesChannelForOrder(OrderInterface $order): SalesChannelInterface
    {
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();

        return $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);
    }

    private function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }

    private function getTotalDeductedQty($item, array $deductedItems): float
    {
        $result = 0;

        foreach ($deductedItems as $deductedItemResult) {
            foreach ($deductedItemResult->getItems() as $deductedItem) {
                if ($item->getSku() != $deductedItem->getSku()) {
                    continue;
                }
                $result += $deductedItem->getQuantity();
            }
        }

        return $result;
    }
}

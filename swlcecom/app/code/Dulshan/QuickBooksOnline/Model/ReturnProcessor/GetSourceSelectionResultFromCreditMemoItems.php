<?php
declare(strict_types=1);

namespace Dulshan\QuickBooksOnline\Model\ReturnProcessor;

use Magento\InventorySalesApi\Model\ReturnProcessor\GetSourceDeductedOrderItemsInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder;
use Magento\Sales\Api\Data\OrderInterface;

class GetSourceSelectionResultFromCreditMemoItems extends \Magento\InventorySales\Model\ReturnProcessor\GetSourceSelectionResultFromCreditMemoItems
{
    private $getSourceDeductedOrderItems;
    private $itemRequestFactory;
    private $getInventoryRequestFromOrder;
    private $sourceSelectionService;
    private $getDefaultSourceSelectionAlgorithmCode;

    public function __construct(
        GetSourceDeductedOrderItemsInterface $getSourceDeductedOrderItems,
        ItemRequestInterfaceFactory $itemRequestFactory,
        GetInventoryRequestFromOrder $getInventoryRequestFromOrder,
        SourceSelectionServiceInterface $sourceSelectionService,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode
    ) {
        $this->getSourceDeductedOrderItems = $getSourceDeductedOrderItems;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->getInventoryRequestFromOrder = $getInventoryRequestFromOrder;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        parent::__construct($getSourceDeductedOrderItems, $itemRequestFactory, $getInventoryRequestFromOrder, $sourceSelectionService, $getDefaultSourceSelectionAlgorithmCode);
    }

    public function execute(
        OrderInterface $order,
        array $itemsToRefund,
        array $itemsToDeductFromSource
    ):
    SourceSelectionResultInterface {
        $deductedItems = $this->getSourceDeductedOrderItems->execute($order, $itemsToDeductFromSource);
        $requestItems = [];
        foreach ($itemsToRefund as $item) {
            $sku = $item->getSku();

            $totalDeductedQty = $this->getTotalDeductedQty($item, $deductedItems);
            $processedQty = $item->getProcessedQuantity() - $totalDeductedQty;
            $backQty = ($processedQty > 0) ? $item->getQuantity() - $processedQty : $item->getQuantity();
            $qtyBackToSource = $item->getQuantity();

            $oderItems = $order->getItems();
            foreach ($oderItems as $oderItem) {
                if ($oderItem->getSku() == $sku) {
                    $shipingQty  =  $oderItem->getQtyShipped();
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
                            $shipingQty = $parent->getQtyShipped()*$qty;
                        }
                    }
                    $invoicedQty = $oderItem->getQtyInvoiced();
                    $notBacktoStock =$oderItem->getQtyRefunded() -$oderItem->getQtyBack();
                    $reservation = $oderItem->getQtyReservation();
                    $do = $notBacktoStock + $reservation + $shipingQty;
                    if ($do >= $invoicedQty) {
                        $qtyBackToSource = 0;
                    }
                    break;
                }
            }

            $requestItems[] = $this->itemRequestFactory->create([
                'sku' => $sku,
                'qty' => (float)$qtyBackToSource
            ]);
        }

        $inventoryRequest = $this->getInventoryRequestFromOrder->execute((int)$order->getEntityId(), $requestItems);
        $selectionAlgorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();

        return $this->sourceSelectionService->execute($inventoryRequest, $selectionAlgorithmCode);
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

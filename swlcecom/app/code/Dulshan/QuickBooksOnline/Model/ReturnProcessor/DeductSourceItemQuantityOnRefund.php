<?php
declare(strict_types=1);
namespace Dulshan\QuickBooksOnline\Model\ReturnProcessor;

use Magento\InventorySales\Model\ReturnProcessor\GetSourceDeductionRequestFromSourceSelection;
use Magento\InventorySales\Model\ReturnProcessor\GetSourceSelectionResultFromCreditMemoItems;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\Sales\Api\Data\OrderInterface;

class DeductSourceItemQuantityOnRefund extends \Magento\InventorySales\Model\ReturnProcessor\DeductSourceItemQuantityOnRefund
{
    private $getSourceSelectionResultFromCreditMemoItems;
    private $getSourceDeductionRequestFromSourceSelection;
    private $itemsToSellFactory;
    private $placeReservationsForSalesEvent;
    private $sourceSelectionService;
    private $sourceDeductionService;

    public function __construct(
        GetSourceSelectionResultFromCreditMemoItems $getSourceSelectionResultFromCreditMemoItems,
        GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection,
        SourceSelectionServiceInterface $sourceSelectionService,
        SourceDeductionServiceInterface $sourceDeductionService,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
    ) {
        $this->getSourceSelectionResultFromCreditMemoItems = $getSourceSelectionResultFromCreditMemoItems;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->getSourceDeductionRequestFromSourceSelection = $getSourceDeductionRequestFromSourceSelection;
        parent::__construct($getSourceSelectionResultFromCreditMemoItems, $getSourceDeductionRequestFromSourceSelection, $sourceSelectionService, $sourceDeductionService, $itemsToSellFactory, $placeReservationsForSalesEvent);
    }

    public function execute(
        OrderInterface $order,
        array $itemsToRefund,
        array $itemsToDeductFromSource
    ): void {
        $sourceSelectionResult = $this->getSourceSelectionResultFromCreditMemoItems->execute(
            $order,
            $itemsToRefund,
            $itemsToDeductFromSource
        );

        $sourceDeductionRequests = $this->getSourceDeductionRequestFromSourceSelection->execute(
            $order,
            $sourceSelectionResult
        );

        foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
//            $this->sourceDeductionService->execute($sourceDeductionRequest);
            $this->placeCompensatingReservation($sourceDeductionRequest);
        }
    }

    private function placeCompensatingReservation(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        $items = [];
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $items[] = $this->itemsToSellFactory->create([
                'sku' => $item->getSku(),
                'qty' => $item->getQty()
            ]);
        }
        $this->placeReservationsForSalesEvent->execute(
            $items,
            $sourceDeductionRequest->getSalesChannel(),
            $sourceDeductionRequest->getSalesEvent()
        );
    }
}

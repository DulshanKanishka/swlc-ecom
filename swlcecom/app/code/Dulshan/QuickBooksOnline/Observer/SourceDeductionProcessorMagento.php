<?php

namespace Dulshan\QuickBooksOnline\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;

class SourceDeductionProcessorMagento extends \Magento\InventoryShipping\Observer\SourceDeductionProcessor
{
    private $isSingleSourceMode;
    private $defaultSourceProvider;
    private $getItemsToDeductFromShipment;
    private $sourceDeductionRequestFromShipmentFactory;
    private $sourceDeductionService;
    private $itemsToSellFactory;
    private $placeReservationsForSalesEvent;

    public function __construct(IsSingleSourceModeInterface $isSingleSourceMode, DefaultSourceProviderInterface $defaultSourceProvider, GetItemsToDeductFromShipment $getItemsToDeductFromShipment, SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory, SourceDeductionServiceInterface $sourceDeductionService, ItemToSellInterfaceFactory $itemsToSellFactory, PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent)
    {
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->sourceDeductionRequestFromShipmentFactory = $sourceDeductionRequestFromShipmentFactory;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        parent::__construct($isSingleSourceMode, $defaultSourceProvider, $getItemsToDeductFromShipment, $sourceDeductionRequestFromShipmentFactory, $sourceDeductionService, $itemsToSellFactory, $placeReservationsForSalesEvent);
    }

    public function execute(EventObserver $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getIsUdate() == '1') {
            return;
        }

        if ($shipment->getOrigData('entity_id')) {
            return;
        }

        if (!empty($shipment->getExtensionAttributes())
            && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        $shipmentItems = $this->getItemsToDeductFromShipment->execute($shipment);

        if (!empty($shipmentItems)) {
            $sourceDeductionRequest = $this->sourceDeductionRequestFromShipmentFactory->execute(
                $shipment,
                $sourceCode,
                $shipmentItems
            );
            $this->sourceDeductionService->execute($sourceDeductionRequest);
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

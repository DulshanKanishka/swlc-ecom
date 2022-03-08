<?php

namespace Dulshan\Custom\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;

class GetSourceItemsDataBySku extends \Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku
{
    private $sourceItemRepository;

    private $sourceRepository;

    private $searchCriteriaBuilder;

    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($sourceItemRepository, $sourceRepository, $searchCriteriaBuilder);
    }

    public function execute(string $sku): array
    {
        $sourceItemsData = [];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $sourcesCache = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCode = $sourceItem->getSourceCode();
            if (!isset($sourcesCache[$sourceCode])) {
                $sourcesCache[$sourceCode] = $this->sourceRepository->get($sourceCode);
            }

            $source = $sourcesCache[$sourceCode];

            $sourceItemsData[] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
                SourceInterface::NAME => $source->getName(),
                'source_status' => $source->isEnabled(),
                'shelf_location' => $sourceItem->getShelfLocation()
            ];
        }

        return $sourceItemsData;
    }
}

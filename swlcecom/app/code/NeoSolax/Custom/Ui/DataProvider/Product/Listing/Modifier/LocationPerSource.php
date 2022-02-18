<?php

namespace NeoSolax\Custom\Ui\DataProvider\Product\Listing\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Listing\Columns\Column;

class LocationPerSource extends AbstractModifier
{
    private $isSingleSourceMode;

    private $sourceRepository;

    private $searchCriteriaBuilder;

    private $sourceItemRepository;

    private $getAllowedProductTypesForSourceItemManagement;

    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        $isSourceItemManagementAllowedForProductType,
        SourceRepositoryInterface $sourceRepository,
        $getSourceItemsBySku,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        SourceItemRepositoryInterface $sourceItemRepository = null,
        GetAllowedProductTypesForSourceItemManagementInterface $getAllowedProductTypesForSourceItemManagement = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?: $objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = $sourceItemRepository ?:
            $objectManager->get(SourceItemRepositoryInterface::class);
        $this->getAllowedProductTypesForSourceItemManagement = $getAllowedProductTypesForSourceItemManagement ?:
            $objectManager->get(GetAllowedProductTypesForSourceItemManagementInterface::class);
    }

    public function modifyData(array $data)
    {
        if (0 === $data['totalRecords'] || true === $this->isSingleSourceMode->execute()) {
            return $data;
        }

        $data['items'] = $this->getSourceItemsData($data['items']);

        return $data;
    }


    private function getSourceItemsData(array $dataItems): array
    {
        $itemsBySkus = [];
        $allowedProductTypes = $this->getAllowedProductTypesForSourceItemManagement->execute();

        foreach ($dataItems as $key => $item) {
            if (in_array($item['type_id'], $allowedProductTypes)) {
                $itemsBySkus[$item['sku']] = $key;
                continue;
            }
            $dataItems[$key]['location_per_source'] = [];
        }

        unset($item);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, array_keys($itemsBySkus), 'in')
            ->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourcesBySourceCode = $this->getSourcesBySourceItems($sourceItems);

        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();

            if (isset($itemsBySkus[$sku])) {
                $source = $sourcesBySourceCode[$sourceItem->getSourceCode()];
                $shelfLocation =$sourceItem->getShelfLocation();
                $dataItems[$itemsBySkus[$sku]]['location_per_source'][] = [
                    'source_name' => $source->getName(),
                    'source_code' => $source->getSourceCode(),
                    'shelf_location' => $shelfLocation,
                ];
            }
        }

        return $dataItems;
    }


    public function modifyMeta(array $meta)
    {
        if (true === $this->isSingleSourceMode->execute()) {
            return $meta;
        }

        $meta = array_replace_recursive(
            $meta,
            [
                'product_columns' => [
                    'children' => [
                        'location_per_source' => $this->getLocationPerSourceMeta(),
                    ],
                ],
            ]
        );
        return $meta;
    }


    private function getLocationPerSourceMeta(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 80,
                        'filter' => false,
                        'sortable' => false,
                        'label' => __('Shelf Location'),
                        'dataType' => Text::NAME,
                        'componentType' => Column::NAME,
                        'component' => 'NeoSolax_Custom/js/product/grid/cell/location-per-source',
                    ]
                ],
            ],
        ];
    }


    private function getSourcesBySourceItems(array $sourceItems): array
    {
        $newSourceCodes = $sourcesBySourceCodes = [];

        foreach ($sourceItems as $sourceItem) {
            $newSourceCodes[$sourceItem->getSourceCode()] = $sourceItem->getSourceCode();
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceInterface::SOURCE_CODE, array_keys($newSourceCodes), 'in')
            ->create();
        $sources = $this->sourceRepository->getList($searchCriteria)->getItems();

        foreach ($sources as $source) {
            $sourcesBySourceCodes[$source->getSourceCode()] = $source;
        }

        return $sourcesBySourceCodes;
    }
}

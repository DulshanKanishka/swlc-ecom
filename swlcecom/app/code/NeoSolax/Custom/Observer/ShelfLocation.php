<?php

namespace NeoSolax\Custom\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class ShelfLocation implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        Context $context
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->context     = $context;
        $this->_request   = $context->getRequest();
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $params = $this->_request->getParams();
        if ($params) {
            if ($params['type'] != 'configurable' && $params['type'] != 'bundle') {
                if(isset($params['sources'])) {
                    $sources = $params['sources']['assigned_sources'];
                    $sku = $params['product']['sku'];

                    $searchCriteria = $this->searchCriteriaBuilder
                        ->addFilter(SourceItemInterface::SKU, $sku)
                        ->create();
                    $result = $this->sourceItemRepository->getList($searchCriteria)->getItems();

                    foreach ($sources as $item) {
                        $shelf_location = $item['shelf_location'];
                        $sourceCode = $item['source_code'];
                        if (isset($shelf_location)) {
                            foreach ($result as $value) {
                                if ($value->getSourceCode() == $sourceCode) {
                                    $value->setShelfLocation($shelf_location);
                                    $value->save();
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

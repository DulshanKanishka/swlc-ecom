<?php

namespace Dulshan\QuickBooksOnline\Observer\Item;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class UpdateInventory extends \Magenest\QuickBooksOnline\Observer\Item\UpdateInventory
{
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Config $config,
        QueueFactory $queueFactory,
        Item $item,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        StockRegistryInterface $stockInterface,
        ProductMetadataInterface $productMetadata,
        ModuleManager $moduleManager,
        Context $context
    ) {
        $this->productRepository = $productRepository;
        parent::__construct($config, $queueFactory, $item, $registry, $storeManager, $productFactory, $stockInterface, $productMetadata, $moduleManager, $context);
    }

    public function checkQty($id, $sku, $sources, $websiteStockId)
    {
        $product = $this->productRepository->getById($id);
        $qty = null;
        $currentQty = $this->stockInterface->getStockItem($id)->getQty();
        $QBId = $this->_item->getQboId($product);
        if ($QBId != 0) {
            $params = [
                'type' => 'id',
                'input' => $QBId
            ];

            $QBProduct = $this->_item->getProduct($params);
            if ($QBProduct) {
                $QBQty = $QBProduct['Item'][0]["QtyOnHand"];

                if (!empty($sources['assigned_sources'])) {
                    foreach ($sources['assigned_sources'] as $source) { /*get stock qty per valid source*/
                        $sourceCode = $source['source_code'];
                        if ($this->validateSource($websiteStockId, $sourceCode) && isset($source['status']) && $source['status'] == 1) {
                            $qty += isset($source['quantity']) ? $source['quantity'] : 0;
                        }
                    }
                } else {
                    $qty = $currentQty;
                }

                $currentSalableQty = $this->_item->getSalableQty($sku);
                if (isset($currentQty) && isset($currentSalableQty)) {
                    if ($currentSalableQty == 0) {
                        if ($currentQty == 0) {
                            $qty = 0;
                        }
                    } else {
                        $pendingQty = $currentQty - $qty; /*sold but not delivered qty*/
                        $qty = $QBQty - $pendingQty; /*get updated salable qty*/
                    }
                }

                return $qty;
            }
        }
    }
}

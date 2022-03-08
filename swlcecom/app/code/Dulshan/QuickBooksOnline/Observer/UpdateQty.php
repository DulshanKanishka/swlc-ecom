<?php

namespace Dulshan\QuickBooksOnline\Observer;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class UpdateQty implements ObserverInterface
{
    public function __construct(
        Config $config,
        StockRegistryInterface $stockInterface,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductRepositoryInterface $productRepository,
        Item $item,
        DefaultSourceProviderInterface $defaultSourceProvider
    )
    {
        $this->config = $config;
        $this->stockInterface = $stockInterface;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productRepository = $productRepository;
        $this->item = $item;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->config->getConnected()) {
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();

            $sourceCode = $this->defaultSourceProvider->getCode();

            $items = $order->getItems();

            foreach ($items as $product) {
                if ($product->getProductType() !== 'bundle') {
                    $sku = $product->getSku();
                    try {
                        $model = $this->productRepository->get($sku);
                    } catch (\Exception $e) {
                        continue;
                    }
                    $id = $model->getId();
                    $QBId = $this->item->getQboId($model);

                    if ($QBId != 0) {
                        $params = [
                            'type' => 'id',
                            'input' => $QBId
                        ];
                        $QBProduct = $this->item->getProduct($params);
                        if ($QBProduct) {
                            if ($QBProduct['Item'][0]['Type'] == 'Inventory') {
                                $QBQty = $QBProduct['Item'][0]["QtyOnHand"];
                                $currentQty = $this->stockInterface->getStockItem($id)->getQty();
                                if ($QBQty != $currentQty) {
                                    $sourceItem = $this->sourceItemFactory->create();
                                    $sourceItem->setSourceCode($sourceCode);
                                    $sourceItem->setSku($sku);
                                    if ($currentQty == 0) {
                                        $sourceItem->setQuantity($QBQty);
                                        $sourceItem->setStatus(0);
                                    } else {
                                        $sourceItem->setQuantity($QBQty);
                                        $sourceItem->setStatus(1);
                                    }
                                    $this->sourceItemsSaveInterface->execute([$sourceItem]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

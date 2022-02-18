<?php

namespace NeoSolax\QuickBooksOnline\Observer;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Observer\AbstractObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;

class SourceDeductionProcessor extends AbstractObserver implements ObserverInterface
{
    public function __construct(
        Registry $registryObject,
        ManagerInterface $messageManager,
        QueueFactory $queueFactory,
        Config $config,
        StockRegistryInterface $stockInterface,
        ProductRepositoryInterface $productRepository,
        Item $item
    )
    {
        $this->registryObject = $registryObject;
        $this->type = 'item';
        $this->config = $config;
        $this->stockInterface = $stockInterface;
        $this->productRepository = $productRepository;
        $this->item = $item;
        parent::__construct($messageManager, $config, $queueFactory);
    }

    public function execute(EventObserver $observer)
    {
        if ($this->config->getConnected()) {
            $shipment = $observer->getEvent()->getShipment();

            if ($shipment->getIsUdate() == '1') {
                return;
            } else {
                $shipment->setIsUdate(1);
                $shipment->save();
            }

            $items = $shipment->getItems();
            $skus = [];
            foreach ($items as $product) {
                $sku = $product->getSku();
                if (!in_array($sku, $skus)) {
                    $skus[] = $sku;
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
                                $currentQty = $this->stockInterface->getStockItem($id)->getQty();
                                $QBQty = $QBProduct['Item'][0]['QtyOnHand'];
                                if ($QBQty == $currentQty && $product->getQty() > 0) {
                                    $currentQty = $currentQty - $product->getQty();
                                } elseif ($QBQty != $currentQty) {
                                    $currentQty = $QBQty - $product->getQty();
                                }
                                if (sizeof($items) > 10) {
                                    $this->addToQueue($id);
                                } else {
                                    $this->item->sendItems($id, true, $currentQty);
                                    $this->registryObject->unregister('check_to_syn' . $id);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

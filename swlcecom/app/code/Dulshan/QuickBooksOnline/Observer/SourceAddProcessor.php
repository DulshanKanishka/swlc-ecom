<?php

namespace Dulshan\QuickBooksOnline\Observer;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Observer\AbstractObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class SourceAddProcessor extends AbstractObserver implements ObserverInterface
{
    public function __construct(
        ManagerInterface $messageManager,
        QueueFactory $queueFactory,
        Config $config,
        StockRegistryInterface $stockInterface,
        ProductRepositoryInterface $productRepository,
        Item $item
    )
    {
        $this->type = 'item';
        $this->config = $config;
        $this->stockInterface = $stockInterface;
        $this->productRepository = $productRepository;
        $this->item = $item;
        parent::__construct($messageManager, $config, $queueFactory);
    }

    public function execute(EventObserver $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        $items = $creditmemo->getItems();
        $ids = [];
        $skus = [];

        foreach ($items as $product) {
            $sku = $product->getSku();
            try {
                $model = $this->productRepository->get($sku);
            } catch (\Exception $e) {
                continue;
            }
            $id = $model->getId();
            $oderItems = $order->getItems();

            if ($product->getBackToStock()) {
                foreach ($oderItems as $oderItem) {
                    if ($oderItem->getSku() == $sku && !in_array($oderItem->getSku(), $skus)) {
                        $skus[] = $oderItem->getSku();

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
                        $addReservation = 0;
                        $backQty = 0;

                        if ($canRefund >= $product->getQty()) {
                            $backQty = $product->getQty();
                        } else {
                            if ($canRefund >= 0) {
                                $backQty = $canRefund;
                                if ($invoicedQty > $product->getQty()) {
                                    if ($product->getQty() > $backQty) {
                                        $addReservation = $product->getQty() - $backQty;
                                    } else {
                                        $addReservation = $backQty;
                                    }
                                }
                                if ($invoicedQty <= $product->getQty()) {
                                    $addReservation = $invoicedQty - $backQty;
                                }
                                if ($backQty != '0') {
                                    $value = $oderItem->getQtyBackres();
                                    $newValue = $value + $addReservation;
                                    $oderItem->setQtyBackres($newValue);
                                }
                            }
                        }
                        if ($backQty == '0') {
                            $addReservation = $product->getQty();
                            $value = $oderItem->getQtyBackres();
                            $newValue = $value + $addReservation;
                            $oderItem->setQtyBackres($newValue);
                        }
                        if ($addReservation) {
                            $value = $oderItem->getQtyReservation();
                            $newValue = $value + $addReservation;
                            $oderItem->setQtyReservation($newValue);
                        }

                        $value = $oderItem->getQtyBack();
                        $newValue = $value + $product->getQty();
                        $oderItem->setQtyBack($newValue);
                        $oderItem->save();
                        if ($this->config->getConnected() && !in_array($id, $ids)) {
                            $ids[] = $id;
                            $this->QBUpdate($backQty, $model, sizeof($items), $id);
                        }
                        break;
                    }
                }
            }
        }
    }

    public function QBUpdate($backQty, $model, $size, $id)
    {
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
                    if ($QBQty == $currentQty && $backQty > 0) {
                        $currentQty = $currentQty + $backQty;
                    }
                    if ($size > 10) {
                        $this->addToQueue($model->getId());
                    } else {
                        $this->item->sendItems($model->getId(), true, $currentQty);
                    }
                }
            }
        }
    }
}

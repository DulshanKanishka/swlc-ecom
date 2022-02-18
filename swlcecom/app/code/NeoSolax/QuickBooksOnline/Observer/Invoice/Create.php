<?php

namespace NeoSolax\QuickBooksOnline\Observer\Invoice;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Invoice;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\Order;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;

class Create extends \Magenest\QuickBooksOnline\Observer\Invoice\Create
{
    public function __construct(
        Registry $registryObject,
        StockRegistryInterface $stockInterface,
        Item $item,
        ProductRepositoryInterface $productRepository,
        Order $order,
        ManagerInterface $messageManager,
        Config $config,
        QueueFactory $queueFactory,
        Invoice $invoice
    )
    {
        $this->registryObject = $registryObject;
        $this->stockInterface = $stockInterface;
        $this->item = $item;
        $this->productRepository = $productRepository;
        $this->_order = $order;
        parent::__construct($messageManager, $config, $queueFactory, $invoice);
    }

    public function execute(Observer $observer)
    {
        if ($this->isConnected() && $this->isConnected() == 1) {
            try {
                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                $invoice = $observer->getEvent()->getInvoice();
                $note = $invoice->getCustomerNote();
                if ($note != 'quickbook') {
                    $oderIncrementId = $invoice->getOrder()->getIncrementId();
                    $qboIdOder = $this->_order->sync($oderIncrementId, true);

                    if ($qboIdOder) {
                        $skus = [];
                        $order = $invoice->getOrder();
                        $items = $order->getItems();
                        foreach ($items as $product) {
                            $sku = $product->getSku();
                            if (!in_array($product->getSku(), $skus)) {
                                $skus[] = $sku;
                                try {
                                    $model = $this->productRepository->get($sku);
                                } catch (\Exception $e) {
                                    continue;
                                }
                                $id = $model->getId();
                                $QBId = $this->item->getQboId($model);
                                if ($QBId == 0) {
                                    $QBId = $this->item->sync($id);
                                }
                                if ($QBId != 0) {
                                    $params = [
                                        'type' => 'id',
                                        'input' => $QBId
                                    ];
                                    $QBProduct = $this->item->getProduct($params);
                                    if ($QBProduct) {
                                        if ($QBProduct['Item'][0]['Type'] == 'Inventory') {
                                            $OderedQty = $product->getQtyOrdered();
                                            $QBQty = $QBProduct['Item'][0]["QtyOnHand"];
                                            $newQty = $QBQty + $OderedQty;
                                            if (sizeof($items) > 10) {
                                                $this->addProductToQueue($id, 'item');
                                            } else {
                                                $this->item->sendItems($id, true, $newQty);
                                                $this->registryObject->unregister('check_to_syn' . $id);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                    }
                    $incrementId = $invoice->getIncrementId();

                    if ($incrementId && $this->isEnabled() && !$invoice->getIsUsedForRefund()) {
                        if ($this->isImmediatelyMode()) {
                            $qboId = $this->_invoice->sync($incrementId);

                            $adminSession = ObjectManager::getInstance()->get('\Magento\Backend\Model\Auth\Session');
                            $isAdminPage = $adminSession->isLoggedIn();
                            if ($qboId && $isAdminPage) {
//                                $this->messageManager->addSuccessMessage(__('Successfully updated this Order(Id: %1) in QuickBooksOnline.', $qboIdOder));
                                $this->messageManager->addSuccessMessage(__('Successfully updated this Invoice(Id: %1) in QuickBooksOnline.', $qboId));
                            }
                        } else {
//                            $this->addToQueue($oderIncrementId);
                            $this->addToQueue($incrementId);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }

    public function addProductToQueue($id, $type)
    {
        $collection = $this->queueFactory->create()->getCollection();
        $data = [
            'type' => $type,
            'type_id' => $id
        ];
        $model = $collection->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('type_id', $id)
            ->getFirstItem();

        $model->addData($data);
        $model->save();
    }


}

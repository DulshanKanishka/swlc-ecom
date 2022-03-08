<?php

namespace Dulshan\QuickBooksOnline\Observer;

use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;


class BeforeProductSave implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magenest\QuickBooksOnline\Model\Config $config,
        StockRegistryInterface $stockInterface,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\ActionFlag $actionFlag,
        Item $item
    )
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->redirect = $redirect;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->config = $config;
        $this->stockInterface = $stockInterface;
        $this->messageManager = $messageManager;
        $this->productRepository = $productRepository;
        $this->item = $item;
        $this->actionFlag = $actionFlag;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->config->getConnected()) {
            $product = $observer->getEvent()->getProduct();
            $sku = $product->getSku();
            if ($this->isDeferQBQty($sku)) {
                $this->messageManager->addError(__("Quantity seems to be different with the QB. please sync it first"));
                $url = $this->url->getUrl('adminhtml/system_config/edit/section/qbonline');
                $this->responseFactory->create()->setRedirect($url)->sendResponse();
                die();
            }
        }
    }


    public function isDeferQBQty($sku)
    {
        try {
            $model = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            return false;
        }
        if ($model) {
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
                            return true;
                        }
                    }
                }
            }
            return false;
        }
    }
}

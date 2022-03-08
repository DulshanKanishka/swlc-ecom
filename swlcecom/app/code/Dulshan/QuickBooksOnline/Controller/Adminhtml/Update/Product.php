<?php

namespace Dulshan\QuickBooksOnline\Controller\Adminhtml\Update;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Controller\ResultFactory;
use Dulshan\QuickBooksOnline\Model\Synchronization\Item;

class Product extends Action
{

    public function __construct(
        \Magenest\QuickBooksOnline\Model\Config $config,
        StockRegistryInterface $stockInterface,
        ProductRepositoryInterface $productRepository,
        Context $context,
        Item $item
    )
    {
        $this->config = $config;
        $this->stockInterface = $stockInterface;
        $this->item = $item;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $products = $this->getRequest()->getParam('products');

        if ($products) {
            $isDeferQBQty = $this->isDeferQBQty($products);

            $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
            $result->setData(['isDeferQBQty' => $isDeferQBQty]);
            return $result;
        } else {
            try {
                $this->item->UpdateInventoryItemStock();
//            $this->item->UpdateNonInventoryItemStock();

                $this->messageManager->addSuccessMessage(
                    __('Successfully Synced')
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while syncing products. Please check your connection. Detail: ' . $e->getMessage())
                );
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }
    }

    public function isDeferQBQty($products)
    {
        if ($this->config->getConnected()) {
            $skus = explode(',', $products);
            foreach ($skus as $sku) {
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
                                return true;
                            }
                        }
                    }
                }

            }
        }
        return false;
    }


}

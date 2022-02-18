<?php

namespace NeoSolax\QuickBooksOnline\Block\Adminhtml\Create;

use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Items extends \Magento\Shipping\Block\Adminhtml\Create\Items
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        array $data = []
    )
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $carrierFactory, $data);
    }


    public function getSku()
    {
        $skus = [];
        $oderItems = $this->getOrder()->getItems();
        foreach ($oderItems as $product) {
            $sku = $product->getSku();
            if (!in_array($sku, $skus)) {
                $skus[] = $sku;
            }
        }
        return $skus;
    }

    public function getRedirectUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/qbonline');
    }

    public function getActionUrl()
    {
        return $this->getUrl('neoqb/update/product');
    }

}




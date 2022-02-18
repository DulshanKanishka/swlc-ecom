<?php

namespace NeoSolax\QuickBooksOnline\Block\Adminhtml\Order\Creditmemo\Create;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        array $data = []
    )
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $data);
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

    public function getActionUrl()
    {
        return $this->getUrl('neoqb/update/product');
    }

    public function getRedirectUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/qbonline');
    }
}

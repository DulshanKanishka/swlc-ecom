<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */


namespace Amasty\Preorder\Plugin;

use Amasty\Preorder\Helper\Data;
use Magento\Store\Model\ScopeInterface;

class StockStateProvider
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var null|\Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    private $stockItem = null;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    public function aroundCheckQty(
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Closure $closure,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem,
        $qty
    ) {
        $this->stockItem = $stockItem;
        $result = $closure($stockItem, $qty);
        if ($result) {
            return $result;
        }

        $preordersEnabled = $this->helper->preordersEnabled();
        $isPreorder = $stockItem->getBackorders() == Data::BACKORDERS_PREORDER_OPTION;
        $emptyQtyAllowed = $this->scopeConfig->isSetFlag(
            'ampreorder/functional/allowemptyqty',
            ScopeInterface::SCOPE_STORE
        );

        $result = $preordersEnabled && $isPreorder && $emptyQtyAllowed;

        return $result;
    }

    public function aroundVerifyStock(
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Closure $closure,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ) {

        $result = $closure($stockItem);
        if (!$result) {
            return $result;
        }

        if ($stockItem->getQty() <= $stockItem->getMinQty()
            && $stockItem->getBackorders() == Data::BACKORDERS_PREORDER_OPTION
        ) {
            return $this->scopeConfig->isSetFlag(
                'ampreorder/functional/allowemptyqty',
                ScopeInterface::SCOPE_STORE
            );
        }

        return true;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockStateProvider $subject
     * @param \Magento\Framework\DataObject $result
     *
     * @return \Magento\Framework\DataObject
     */
    public function afterCheckQuoteItemQty(
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Magento\Framework\DataObject $result
    ) {
        if ($this->stockItem
            && $this->stockItem->getBackorders() == Data::BACKORDERS_PREORDER_OPTION
            && $this->stockItem->getQty() > 0
            && $this->helper->disableForPositiveQty()
            && $result->getItemBackorders()
            && $this->helper->getCartMessage()
        ) {
            $result->setMessage(
                sprintf(
                    $this->helper->getCartMessage(),
                    $this->stockItem->getProductName(),
                    $result->getItemBackorders() * 1
                )
            );
        }

        return $result;
    }
}

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */


namespace Amasty\Preorder\Plugin\InventorySales\Plugin\StockState;

use Amasty\Preorder\Helper\Data;
use Magento\CatalogInventory\Api\StockStateInterface;

class CheckQuoteItemQtyPlugin
{
    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    private $stockRegistry;

    /**
     * @var null|\Magento\CatalogInventory\Api\Data\StockStatusInterface
     */
    private $stockStatus = null;

    /**
     * @var null|int
     */
    private $backordersQty = null;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        Data $helper
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\InventorySales\Plugin\StockState\CheckQuoteItemQtyPlugin $currentSubject
     * @param \Closure $proceed
     * @param $productId
     * @param $itemQty
     * @param $qtyToCheck
     * @param $origQty
     * @param null $scopeId
     *
     * @return array
     */
    public function beforeAroundCheckQuoteItemQty(
        $currentSubject,
        StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId = null
    ) {
        $this->stockStatus = $this->stockRegistry->getStockStatus($productId, $scopeId);
        if ($itemQty > $this->stockStatus->getQty()) {
            $this->backordersQty = $itemQty - $this->stockStatus->getQty();
        }

        return [$subject, $proceed, $productId, $itemQty, $qtyToCheck, $origQty, $scopeId];
    }

    /**
     * @param \Magento\InventorySales\Plugin\StockState\CheckQuoteItemQtyPlugin $subject
     * @param \Magento\Framework\DataObject $result
     *
     * @return \Magento\Framework\DataObject
     */
    public function afterAroundCheckQuoteItemQty(
        $subject,
        $result
    ) {
        if ($this->stockStatus
            && $this->stockStatus->getStockItem()->getBackorders() == Data::BACKORDERS_PREORDER_OPTION
            && $this->stockStatus->getQty() > 0
            && $this->helper->disableForPositiveQty()
            && $this->backordersQty
            && $this->helper->getCartMessage()
        ) {
            $result->setMessage(
                sprintf(
                    $this->helper->getCartMessage(),
                    $this->stockStatus->getStockItem()->getProductName(),
                    $this->backordersQty * 1
                )
            );
        }
        $this->backordersQty = null;
        $this->stockStatus = null;

        return $result;
    }
}

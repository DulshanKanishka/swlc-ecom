<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */


namespace Amasty\Preorder\Model\Order;

use Magento\CatalogInventory\Observer\ProductQty as NativeProductQty;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;

class ProductQty
{
    /**
     * @var NativeProductQty
     */
    private $productQty;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var array|null
     */
    private $placedItems = null;

    /**
     * @var Quote|null
     */
    private $quote = null;

    /**
     * @var array
     */
    private $backordered = [];

    public function __construct(NativeProductQty $productQty, CheckoutSession $checkoutSession)
    {
        $this->productQty = $productQty;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param int $productId
     *
     * @return int
     */
    public function getPlacedQty($productId)
    {
        if ($this->placedItems === null && $this->getQuote()->getInventoryProcessed()) {
            $this->placedItems = $this->productQty->getProductQty(
                $this->getQuote()->getAllItems()
            );
        }
        $placedQty = isset($this->placedItems[$productId])
            ? $this->placedItems[$productId]
            : 0;

        return $placedQty - $this->getBackorderedQty($productId);
    }

    /**
     * @return Quote|null
     */
    private function getQuote()
    {
        if ($this->quote === null) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * @param int $productId
     * @param $backorderedQty
     */
    public function addBackorderedQty($productId, $backorderedQty)
    {
        $this->backordered[$productId] = (int)$backorderedQty;
    }

    /**
     * @param int $productId
     *
     * @return int
     */
    public function getBackorderedQty($productId)
    {
        return isset($this->backordered[$productId])
            ? $this->backordered[$productId]
            : 0;
    }
}

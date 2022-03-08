<?php
namespace Dulshan\DataImport\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;

class productsave implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getData('product');
        $product->setData('store_id', Store::DEFAULT_STORE_ID);
    }
}

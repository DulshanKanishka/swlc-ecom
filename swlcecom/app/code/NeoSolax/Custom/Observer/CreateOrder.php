<?php

namespace NeoSolax\Custom\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CreateOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $order->setPhoneNumber($order->getBillingAddress()->getTelephone());
        $order->save();
    }
}

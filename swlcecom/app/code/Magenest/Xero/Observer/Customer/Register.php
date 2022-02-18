<?php
namespace Magenest\Xero\Observer\Customer;

use Magenest\Xero\Observer\AbstractCustomer;
use Magento\Framework\Event\Observer as EventObserver;

class Register extends AbstractCustomer
{
    public function execute(EventObserver $observer)
    {
        parent::execute($observer);
    }
}

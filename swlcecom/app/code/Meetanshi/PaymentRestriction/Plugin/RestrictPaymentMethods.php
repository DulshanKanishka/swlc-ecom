<?php

namespace Meetanshi\PaymentRestriction\Plugin;

use Magento\Payment\Model\PaymentMethodList;
use Magento\Checkout\Model\Session;
use Meetanshi\PaymentRestriction\Model\Rule;

class RestrictPaymentMethods
{
    protected $rule;
    protected $checkoutSession;
    protected $allRules = null;

    public function __construct(Session $checkoutSession, Rule $rule)
    {
        $this->checkoutSession = $checkoutSession;
        $this->rule = $rule;
    }

    public function afterGetActiveList(PaymentMethodList $subject, $result)
    {
        $methods = $result;
        $checkoutsession = $this->checkoutSession;
        $quote = $checkoutsession->getQuote();

        $address = $quote->getShippingAddress();
        foreach ($methods as $k => $method) {
            foreach ($this->getAllRules($address) as $rule) {
                if ($rule->restrictPayment($method)) {
                    if ($rule->validate($address)) {
                        unset($methods[$k]);
                    }
                }
            }
        }
        return $methods;
    }

    public function getAllRules($address)
    {
        if ($this->allRules === null) {
            $this->allRules = $this->rule->getCollection()->shippingAddressFilter($address)->load();
            foreach ($this->allRules as $rule) {
                $rule->afterLoad();
            }
        }
        return $this->allRules;
    }
}

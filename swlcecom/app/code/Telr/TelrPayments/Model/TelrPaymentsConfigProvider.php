<?php

namespace Telr\TelrPayments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class TelrPaymentsConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "telr_telrpayments";

    protected $method;

    public function __construct(
        PaymentHelper $paymentHelper
    ) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'telr_telrpayments' => [
                    'redirectUrl' => $this->getRedirectUrl()
                ]
            ]
        ] : [];
    }

    protected function getRedirectUrl()
    {
        return $this->method->getRedirectUrl();
    }
}

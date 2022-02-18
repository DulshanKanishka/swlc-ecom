<?php

namespace Telr\TelrPayments\Controller\Standard;

class Cancel extends \Telr\TelrPayments\Controller\TelrPayments {

    public function execute() {
        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->getTelrHelper()->getUrl('checkout')
        );
    }

}

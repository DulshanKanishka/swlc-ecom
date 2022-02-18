<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_StorePickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Observer;
use Magento\Framework\Event\ObserverInterface;
use Exception;

class PaymentActive implements ObserverInterface
{

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @codeCoverageIgnore
     */
    protected $checkoutSession;

    /**
     * @param \Magestore\Storepickup\Helper\Data
     * @codeCoverageIgnore
     */
    protected $helperData;

    public function __construct (
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magestore\Storepickup\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
    }
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $event           = $observer->getEvent();
        $method          = $event->getMethodInstance();
        $result          = $event->getResult();
        $quote = $this->checkoutSession->getQuote();
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        if ($shippingMethod && $shippingMethod == 'storepickup_storepickup') {
            $isAllMethods = $this->helperData->isAllowSpecificPayments();
            if (intval($isAllMethods) == 1) {
                $allowMethodKeys = $this->helperData->getSelectpaymentmethod();
                $allowMethodKeys = explode(',', $allowMethodKeys);
                if (!count($allowMethodKeys))
                    return $this;
                if (!in_array($method->getCode(), $allowMethodKeys)) {
                    $result->setData( 'is_available', false);
                }
            }
        }
        return $this;
    }
}
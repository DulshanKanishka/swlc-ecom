<?php
namespace Magenest\AbandonedCart\Plugin;

class CheckoutPageCoupon
{
    public function beforeSet(\Magento\Quote\Model\CouponManagement $couponManagement,$cartId, $couponCode){
        $objectManager     = \Magento\Framework\App\ObjectManager::getInstance();
        $helperData        = $objectManager->get(\Magenest\AbandonedCart\Helper\Data::class);
        $considered_coupon = $helperData->getConfig('abandonedcart/setting/considered_coupon');
        if ($considered_coupon == 1 && $couponCode && $logContent = $this->checkCouponCode($couponCode)) {
            $customerId = $helperData->getCustomerId();
            if ($customerId == null) {
                $couponCode = 'abandonedcart-checkapply-coupon';
                $couponManagement->set($cartId,$couponCode);
            }else{
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer = $objectManager->create(\Magento\Customer\Model\Customer::class)->load($customerId);
                $customerEmail = $customer->getEmail();
                $address = $logContent->getData('recipient_adress');
                if($customerEmail != $address){
                    $couponCode = 'abandonedcart-checkapply-coupon';
                    $couponManagement->set($cartId,$couponCode);
                }
            }
        }
    }
    public function checkCouponCode($code)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logContent    = $objectManager->create(\Magenest\AbandonedCart\Model\LogContent::class);
        $collection    = $logContent->getCollection();
        /** @var \Magenest\AbandonedCart\Model\LogContent $logContent */
        foreach ($collection as $logContent) {
            $couponData = json_decode($logContent->getCouponCode(), true);
            if (is_array($couponData) && in_array($code, $couponData)) {
                return $logContent;
            }
        }
        return false;
    }
}
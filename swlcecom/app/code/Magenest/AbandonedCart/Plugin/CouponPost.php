<?php
namespace Magenest\AbandonedCart\Plugin;


use Magento\Framework\Exception\LocalizedException;

class CouponPost {
    public function beforeExecute(\Magento\Checkout\Controller\Cart\CouponPost $couponPost){
        $couponCode        = $couponPost->getRequest()->getParam('remove') == 1
            ? ''
            : trim($couponPost->getRequest()->getParam('coupon_code'));
        $objectManager     = \Magento\Framework\App\ObjectManager::getInstance();
        $helperData        = $objectManager->get(\Magenest\AbandonedCart\Helper\Data::class);
        $considered_coupon = $helperData->getConfig('abandonedcart/setting/considered_coupon');
        if ($considered_coupon == 1 && $couponCode && $logContent = $this->checkCouponCode($couponCode)) {
            $customerId = $helperData->getCustomerId();
            if ($customerId == null) {
                $messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
                $messageManager->addErrorMessage(__("The coupon code isn't valid. Verify the code and try again."));
                $couponPost->getRequest()->setParams(['coupon_code' => '']);
            }else{
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer = $objectManager->create(\Magento\Customer\Model\Customer::class)->load($customerId);
                $customerEmail = $customer->getEmail();
                $address = $logContent->getData('recipient_adress');
                if($customerEmail != $address){
                    $messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
                    $messageManager->addErrorMessage(__("The coupon code isn't valid. Verify the code and try again."));
                    $couponPost->getRequest()->setParams(['coupon_code' => '']);
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
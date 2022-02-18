<?php
namespace Magestore\Storepickup\Model\Order;

use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Class CustomerManagement
 */
class CustomerManagement extends \Magento\Sales\Model\Order\CustomerManagement
{
    /**
     * {@inheritdoc}
     */
    public function create($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerId()) {
            throw new AlreadyExistsException(__("This order already has associated customer account"));
        }
        $customerData = $this->objectCopyService->copyFieldsetToTarget(
            'order_address',
            'to_customer',
            $order->getBillingAddress(),
            []
        );
        $addresses = $order->getAddresses();
        $carrierCode = $order->getShippingMethod(true)->getCarrierCode();
        foreach ($addresses as $address) {
            if($carrierCode=="storepickup" && $address->getAddressType() == QuoteAddress::ADDRESS_TYPE_SHIPPING)
                continue;
            $addressData = $this->objectCopyService->copyFieldsetToTarget(
                'order_address',
                'to_customer_address',
                $address,
                []
            );
            /** @var \Magento\Customer\Api\Data\AddressInterface $customerAddress */
            $customerAddress = $this->addressFactory->create(['data' => $addressData]);
            switch ($address->getAddressType()) {
                case QuoteAddress::ADDRESS_TYPE_BILLING:
                    $customerAddress->setIsDefaultBilling(true);
                    break;
                case QuoteAddress::ADDRESS_TYPE_SHIPPING:
                    $customerAddress->setIsDefaultShipping(true);
                    break;
            }

            if (is_string($address->getRegion())) {
                /** @var \Magento\Customer\Api\Data\RegionInterface $region */
                $region = $this->regionFactory->create();
                $region->setRegion($address->getRegion());
                $region->setRegionCode($address->getRegionCode());
                $region->setRegionId($address->getRegionId());
                $customerAddress->setRegion($region);
            }
            $customerData['addresses'][] = $customerAddress;
        }

        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->customerFactory->create(['data' => $customerData]);
        $account = $this->accountManagement->createAccount($customer);
        $order->setCustomerId($account->getId());
        $this->orderRepository->save($order);
        return $account;
    }
}

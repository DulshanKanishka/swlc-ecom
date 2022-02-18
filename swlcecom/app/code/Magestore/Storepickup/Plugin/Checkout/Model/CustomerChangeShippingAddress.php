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
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Plugin\Checkout\Model;

class CustomerChangeShippingAddress extends \Magento\Checkout\Model\ShippingInformationManagement{

    
    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @codeCoverageIgnore
     */
    protected $_checkoutSession;

    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeCollection;    

    public function __construct(
		\Magento\Checkout\Model\Session $checkoutSession,
        \Magestore\Storepickup\Model\StoreFactory $storeCollection,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteAddressValidator $addressValidator,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
    ) {
		$this->_checkoutSession = $checkoutSession;
        $this->_storeCollection = $storeCollection;		
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->totalsCollector = $totalsCollector;
    }

    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $address = $addressInformation->getShippingAddress();
        $carrierCode = $addressInformation->getShippingCarrierCode();
        $methodCode = $addressInformation->getShippingMethodCode();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $this->validateQuote($quote);

        $saveInAddressBook = $address->getSaveInAddressBook() ? 1 : 0;
        $sameAsBilling = $address->getSameAsBilling() ? 1 : 0;
        $customerAddressId = $address->getCustomerAddressId();
        $this->addressValidator->validate($address);
        $quote->setShippingAddress($address);
        $address = $quote->getShippingAddress();

        if ($customerAddressId) {
            $addressData = $this->addressRepository->getById($customerAddressId);
            $address = $quote->getShippingAddress()->importCustomerAddressData($addressData);
        }
        $billingAddress = $addressInformation->getBillingAddress();
        if ($billingAddress) {
            $quote->setBillingAddress($billingAddress);
        }

        $address->setSaveInAddressBook($saveInAddressBook);
        $address->setSameAsBilling($sameAsBilling);
        $address->setCollectShippingRates(true);

        if (!$address->getCountryId()) {
            throw new StateException(__('Shipping address is not set'));
        }

        $address->setShippingMethod($carrierCode . '_' . $methodCode);

        try {
            $this->totalsCollector->collectAddressTotals($quote, $address);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to save address. Please, check input data.'));
        }

        if (!$address->getShippingRateByCode($address->getShippingMethod())) {
            throw new NoSuchEntityException(
                __('Carrier with such method not found: %1, %2', $carrierCode, $methodCode)
            );
        }

        if (!$quote->validateMinimumAmount($quote->getIsMultiShipping())) {
            throw new InputException($this->scopeConfig->getValue(
                'sales/minimum_order/error_message',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ));
        }
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
		if($addressInformation->getShippingMethodCode()=="storepickup" && $customerSession->isLoggedIn()){
            $storepickup_session = $this->_checkoutSession->getData('storepickup_session');
            $datashipping = array();
            $storeId = $storepickup_session['store_id'];
            $collectionstore = $this->_storeCollection->create();
            $store = $collectionstore->load($storeId, 'storepickup_id');
            $datashipping['firstname'] = __('Store 123123');
            $datashipping['lastname'] = $store->getData('store_name');
            $datashipping['street'][0] = $store->getData('address');
            $datashipping['city'] = $store->getCity();
            $datashipping['region'] = $store->getState();
            $datashipping['postcode'] = $store->getData('zipcode');
            $datashipping['country_id'] = $store->getData('country_id');
            $datashipping['company'] = '';
            if ($store->getFax()) {
                $datashipping['fax'] = $store->getFax();
            } else {
                unset($datashipping['fax']);
            }

            if ($store->getPhone()) {
                $datashipping['telephone'] = $store->getPhone();
            } else {
                unset($datashipping['telephone']);
            }

            $datashipping['save_in_address_book'] = 0;
			$address->setSameAsBilling(0);
            $address->addData($datashipping);

            try {
                $address->save();
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                throw new InputException(__('Unable to save shipping information. Please, check input data.'));
            }
        }

        /** @var \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
        return $paymentDetails;
    }

}

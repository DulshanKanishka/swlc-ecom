<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Helper;

/**
 * Class QuoteOrder
 * @package Magenest\QuickBooksOnline\Helper
 */
class QuoteOrder
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_product;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formkey;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    protected $orderService;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Rate
     */
    protected $shippingRate;

    /**
     * @var
     */
    protected $orederFactory;

    /**
     * QuoteOrder constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Magento\Framework\Data\Form\FormKey $formkey
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Shipping\Model\Config $_shippingConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Quote\Model\Quote\Address\Rate $shippingRate
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Shipping\Model\Config $_shippingConfig,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->_shippingConfig= $_shippingConfig;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->shippingRate = $shippingRate;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Create new order
     * @param $orderData
     * @return int
     */
    public function createOrder($orderData)
    {
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        //set customer
        $customer=$this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);
        //check the customer
        if (!$customer->getEntityId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderData['shipping_address']['firstname'])
                ->setLastname($orderData['shipping_address']['lastname'])
                ->setEmail($orderData['email'])
                ->setPassword($orderData['email']);
            $customer->save();
        }
        //create quote
        $cart_id = $this->cartManagementInterface->createEmptyCart();
        $cart = $this->cartRepositoryInterface->get($cart_id);
        $cart->setStore($store);
        $customer= $this->customerRepository->getById($customer->getEntityId());
        $cart->setCurrency();
        $cart->assignCustomer($customer);
        foreach ($orderData['items'] as $item) {
            $product = $this->_product->create()->load($item['product_id']);
            $cart->addProduct($product, intval($item['qty']));
        }
        if (isset($orderData['billing_address'])) {
            $cart->getBillingAddress()->addData($orderData['billing_address']);
        }
        if (isset($orderData['shipping_address'])) {
            $cart->getShippingAddress()->addData($orderData['shipping_address']);
        }

        // Collect Rates and Set Shipping & Payment Method
        $this->shippingRate
            ->setCode('customname_customname')
            ->setPrice($orderData['shipping_amount']);
        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('customname_customname'); //shipping method
        $cart->getShippingAddress()->addShippingRate($this->shippingRate);
        $cart->setPaymentMethod('checkmo'); //payment method
        $cart->setInventoryProcessed(false);
        $cart->save();
        $cart->getPayment()->importData(['method' => 'checkmo']);

        $cart->collectTotals();
        // save cart to quote
        $cart->save();

        if (isset($orderData['shipping_address'])) {
            $modeShipping = $cart->getShippingAddress();
            $modeShipping->setTaxAmount($orderData['tax']);
            $modeShipping->setBaseTaxAmount($orderData['tax']);
            $modeShipping->setSubtotalInclTax($modeShipping->getSubtotal() + $orderData['tax']);
            $modeShipping->setGrandTotal($modeShipping->getGrandTotal() + $orderData['tax']);
            $modeShipping->setBaseGrandTotal($modeShipping->getBaseGrandTotal() + $orderData['tax']);
            $modeShipping->save();
        } elseif (isset($orderData['billing_address'])) {
            $modeBilling = $cart->getBillingAddress();
            $modeBilling->setTaxAmount($orderData['tax']);
            $modeBilling->setBaseTaxAmount($orderData['tax']);
            $modeBilling->setSubtotalInclTax($modeBilling->getSubtotal() + $orderData['tax']);
            $modeBilling->setGrandTotal($modeBilling->getSubtotal() + $orderData['tax']);
            $modeBilling->setBaseGrandTotal($modeBilling->getSubtotal() + $orderData['tax']);
            $modeBilling->save();
        }
        $cart = $this->cartRepositoryInterface->get($cart->getId());
        // create order
        $orderId = $this->cartManagementInterface->placeOrder($cart->getId());
        return $orderId;
    }
}

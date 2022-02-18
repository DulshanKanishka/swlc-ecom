<?php

namespace Magenest\AbandonedCart\Controller\Track;

class Restore extends \Magenest\AbandonedCart\Controller\Track
{
    /** @var \Magento\Framework\Encryption\Encryptor $_encryptor */
    protected $_encryptor;

    /** @var \Magento\Customer\Model\CustomerFactory $customerFactory */
    protected $customerFactory;

    /** @var \Magento\Quote\Api\CartRepositoryInterface $cartRepository */
    protected $cartRepository;

    /** @var \Magenest\AbandonedCart\Model\AbandonedCartFactory $_abandonedCartFactory */
    protected $_abandonedCartFactory;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /**
     * Restore constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory,
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
    ) {
        parent::__construct($context, $checkoutSession, $customerSession);
        $this->_encryptor            = $encryptor;
        $this->customerFactory       = $customerFactory;
        $this->cartRepository        = $cartRepository;
        $this->_abandonedCartFactory = $abandonedCartFactory;
        $this->_logContentFactory    = $logContentFactory;
    }

    public function execute()
    {
        $resumeRequest    = $this->getRequest()->getParam('utc');
        $userAutoLoginKey = \Magenest\AbandonedCart\Model\Cron::base64UrlDecode($this->getRequest()->getParam('u'));
        $cartId           = $resumeRequest;

        $quote = $this->_objectManager->create('Magento\Quote\Model\Quote')->load($cartId);
        if (!$this->checkoutSession) {
            $this->checkoutSession = $this->_objectManager->create('\Magento\Checkout\Model\Session');
        }
        if ($quote->getReservedOrderId()) {
            $quote = $this->_objectManager->create('Magento\Quote\Model\Quote')->merge($quote);
            if ($this->checkoutSession->getQuote()) {
                $this->checkoutSession->getQuote()->merge($quote);
                $this->cartRepository->save($this->checkoutSession->getQuote());
                $quote = $this->checkoutSession->getQuote();
            } else {
                $this->cartRepository->save($quote);
            }
        }
        if ($userAutoLoginKey) {
            if (!$this->customerSession->isLoggedIn()) {
                $customerKey   = $this->_encryptor->decrypt($userAutoLoginKey);
                $customerId    = substr($customerKey, 0, 1);
                $customerEmail = substr($customerKey, 1);
                $customer      = $this->customerFactory->create()->load($customerId);
                if ($customer->getId() && $customer->getEmail() === $customerEmail) {
                    $this->customerSession->setCustomerAsLoggedIn($customer);
                }
            }
        }

        $logId = $this->getRequest()->getParam('l');
        if ($logId) {
            $logContent = $this->_logContentFactory->create()->load($logId);
            $logContent->setIsRestore(1)->save();
        }
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        $this->checkoutSession->replaceQuote($quote);
        $resultRedirect->setPath('checkout/cart/index');
        return $resultRedirect;
    }
}
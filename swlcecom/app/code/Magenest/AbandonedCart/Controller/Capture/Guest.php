<?php

namespace Magenest\AbandonedCart\Controller\Capture;

use Magenest\AbandonedCart\Model\GuestCapture;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;

class Guest extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    protected $checkoutSession;

    /** @var \Magenest\AbandonedCart\Model\GuestCaptureFactory $guestFactory */
    protected $guestFactory;

    /**
     * Guest constructor.
     *
     * @param Session $session
     * @param \Magenest\AbandonedCart\Model\GuestCaptureFactory $captureFactory
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Magenest\AbandonedCart\Model\GuestCaptureFactory $captureFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->checkoutSession = $session;
        $this->guestFactory    = $captureFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['email'])) {
            $email    = $params['email'];
            $quoteId  = $this->checkoutSession->getQuoteId();
            $customer = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Customer\Model\Session::class);
            if ($customer->isLoggedIn()) {
                $type = 'customer';
            } else {
                $type = 'guest';
            }
            /** @var \Magenest\AbandonedCart\Model\GuestCapture $guestModel */
            $guestModel = $this->guestFactory->create()->load($quoteId, 'quote_id');
            $guestModel->addData(['email' => $email, 'quote_id' => $quoteId, 'type' => $type])->save();
        }
    }
}
<?php

namespace Magenest\AbandonedCart\Controller;

abstract class Track extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    protected $checkoutSession;

    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;

    /** @var \Magento\Framework\UrlInterface $_urlBuilder */
    protected $_urlBuilder;

    /**
     * Track constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_urlBuilder     = $context->getUrl();
        parent::__construct($context);
    }
}
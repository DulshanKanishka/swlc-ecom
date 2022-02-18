<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Redirect
 */
class Redirect extends \Magento\Framework\App\Action\Action
{

    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var
     */
    protected $logger;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param ResultFactory $resultRedirect
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        ResultFactory $resultRedirect,
        Session $checkoutSession,
        Logger $logger
    ) {
        $this->resultRedirect = $resultRedirect;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        return parent::__construct($context);
    }

    /**
     * Default execute function.
     *
     * @return ResultFactory
     */
    public function execute()
    {
        $url = $this->checkoutSession->getPaymentURL();
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        if ($url) {
            $resultRedirect->setUrl($url);
        } else {
            $this->logger->debug(['redirect_url'=>'session does not have redirect url']);
            $resultRedirect->setPath('checkout');
        }
        $this->checkoutSession->unsPaymentURL();
        return $resultRedirect;
    }
}

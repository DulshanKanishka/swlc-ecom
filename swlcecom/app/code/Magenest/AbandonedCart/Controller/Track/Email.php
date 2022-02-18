<?php

namespace Magenest\AbandonedCart\Controller\Track;

use Magenest\AbandonedCart\Model\LogContent;

class Email extends \Magenest\AbandonedCart\Controller\Track
{
    /** @var \Magento\Framework\Encryption\EncryptorInterface $_encryptor */
    protected $_encryptor;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /**
     * Email constructor.
     *
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_encryptor         = $encryptor;
        $this->_logContentFactory = $logContentFactory;
        parent::__construct($context, $checkoutSession, $customerSession);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $logContentId = $this->_encryptor->decrypt(\Magenest\AbandonedCart\Model\Cron::base64UrlDecode($id));
            $logContent   = $this->_logContentFactory->create()->load($logContentId);
            if ($logContent->getId()) {
                $logContent->setOpened(1)->save();
            }
        }
        $this->getResponse()->setHeader('Content-Type', 'image/jpg', true);
    }
}
<?php

namespace Magenest\AbandonedCart\Controller\Unsubscribe;

use Magento\Framework\App\Action\Context;
use Magenest\AbandonedCart\Model\Cron as MagenestCron;
use Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus;

class Resubscribe extends \Magento\Framework\App\Action\Action
{
    /** @var \Magenest\AbandonedCart\Model\UnsubscribeFactory $_unsubscribeFactory */
    protected $_unsubscribeFactory;

    /** @var \Magento\Framework\Encryption\Encryptor $_encryptor */
    protected $_encryptor;

    /**
     * Resubscribe constructor.
     *
     * @param \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_unsubscribeFactory = $unsubscribeFactory;
        $this->_encryptor          = $encryptor;
        parent::__construct($context);
    }

    public function execute()
    {
        $email = MagenestCron::base64UrlDecode($this->getRequest()->getParam('e'));
        if ($ruleId = $this->getRequest()->getParam('r')) {
            $ruleId = $this->_encryptor->decrypt(MagenestCron::base64UrlDecode($ruleId));
        } else {
            $ruleId = 0;
        }
        if ($email) {
            $emailCustomer    = $this->_encryptor->decrypt($email);
            $unsubscribeModel = $this->_unsubscribeFactory->create();
            $unsubscribeModel->getResource()->load($unsubscribeModel, $email, 'unsubscriber_email');
            $unsubscribeModel->setUnsubscriberEmail($emailCustomer);
            $unsubscribeModel->setUnsubscriberStatus(UnsubscriberStatus::SUBSCRIBED);
            $unsubscribeModel->setRuleId($ruleId);
            $unsubscribeModel->getResource()->save($unsubscribeModel);
            $this->messageManager->addSuccess('You have successfully re-subscribed our email letters');
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('customer/account/create');
        return $resultRedirect;
    }
}
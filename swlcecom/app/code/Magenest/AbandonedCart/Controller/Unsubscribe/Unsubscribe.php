<?php

namespace Magenest\AbandonedCart\Controller\Unsubscribe;

use Magento\Framework\Encryption\Encryptor;
use Magenest\AbandonedCart\Model\Cron as MagenestCron;
use Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus;
use Magento\Framework\Controller\ResultFactory;

class Unsubscribe extends \Magento\Framework\App\Action\Action
{
    /** @var \Magenest\AbandonedCart\Model\UnsubscribeFactory $_unsubscribeFactory */
    protected $_unsubscribeFactory;

    /** @var  \Magenest\AbandonedCart\Model\LogContentFactory $_logContent */
    protected $_logContentFactory;

    /** @var Encryptor $_encryptor */
    protected $_encryptor;

    /**
     * Unsubscribe constructor.
     *
     * @param \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory
     * @param Encryptor $encryptor
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory,
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_unsubscribeFactory = $unsubscribeFactory;
        $this->_logContentFactory  = $contentFactory;
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
            $unsubscribeModel->getResource()->load($unsubscribeModel, $emailCustomer, 'unsubscriber_email');
            if ($unsubscribeModel->getId()) {
                $unsubscribeModel->setData('unsubscriber_status', UnsubscriberStatus::UNSUBSCRIBED);
            } else {
                $unsubscribeModel->setUnsubscriberEmail($emailCustomer);
                $unsubscribeModel->setData('unsubscriber_status', UnsubscriberStatus::UNSUBSCRIBED);
                $unsubscribeModel->setRuleId($ruleId);
            }
            $this->cancelNotiToCustomer($emailCustomer);
            $unsubscribeModel->save($unsubscribeModel);
            $this->messageManager->addSuccessMessage('You have successfully unsubscribed our email letters');
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('customer/account/create');
        return $resultRedirect;
    }

    public function cancelNotiToCustomer($email)
    {
        $collections = $this->_logContentFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'recipient_adress',
                $email
            )->addFieldToFilter(
                'status',
                \Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_QUEUED
            );
        if (count($collections)) {
            foreach ($collections as $collection) {
                $collection->setStatus(\Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_CANCELLED);
                $collection->setLog(__('Admin change status to unsubscribe email'));
                $collection->save();
            }
        }
    }
}
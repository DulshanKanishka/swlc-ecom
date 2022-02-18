<?php

namespace Magenest\AbandonedCart\Controller\Track;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class Click extends \Magenest\AbandonedCart\Controller\Track
{
    /** @var \Magento\Framework\Encryption\EncryptorInterface $_encryptor */
    protected $_encryptor;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /**
     * Click constructor.
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
            $id   = $this->_encryptor->decrypt(\Magenest\AbandonedCart\Model\Cron::base64UrlDecode($id));
            $mail = $this->_logContentFactory->create()->load($id);
            if ($mail->getId()) {
                $mail->setClicks(1)->save();
                // check cancel conditions send email
                $ruleId          = $mail->getRuleId();
                $abandonedcartId = $mail->getAbandonedcartId();
                $objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
                $ruleModel = $objectManager->create(\Magenest\AbandonedCart\Model\Rule::class)->load($ruleId);

                if ($ruleModel->getCancelRuleWhen()) {
                    $cancel_rule_when = json_decode($ruleModel->getCancelRuleWhen(), true);
                    if (in_array(3, $cancel_rule_when)) {
                        $collections = $this->_logContentFactory->create()->getCollection()
                            ->addFieldToFilter('rule_id', $ruleId)
                            ->addFieldToFilter('abandonedcart_id', $abandonedcartId)
                            ->addFieldToFilter('status', EmailStatus::STATUS_QUEUED);
                        foreach ($collections as $collection) {
                            $opened = $collection->getOpened();
                            if ($opened == 0 || $opened == '') {
                                $collection->addData([
                                    'status' => EmailStatus::STATUS_CANCELLED,
                                    'log'    => 'Link from Email Clicked'
                                ]);
                            } else {
                                $collection->addData([
                                    'status' => EmailStatus::STATUS_CANCELLED,
                                    'log'    => 'Link from Email Clicked'
                                ]);
                            }
                            $collection->save();
                        }
                    }
                }
            }

        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($url = $this->getRequest()->getParam('des')) {
            $url = \Magenest\AbandonedCart\Model\Cron::base64UrlDecode($url);
            $resultRedirect->setUrl($url);
        } else {
            $resultRedirect->setPath('/');
        }
        return $resultRedirect;
    }
}
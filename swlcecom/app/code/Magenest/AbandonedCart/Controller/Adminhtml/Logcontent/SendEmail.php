<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Logcontent;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class SendEmail extends \Magenest\AbandonedCart\Controller\Adminhtml\Logcontent
{
    /** @var  \Magenest\AbandonedCart\Model\LogContentFactory $_logContent */
    protected $_logContentFactory;

    /** @var  \Magenest\AbandonedCart\Helper\SendMail $_sendMailHelper */
    protected $_sendMailHelper;

    /** @var \Magenest\AbandonedCart\Helper\MandrillConnector $_mandrillConnector */
    protected $_mandrillConnector;

    /** @var \Magenest\AbandonedCart\Helper\SendSms $_sendSmsHelper */
    protected $_sendSmsHelper;

    /**
     * SendEmail constructor.
     *
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory
     * @param \Magenest\AbandonedCart\Helper\SendMail $sendMail
     * @param \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
     * @param \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector
     * @param \Magenest\AbandonedCart\Helper\SendSms $sendSms
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magenest\AbandonedCart\Helper\SendMail $sendMail,
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory,
        \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector,
        \Magenest\AbandonedCart\Helper\SendSms $sendSms,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_logContentFactory = $contentFactory;
        $this->_sendMailHelper    = $sendMail;
        $this->_mandrillConnector = $mandrillConnector;
        $this->_sendSmsHelper     = $sendSms;
        parent::__construct($logger, $contentFactory, $registry, $pageFactory, $context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['id']) && $params['id']) {
                $id              = $params['id'];
                $logContentModel = $this->_logContentFactory->create()->load($id);
                if ($logContentModel->getType() == "Email") {
                    if ($this->_mandrillConnector->isEnable()) {
                        $this->_mandrillConnector->sendEmails([$logContentModel]);
                    } else {
                        $this->_sendMailHelper->send($logContentModel);
                    }
                } elseif ($logContentModel->getType() == "SMS") {
                    $respones = $this->_sendSmsHelper->send($logContentModel);
                    if ($respones['messages'][0]['status'] == "0") {
                        $logContentModel->addData(['status' => EmailStatus::STATUS_SENT, 'log' => 'Ok']);
                    } else {
                        $logContentModel->addData(['status' => EmailStatus::STATUS_FAILED, 'log' => 'FAILED']);
                    }
                    $logContentModel->save();
                }

            }
            $this->messageManager->addSuccessMessage(__('Send message successfully!'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
<?php

namespace Magenest\AbandonedCart\Helper;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class MandrillConnector extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLE = 'abandonedcart/mandrill/mandrill_enable';
    const XML_PATH_APIKEY = 'abandonedcart/mandrill/api_key';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    protected $_mailData = [];

    /** @var \Magento\Email\Model\TemplateFactory $templateFactory */
    protected $templateFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
    protected $storeManager;

    /** @var \Magenest\AbandonedCart\Model\BlackListFactory $_blacklistFactory */
    protected $_blacklistFactory;

    /**
     * MandrillConnector constructor.
     *
     * @param \Magento\Email\Model\TemplateFactory $templateFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magenest\AbandonedCart\Model\BlackListFactory $blackListFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magenest\AbandonedCart\Model\BlackListFactory $blackListFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig       = $context->getScopeConfig();
        $this->templateFactory   = $templateFactory;
        $this->storeManager      = $storeManager;
        $this->_blacklistFactory = $blackListFactory;
        parent::__construct($context);
    }

    public function getUserInformation()
    {
        $userInfo = null;
        $apiKey   = $this->scopeConfig->getValue(self::XML_PATH_APIKEY);
        if (!$apiKey) {
            return "No API key.";
        }
        if (!class_exists("Mandrill")) {
            return "Mandrill is not installed, please run \"composer require mandrill/mandrill\" on your server.";
        }
        $mandrill = new \Mandrill($apiKey);
        try {
            $userInfo = $mandrill->users->info();
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
        if ($userInfo == null) {
            $userInfo = "Your API key is incorrect.";
        }
        return $userInfo;
    }

    public function isEnable()
    {
        $enable = $this->scopeConfig->getValue(self::XML_PATH_ENABLE);

        if ($enable === '1') {
            return true;
        } else {
            return false;
        }
    }

    public function sendEmails($mailCollections, $blackListCollection = null)
    {
        $apiKey = $this->scopeConfig->getValue(self::XML_PATH_APIKEY);
        try {
            if (!$apiKey) {
                throw new \Exception('Mandrill is enabled but the API key was not found.');
            }
            $mandrill = new \Mandrill($apiKey);
        } catch (\Exception $exception) {
            foreach ($mailCollections as $mail) {
                $mail->setStatus(EmailStatus::STATUS_QUEUED);
                $mail->setLog($exception->getMessage());
                $mail->save();
            }
            return $this;
        }
        $mailData       = [];
        $dataLogContent = [];
        foreach ($mailCollections as $logContentModel) {
            if ($this->checkInBlacklist($logContentModel->getRecipientAdress(), $blackListCollection)) {
                continue;
            }
            $mailDataKey      = $logContentModel->getRuleId() . ':' . $logContentModel->getTemplateId();
            $dataLogContent[] = $logContentModel;
            if (!isset($mailData[$mailDataKey])) {
                $mailData = $this->prepareTemplate($logContentModel, $mailDataKey, $mailData);
                $mailData = $this->prepareSender($logContentModel, $mailDataKey, $mailData);
                $mailData = $this->prepareSubject($logContentModel, $mailDataKey, $mailData);
                $mailData = $this->prepareAttachments($logContentModel, $mailDataKey, $mailData);
                $mailData = $this->prepareBcc($logContentModel, $mailDataKey, $mailData);
            }
            $mailData[$mailDataKey] = array_merge_recursive($mailData[$mailDataKey], $this->getEmailData($logContentModel, $mailDataKey));
        }

        try {
            if (!empty($mailData) && !empty($dataLogContent)) {
                foreach ($mailData as $message) {
                    $result = $mandrill->messages->send($message);
                }
                if (isset($result[0]['status']) && ($result[0]['status'] == 'sent' || $result[0]['status'] == 'queued')) {
                    foreach ($dataLogContent as $logContentModel) {
                        $now  = new \DateTime();
                        $date = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                        $logContentModel->setSendDate($date);
                        $logContentModel->setStatus(EmailStatus::STATUS_SENT);
                        $logContentModel->setLog('Ok');
                        $logContentModel->save();
                    }
                } else {
                    $errorCode = isset($result[0]['reject_reason']) ? $result[0]['reject_reason'] : 'unable to retrieve error code';
                    throw new \Exception("Mandrill email rejected, error code: " . $errorCode);
                }
            }
        } catch (\Exception $exception) {
            foreach ($dataLogContent as $logContentModel) {
                $logContentModel->setStatus(EmailStatus::STATUS_FAILED);
                $logContentModel->setLog($exception->getMessage());
                $logContentModel->save();
            }
            return $this;
        }
        return $this;
    }

    public function getEmailData(\Magenest\AbandonedCart\Model\LogContent $mail, $mailDataKey)
    {
        $data = [];
        try {
            $data['to'][]   = ['email' => $mail->getData('recipient_adress')];
            $templateVars   = [];
            $templateVars[] = [
                'name'    => 'trackingCode',
                'content' => \Magenest\AbandonedCart\Model\Cron::getTrackingCode($mail->getId())
            ];
            if (!empty($mail->getContextVars())) {
                $contextVars = json_decode($mail->getContextVars(), true);
                foreach ($contextVars as $key => $value) {
                    if (is_string($value)) {
                        $value          = \Magenest\AbandonedCart\Model\Cron::applyClickTracking($value, $mail->getId());
                        $templateVars[] = [
                            'name'    => $key,
                            'content' => $value
                        ];
                    }
                }
            }

            if (!empty($templateVars)) {
                $data['merge_vars'][] = [
                    'rcpt' => $mail->getData('recipient_adress'),
                    'vars' => $templateVars
                ];
            }
        } catch (\Exception $e) {
            $mail->setStatus(EmailStatus::STATUS_FAILED);
            $mail->setLog($e->getMessage());
            $mail->save();
        }
        return $data;
    }

    protected function prepareTemplate($logContentModel, $mailDataKey, $mailData)
    {
        $templateVars = [];
        $template     = $this->templateFactory->create()->load($logContentModel->getTemplateId());
        if (preg_match_all('/{{var(.*?)}}/si', $template->getTemplateText(), $vars, PREG_SET_ORDER)) {
            foreach ($vars as $var) {
                $key                = trim($var[1]);
                $templateVars[$key] = '*|' . $key . '|*';
            }
        }
        $content                        = $template->setVars($templateVars)
            ->setDesignConfig([
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId()
            ])->getProcessedTemplate($templateVars);
        $mailData[$mailDataKey]['html'] = html_entity_decode($content . " *|trackingCode|*");
        return $mailData;
    }

    protected function prepareSender($logContentModel, $mailDataKey, $mailData)
    {
        $_senderResolver                      = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\Email\Model\Template\SenderResolver::class);
        $result                               = $_senderResolver->resolve($this->scopeConfig->getValue('abandonedcart/general/email_identity'));
        $mailData[$mailDataKey]['from_name']  = $result['name'];
        $mailData[$mailDataKey]['from_email'] = $result['email'];
        return $mailData;
    }

    protected function prepareSubject($logContentModel, $mailDataKey, $mailData)
    {
        $mailData[$mailDataKey]['subject'] = $logContentModel->getData('subject');
        return $mailData;
    }

    protected function prepareBcc($logContentModel, $mailDataKey, $mailData)
    {
        if ($logContentModel->getData('bcc_email')) {
            $mailData[$mailDataKey]['to'][] = [
                'email' => $logContentModel->getData('bcc_email'),
                'type'  => 'bcc',
            ];
        }
        return $mailData;
    }

    protected function prepareAttachments($logContentModel, $mailDataKey, $mailData)
    {
        $attachedFiles = json_decode($logContentModel->getData('attachments'), true);

        if (is_array($attachedFiles) && !empty($attachedFiles)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\App\Filesystem\DirectoryList $dir */
            $dir = $objectManager->get('\Magento\Framework\App\Filesystem\DirectoryList');
            /** @var \Magento\Catalog\Model\Product\Media\Config $mediaConfig */
            $mediaConfig = $objectManager->get('Magento\Catalog\Model\Product\Media\Config');
            /** @var \Magento\Framework\Filesystem\Io\File $fileReader */
            $fileReader  = $objectManager->get('\Magento\Framework\Filesystem\Io\File');
            $mediaPath   = $dir->getPath('media');
            $attachments = [];
            $images      = [];
            foreach ($attachedFiles as $attachFileTypes) {
                if (!is_array($attachFileTypes)) {
                    break;
                }
                foreach ($attachFileTypes as $file) {
                    if (!isset($file['file'])) {
                        continue;
                    }
                    $filepath = $mediaPath . '/' . $mediaConfig->getTmpMediaPath($file['file']);
                    $body     = $fileReader->read($filepath);
                    if (!$body) {
                        \Magento\Framework\App\ObjectManager::getInstance()->create('Psr\Log\LoggerInterface')
                            ->critical('Could not read attachment file for mail ' . $logContentModel->getId());
                        continue;
                    }
                    $info = pathinfo($file['file']);
                    if (!isset($info['extension'])) {
                        continue;
                    }
                    switch ($info['extension']) {
                        case 'gif':
                            $type = 'image/gif';
                            break;
                        case 'jpg':
                        case 'jpeg':
                            $type = 'image/jpg';
                            break;
                        case 'png':
                            $type = 'image/png';
                            break;
                        case 'pdf':
                            $type = 'application/pdf';
                            break;
                        case 'txt':
                            $type = 'text/plain';
                            break;
                        default:
                            $type = 'application/octet-stream';
                    }
                    switch ($info['extension']) {
                        case 'gif':
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                            $images[] = [
                                'type'    => $type,
                                'name'    => $file['label'],
                                'content' => base64_encode($body)
                            ];
                            break;
                        default:
                            $attachments[] = [
                                'type'    => $type,
                                'name'    => $file['label'],
                                'content' => base64_encode($body)
                            ];
                    }
                }
            }
        }
        if (isset($images)) {
            $mailData[$mailDataKey]['images'] = $images;
        }
        if (isset($attachments)) {
            $mailData[$mailDataKey]['attachments'] = $attachments;
        }
        return $mailData;
    }

    public function checkInBlacklist($address, $collection)
    {
        if ($collection == null) {
            return false;
        }
        /** @var \Magenest\AbandonedCart\Model\BlackList $blackListModel */
        $blackListModel = $collection->addFieldToFilter('address', $address)->getFirstItem();
        if ($blackListModel == null || !$blackListModel->getId()) {
            return false;
        } else {
            return true;
        }
    }
}
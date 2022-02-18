<?php

namespace Magenest\AbandonedCart\Helper;

use Magenest\AbandonedCart\Model\Mail\TransportBuilder;
use Magento\Framework\App\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem\Io\File;
use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class SendMail extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magenest\AbandonedCart\Model\Mail\TransportBuilder $_transportBuilder */
    protected $_transportBuilder;

    /** @var Data $_helperData */
    protected $_helperData;

    /** @var \Magento\Framework\Translate\Inline\StateInterface $_inlineTranslation */
    protected $_inlineTranslation;

    protected $_vars = [];

    /** @var \Magento\Framework\Encryption\EncryptorInterface $_encryptor */
    protected $_encryptor;

    /** @var \Magento\Store\Model\StoreManagerInterface $_storeManager */
    protected $_storeManager;

    /** @var  \Magento\Framework\Filesystem\Io\File $_file */
    protected $_file;

    /**
     * SendMail constructor.
     *
     * @param TransportBuilder $transportBuilder
     * @param Data $helperData
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param EncryptorInterface $encryptor
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param File $file
     * @param Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\Mail\TransportBuilder $transportBuilder,
        \Magenest\AbandonedCart\Helper\Data $helperData,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_transportBuilder  = $transportBuilder;
        $this->_helperData = $helperData;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_encryptor         = $encryptor;
        $this->_storeManager      = $storeManager;
        $this->_file              = $file;
        parent::__construct($context);
    }

    public function send($abandonedCartLog)
    {
        try {
            $this->sendMail($abandonedCartLog);
            $log    = 'Ok';
            $status = EmailStatus::STATUS_SENT;
            $now    = new \DateTime();
            $date   = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $abandonedCartLog->setSendDate($date);
        } catch (\Exception $exception) {
            $log    = $exception->getMessage();
            $status = EmailStatus::STATUS_FAILED;
        }
        $abandonedCartLog->setStatus($status);
        $abandonedCartLog->setLog($log);
        $abandonedCartLog->save();
    }

    protected function sendMail($abandonedCartLog)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $from       = $this->scopeConfig->getValue('abandonedcart/general/email_identity', $storeScope);
        $this->_transportBuilder->setMessageContent(
            htmlspecialchars_decode($abandonedCartLog->getContent()), $abandonedCartLog->getSubject(), $from
        );
        $attachments = [];

        $attachedFiles = json_decode($abandonedCartLog->getData('attachments'), true);
        if (is_array($attachedFiles) && !empty($attachedFiles)) {
            $objectManager = ObjectManager::getInstance();
            /** @var \Magento\Framework\App\Filesystem\DirectoryList $dir */
            $dir = $objectManager->get('Magento\Framework\App\Filesystem\DirectoryList');
            /** @var \Magento\Catalog\Model\Product\Media\Config $mediaConfig */
            $mediaConfig = $objectManager->get('Magento\Catalog\Model\Product\Media\Config');
            $mediaPath   = $dir->getPath('media');
            foreach ($attachedFiles as $attachFileTypes) {
                if (!is_array($attachFileTypes)) {
                    break;
                }
                if (!isset($attachFileTypes['file'])) {
                    continue;
                }
                $filepath = $mediaPath . '/' . $mediaConfig->getTmpMediaPath($attachFileTypes['file']);
                $body     = $this->_file->read($filepath);
                if (!$body) {

                    \Magento\Framework\App\ObjectManager::getInstance()->create('Psr\Log\LoggerInterface')->debug('Could not read attachment file for mail ' . $this->getId());
                    continue;
                }
                $attachments[] = [
                    'body'  => $body,
                    'name'  => $attachFileTypes['file'],
                    'label' => $attachFileTypes['label']
                ];
            }
        }
        $vars = json_decode($abandonedCartLog->getContextVars(), true);
        $this->_inlineTranslation->suspend();
        $version = $this->_helperData->getVersionMagento();
        if(version_compare($version,'2.2.0') < 0){
            // clear previous data first.
            $this->_transportBuilder->setTemplateOptions(
                [
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $abandonedCartLog->getStoreId(),
                ]
            )->setTemplateVars(
                $vars
            )->addTo(
                $abandonedCartLog->getRecipientAdress(),
                $abandonedCartLog->getRecipientName()
            );
        }else{
            $this->_transportBuilder->setTemplateOptions(
                [
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $abandonedCartLog->getStoreId(),
                ]
            )->setTemplateVars(
                $vars
            )->setFrom(
                $this->scopeConfig->getValue('abandonedcart/general/email_identity', $storeScope)
            )->addTo(
                $abandonedCartLog->getRecipientAdress(),
                $abandonedCartLog->getRecipientName()
            );
        }

        if ($bccMail = $abandonedCartLog->getData('bcc_email')) {
            $this->_transportBuilder->addBcc($bccMail);
        }
        if ($attachments) {
            if (method_exists($this->_transportBuilder->getMessage(), 'createAttachment')) {
                foreach ($attachments as $attachment) {
                    if ($attachment) {
                        $this->_transportBuilder->createAttachment($attachment);
                    }
                }
                $transport = $this->_transportBuilder->getTransport();
            } else {
                $transport = $this->_transportBuilder->getTransport();
                foreach ($attachments as $attachment) {
                    if ($attachment) {
                        $this->_transportBuilder->createAttachment($attachment, $transport);
                    }
                }
            }
        }
        if (!isset($transport)) {
            $transport = $this->_transportBuilder->getTransport();
        }
        try {
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (\Exception $exception) {

            $this->_logger->critical($exception->getMessage());
        }

    }
}
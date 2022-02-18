<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_PdfInvoice
 * @copyright   Copyright (c) 2017-2018 Mageplaza (https://www.mageplaza.com/)
 * @license     http://mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PdfInvoice\Model\Template;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Filesystem;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\PdfInvoice\Helper\Data;
use Zend\Mime\Part;
use Zend_Mime;

/**
 * Class TransportBuilder
 * @package Mageplaza\PdfInvoice\Model\Template
 */
class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var array
     */
    private $messageData = [];

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var AbstractData
     */
    protected $_helperData;

    /**
     * @var Data
     */
    protected $helperPdf;

    /**
     * TransportBuilder constructor.
     *
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param Filesystem $filesystem
     * @param UrlInterface $urlBuilder
     * @param AbstractData $helperData
     * @param Data $helperPdf
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        Filesystem $filesystem,
        UrlInterface $urlBuilder,
        AbstractData $helperData,
        Data $helperPdf
    ) {
        $this->_filesystem = $filesystem;
        $this->_urlBuilder = $urlBuilder;
        $this->_helperData = $helperData;
        $this->helperPdf   = $helperPdf;

        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
    }

    /**
     * @param $content
     * @param string $mimeType
     * @param string $disposition
     * @param string $encoding
     * @param string $filename
     *
     * @return $this|Part
     */
    public function addAttachment(
        $content,
        $mimeType = Zend_Mime::TYPE_OCTETSTREAM,
        $disposition = Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = Zend_Mime::ENCODING_BASE64,
        $filename = 'pdf_invoice.pdf'
    ) {
        if ($this->_helperData->versionCompare('2.2.8')) {
            $attachment              = new Part($content);
            $attachment->type        = $mimeType;
            $attachment->disposition = $disposition;
            $attachment->encoding    = $encoding;
            $attachment->filename    = $filename;

            return $attachment;
        }

        $this->message->createAttachment(
            $content,
            $mimeType,
            $disposition,
            $encoding,
            $filename
        );

        return $this;
    }

    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addTo($address, $name = '')
    {
        if ($this->_helperData->versionCompare('2.3.3')) {
            $this->addAddressByType('to', $address, $name);
        } else {
            parent::addTo($address, $name);
        }

        return $this;
    }

    /**
     * Add cc address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addCc($address, $name = '')
    {
        if ($this->_helperData->versionCompare('2.3.3')) {
            $this->addAddressByType('cc', $address, $name);
        } else {
            parent::addCc($address, $name);
        }

        return $this;
    }

    /**
     * Add bcc address
     *
     * @param array|string $address
     *
     * @return $this
     */
    public function addBcc($address)
    {
        if ($this->_helperData->versionCompare('2.3.3')) {
            $this->addAddressByType('bcc', $address);
        } else {
            parent::addBcc($address);
        }

        return $this;
    }

    /**
     * Handles possible incoming types of email (string or array)
     *
     * @param string $addressType
     * @param string|array $email
     * @param string|null $name
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function addAddressByType($addressType, $email, $name = null)
    {
        $addressConverter = $this->objectManager->create('Magento\Framework\Mail\AddressConverter');

        if (is_string($email)) {
            $this->messageData[$addressType][] = $addressConverter->convert($email, $name);

            return;
        }

        $convertedAddressArray = $addressConverter->convertMany($email);

        if (isset($this->messageData[$addressType])) {
            $this->messageData[$addressType] = array_merge(
                $this->messageData[$addressType],
                $convertedAddressArray
            );
        }
    }

    /**
     * @param array|string $from
     * @param null $scopeId
     *
     * @return $this|\Magento\Framework\Mail\Template\TransportBuilder
     * @throws MailException
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $result = $this->_senderResolver->resolve($from, $scopeId);

        if ($this->_helperData->versionCompare('2.3.3')) {
            $this->addAddressByType('from', $result['email'], $result['name']);
        } else {
            parent::setFromByScope($from, $scopeId = null);
        }

        return $this;
    }

    /**
     * Reset object state
     *
     * @return $this
     */
    protected function reset()
    {
        $this->messageData        = [];
        $this->templateIdentifier = null;
        $this->templateVars       = null;
        $this->templateOptions    = null;

        return $this;
    }

    /**
     * @return $this|\Magento\Framework\Mail\Template\TransportBuilder
     * @throws LocalizedException
     */
    public function prepareMessage()
    {
        if ($this->_helperData->versionCompare('2.3.3')) {
            $objectManager = ObjectManager::getInstance();
            $coreSession   = $objectManager->get(SessionManagerInterface::class);
            $coreSession->start();
            $attachPdf = $coreSession->getAttachPdf();

            if (!$this->helperPdf->checkEmailAttachmentsIsEnable()) {
                $coreSession->unsAttachPdf();
            }

            $template = $this->getTemplate();
            $content  = $template->processTemplate();

            $mimePartInterfaceFactory     = $objectManager->get(MimePartInterfaceFactory::class);
            $mimeMessageInterfaceFactory  = $objectManager->get(MimeMessageInterfaceFactory::class);
            $emailMessageInterfaceFactory = $objectManager->get(EmailMessageInterfaceFactory::class);

            switch ($template->getType()) {
                case TemplateTypesInterface::TYPE_TEXT:
                    $part['type'] = MimeInterface::TYPE_TEXT;
                    break;

                case TemplateTypesInterface::TYPE_HTML:
                    $part['type'] = MimeInterface::TYPE_HTML;
                    break;

                default:
                    throw new LocalizedException(
                        new Phrase('Unknown template type')
                    );
            }

            $mimePart = $mimePartInterfaceFactory->create(['content' => $content]);

            if (is_object($attachPdf) && !$this->helperPdf->checkEmailAttachmentsIsEnable()) {
                $this->messageData['body'] = $mimeMessageInterfaceFactory->create(
                    ['parts' => [$mimePart, $attachPdf]]
                );
            } else {
                $this->messageData['body'] = $mimeMessageInterfaceFactory->create(
                    ['parts' => [$mimePart]]
                );
            }

            $this->messageData['subject'] = html_entity_decode((string)$template->getSubject(), ENT_QUOTES);
            $this->message = $emailMessageInterfaceFactory->create($this->messageData);

            return $this;
        }

        return parent::prepareMessage();
    }
}

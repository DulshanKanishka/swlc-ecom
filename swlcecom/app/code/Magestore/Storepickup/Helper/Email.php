<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
/**
 * Helper Data.
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ADMIN_EMAIL_IDENTITY = "trans_email/ident_general";
    const XML_PATH_ADMIN_EMAIL_GENERAL_NAME = 'trans_email/ident_general/name';
    const XML_PATH_SALES_EMAIL_IDENTITY = "trans_email/ident_sales";
    const XML_PATH_SALES_EMAIL_IDENTITY_NAME = "trans_email/ident_sales/name";
    const XML_PATH_NEW_ORDER_TO_ADMIN_EMAIL = 'carriers/storepickup/shopadmin_email_template';
    const XML_PATH_NEW_ORDER_TO_STORE_OWNER_EMAIL = 'carriers/storepickup/storeowner_email_template';
    const XML_PATH_SEND_EMAIL_CUSTOMER_TO_ADMIN = 'carriers/storepickup/storeowner_email_customer';
    const XML_PATH_STATUS_ORDER_TO_STORE_OWNER_EMAIL = 'carriers/storepickup/storeowner_email_change_status';
    const TEMPLATE_ID_NONE_EMAIL = 'none_email';
    const SECTION_CONFIG_STOREPICKUP = 'storepickup';


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelperData,
        //ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        //\Magestore\Storepickup\Model\MessageFactory $messageFactory,
        \Magestore\Storepickup\Helper\Data $storePickupHelper
    )
    {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_paymentHelperData = $paymentHelperData;
        $this->_addressRenderer = $addressRenderer;
        $this->_objectManager = $objectManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_priceCurrency = $priceCurrency;
        //$this->_scopeConfig = $scopeConfig;
        $this->_storePickupHelper = $storePickupHelper;
        //$this->_messageFactory = $messageFactory;
    }

    protected function getPaymentHtml(Order $order, $storeId)
    {
        return $this->_paymentHelperData->getInfoBlockHtml(
            $order->getPayment(),
            $storeId
        );
    }

    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? NULL
            : $this->_addressRenderer->format($order->getShippingAddress(), 'html');
    }

    protected function getFormattedBillingAddress($order)
    {
        return $this->_addressRenderer->format($order->getBillingAddress(), 'html');
    }

    public function getConfig($path)
    {
        $storeId = $this->_storeManager->getStore()->getId();

        return $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


    public function sendNoticeEmailToAdmin($order) {

        $storeId = $order->getStore()->getId();

        $this->inlineTranslation->suspend();

        $template = $this->getConfig(self::XML_PATH_NEW_ORDER_TO_ADMIN_EMAIL);
        if ($template === self::TEMPLATE_ID_NONE_EMAIL) {
            return $this;
        }
        $sendTo = array(
            $this->getConfig(self::XML_PATH_ADMIN_EMAIL_IDENTITY),
        );

        foreach ($sendTo as $recipient) {
            try {
                $transport = $this->_transportBuilder->setTemplateIdentifier(
                    $template
                )->setTemplateOptions(
                    ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
                )->setTemplateVars(
                    [
                        'order' => $order,
                        'billing' => $order->getBillingAddress(),
                        'payment_html' => $this->getPaymentHtml($order, $storeId),
                        'store' => $order->getStore(),
                        'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                        'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                    ]
                )->setFrom(
                    [
                        'email' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'name' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY_NAME,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                    ]
                )->addTo(
                    $recipient['email'],
                    $recipient['name']
                )->getTransport();
                $transport->sendMessage();
            } catch (\Magento\Framework\Exception\MailException $ex) {

            }
        }
        $this->inlineTranslation->resume();

        return $this;
    }

    public function sendEmailtoAdmin($id_message) {
        $storeId = $this->_storeManager->getStore()->getId();

        $template_id = $this->getConfig(self::XML_PATH_SEND_EMAIL_CUSTOMER_TO_ADMIN);
        if ($template_id === self::TEMPLATE_ID_NONE_EMAIL) {
            return ;
        }

        $this->inlineTranslation->suspend();
        $information_sender = $this->_messageFactory->create()->load($id_message);
        $email_sender = $information_sender->getEmail();
        $name_sender = $information_sender->getName();
        $mailSubject = "Email from Customer";
        $sender = array(
            'name' => $name_sender,
            'email' => $email_sender,
        );
        $message = $information_sender->getMessage();
        $sendTo = array(
            $this->getConfig(self::XML_PATH_ADMIN_EMAIL_IDENTITY),
        );

        foreach ($sendTo as $item) {
            $email_contact = $item['email'];
            $name_contact = $item['name'];
            try {
                $transport = $this->_transportBuilder->setTemplateIdentifier(
                    $template_id
                )->setTemplateOptions(
                    ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
                )->setTemplateVars(
                    [
                        'message' => $message,
                        'email_sender' => $email_sender,
                        'name_sender' => $name_sender,
                        'name_contact' => $name_contact,
                    ]
                )->setFrom(
                    [
                        'email' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'name' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY_NAME,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                    ]
                )->addTo(
                    $email_contact,
                    $name_contact
                )->getTransport();
                $transport->sendMessage();
            } catch (\Magento\Framework\Exception\MailException $ex) {

            }
        }
        $this->inlineTranslation->resume();

    }


    public function sendNoticeEmailToStoreOwner($order,$store) {
        $storeId = $order->getStore()->getId();
        $this->inlineTranslation->suspend();

        $template = $this->getConfig(self::XML_PATH_NEW_ORDER_TO_STORE_OWNER_EMAIL);
        if ($template === self::TEMPLATE_ID_NONE_EMAIL) {
            return $this;
        }
        $sendTo = array(
            array(
                'name' => "nhagg",
                'email' => $store->getData('email')
            ),
        );

        foreach ($sendTo as $recipient) {
            try {
                $transport = $this->_transportBuilder->setTemplateIdentifier(
                    $template
                )->setTemplateOptions(
                    ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
                )->setTemplateVars(
                    [
                        'order' => $order,
                        'billing' => $order->getBillingAddress(),
                        'payment_html' => $this->getPaymentHtml($order, $storeId),
                        'store' => $order->getStore(),
                        'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                        'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                    ]
                )->setFrom(
                    [
                        'email' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'name' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY_NAME,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                    ]
                )->addTo(
                    $recipient['email'],
                    $recipient['name']
                )->getTransport();
                $transport->sendMessage();
            } catch (\Magento\Framework\Exception\MailException $ex) {

            }
        }

        $this->inlineTranslation->resume();

        return $this;
    }


    public function sendEmailtoStoreOwner($id_message, $id_store) {
        $storeId = $this->_storeManager->getStore()->getId();
        $template_id =$this->getConfig(self::XML_PATH_SEND_EMAIL_CUSTOMER_TO_ADMIN);
        if ($template_id === self::TEMPLATE_ID_NONE_EMAIL) {
            return;
        }
        $this->inlineTranslation->suspend();
        $information_sender = 'meesage';
        $email_sender = $information_sender->getEmail();
        $name_sender = $information_sender->getName();
        $mailSubject = "Email from Customer";
        $sender = array(
            'name' => $name_sender,
            'email' => $email_sender,
        );
        $message = $information_sender->getMessage();
        $imforSoter = 'message';
        $email_contact = $imforSoter->getStoreEmail();
        $name_contact = $imforSoter->getStoreName();
        $vars = Array(
            'message' => $message,
            'email_sender' => $email_sender,
            'name_sender' => $name_sender,
            'name_contact' => $name_contact,
        );

        try {
            $transport = $this->_transportBuilder->setTemplateIdentifier(
                $template_id
            )->setTemplateOptions(
                ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
            )->setTemplateVars(
                [
                    'message' => $message,
                    'email_sender' => $email_sender,
                    'name_sender' => $name_sender,
                    'name_contact' => $name_contact,
                ]
            )->setFrom(
                [
                    'email' => $this->scopeConfig->getValue(
                        self::XML_PATH_SALES_EMAIL_IDENTITY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    'name' => $this->scopeConfig->getValue(
                        self::XML_PATH_SALES_EMAIL_IDENTITY_NAME,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                ]
            )->addTo(
                $email_contact,
                $name_contact
            )->getTransport();
            $transport->sendMessage();
        } catch (\Magento\Framework\Exception\MailException $ex) {

        }
        $this->inlineTranslation->resume();
    }

    public function sendStatusEmailToStoreOwner($order) {
        $order_id = $order->getId();
        $storeLocation = $this->getStorepickupByOrderId($order_id);

        if (!$storeLocation) {
            return $this;
        }

        $store = $order->getStore();
        $storeId = $order->getStore()->getId();
        $this->inlineTranslation->suspend();

        $template = $this->getConfig(self::XML_PATH_STATUS_ORDER_TO_STORE_OWNER_EMAIL);
        if ($template === self::TEMPLATE_ID_NONE_EMAIL) {
            return $this;
        }
        $sendTo = array(
            array(
                'name' => $storeLocation->getStoreManager(),
                'email' => $storeLocation->getStoreEmail(),
            ),
        );

        foreach ($sendTo as $recipient) {
            try {
                $transport = $this->_transportBuilder->setTemplateIdentifier(
                    $template
                )->setTemplateOptions(
                    ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
                )->setTemplateVars(
                    [
                        'order' => $order,
                        'billing' => $order->getBillingAddress(),
                        'payment_html' => $this->getPaymentHtml($order, $storeId),
                        'store' => $order->getStore(),
                        'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                        'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                    ]
                )->setFrom(
                    [
                        'email' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'name' => $this->scopeConfig->getValue(
                            self::XML_PATH_SALES_EMAIL_IDENTITY_NAME,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                    ]
                )->addTo(
                    $recipient['email'],
                    $recipient['name']
                )->getTransport();
                $transport->sendMessage();
            } catch (\Magento\Framework\Exception\MailException $ex) {

            }
        }
        $this->inlineTranslation->resume();
        return $this;
    }

    public function getStorepickupByOrderId($order_id)
    {
        //cho code
        return 1;
    }
}

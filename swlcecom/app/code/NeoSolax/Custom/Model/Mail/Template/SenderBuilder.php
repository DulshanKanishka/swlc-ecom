<?php

namespace NeoSolax\Custom\Model\Mail\Template;

use Magento\Framework\App\ObjectManager;
use Mageplaza\PdfInvoice\Helper\Data;
use Mageplaza\PdfInvoice\Helper\PrintProcess;
use Mageplaza\PdfInvoice\Model\Source\Type;
use Zend\Mail\Message as Message;

/**
 * Class SenderBuilder
 * @package Mageplaza\PdfInvoice\Model\Template
 */
class SenderBuilder extends \Mageplaza\PdfInvoice\Model\Template\SenderBuilder
{
    /**
     * @inheritdoc
     */
    public function send()
    {
        $objectManager = ObjectManager::getInstance();
        $configHelper = $objectManager->get(Data::class);
        if ($configHelper->versionCompare("2.3")) {
            $this->configureEmailTemplate();
            $this->transportBuilder->addTo(
                $this->identityContainer->getCustomerEmail(),
                $this->identityContainer->getCustomerName()
            );
            $copyTo = $this->identityContainer->getEmailCopyTo();
            if (!empty($copyTo) && $this->identityContainer->getCopyMethod() == 'bcc') {
                foreach ($copyTo as $email) {
                    $this->transportBuilder->addBcc($email);
                }
            }
            $this->attachPDF();
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        } else {
            $this->attachPDF();
            parent::send();
        }
    }
    public function attachPDF()
    {
        $objectManager = ObjectManager::getInstance();
        $configHelper = $objectManager->get(Data::class);

        $templateVars = $this->templateContainer->getTemplateVars();
        $store = $templateVars['store'];
        if ($configHelper->isEnabled($store->getId())) {
            try {
                $dataHelper = $objectManager->get(PrintProcess::class);

                if (isset($templateVars['invoice'])) {
                    $invoice = $templateVars['invoice'];
                    $content = $dataHelper->processPDFTemplate(Type::INVOICE, $templateVars, $store->getId());
                    $fileName = 'Invoice' . $invoice->getIncrementId();
                } elseif (isset($templateVars['shipment'])) {
                    $shipment = $templateVars['shipment'];
                    $content = $dataHelper->processPDFTemplate(Type::SHIPMENT, $templateVars, $store->getId());
                    $fileName = 'Shipment' . $shipment->getIncrementId();
                } elseif (isset($templateVars['creditmemo'])) {
                    $creditmemo = $templateVars['creditmemo'];
                    $content = $dataHelper->processPDFTemplate(Type::CREDIT_MEMO, $templateVars, $store->getId());
                    $fileName = 'Creditmemo' . $creditmemo->getIncrementId();
                } else {
                    $order = $templateVars['order'];
                    $fileName = 'Order' . $order->getIncrementId();
                    $content = $dataHelper->processPDFTemplate(Type::ORDER, $templateVars, $store->getId());
                }

                if ($content) {
                    $attachment = $this->transportBuilder->addAttachmentcustom($content, $fileName, 'application/pdf');
                    if ($configHelper->versionCompare("2.3")) {
                        return $attachment;
                    }
                }
            } catch (\Exception $e) {
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pdfinvoice.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($e->getMessage());
            }
        }
    }
}

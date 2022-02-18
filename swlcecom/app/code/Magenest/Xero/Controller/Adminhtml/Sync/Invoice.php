<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\InvoiceFactory;
use Magenest\Xero\Model\Config;


/**
 * Class Invoice
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Invoice extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\Invoice
     */
    protected $syncInvoice;

    /**
     * @var Synchronization\Payment
     */
    protected $syncPayment;

    /**
     * @var \Magento\Sales\Model\Order\Invoice
     */
    protected $invoiceFactory;

    protected $xeroHelper;

    /**
     * Invoice constructor.
     * @param Context $context
     * @param Synchronization\Invoice $syncInvoice
     * @param Synchronization\Payment $syncPayment
     * @param InvoiceFactory $invoiceFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Synchronization\Invoice $syncInvoice,
        Synchronization\Payment $syncPayment,
        InvoiceFactory $invoiceFactory,
        Helper $helper
    ) {
        $this->syncInvoice = $syncInvoice;
        $this->syncPayment = $syncPayment;
        $this->invoiceFactory = $invoiceFactory;
        $this->xeroHelper = $helper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {
            $invoiceId = $this->getRequest()->getParam('id');
            $invoice = $this->invoiceFactory->create()->loadByIncrementId($invoiceId);

            if ($this->xeroHelper->getConfig(Config::XML_PATH_XERO_MULTIPLE_ENABLED)){
                $this->xeroHelper->setScope('websites');
                $this->xeroHelper->setScopeId($invoice->getStore()->getWebsiteId());
            }
            if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                return $resultRedirect;
            }
            if ($invoice->getIncrementId()) {
                /** sync customers */
                $xml = $this->syncInvoice->addRecord($invoice);
            } else {
                $xml = '<Invoice>';
                $xml .= '<InvoiceNumber>' . $invoiceId . '</InvoiceNumber>';
                $xml .= '<Status>DELETED</Status>';
                $xml .= '</Invoice>';
            }
            $xml = '<Invoices>' . $xml . '</Invoices>';
            $this->syncInvoice->syncAllGuestToXero();

            $this->syncInvoice->syncData($xml);

            $this->syncPayment->syncPayments();

            $this->messageManager->addSuccess(
                __('Sync process complete, please check out Logs for results')
            );

        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('Something happen during syncing process. Detail: ' . $e->getMessage())
            );
        }
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }

}

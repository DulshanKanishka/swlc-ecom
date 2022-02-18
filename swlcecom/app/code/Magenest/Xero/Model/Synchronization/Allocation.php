<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magento\Framework\ObjectManagerInterface;
use Magenest\Xero\Helper\Signature;
use Magenest\Xero\Model\QueueFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\ResourceModel\Queue\Collection;


/**
 * Class Allocation
 * @package Magenest\Xero\Model\Synchronization
 */
class Allocation extends Synchronization
{
    /**
     * @var string
     */
    protected $type = 'CreditNotes';

    protected $syncType = 'CreditNote';

    /**
     * @var array
     */
    protected $creditmemos = [];

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Signature
     */
    protected $_helper;

    /**
     * Allocation constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Signature $signature
     * @param ObjectManagerInterface $objectManagerInterface
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Signature $signature,
        ObjectManagerInterface $objectManagerInterface,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Helper $helper
    ) {
        $this->_helper = $signature;
        $this->_objectManager = $objectManagerInterface;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * Add creditmemos to a queue
     *
     * @param $creditmemoId
     * @return $this
     */
    public function addRecord($creditmemoId)
    {
        $this->creditmemos[] = $creditmemoId;
        $this->creditmemos = [];

        return $this;
    }

    /**
     * Sync Creditmemo datas to xero
     */
    public function syncAllocations()
    {
        foreach ($this->creditmemos as $creditId) {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $this->_objectManager->create('\Magento\Sales\Model\Order\Creditmemo')->load($creditId);

            $total = $creditmemo->getSubtotal() + $creditmemo->getTaxAmount() + $creditmemo->getDiscountAmount();
            $invoiceId = $creditmemo->getOrder()->getInvoiceCollection()->getLastItem()->getIncrementId();
            $invoiceXeroId = $this->getInvoiceOnXero($invoiceId);
            $creditNoteXeroId = $this->getCreditNoteOnXero($creditId);

            // Setup request url
            $url = $this->getRequestUrl($creditNoteXeroId);

            // Setup xml param
            $xml = $this->getRequestXml($total, $invoiceXeroId);

            // Send request Allocate the CreditNote to the Invoice
            $client = new \Zend_Http_Client($url,[             'timeout' => 30,             'useragent' => XeroClient::getUserAgent()         ]);
            $client->setRawData($xml);
            $client->request('PUT')->getBody();
        }
    }

    /**
     * Get all CreditNotes existed on Xero
     *
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     */
    protected function getCreditNoteOnXero($creditId)
    {
        $helper = $this->_helper;
        $helper->setUri('CreditNotes/' . $creditId);
        $helper->setMethod();
        $helper->setParams();
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url,[             'timeout' => 30,             'useragent' => XeroClient::getUserAgent()         ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['CreditNotes']['CreditNote']['CreditNoteID'])) {
            return ($parsedResponse['CreditNotes']['CreditNote']['CreditNoteID']);
        }

        return false;
    }

    /**
     * Get all Invoices existed on Xero
     *
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     */
    protected function getInvoiceOnXero($incrementId)
    {
        $helper = $this->_helper;
        $helper->setUri('Invoices');
        $helper->setMethod();
        $helper->setParams(['where'=>'InvoiceNumber="'.$incrementId.'"']);
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url,[
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['Invoices']['Invoice']['InvoiceID'])) {
            return $parsedResponse['Invoices']['Invoice']['InvoiceID'];
        }

        return false;
    }

    /**
     * @param $creditNoteXeroId
     * @return string
     */
    protected function getRequestUrl($creditNoteXeroId)
    {
        $helper = $this->_helper;
        $helper->setUri('CreditNotes/' . $creditNoteXeroId . '/Allocations');
        $helper->setMethod('PUT');
        $helper->setParams();
        $url = $helper->getUri() . '?' . $helper->sign();

        return $url;
    }

    /**
     * @param float $total
     * @param string $invoiceXeroId
     * @return string
     */
    protected function getRequestXml($total, $invoiceXeroId)
    {
        $xml = '<Allocations><Allocation>';
        $xml .= '<AppliedAmount>' . $total . '</AppliedAmount>';
        $xml .= '<Invoice><InvoiceID>' . $invoiceXeroId . '</InvoiceID></Invoice>';
        $xml .= '</Allocation></Allocations>';

        return $xml;
    }

    /**
     * Unset sync queue
     */
    public function unsetRecords()
    {
        $this->creditmemos = [];
    }
}

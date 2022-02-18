<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magenest\Xero\Helper\Signature;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\QueueFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\OrderFactory;
use Magenest\Xero\Model\PaymentMappingFactory;



/**
 * Class Allocation
 * @package Magenest\Xero\Model\Synchronization
 */
class Payment extends Synchronization
{
    /**
     * @var string
     */
    protected $type = 'Payment';

    protected $syncType = 'Payment';

    protected $syncIdKey = 'InvoiceNumber';

    protected $syncTypeKey = 'Invoice';

    /**
     * @var Signature
     */
    protected $_helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var string
     */
    protected $xml = '';

    protected $bankAccountId = '';

    protected $paymentMappingFactory;

    /**
     * Payment constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Signature $signature
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderFactory $orderFactory
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param Account $account
     * @param PaymentMappingFactory $paymentMappingFactory
     * @throws
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Signature $signature,
        ScopeConfigInterface $scopeConfig,
        OrderFactory $orderFactory,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Account $account,
        PaymentMappingFactory $paymentMappingFactory,
        Helper $helper
    ) {
    
        $this->_helper = $signature;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->bankAccountId = $account->getAccountId($account::BANK_ACC_TYPE);
        $this->paymentMappingFactory = $paymentMappingFactory;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * return xml string of invoice record
     *
     * @param $order
     * @return string
     */
    public function addRecord($order)
    {
        $amount = $order->getGrandTotal();
        if ($order instanceof \Magento\Sales\Model\Order){
            $payment = $order->getPayment();
        } else {
//            $order = $order->getOrder();
            $payment = $order->getOrder()->getPayment();
        }
        $accountId = $this->getAccountId($payment->getMethod());
        $xml = '<Payment>';
        $xml .= '<Invoice><InvoiceNumber>' . $order->getIncrementId() . '</InvoiceNumber></Invoice>';
        $xml .= '<Account><AccountID>' . $accountId . '</AccountID></Account>';
        $xml .= '<Date>' . date('Y-m-d') . '</Date>';
        $xml .= '<Amount>'.$amount.'</Amount>';
        $xml .= '</Payment>';
        $this->xml .= $xml;

        return $xml;
    }


    protected function getAccountId($code)
    {
        $accountId = $this->bankAccountId;
        if ($this->helper->isMultipleWebsiteEnable()) {
            $accountId = $this->getBankId();
        }
        $mapping = $this->paymentMappingFactory->create()->loadByPaymentCode($code);
        if ($mapping && $mapping->getBankAccountId()) {
            $accountId = $mapping->getBankAccountId();
        }
        return $accountId;
    }

    /**
     * @param $xml
     * @return string
     */
    public function syncPayments($xml = "")
    {
        if ($xml == '') {
            $xml = $this->xml;
        }
        if ($xml == ''){
            return '';
        }
        $xml = '<' . $this->type . 's>' . $xml . '</' . $this->type . 's>';
        try {
            $xml = trim($xml);
            $xml = str_replace('&', ' and ', $xml);
            $helper = $this->xeroClient->getSignature();
            $params['xml'] = $this->safeEncode($xml);
            $method = 'PUT';
            $helper->setMethod($method);
            $helper->setParams();
            $helper->setUri($this->type);
            $url = $helper->getUri() . '?' . $helper->sign();

            $client = new \Zend_Http_Client($url,[
                'timeout' => 30,
                'useragent' => XeroClient::getUserAgent()
            ]);
            $client->setRawData($xml);
            $response = $client->request($method)->getBody();
            $this->logResponse($this->syncType, $this->type, $this->parseXML($response));
            $this->addRequest();
            return $response;
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug($e->getMessage());
        }

        return '';
    }

    /**
     * @param $creditmemo Creditmemo
     * @return string
     */
    public function addCreditRecord($creditmemo)
    {
        $payment = $creditmemo->getOrder()->getPayment();

        $accountId = $this->getBankId();
        if ($payment instanceof OrderPaymentInterface) {
            $accountId = $this->getAccountId($payment->getMethod());
        }

        $xml = '<Payment>';
        $xml .= '<CreditNote><CreditNoteNumber>' .'C'. $creditmemo->getIncrementId() . '</CreditNoteNumber></CreditNote>';
        $xml .= '<Account><AccountID>' . $accountId . '</AccountID></Account>'; // magenest_xero_config/xero_account/bank_id
        $xml .= '<Date>' . date('Y-m-d') . '</Date>';
        $xml .= '<Amount>'.$creditmemo->getGrandTotal().'</Amount>';
        $xml .= '</Payment>';
        $this->xml .= $xml;

        return $xml;
    }

    /**
     * @return mixed
     */
    private function getBankId()
    {
        return $this->helper->getConfig('magenest_xero_config/xero_account/bank_id');
    }

    /**
     * Unset sync queue
     */
    public function unsetRecords()
    {
        $this->xml = '';
    }
}

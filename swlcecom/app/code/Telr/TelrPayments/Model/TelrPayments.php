<?php

namespace Telr\TelrPayments\Model;

use Telr\TelrPayments\Helper\TelrPayments as TelrPaymentsHelper;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class TelrPayments extends \Magento\Payment\Model\Method\AbstractMethod {
    const CODE = 'telr_telrpayments';
    protected $_code = self::CODE;
    protected $_isGateway = false;
    protected $_isOffline = true;
    protected $helper;
    protected $logger;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_orderFactory;
    protected $_checkoutSession;
    protected $orderManagement;
    protected $orderSender;
    protected $_order;
    protected $_invoiceService;
    protected $_transaction;
    protected $_creditmemoFactory;
    protected $_creditmemoService;

    protected $_supportedCurrencyCodes = array(
        'AFN', 'ALL', 'DZD', 'ARS', 'AUD', 'AZN', 'BSD', 'BDT', 'BBD',
        'BZD', 'BMD', 'BOB', 'BWP', 'BRL', 'GBP', 'BND', 'BGN', 'CAD',
        'CLP', 'CNY', 'COP', 'CRC', 'HRK', 'CZK', 'DKK', 'DOP', 'XCD',
        'EGP', 'EUR', 'FJD', 'GTQ', 'HKD', 'HNL', 'HUF', 'INR', 'IDR',
        'ILS', 'JMD', 'JPY', 'KZT', 'KES', 'LAK', 'MMK', 'LBP', 'LRD',
        'MOP', 'MYR', 'MVR', 'MRO', 'MUR', 'MXN', 'MAD', 'NPR', 'TWD',
        'NZD', 'NIO', 'NOK', 'PKR', 'PGK', 'PEN', 'PHP', 'PLN', 'QAR',
        'RON', 'RUB', 'WST', 'SAR', 'SCR', 'SGF', 'SBD', 'ZAR', 'KRW',
        'LKR', 'SEK', 'CHF', 'SYP', 'THB', 'TOP', 'TTD', 'TRY', 'UAH',
        'AED', 'USD', 'VUV', 'VND', 'XOF', 'YER'
    );

    protected $_formBlockType = 'Telr\TelrPayments\Block\Form\TelrPayments';
    protected $_infoBlockType = 'Telr\TelrPayments\Block\Info\TelrPayments';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Telr\TelrPayments\Helper\TelrPayments $helper,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        \Magento\Sales\Model\Order\Invoice $Invoice,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->orderManagement = $orderManagement;
        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;
        $this->_transaction = $transaction;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoService = $creditmemoService;
        $this->_invoice = $Invoice;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    public function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    /**
     * Determine method availability based on [CURL, quote amount,config data]
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {

        if (function_exists('curl_init') == false) {
            return false;
        }

        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode) {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    private function requestGateway($api_url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $returnData = json_decode(curl_exec($ch),true);
        curl_close($ch);
        return $returnData;
    }


    /**
     * Payment request
     *
     * @param $order Object
     * @throws \Magento\Framework\Validator\Exception
     */
    public function buildTelrRequest($order) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion(); //will return the magento version

        $this->_order=$order;
        $billing_address = $this->_order->getBillingAddress();
        $shipping_address = $this->_order->getShippingAddress();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        //ToDo: Add condition for isSSL check.
        $ivp_framed = ($this->getConfigData('ivp_framed') == 1 && $this->isSSL()) ? 2 : 0;
        $telr_lang = $this->getConfigData('telr_lang');

        $params['ivp_method']          = 'create';
        $params['ivp_store']           = $this->getConfigData("store_id");
        $params['ivp_authkey']         = $this->getConfigData("auth_key");
        $params['ivp_desc']            = $this->getConfigData("transaction_desc");
        $params['ivp_test']            = $this->getConfigData('sandbox') ? 1 : 0;
        $params['ivp_source']          = $version;
        $params['ivp_cart']            = $this->_order->getRealOrderId().'_'.(string)time();
        $params['ivp_currency']        = $this->_order->getOrderCurrencyCode();
        $params['ivp_amount']          = round($this->_order->getGrandTotal(), 2);
        $params['bill_fname']          = $billing_address->getName();
        $params['bill_sname']          = $billing_address->getName();
        $params['bill_addr1']          = $billing_address->getStreet()[0];
        $params['ivp_framed']          = $ivp_framed;
        $params['ivp_lang']            = $telr_lang;

        if (count($billing_address->getStreet()) > 1) {
            $params['bill_addr2']  = $billing_address->getStreet()[1];
        }

        if (count($billing_address->getStreet()) > 2) {
            $params['bill_addr3']  = $billing_address->getStreet()[2];
        }

        $params['bill_city']           = $billing_address->getCity();
        $params['bill_region']         = $billing_address->getRegion();
        $params['delv_zip']            = $billing_address->getPostcode();
        $params['bill_country']        = $billing_address->getCountryId();
        $params['bill_email']          = $this->_order->getCustomerEmail();
        $params['bill_phone1']         = $billing_address->getTelephone();
        $params['return_auth']         = $this->getReturnUrl().'?coid='.$this->_order->getRealOrderId();
        $params['return_can']          = $this->getCancelUrl();
        $params['return_decl']         = $this->getCancelUrl();
        $params['ivp_update_url']      = $this->getIvpCallbackUrl() . "?cart_id=" . $this->_order->getRealOrderId();

        if($this->isSSL() && $customerSession->isLoggedIn()) {
            $params['bill_custref'] = $customerSession->getCustomerId();
        }

        $api_url = $this->getConfigData('sandbox') ? $this->getConfigData('api_url_sandbox') : $this->getConfigData('api_url');

        try {
            $results = $this->requestGateway($api_url, $params);
            $url = false;
            if (isset($results['order']['ref']) && isset($results['order']['url'])) {
                $ref = trim($results['order']['ref']);
                $url = trim($results['order']['url']);
                $this->getCheckoutSession()->setOrderRef($ref);
                return $url;
            }
        } catch (Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->logDebug(__('Error creating transaction, exception from curl request.'));
        }
        $this->logDebug(__('Error creating transaction, no ref/url obtained.'));
        return false;
    }

    private function notifyOrder() {
        $this->orderSender->send($this->_order);
        $this->order->addStatusHistoryComment('Customer email sent')->setIsCustomerNotified(true)->save();
    }

    public function getConfig($key){
        return $this->getConfigData($key);
    }

    /**
     * Return the provided comment as either a string or a order status history object
     *
     * @param string $comment
     * @param bool $makeHistory
     * @return string|\Magento\Sales\Model\Order\Status\History
     */
    protected function addOrderComment($comment,$makeHistory=false) {
        $message=$comment;
        if ($makeHistory) {
            $message=$this->_order->addStatusHistoryComment($message);
            $message->setIscustomerNotified(null);
        }
        return $message;
    }

    private function registerAuth($message,$txref) {
        $this->logDebug("registerAuth");

        $payment = $this->_order->getPayment();
        $payment->setTransactionId($txref);
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation('telr_message', $message);
        $payment->setAdditionalInformation('telr_ref', $txref);
        $payment->setAdditionalInformation('telr_status', \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
        $payment->place();

        /*
        I've commented this line because it actually stores huge useless data that is ver hard to be investigated,.
        If you try var_dump() this in the browser it will hangup and the machine itself !
        */
        //$this->logDebug(print_r($payment->getData(),true));

    }

    private function registerPending($message,$txref) {
        $this->logDebug("registerPending");

        $payment = $this->_order->getPayment();
        $payment->setTransactionId($txref);
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation('telr_message', $message);
        $payment->setAdditionalInformation('telr_ref', $txref);
        $payment->setAdditionalInformation('telr_status', 'Pending');
        $payment->place();

        /*
        I've commented this line because it actually stores huge useless data that is ver hard to be investigated,.
        If you try var_dump() this in the browser it will hangup and the machine itself !
        */
        //$this->logDebug(print_r($payment->getData(),true));

    }

    private function registerCapture($message,$txref) {
        $this->logDebug("registerCapture");

        $payment = $this->_order->getPayment();
        $payment->setTransactionId($txref);
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation('telr_message', $message);
        $payment->setAdditionalInformation('telr_ref', $txref);
        $payment->setAdditionalInformation('telr_status', \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
        $payment->place();

        /*
        I've commented this line because it actually stores huge useless data that is ver hard to be investigated,.
        If you try var_dump() this in the browser it will hangup and the machine itself !
        */
        //$this->logDebug(print_r($payment->getData(),true));

    }

    private function updateOrder($message, $state, $status, $notify) {
        $this->logDebug("updateOrder");
        if ($state) {
            $this->_order->setState($state);
            if ($status) {
                $this->_order->setStatus($status);
            }
            $this->_order->save();
        } else if ($status) {
            $this->_order->setStatus($status);
            $this->_order->save();
        }
        if ($message) {
            $this->_order->addStatusHistoryComment($message);
            $this->_order->save();
        }
        $this->logDebug("OrderState = ".$this->_order->getState());
        $this->logDebug("OrderStatus = ".$this->_order->getStatus());
        if ($notify) {
            $this->notifyOrder();
        }
    }

    private function getStateCode($name) {
        if (strcasecmp($name,"processing")==0) { return \Magento\Sales\Model\Order::STATE_PROCESSING; }
        if (strcasecmp($name,"review")==0)     { return \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW; }
        if (strcasecmp($name,"paypending")==0) { return \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT; }
        if (strcasecmp($name,"pending")==0)    { return \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT; }
        if (strcasecmp($name,"cancelled")==0)   { return \Magento\Sales\Model\Order::STATE_CANCELED; }
        if (strcasecmp($name,"canceled")==0)   { return \Magento\Sales\Model\Order::STATE_CANCELED; }
        if (strcasecmp($name,"closed")==0)   { return \Magento\Sales\Model\Order::STATE_CLOSED; }
        if (strcasecmp($name,"holded")==0)   { return \Magento\Sales\Model\Order::STATE_HOLDED; }
        if (strcasecmp($name,"complete")==0)   { return \Magento\Sales\Model\Order::STATE_COMPLETE; }
        if (strcasecmp($name,"fraud")==0)   { return \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW; }
        return false;
    }

    /**
     * Transaction was authorised
     */
    private function paymentCompleted($txref) {
        $this->registerCapture('Payment completed',$txref);
        $message='Payment completed by Telr: '.$txref;
        $state=$this->getStateCode("processing");
        $this->updateOrder($message, $state, $state, false);
    }

    /**
     * Transaction has not been completed (deferred payment method, or on hold)
     */
    private function paymentPending($txref) {
        $this->registerPending('Payment pending',$txref);
        $message='Payment pending by Telr: '.$txref;
        $state=$this->getStateCode("paypending");
        $this->updateOrder($message, $state, $state, false);
    }

    /**
     * Transaction has not been authorised but completed (auth method used, or sale put on hold)
     */
    private function paymentAuthorised($txref) {
        $this->registerAuth('Payment authorised',$txref);
        $message='Payment authorisation by Telr: '.$txref;
        $state=$this->getStateCode("review");
        $this->updateOrder($message, $state, $state, false);
    }

    /**
     * Transaction has been refunded (may be partial refund)
     */
    private function paymentRefund($txref, $currency, $amount) {
        $message='Refund of '.$currency.' '.$amount.': '.$txref;
        $this->updateOrder($message, false, false, false);
    }

    /**
     * Transaction has been voided
     */
    private function paymentVoided($txref, $currency, $amount) {
        $message='Void of '.$currency.' '.$amount.': '.$txref;
        $this->updateOrder($message, false, false, false);
    }

    /**
     * Transaction request has been cancelled
     */
    private function paymentCancelled() {
        $message='Payment request cancelled by Telr';
        $state=$this->getStateCode("cancelled");
        $this->updateOrder($message, $state, $state, false);
    }

    public function logDebug($message) {
        $dbg['telr']=$message;
        $this->logger->debug($dbg,null,true);
    }

    /**
     * Payment request validation
     */
    public function validateResponse($order_id) {
        $api_url = $this->getConfigData('sandbox') ? $this->getConfigData('api_url_sandbox') : $this->getConfigData('api_url');
        $auth_key = $this->getConfigData('auth_key');
        $store_id = $this->getConfigData('store_id');
        $defaultStatus = $this->getConfigData('order_status');
        $telr_order_ref = $this->getCheckoutSession()->getOrderRef();
        //$this->_order=$this->_orderFactory->create()->load($order_id);
        $this->_order=$this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());

        $this->logDebug(print_r($this->_order->getData(),true));

        $params = array(
            'ivp_method'   => 'check',
            'ivp_store'    => $store_id,
            'ivp_authkey'  => $auth_key,
            'order_ref'    => $telr_order_ref
        );

        $results = $this->requestGateway($api_url, $params);
        $objOrder='';
        $objError='';
        if (isset($results['order'])) { $objOrder = $results['order']; }
        if (isset($results['error'])) { $objError = $results['error']; }
        if (is_array($objError)) { // Failed
             return false;
        }
        if (!isset(
            $objOrder['cartid'],
            $objOrder['status']['code'],
            $objOrder['transaction']['status'],
            $objOrder['transaction']['ref'])) {
            // Missing fields
            return false;
        }

        $new_tx=$objOrder['transaction']['ref'];
        $ordStatus=$objOrder['status']['code'];
        $txStatus=$objOrder['transaction']['status'];
        $cart_id=$objOrder['cartid'];
        $parts=explode('~', $cart_id, 2);
        $order_id=$parts[0];
        if (($ordStatus==-1) || ($ordStatus==-2)) {
            // Order status EXPIRED (-1) or CANCELLED (-2)
            $this->paymentCancelled($new_tx);
            return false;
        }
        if ($ordStatus==4) {
            // Order status PAYMENT_REQUESTED (4)
            $this->paymentPending($new_tx);
            return true;
        }
        if ($ordStatus==2) {
            // Order status AUTH (2)
            $this->paymentAuthorised($new_tx);
            return true;
        }
        if ($ordStatus==3) {
            // Order status PAID (3)
            if ($txStatus=='P') {
                // Transaction status of pending or held
                $this->paymentPending($new_tx);
                return true;
            }
            if ($txStatus=='H') {
                // Transaction status of pending or held
                $this->paymentAuthorised($new_tx);
                return true;
            }
            if ($txStatus=='A') {
                // Transaction status = authorised
                if($defaultStatus != ''){
                     $this->updateOrderStatusWithMessage($this->_order, $defaultStatus, $new_tx);
                }else{
                    $this->paymentCompleted($new_tx);
                }
                if($this->_order->canInvoice()) {
                    $invoice = $this->_invoiceService->prepareInvoice($this->_order);
                    $invoice->register();
                    $invoice->save();
                    $transactionSave = $this->_transaction->addObject(
                        $invoice
                    )->addObject(
                        $invoice->getOrder()
                    );
                    $transactionSave->save();
                    $this->_invoiceSender->send($invoice);
                    //send notification code
                    $this->_order->addStatusHistoryComment(
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    )
                    ->setIsCustomerNotified(true)
                    ->save();
                }
                return true;
            }
        }
        // Declined
        return false;
    }

    public function getRedirectUrl() {
        $url = $this->helper->getUrl($this->getConfigData('redirect_url'));
        return $url;
    }

    public function getReturnUrl() {
        $url = $this->helper->getUrl($this->getConfigData('return_url'));
        return $url;
    }

    public function getCancelUrl() {
        $url = $this->helper->getUrl($this->getConfigData('cancel_url'));
        return $url;
    }

    public function getIvpCallbackUrl() {
        $url = $this->helper->getUrl($this->getConfigData('ivp_update_url'));
        return $url;
    }

    public function isSSL() {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }
        return $isSecure;
    }

    public function updateOrderStatusWithMessage($order, $status, $txnref){
        $this->_order = $order;
        $message = '';
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        switch ($status) {
            case 'complete':
                $message='Payment completed by Telr: ' . $txnref;
                $state=$this->getStateCode("processing");
                break;

            case 'canceled':
                $message='Payment request canceled by Telr: ' . $txnref;
                $state=$this->getStateCode("canceled");
                break;

            case 'refunded':
                $message='Transaction Refunded by Telr: ' . $txnref;
                $state=$this->getStateCode("closed");
                break;


            case 'processing':
                $message='Transaction Refunded by Telr: ' . $txnref;
                $state=$this->getStateCode("processing");
                break;


            case 'fraud':
                $message='Transaction Refunded by Telr: ' . $txnref;
                $state=$this->getStateCode("fraud");
                break;


            case 'complete':
                $message='Transaction Refunded by Telr: ' . $txnref;
                $state=$this->getStateCode("complete");
                break;

            case 'holded':
                $message='Transaction Refunded by Telr: ' . $txnref;
                $state=$this->getStateCode("holded");
                break;

            default:
                $message = 'Transaction ' . $status . ' by Telr: ' . $txnref;
                $state=$this->getStateCode($status);
                break;
        }

        $this->updateOrder($message, $state, $status, false);
        if($status == 'complete'){
        	if($this->_order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($this->_order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $this->_invoiceSender->send($invoice);
                //send notification code
                $this->_order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                ->setIsCustomerNotified(true)
                ->save();
            }
        }

        if($status == 'refunded'){
    		$invoices = $this->_order->getInvoiceCollection();
	        foreach($invoices as $invoice){
	            $invoiceincrementid = $invoice->getIncrementId();
	        }

	        $invoiceobj =  $this->_invoice->loadByIncrementId($invoiceincrementid);
	        $creditmemo = $this->_creditmemoFactory->createByOrder($this->_order);
	        $this->_creditmemoService->refund($creditmemo);
        }
    }

}

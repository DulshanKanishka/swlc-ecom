<?php

namespace Dulshan\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\Order;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;
use Magento\Sales\Model\OrderFactory;

class Invoice extends \Magenest\QuickBooksOnline\Model\Synchronization\Invoice
{
    public function __construct(
        Client $client,
        Log $log,
        InvoiceModel $invoice,
        Item $item,
        Customer $customer,
        TaxFactory $taxFactory,
        Config $config,
        \Magenest\QuickBooksOnline\Model\PaymentMethodsFactory $paymentMethods,
        Order $orderSync,
        \Magento\Catalog\Model\ProductFactory $product,
        InvoiceModel\ItemFactory $invoiceItemFactory,
        \Psr\Log\LoggerInterface $logger,
        OrderFactory $orderFactory,
        Context $context,
        TimezoneInterface $timezone
    ) {
        parent::__construct($client, $log, $invoice, $item, $customer, $taxFactory, $config, $paymentMethods, $orderSync, $product, $invoiceItemFactory, $logger, $orderFactory, $context, $timezone);
    }

    public function sync($incrementId)
    {
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
        $registryObject->unregister('skip_log');
        try {
            $model            = ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Invoice')->loadByIncrementId($incrementId);
            $modelOrder       = $this->_orderFactory->create()->load($model->getOrderId());
            $orderIncrementId = $modelOrder->getIncrementId();
            $checkInvoice     = $this->checkInvoice($incrementId);

            if (!isset($checkInvoice['Id'])) {
                //force a sync on order if order is not found on QBO
                $orderQBOId = $this->orderSync->sync($orderIncrementId);
                if (empty($orderQBOId)) {
                    $this->addLog($incrementId, null, __('We can\'t find the Order #%1 on QBO to map with this invoice #%2', $orderIncrementId, $incrementId));

                    return null;
                }
            }

            if (!isset($orderQBOId)) {
                $orderQBOId = $checkInvoice['Id'];
            }

            if (!$model->getId()) {
                throw new LocalizedException(__('We can\'t find the Invoice #%1', $incrementId));
            }

            $customerIsGuest = true;
            if ($modelOrder->getCustomerId()) {
                $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\ResourceModel\Customer\Collection')->addFieldToFilter('entity_id', $modelOrder->getCustomerId());
                if (!$customerCollection->getData()) {
                    $customerIsGuest = true;
                } else {
                    $customerIsGuest = false;
                }
            }

            $checkPayment = $this->checkPayment($model->getIncrementId());

            $this->setModel($model);
            $this->prepareParams($orderQBOId, $customerIsGuest);
            $params   = array_replace_recursive($this->getParameter(), $checkPayment);
            $response = $this->sendRequest(\Zend_Http_Client::POST, 'payment', $params);
            if (!empty($response['Payment']['Id'])) {
                $qboId    = $response['Payment']['Id'];
                $this->addLog($incrementId, $qboId);
            }
            $this->parameter = [];

            $this->parameter = [];

            return isset($qboId) ? $qboId : null;
        } catch (LocalizedException $e) {
            $this->addLog($incrementId, null, $e->getMessage());
        }

        return null;
    }


    public function preparePaymentMethod()
    {
        $modelOrder    = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
        $code          = $modelOrder->getPayment()->getMethodInstance()->getCode();
        $paymentMethod = $this->_paymentMethods->create()->load($code, 'payment_code');
        if ($paymentMethod->getId()) {
            $params['PaymentMethodRef'] = [
                'value' => $paymentMethod->getQboId(),
            ];
            if ($code == 'telr_telrpayments') {
                $params['DepositToAccountRef'] = [
                    'value' => '643',
                ];
            }
            $this->setParameter($params);
        }
    }
}

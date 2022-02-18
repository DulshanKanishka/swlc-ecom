<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Helper;

use Magenest\QuickBooksOnline\Model\ResourceModel\Log\CollectionFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\Order;
use Magenest\QuickBooksOnline\Model\Synchronization\Invoice;
use Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Message\ManagerInterface;
use Magenest\QuickBooksOnline\Model\Synchronization\PaymentMethods;
use Magenest\QuickBooksOnline\Model\Synchronization\TaxCode;


/**
 * Class ReSynchronization
 * @package Magenest\QuickBooksOnline\Helper
 */
class ReSynchronization
{
    const PREFIX_GUEST = 'Guest ';
    /**
     * @var \Magenest\QuickBooksOnline\Model\Synchronization\TaxCode
     */
    protected $taxCode;
    /**
     * @var PaymentMethods
     */
    protected $paymentMethods;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * ReSynchronization constructor.
     * @param CollectionFactory $collectionFactory
     * @param Customer $customer
     * @param Item $item
     * @param Order $order
     * @param Invoice $invoice
     * @param Creditmemo $creditmemo
     * @param PaymentMethods $paymentMethods
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Customer $customer,
        Item $item,
        Order $order,
        Invoice $invoice,
        Creditmemo $creditmemo,
        PaymentMethods $paymentMethods,
        TaxCode $taxCode,
        ManagerInterface $messageManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->customer = $customer;
        $this->item = $item;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->messageManager = $messageManager;
        $this->paymentMethods = $paymentMethods;
        $this->taxCode = $taxCode;
    }


    public function execute()
    {
        //execute
        try {
            /**
             * @var \Magenest\QuickBooksOnline\Model\Config $config
             */
            $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
            if (count($this->getLogCollection())) {
                $count = 0;
                if ($config->isEnableByType('customer')) $count += $this->reSyncCustomer();
                if ($config->isEnableByType('item')) $count += $this->reSyncItem();
                if ($config->isEnableByType('order')) $count += $this->reSyncOrder();
                if ($config->isEnableByType('invoice')) $count += $this->reSyncInvoice();
                if ($config->isEnableByType('creditmemo')) $count += $this->reSyncCreditmemo();
                $count += $this->reSyncPaymentMethod();
                $count += $this->reSyncTaxCode();
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been ReSync.', $count));
            } else {
                $this->messageManager->addNoticeMessage(__('Nothing in logs to resync'));
            }
        } catch (\Exception $e) {
            $details = '';
            $message = $e->getMessage();
            try {
                $parser = new \Magento\Framework\Xml\Parser();
                $parser->loadXML($e->getMessage());
                if ($err = $parser->getDom()->getElementsByTagName('Message')->item(0)) {
                    $message = $err->textContent;
                    $details .= $message;
                }
            } catch (\Exception $e) {
                $details = $message;
            }
            $this->messageManager->addErrorMessage('Error Re-Syncing Data to QuickbookOnlines. Details: ' . $details);
        }
    }

    /**
     * @return mixed
     */
    public function getLogCollection()
    {
        return $this->collectionFactory->create();
    }

    public function reSyncCustomer()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'customer');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $logId = $log->getLogId();
                    $log->delete();
                    if (mb_substr($log->getTypeId(),0,6 )!= self::PREFIX_GUEST) {
                        $this->customer->sync($log->getTypeId(), true);
                        $count++;
                    } else {

                        $orderId = (int)mb_substr($log->getTypeId(), 6, strlen($log->getTypeId()));

//                        $orderId = 0;
//                        for ($index = $logId; ; $index++) {
//                            /**
//                             * @var \Magenest\QuickBooksOnline\Model\Log $modelLog
//                             */
//                            $modelLog = ObjectManager::getInstance()->get('Magenest\QuickBooksOnline\Model\Log')->load($index);
//                            if ($modelLog->getType() == 'order') {
//                                $orderId = $modelLog->getTypeId();
//                                break;
//                            }
//                        }
                        /**
                         * @var \Magento\Sales\Model\Order $modelSales
                         */
                        $modelSales = ObjectManager::getInstance()->create('Magento\Sales\Model\Order')->load($orderId);
                        $this->customer->syncGuest($modelSales->getBillingAddress(), $modelSales->getShippingAddress(), true);
                        $count++;
                    }
                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }

    public function reSyncItem()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'item');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $log->delete();
                    $this->item->sync($log->getTypeId(), true);
                    $count++;
                } catch (\Exception $exception) {
                }

            }
        }
        return $count;
    }

    public function reSyncOrder()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'order');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $log->delete();
                    $this->order->sync($log->getTypeId());
                    $count++;
                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }

    public function reSyncInvoice()
    {
        $count = 0;

        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'invoice');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $log->delete();
                    $this->invoice->sync($log->getTypeId());
                    $count++;
                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }

    /**
     * sync credit memo
     */
    public function reSyncCreditmemo()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'creditmemo');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $log->delete();
                    $this->creditmemo->sync($log->getTypeId());
                    $count++;
                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }

    /**
     * sync payment method
     */
    public function reSyncPaymentMethod()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'paymentmethod');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $codeMethod = $log->getTypeId();
                    $log->delete();
                    $scopeConfig = ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');
                    $paymentMethodsList = $scopeConfig->getValue('payment');
                    foreach ($paymentMethodsList as $key => $value) {
                        if (mb_substr($key, 0, 20) == $codeMethod) {
                            $codeMethod = $key;
                            break;
                        }
                    }
                    $title = $paymentMethodsList[$codeMethod]['title'];
                    if (strlen($title) > 31) {
                        $title = mb_substr($title, 0, 31);
                        $this->messageManager->addNoticeMessage(
                            __(
                                'Payment Methods %1 renamed to "%2" when synced to QuickBooks Online',
                                $paymentMethodsList[$codeMethod]['title'],
                                $title
                            )
                        );
                    }
                    try {
                        $this->paymentMethods->sync($title, $codeMethod);
                        $count++;
                    } catch (\Exception $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }

                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }

    /**
     * sync credit memo
     */
    public function reSyncTaxCode()
    {
        $count = 0;
        $collection = $this->getLogCollection();
        $collection->addFieldToFilter('status', 2)->addFieldToFilter('type', 'taxcode');
        if ($collection->count() > 0) {
            /** @var \Magenest\QuickBooksOnline\Model\Queue $log */
            foreach ($collection as $log) {
                try {
                    $taxId = $log->getTypeId();
                    $log->delete();
                    $taxList = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\ResourceModel\Tax\Collection')->addFieldToFilter('tax_id', $taxId);
                    foreach ($taxList as $listTax) {
                        try {
                            $this->taxCode->sync($listTax->getTaxId(), trim($listTax->getTaxCode()), $listTax->getRate());
                            $count++;
                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage($e->getMessage());
                        }
                    }
                } catch (\Exception $exception) {
                }
            }
        }
        return $count;
    }
}

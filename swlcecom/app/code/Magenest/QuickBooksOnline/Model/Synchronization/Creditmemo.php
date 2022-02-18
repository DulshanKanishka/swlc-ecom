<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Creditmemo as CreditmemoModel;
use Magento\Framework\Exception\LocalizedException;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Sales\Model\OrderFactory;
use Magenest\QuickBooksOnline\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magento\Sales\Model\Order\TaxFactory as SalesOrderTax;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Creditmemo
 * @package Magenest\QuickBooksOnline\Model\Synchronization
 * @method CreditMemoModel getModel()
 */
class Creditmemo extends Synchronization
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var Customer
     */
    protected $_syncCustomer;

    /**
     * @var Item
     */
    protected $_item;

    /**
     * @var CreditmemoModel
     */
    protected $_creditmemo;

    /**
     * @var PaymentMethods
     */
    protected $_paymentMethods;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var TaxFactory
     */
    protected $tax;

    /**
     * @var TaxCode
     */
    protected $taxSync;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CreditmemoModel\ItemFactory
     */
    protected $itemCreditmemo;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $product;

    /**
     * @var TaxItem
     */
    protected $taxItem;

    /**
     * @var SalesOrderTax
     */
    protected $salesOrderTax;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Creditmemo constructor.
     *
     * @param Client $client
     * @param Log $log
     * @param CreditmemoModel $creditmemo
     * @param Item $item
     * @param Customer $customer
     * @param \Magenest\QuickBooksOnline\Model\PaymentMethodsFactory $paymentMethods
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Psr\Log\LoggerInterface $logger
     * @param TaxFactory $taxFactory
     * @param TaxCode $taxSync
     * @param OrderFactory $orderFactory
     * @param Config $config
     * @param Context $context
     * @param TaxItem $taxItem
     * @param SalesOrderTax $salesOrderTax
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Client $client,
        Log $log,
        CreditmemoModel $creditmemo,
        Item $item,
        Customer $customer,
        \Magenest\QuickBooksOnline\Model\PaymentMethodsFactory $paymentMethods,
        \Magento\Catalog\Model\ProductFactory $product,
        \Psr\Log\LoggerInterface $logger,
        TaxFactory $taxFactory,
        TaxCode $taxSync,
        OrderFactory $orderFactory,
        Config $config,
        Context $context,
        TaxItem $taxItem,
        SalesOrderTax $salesOrderTax,
        TimezoneInterface $timezone
    ) {
        parent::__construct($client, $log, $context);
        $this->_creditmemo     = $creditmemo;
        $this->_item           = $item;
        $this->_syncCustomer   = $customer;
        $this->_paymentMethods = $paymentMethods;
        $this->tax             = $taxFactory;
        $this->taxSync         = $taxSync;
        $this->type            = 'creditmemo';
        $this->logger          = $logger;
        $this->_orderFactory   = $orderFactory;
        $this->config          = $config;
        $this->product         = $product;
        $this->taxItem         = $taxItem;
        $this->salesOrderTax   = $salesOrderTax;
        $this->timezone        = $timezone;
    }

    /**
     * @param $id
     * @param null $item
     *
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     * @throws \Exception
     */
    public function sync($id, $item = null)
    {
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
        $registryObject->unregister('skip_log');
        try {
            $model = $this->loadByIncrementId($id);
            if ($item != null) {
                $model->setItem($item);
            }

            $invoiceEnabled = $this->config->isEnableByType('invoice');

            $orderId    = $model->getOrderId();
            $modelOrder = ObjectManager::getInstance()->create('Magento\Sales\Model\Order')->load($orderId);
            $invoice    = $this->checkInvoice($modelOrder->getIncrementId());
            if (empty($invoice)) {
                $this->addLog($id, null, __('We can\'t find the Invoice #%1 on QBO to map with this Memo #%2', $modelOrder->getIncrementId(), $id));

            } else {
                $amountReceive = $invoice['TotalAmt'] - $invoice['Balance'];
                if ($this->getShippingAllow() == true) {
                    $amountRefund = $model->getBaseGrandTotal();
                } else $amountRefund = $model->getBaseGrandTotal() - $model->getBaseShippingAmount();
                if ($amountRefund < 0) {
                    $this->addLog($id, null, __('QuickBooks only accept credit memos with transaction amount that is 0 or greater. Recorded amount: %1', $amountRefund));
                } else if ($amountReceive < $amountRefund) {
                    if ($invoiceEnabled == 0) {
                        $this->addLog($id, null, __('You need to update this Invoice #%1 on QuickBooksOnline before credit memo can be synced', $modelOrder->getIncrementId()));
                    } else
                        $this->addLog($id, null, 'Refund amount must be equal or less than invoiced amount. Please sync invoice before credit memo.');
                } else {
                    $checkCredit = $this->checkCreditmemo($id);
                    if (isset($checkCredit['Id'])) {
                        $this->addLog(
                            $id,
                            $checkCredit['Id'],
                            __('This Creditmemo already exists.'),
                            'skip'
                        );
                    } else {
                        if (!$model->getId()) {
                            throw new LocalizedException(__('We can\'t find the Creditmemo #%1', $id));
                        }
                        /**
                         * check the case delete customer before sync their creditmemo
                         */
                        $customerIsGuest = true;
                        if ($modelOrder->getCustomerId()) {
                            $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\ResourceModel\Customer\Collection')->addFieldToFilter('entity_id', $modelOrder->getCustomerId());
                            if (!$customerCollection->getData()) {
                                $customerIsGuest = true;
                            } else $customerIsGuest = false;

                        }

                        $this->setModel($model);
                        $this->prepareParams($customerIsGuest);
                        $params   = $this->getParameter();
                        $response = $this->sendRequest(\Zend_Http_Client::POST, 'creditmemo', $params);
                        if (!empty($response['CreditMemo']['Id'])) {
                            $qboId = $response['CreditMemo']['Id'];
                            $this->addLog($id, $qboId);
                        }
                        $this->parameter = [];

                        /** Sync memo items when creating new memo */
                        if ($this->config->getTrackQty()) {
                            $itemCreditCollection = $item;
                            if (!($itemCreditCollection and $itemCreditCollection[0] instanceof \Magento\Sales\Model\Order\CreditMemo\Item)) {
                                $itemCreditCollection = $this->getModel()->getAllItems();
                            }
                            foreach ($itemCreditCollection as $creditItemModel) {
                                $this->_item->sync($creditItemModel->getProductId(), true);
                            }
                        }

                        return isset($qboId) ? $qboId : null;
                    }
                    $this->parameter = [];
                }
            }
        } catch (LocalizedException $e) {
            $this->addLog($id, null, $e->getMessage());
        }
    }

    /**
     * @param $id
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function loadByIncrementId($id)
    {
        $ids = $this->_creditmemo->getCollection()->addFieldToFilter('increment_id', $id)->getAllIds();

        if (!empty($ids)) {
            reset($ids);
            $this->_creditmemo->load(current($ids));
        }

        return $this->_creditmemo;
    }

    /**
     * @param null $customerIsGuest
     *
     * @return $this
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function prepareParams($customerIsGuest = null)
    {
        $model      = $this->getModel();
        $modelOrder = $this->_orderFactory->create()->load($this->getModel()->getOrderId());

        //set billing address
        $billCountry = $this->prepareBillingAddress();

        $prefix = $this->config->getPrefix('creditmemos');
        $params = [
            'DocNumber'    => $prefix . $model->getIncrementId(),
            'TxnDate'      => (new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(new \DateTime) == 0
                ? $model->getCreatedAt() :
                $this->timezone->date($model->getCreatedAt())->format('Y-m-d'),
            'TxnTaxDetail' => ['TotalTax' => $model->getBaseTaxAmount()],
            'CustomerRef'  => $this->prepareCustomerId($customerIsGuest),
            'Line'         => $this->prepareLineItems($billCountry),
            'TotalAmt'     => $model->getBaseGrandTotal(),
            'BillEmail'    => ['Address' => mb_substr((string)$modelOrder->getCustomerEmail(), 0, 100)],
        ];

        $this->setParameter($params);
        // st Tax
        if ($this->config->getCountry() == 'OTHER' && $model->getBaseTaxAmount() > 0) {
            $this->prepareTax();
        }

        if ($this->getShippingAllow() == true) {
            $this->prepareShippingAddress();
        }

        //set payment method
        $this->preparePaymentMethod();

        return $this;
    }

    /**
     * Create Tax
     */
    public function prepareTax()
    {
        $params['TxnTaxDetail'] = [
            'TotalTax' => $this->getModel()->getBaseTaxAmount(),
        ];
    }

    /**
     * @param $taxId
     *
     * @return int
     */
    protected function getTaxQBOIdFromTaxItem($taxId)
    {
        $code      = $this->salesOrderTax->create()->load($taxId)->getCode();
        $taxCodeId = $this->tax->create()->loadByCode($code)->getQboId();

        return $taxCodeId;
    }

    /**
     * @param null $customerIsGuest
     *
     * @return array
     * @throws LocalizedException
     */
    public function prepareCustomerId($customerIsGuest = null)
    {
        try {
            $modelOrder = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
            $customerId = $modelOrder->getCustomerId();
            if ($customerId and $customerIsGuest == false) {
                $cusRef = $this->_syncCustomer->sync($customerId, false);
            } else {
                $cusRef = $this->_syncCustomer->syncGuest(
                    $modelOrder->getBillingAddress(),
                    $modelOrder->getShippingAddress()
                );
            }

            return ['value' => $cusRef];
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Can\'t sync customer on Invoice to QBO')
            );
        }
    }

    /**
     * @return null|string
     */
    public function prepareBillingAddress()
    {
        /** @var \Magento\Sales\Model\Order\Address $billAddress */
        $billAddress = $this->getModel()->getBillingAddress();
        if ($billAddress !== null) {
            $params['BillAddr'] = $this->getAddress($billAddress);
            $billCountry        = isset($params['BillAddr']['Country']) ? $params['BillAddr']['Country'] : '';
            if ($this->config->getCountry() == 'FR') {
                if ($billCountry == 'FR')
                    $params['TransactionLocationType'] = 'WithinFrance';
                else if (in_array($billCountry, $this->config->getFranceTerritories()))
                    $params['TransactionLocationType'] = 'FranceOverseas';
                else if (in_array($billCountry, $this->config->getEUCountries()))
                    $params['TransactionLocationType'] = 'OutsideFranceWithEU';
                else
                    $params['TransactionLocationType'] = 'OutsideEU';
            }
            $this->setParameter($params);

            return $billCountry;
        }

        return null;
    }

    /**
     * @return void
     */
    public function prepareShippingAddress()
    {
        $shippingAddress = $this->getModel()->getShippingAddress();
        if ($shippingAddress !== null) {
            $params['ShipAddr'] = $this->getAddress($shippingAddress);
            $this->setParameter($params);
        }
    }

    /**
     * set payment method
     */
    public function preparePaymentMethod()
    {
        $orderData = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
        $code      = $orderData->getPayment()->getMethodInstance()->getCode();


        $paymentMethod = $this->_paymentMethods->create()->load($code, 'payment_code');

        if ($paymentMethod->getId()) {
            $params['PaymentMethodRef'] = [
                'value' => $paymentMethod->getQboId(),
                'name'  => $paymentMethod->getTitle()
            ];
            $this->setParameter($params);
        }
    }

    /**
     * @param $billCountry
     *
     * @return array
     * @throws LocalizedException
     */
    public function prepareLineItems($billCountry = null)
    {
        try {
//            $model                = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
            /** @var /Magento/Sales/Model/Order/Creditmemo $creditModel */
            $creditModel          = $this->getModel();
            $itemCreditCollection = $creditModel->getItem();
            if (!($itemCreditCollection and $itemCreditCollection[0] instanceof \Magento\Sales\Model\Order\CreditMemo\Item)) {
                $itemCreditCollection = $this->getModel()->getAllItems();
            }
            $i     = 1;
            $lines = [];

            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
            $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
            foreach ($itemCreditCollection as $item) {
                $registryObject->unregister('check_to_syn' . $item->getProductId());
                $qty   = $item->getQty();
                $sku   = $item->getSku();
                $total = $item->getBaseRowTotal();
                $tax   = $item->getBaseTaxAmount();
                $price = $item->getBasePrice();
                if (!empty($item->getOrderItem())) {
                    $productType = $item->getOrderItem()->getProductType();
                    if ($productType == 'bundle')
                        if (!empty($item->getOrderItem()->getProductOptionByCode('product_calculations'))
                            && $item->getOrderItem()->getProductOptionByCode('product_calculations') == 0) {
                            $total = 0;
                            $price = 0;
                        }
                }

                $itemId = $this->_item->sync($item->getProductId());
                if (!$itemId) throw new \Exception(
                    __('Can\'t sync product with SKU:%1 on CreditMemo to QBO', $sku)
                );
//                }
                /** Only sync up refunded items */
                if ($qty > 0 && isset($total) && isset($itemId)) {
                    if ($this->config->getCountry() == 'FR') {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $this->taxSync->getFRProductTax($billCountry)]
                            ],
                        ];
                    } else if ($this->config->getCountry() == 'OTHER') {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $tax ? 'TAX' : 'NON'],
                            ],
                        ];
                    } else {
                        $taxId   = $this->prepareTaxCodeRef($item->getOrderItemId());
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $taxId ? $taxId : $this->getTaxFree()]
                            ],
                        ];
                    }
                }

                $i++;
            }

            if (empty($itemCreditCollection) || $creditModel->getAdjustment() != 0) {
                $lineAdjustment = $this->prepareLineAdjustment();
                $lines[]        = $lineAdjustment;
            }

            //build shipping fee
            // set shipping fee
            if ($this->getShippingAllow() == true) {
                $lineShipping = $this->prepareLineShippingFee($billCountry);
                if (!empty($lineShipping))
                    $lines[] = $lineShipping;
            }

            //build discount fee
            $lines[] = $this->prepareLineDiscountAmount();

            return $lines;
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __('Error when syncing products: %1', $exception->getMessage())
            );
        }
    }

    /**
     * @return mixed
     */
    public function getTaxFreeId()
    {
        $localId   = $this->config->getTaxFree();
        $taxCodeId = $this->tax->create()->load($localId, 'tax_id')->getQboId();

        return $taxCodeId;
    }

    /**
     * @param $itemId
     *
     * @return bool|int
     */
    public function prepareTaxCodeRef($itemId)
    {
        $taxCode = 'tax_zero_qb';
        /** @var \Magento\Sales\Model\Order\Tax\Item $modelTaxItem */
        $modelTaxItem = ObjectManager::getInstance()->create(\Magento\Sales\Model\Order\Tax\Item::class)->load($itemId, 'item_id');
        if ($modelTaxItem) {
            $taxId        = $modelTaxItem->getData('tax_id');
            $modelTax     = ObjectManager::getInstance()->create('Magento\Sales\Model\Order\TaxFactory');
            $modelTaxData = $modelTax->create()->getCollection()
                ->addFieldToFilter("tax_id", $taxId)->getFirstItem();

            /** @var \Magento\Sales\Model\Order\Tax $modelTaxData */
            if (!empty($modelTaxData->getCode())) {
                $taxCode = $modelTaxData->getCode();
            }
            $tax = $this->tax->create()->loadByCode($taxCode);
            if ($tax->getQboId() && $tax->getQboId() > 0) {
                $taxCodeId = $tax->getQboId();

                return $taxCodeId;
            }
        }

        return false;
    }

    /**
     * @return bool|int
     */
    public function getTaxFree()
    {
        $modelTax = $this->tax->create()->load('tax_zero_qb', 'tax_code');
        if ($modelTax) {
            return $modelTax->getQboId();
        }

        return false;
    }

    /**
     * @param bool $hasTax
     *
     * @return array
     */
    public function getTaxCodeRef($hasTax)
    {
        return ['value' => $hasTax ? 'TAX' : 'NON'];
    }

    /**
     * @param null $billCountry
     *
     * @return array
     * @throws LocalizedException
     */
    public function prepareLineShippingFee($billCountry = null)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $model */
        $model          = $this->getModel();
        $shippingAmount = $model->getBaseShippingAmount();
        if ($this->config->getCountry() == 'FR') {
            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef'    => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => $this->taxSync->getFRShippingTax($billCountry)],
                ],
            ];
        } else if ($this->config->getCountry() != 'OTHER') {
            $taxItems = $this->taxItem->getTaxItemsByOrderId($model->getOrderId());
            foreach ($taxItems as $key => $value) {
                if (isset($value['taxable_item_type']) && $value['taxable_item_type'] == 'shipping') {
                    $taxId     = $value['tax_id'];
                    $taxCodeId = $this->getTaxQBOIdFromTaxItem($taxId);
                    break;
                }
            }

            if ($model->getBaseShippingTaxAmount() == 0)
                $taxCodeId = $this->getTaxFreeId();

            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef'    => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => isset($taxCodeId) ? $taxCodeId : $this->config->getTaxShipping()],
                ],
            ];
        } else {
            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'SHIPPING_ITEM_ID'],
                ],
            ];
        }

        return $lines;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    protected function prepareLineAdjustment()
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $model */
        $model        = $this->getModel();
        $adjustment   = $model->getBaseAdjustment();
        $adjustmentId = $this->_item->getAdjustmentItem();
        $lines        = [
            'Amount'              => $adjustment,
            'DetailType'          => 'SalesItemLineDetail',
            'SalesItemLineDetail' => [
                'ItemRef'   => ['value' => $adjustmentId],
                'UnitPrice' => $adjustment,
                'Qty'       => 1
            ]
        ];

        return $lines;
    }

    /**
     * @return array
     */
    public function prepareLineDiscountAmount()
    {
        $discountAmount       = $this->getModel()->getBaseDiscountAmount();
        $discountCompensation = $this->getModel()->getBaseDiscountTaxCompensationAmount();
        $lines                = [
            'Amount'             => $discountAmount ? (-1 * $discountAmount - $discountCompensation) : 0,
            'DetailType'         => 'DiscountLineDetail',
            'DiscountLineDetail' => [
                'PercentBased' => false,
            ]
        ];

        return $lines;
    }

    /**
     * Check creditmemo by Increment Id
     *
     * @param $id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkCreditmemo($id)
    {
        $prefix = $this->config->getPrefix('creditmemos');
        $name   = $prefix . $id;
        $query  = "SELECT Id, SyncToken FROM CreditMemo WHERE DocNumber='{$name}'";

        return $this->query($query);
    }

    /**
     * Check invoice by Increment Id
     *
     * @param $id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkInvoice($id)
    {
        $prefix = $this->config->getPrefix('order');
        $name   = $prefix . $id;
        $query  = "SELECT TotalAmt, Balance FROM invoice WHERE DocNumber='{$name}'";

        return $this->query($query);
    }

    /**
     * @return mixed
     */
    public function getShippingAllow()
    {
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
        try {
            $shippingAllow = $registryObject->registry('shipping_allow');
            if (isset($shippingAllow)) return $shippingAllow;
        } catch (\Exception $exception) {
        }
        /** @var \Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting $preferenceSetting */
        $preferenceSetting = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting');
        $shippingAllow     = $preferenceSetting->getShippingAllow();
        $registryObject->register('shipping_allow', $shippingAllow);

        return $shippingAllow;
    }

}

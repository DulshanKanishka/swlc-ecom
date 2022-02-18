<?php

namespace NeoSolax\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\TaxCode;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order\Creditmemo as CreditmemoModel;
use Magento\Sales\Model\Order\TaxFactory as SalesOrderTax;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use NeoSolax\QuickBooksOnline\Helper\Data;

class Creditmemo extends \Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo
{
    public function __construct(
        Data $helper,
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
        $this->helper = $helper;
        parent::__construct($client, $log, $creditmemo, $item, $customer, $paymentMethods, $product, $logger, $taxFactory, $taxSync, $orderFactory, $config, $context, $taxItem, $salesOrderTax, $timezone);
    }

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

            foreach ($modelOrder->getInvoiceCollection()->getItems() as $item) {
                $incrementId = $item->getIncrementId();
            }

            $invoice    = $this->checkInvoice($incrementId);
            if (empty($invoice)) {
                $this->addLog($id, null, __('We can\'t find the Invoice #%1 on QBO to map with this Memo #%2', $modelOrder->getIncrementId(), $id));
            } else {
                $amountReceive = $invoice['TotalAmt'] - $invoice['Balance'];
                if ($this->getShippingAllow() == true) {
                    $amountRefund = $model->getBaseGrandTotal();
                } else {
                    $amountRefund = $model->getBaseGrandTotal() - $model->getBaseShippingAmount();
                }
                if ($amountRefund < 0) {
                    $this->addLog($id, null, __('QuickBooks only accept credit memos with transaction amount that is 0 or greater. Recorded amount: %1', $amountRefund));
                } elseif ($amountReceive < $amountRefund) {
                    if ($invoiceEnabled == 0) {
                        $this->addLog($id, null, __('You need to update this Invoice #%1 on QuickBooksOnline before credit memo can be synced', $modelOrder->getIncrementId()));
                    } else {
                        $this->addLog($id, null, 'Refund amount must be equal or less than invoiced amount. Please sync invoice before credit memo.');
                    }
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
                            } else {
                                $customerIsGuest = false;
                            }
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

    protected function prepareParams($customerIsGuest = null)
    {
        $model      = $this->getModel();
        $modelOrder = $this->_orderFactory->create()->load($this->getModel()->getOrderId());

        //set billing address
        $billCountry = $this->prepareBillingAddress();

        $prefix = $this->config->getPrefix('creditmemos');
        $params = [
            'DocNumber'    => $prefix . $model->getIncrementId(),
            'TxnDate'      => (new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(new \DateTime()) == 0
                ? $model->getCreatedAt() :
                $this->timezone->date($model->getCreatedAt())->format('Y-m-d'),
            'TxnTaxDetail' => ['TotalTax' => $model->getBaseTaxAmount()],
            'CustomerRef'  => $this->prepareCustomerId($customerIsGuest),
            'Line'         => $this->prepareLineItems($billCountry),
            'TotalAmt'     => $model->getBaseGrandTotal(),
            'BillEmail'    => ['Address' => mb_substr((string)$modelOrder->getCustomerEmail(), 0, 100)],
            "GlobalTaxCalculation" => "TaxInclusive"
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

    public function prepareLineItems($billCountry = null)
    {
        try {
            $creditModel          = $this->getModel();
            $itemCreditCollection = $creditModel->getItem();
            if (!($itemCreditCollection and $itemCreditCollection[0] instanceof \Magento\Sales\Model\Order\CreditMemo\Item)) {
                $itemCreditCollection = $this->getModel()->getAllItems();
            }
            $i     = 1;
            $lines = [];
            foreach ($itemCreditCollection as $itemm) {
                $item = $itemm->getOrderItem();
                $productType    = $item->getProductType();
                $total          = 0;
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
                if ($productType == 'configurable') {
                    $total         = $this->helper->getExcludeAmount($itemm->getRowTotalInclTax(), $item->getTaxPercent());
                    $totslIncTax  = $itemm->getRowTotalInclTax();
                    $tax           = $itemm->getBaseTaxAmount() > 0 ? true : false;
                    $childrenItems = $item->getChildrenItems();
                    if (isset($childrenItems[0])) {
                        $productId = $childrenItems[0]->getProductId();
                        $sku       = $childrenItems[0]->getSku();
                        $qty       = $itemm->getQty();
                    } else {
                        $productId = $item->getProductId();
                        $sku       = $item->getSku();
                        $qty       = $itemm->getQty();
                    }
                    $price = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($itemm->getPriceInclTax(), $item->getTaxPercent());
                    $registryObject->unregister('check_to_syn' . $productId);
                    $itemId = $this->_item->syncBySku($sku, false);
                    if (!$itemId) {
                        throw new \Exception(
                            __('Can\'t sync Product with SKU:%1 on Order to QBO', $sku)
                        );
                    }
                } elseif ($item->getParentItem() && ($productType == 'virtual' || $productType == 'simple')) {
                    if ($item->getParentItem()->getProductType() == 'configurable') {
                        continue;
                    } else {
                        $productId = $item->getProductId();
                        $sku       = $item->getSku();
                        $qty       = $itemm->getQty();
                        $total     = $this->helper->getExcludeAmount($itemm->getRowTotalInclTax(), $item->getTaxPercent());
                        $totslIncTax  = $itemm->getRowTotalInclTax();
                        $price     = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($itemm->getPriceInclTax(), $item->getTaxPercent());
                        $tax       = $itemm->getBaseTaxAmount() > 0 ? true : false;

                        $registryObject->unregister('check_to_syn' . $productId);
                        if (!empty($item->getProductOptions()['info_buyRequest']['options'])) {
                            $itemId = $this->_item->sync($item->getProductId());
                        } else {
                            $itemId = $this->_item->syncBySku($sku);
                        }
                        $registryObject->unregister('check_to_syn' . $productId);
                        if (!$itemId) {
                            throw new \Exception(
                                __('Can\'t sync Product with SKU:%1 on Order to QBO', $sku)
                            );
                        }
                    }
                } else {
                    $productId = $item->getProductId();
                    $sku       = $item->getSku();
                    $qty       = $itemm->getQty();
                    $total     = $this->helper->getExcludeAmount($itemm->getRowTotalInclTax(), $item->getTaxPercent());
                    $totslIncTax  = $itemm->getRowTotalInclTax();
                    $price     = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($itemm->getPriceInclTax(), $item->getTaxPercent());
                    $tax       = $itemm->getBaseTaxAmount() > 0 ? true : false;

                    $registryObject->unregister('check_to_syn' . $productId);
                    if ($productType == 'bundle') {
                        $priceType = $item->getProductOptionByCode('product_calculations');
                        if ($priceType == 0) {
                            $price = 0;
                            $total = 0;
                        }
                        $itemId = $this->_item->sync($productId);
                    } elseif (!empty($item->getProductOptions()['info_buyRequest']['options'])) {
                        $itemId = $this->_item->sync($item->getProductId());
                    } else {
                        $itemId = $this->_item->syncBySku($sku);
                    }
                    $registryObject->unregister('check_to_syn' . $productId);
                    if (!$itemId) {
                        throw new \Exception(
                            __('Can\'t sync Product with SKU:%1 on Order to QBO', $sku)
                        );
                    }
                }
                if (!empty($itemId)) {
                    if ($this->config->getCountry() == 'FR') {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'Description'         => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $this->taxSync->getFRProductTax($billCountry)],
                                'TaxInclusiveAmt' => $totslIncTax
                            ],
                        ];
                    } elseif ($this->config->getCountry() == 'OTHER') {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'Description'         => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $tax ? 'TAX' : 'NON'],
                                'TaxInclusiveAmt' => $totslIncTax
                            ],
                        ];
                    } else {
                        $taxId   = $this->prepareTaxCodeRef($item->getItemId());
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'Description'         => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $taxId ? $taxId : $this->getTaxFreeId()],
                                'TaxInclusiveAmt' => $totslIncTax
                            ],
                        ];
                    }

                    $i++;
                } else {
                    continue;
                }
            }

            if (empty($itemCreditCollection) || $creditModel->getAdjustment() != 0) {
                $lineAdjustment = $this->prepareLineAdjustment();
                $lines[]        = $lineAdjustment;
            }

            //build shipping fee
            // set shipping fee
            if ($this->getShippingAllow() == true) {
                $lineShipping = $this->prepareLineShippingFee($billCountry);
                if (!empty($lineShipping)) {
                    $lines[] = $lineShipping;
                }
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

    private function getTaxCode()
    {
        $order  = $this->getModel()->getOrder();
        $objMng = ObjectManager::getInstance();

        $orderTaxManagement = $objMng->get(\Magento\Tax\Api\OrderTaxManagementInterface::class);
        $orderTaxDetails    = $orderTaxManagement->getOrderTaxDetails($order->getId())->getAppliedTaxes();
        if (isset($orderTaxDetails[0])) {
            return $orderTaxDetails[0]->getCode();
        }

        return null;
    }

    private function getTaxRateRef()
    {
        $taxCode = $this->getTaxCode();
        if (empty($taxCode)) {
            return null;
        }
        $qboTaxModel = ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Model\Tax::class);
        $qboTaxModel->loadByCode($taxCode);

        return $qboTaxModel->getQboId();
    }

    public function prepareTax()
    {
        $taxRateRef = null;

        try {
            $taxRateRef = $this->getTaxRateRef();
        } catch (\Exception $e) {
        }

        $params['TxnTaxDetail'] = [
            'TotalTax' => $this->getModel()->getBaseTaxAmount(),
        ];

        if (isset($taxRateRef)) {
            $params['TxnTaxDetail']['TxnTaxCodeRef'] = [
                'value' => $taxRateRef
            ];
        }

        return $this->setParameter($params);
    }

    public function prepareLineShippingFee($billCountry = null)
    {
        $model          = $this->getModel();

        $shipping = $model->getShippingInclTax();
        $items = $model->getOrder()->getItems();
        foreach ($items as $item) {
            $taxRate = $item->getTaxPercent();
            if (!$taxRate) {
                $taxRate = 5;
            }
            if ($taxRate) {
                break;
            }
        }

        $shippingAmount = $this->helper->getExcludeAmount($shipping, $taxRate);
        if ($this->config->getCountry() == 'FR') {
            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef'    => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => $this->taxSync->getFRShippingTax($billCountry)],
                ],
            ];
        } elseif ($this->config->getCountry() != 'OTHER') {
            $taxItems = $this->taxItem->getTaxItemsByOrderId($model->getOrderId());
            foreach ($taxItems as $key => $value) {
                if (isset($value['taxable_item_type']) && $value['taxable_item_type'] == 'shipping') {
                    $taxId     = $value['tax_id'];
                    $taxCodeId = $this->getTaxQBOIdFromTaxItem($taxId);
                    break;
                }
            }

            if ($model->getBaseShippingTaxAmount() == 0) {
                $taxCodeId = $this->getTaxFreeId();
            }

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

    protected function prepareLineAdjustment()
    {
        $model        = $this->getModel();
        $adjustment   = $model->getBaseAdjustment();
        $adjustmentId = $this->_item->getAdjustmentItem();
        $lines        = [
            'Amount'              => $adjustment,
            'DetailType'          => 'SalesItemLineDetail',
            'SalesItemLineDetail' => [
                'ItemRef'   => ['value' => $adjustmentId],
                'UnitPrice' => $adjustment,
                'Qty'       => 1,
                'TaxCodeRef' => ['value' => $this->getTaxFree()]
            ]
        ];

        return $lines;
    }

    public function prepareLineDiscountAmount()
    {
        $discountAmount       = $this->getModel()->getBaseDiscountAmount();
        $discountCompensation = $this->getModel()->getBaseDiscountTaxCompensationAmount();
        foreach ($this->getModel()->getOrder()->getItems() as $item) {
            $taxRate = $item->getTaxPercent();
        }
        $lines                = [
            'Amount'             => $discountAmount ? (-1 *$this->helper->getExcludeAmount($this->getModel()->getDiscountAmount(), $taxRate)) : 0,
            'DetailType'         => 'DiscountLineDetail',
            'DiscountLineDetail' => [
                'PercentBased' => false,
            ]
        ];

        return $lines;
    }
}

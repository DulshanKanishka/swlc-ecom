<?php

namespace NeoSolax\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\PaymentMethodsFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\TaxCode;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order\TaxFactory as SalesOrderTax;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use NeoSolax\QuickBooksOnline\Helper\Data;
use Psr\Log\LoggerInterface;

class Order extends \Magenest\QuickBooksOnline\Model\Synchronization\Order
{
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Data $helper,
        Client $client,
        Log $log,
        OrderFactory $orderFactory,
        PaymentMethodsFactory $paymentMethods,
        Item $item,
        Customer $customer,
        TaxFactory $taxFactory,
        TaxCode $taxSync,
        Config $config,
        LoggerInterface $logger,
        Context $context,
        State $state,
        TaxItem $taxItem,
        SalesOrderTax $salesOrderTax,
        TimezoneInterface $timezone
    ) {
        $this->productRepository = $productRepository;
        $this->helper = $helper;
        parent::__construct($client, $log, $orderFactory, $paymentMethods, $item, $customer, $taxFactory, $taxSync, $config, $logger, $context, $state, $taxItem, $salesOrderTax, $timezone);
    }

    public function sync($incrementId, $newOrder = false)
    {
        try {
            $model = $this->_orderFactory->create()->loadByIncrementId($incrementId);
            /** @var \Magento\Sales\Model\Order\Item $item */

            foreach ($model->getInvoiceCollection()->getItems() as $item) {
                $inviceIncrementId = $item->getIncrementId();
            }

            $checkOrder = $this->checkOrder($inviceIncrementId);
            if (isset($checkOrder['Id'])) {
                $this->addLog($incrementId, $checkOrder['Id'], 'This Order already exists.', 'skip');
            } else {
                if (!$model->getId()) {
                    throw new LocalizedException(__('We can\'t find the Order #%1', $inviceIncrementId));
                }

                /**
                 * check the case delete customer before sync their order
                 */
                $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\ResourceModel\Customer\Collection')->addFieldToFilter('entity_id', $model->getCustomerId());
                if (!$customerCollection->getData()) {
                    $model->setCustomerId(null);
                }

                $this->setModel($model);
                $this->prepareParams();
                $params = $this->getParameter();
                $response = $this->sendRequest(\Zend_Http_Client::POST, 'invoice', $params);
                if (!empty($response['Invoice']['Id'])) {
                    $qboId = $response['Invoice']['Id'];
                    $this->addLog($incrementId, $qboId);
                }
                $this->parameter = [];

                /** @var \Magento\Framework\Registry $registryObject */
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');

                $registryObject->register('skip_log', true);

                if (!$this->config->getTrackQty()) {
                    foreach ($model->getAllItems() as $orderItem) {
                        $registryObject->unregister('check_to_syn' . $orderItem->getProductId());
                        if ($orderItem->getProductType() == 'bundle') {
                            $this->_item->sync($orderItem->getProductId());
                        } else {
                            if ($this->state->getAreaCode() == 'adminhtml' && ($newOrder == true)) {
                                $orderedQty = $orderItem->getBuyRequest()->getData('qty');
                            } else {
                                $orderedQty = null;
                            }
                            //product with customizable options cannot be synced by SKU
                            if (!empty($orderItem->getProductOptions()['info_buyRequest']['options'])) {
                                $this->_item->sync($orderItem->getProductId(), true, null, $orderedQty);
                            } else {
                                $this->_item->syncBySku($orderItem->getSku(), true, $orderedQty);
                            }
                        }
                    }
                }
                $registryObject->unregister('skip_log');

                return isset($qboId) ? $qboId : null;
            }

            $this->parameter = [];
        } catch (LocalizedException $e) {
            $this->parameter = [];
            $this->addLog($incrementId, null, $e->getMessage());
        }

        return null;
    }

    protected function prepareParams()
    {
        $model = $this->getModel();
        $prefix = $this->config->getPrefix('order');

        $billCountry = $this->prepareBillingAddress();
        foreach ($model->getInvoiceCollection()->getItems() as $item) {
            $incrementId = $item->getIncrementId();
        }
        $params = [
            'DocNumber' => $prefix . $incrementId,
            'TxnDate' => (new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(new \DateTime()) == 0 ? $model->getCreatedAt() :
                $this->timezone->date($model->getCreatedAt())->format('Y-m-d'),
            'CustomerRef' => $this->prepareCustomerId(),
            'Line' => $this->prepareLineItems($billCountry),
            'TotalAmt' => $model->getBaseGrandTotal(),
            'BillEmail' => ['Address' => mb_substr((string)$model->getCustomerEmail(), 0, 100)],
            "GlobalTaxCalculation" => "TaxInclusive"
        ];

        $this->setParameter($params);
        if ($this->config->getCountry() == 'OTHER' && $model->getBaseTaxAmount() > 0) {
            $this->prepareTax();
        }

        if ($this->getShippingAllow() == true) {
            $this->prepareShippingAddress();
        }

        return $this;
    }

    public function prepareLineItems($billCountry = null)
    {
        try {
            $i = 1;
            $lines = [];
            foreach ($this->getModel()->getItems() as $item) {
                $productType = $item->getProductType();
                $total = 0;
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
                if ($productType == 'configurable') {
                    $total = $this->helper->getExcludeAmount($item->getRowTotalInclTax(), $item->getTaxPercent());
                    $totslIncTax = $item->getRowTotalInclTax();
                    $tax = $item->getBaseTaxAmount() > 0 ? true : false;
                    $childrenItems = $item->getChildrenItems();
                    if (isset($childrenItems[0])) {
                        $productId = $childrenItems[0]->getProductId();
                        $sku = $childrenItems[0]->getSku();
                        $qty = $childrenItems[0]->getQtyOrdered();
                    } else {
                        $productId = $item->getProductId();
                        $sku = $item->getSku();
                        $qty = $item->getQtyOrdered();
                    }
                    $price = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($item->getPriceInclTax(), $item->getTaxPercent());
                    $modelPro = $this->productRepository->get($sku);
                    $QBId = $this->_item->getQboId($modelPro);
                    $itemId = $QBId;
                    $registryObject->unregister('check_to_syn' . $productId);
                    if ($QBId == 0 || !$QBId) {
                        $itemId = $this->_item->syncBySku($sku, false);
                    }
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
                        $sku = $item->getSku();
                        $qty = $item->getQtyOrdered();
                        $total = $this->helper->getExcludeAmount($item->getRowTotalInclTax(), $item->getTaxPercent());
                        $totslIncTax = $item->getRowTotalInclTax();
                        $price = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($item->getPriceInclTax(), $item->getTaxPercent());
                        $tax = $item->getBaseTaxAmount() > 0 ? true : false;

                        $registryObject->unregister('check_to_syn' . $productId);
                        $modelPro = $this->productRepository->get($sku);
                        $QBId = $this->_item->getQboId($modelPro);
                        $itemId = $QBId;
                        if (!empty($item->getProductOptions()['info_buyRequest']['options'])) {
                            if ($QBId == 0 || !$QBId) {
                                $itemId = $this->_item->sync($item->getProductId());
                            }
                        } else {
                            if ($QBId == 0 || !$QBId) {
                                $itemId = $this->_item->syncBySku($sku);
                            }
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
                    $sku = $item->getSku();
                    $qty = $item->getQtyOrdered();
                    $total = $this->helper->getExcludeAmount($item->getRowTotalInclTax(), $item->getTaxPercent());
                    $totslIncTax = $item->getRowTotalInclTax();
                    $price = $qty > 0 ? $total / $qty : $this->helper->getExcludeAmount($item->getPriceInclTax(), $item->getTaxPercent());
                    $tax = $item->getBaseTaxAmount() > 0 ? true : false;

                    $registryObject->unregister('check_to_syn' . $productId);
                    if ($productType !== 'bundle') {
                        $modelPro = $this->productRepository->get($sku);
                        $QBId = $this->_item->getQboId($modelPro);
                        $itemId = $QBId;
                    }
                    if ($productType == 'bundle') {
//                        $priceType = $item->getProductOptionByCode('product_calculations');
//                        if ($priceType == 0) {
                        $price = 0;
                        $total = 0;
                        $totslIncTax = 0;
//                        }
                        $itemId = $this->_item->sync($productId);
                    } elseif (!empty($item->getProductOptions()['info_buyRequest']['options'])) {
                        if ($QBId == 0 || !$QBId) {
                            $itemId = $this->_item->sync($item->getProductId());
                        }
                    } else {
                        if ($QBId == 0 || !$QBId) {
                            $itemId = $this->_item->syncBySku($sku);
                        }
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
                            'LineNum' => $i,
                            'Amount' => $total,
                            'DetailType' => 'SalesItemLineDetail',
                            'Description' => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef' => ['value' => $itemId],
                                'UnitPrice' => $price,
                                'Qty' => $qty,
                                'TaxCodeRef' => ['value' => $this->taxSync->getFRProductTax($billCountry)],
                                'TaxInclusiveAmt' => $totslIncTax
                            ],
                        ];
                    } elseif ($this->config->getCountry() == 'OTHER') {
                        $lines[] = [
                            'LineNum' => $i,
                            'Amount' => $total,
                            'DetailType' => 'SalesItemLineDetail',
                            'Description' => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef' => ['value' => $itemId],
                                'UnitPrice' => $price,
                                'Qty' => $qty,
                                'TaxCodeRef' => ['value' => $tax ? 'TAX' : 'NON'],
                                'TaxInclusiveAmt' => $totslIncTax
                            ],
                        ];
                    } else {
                        $taxId = $this->prepareTaxCodeRef($item->getItemId());
                        $lines[] = [
                            'LineNum' => $i,
                            'Amount' => $total,
                            'DetailType' => 'SalesItemLineDetail',
                            'Description' => $item->getName(),
                            'SalesItemLineDetail' => [
                                'ItemRef' => ['value' => $itemId],
                                'UnitPrice' => $price,
                                'Qty' => $qty,
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

            // set shipping fee
            if ($this->getShippingAllow() == true) {
                $lineShipping = $this->prepareLineShippingFee($billCountry);
                if (!empty($lineShipping)) {
                    $lines[] = $lineShipping;
                }
            }

            // set discount
            $lines[] = $this->prepareLineDiscountAmount();

            return $lines;
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __('Error when syncing products: %1', $exception->getMessage())
            );
        }
    }

    protected function prepareLineShippingFee($billCountry = null)
    {
        $model = $this->getModel();
        $shipping = $model->getShippingInclTax();
        $items = $model->getItems();
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
                'Amount' => $shippingAmount ? $shippingAmount : 0,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => $this->taxSync->getFRShippingTax($billCountry)],
                ],
            ];
        } elseif ($this->config->getCountry() != 'OTHER') {
            $taxItems = $this->taxItem->getTaxItemsByOrderId($model->getId());
            foreach ($taxItems as $key => $value) {
                if (isset($value['taxable_item_type']) && $value['taxable_item_type'] == 'shipping') {
                    $taxId = $value['tax_id'];
                    $taxCodeId = $this->getTaxQBOIdFromTaxItem($taxId);
                    break;
                }
            }

            if ($model->getBaseShippingAmount() == 0) {
                $taxCodeId = $this->getTaxFreeId();
            }

            $lines = [
                'Amount' => $shippingAmount ? $shippingAmount : 0,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => isset($taxCodeId) ? $taxCodeId : $this->config->getTaxShipping()],
                ],
            ];
        } else {
            $lines = [
                'Amount' => $shippingAmount ? $shippingAmount : 0,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'SHIPPING_ITEM_ID'],
                ],
            ];
        }

        return $lines;
    }

    protected function prepareLineDiscountAmount()
    {
        $discountAmount = $this->getModel()->getDiscountAmount();
        $discountCompensation = $this->getModel()->getDiscountTaxCompensationAmount();
        foreach ($this->getModel()->getItems() as $item) {
            $taxRate = $item->getTaxPercent();
        }
        $lines = [
            'Amount' => $discountAmount ? (-1 * $this->helper->getExcludeAmount($this->getModel()->getDiscountAmount(), $taxRate)) : 0,
            'DetailType' => 'DiscountLineDetail',
            'DiscountLineDetail' => [
                'PercentBased' => false,
            ]
        ];

        return $lines;
    }
}

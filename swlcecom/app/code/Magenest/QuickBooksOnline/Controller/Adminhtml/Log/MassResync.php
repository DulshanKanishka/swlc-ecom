<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Log;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractLog;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\View\Result\PageFactory;
use Magenest\QuickBooksOnline\Model\ResourceModel\Log\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Customer;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magenest\QuickBooksOnline\Model\Synchronization\Order;
use Magenest\QuickBooksOnline\Model\Synchronization\Invoice;
use Magenest\QuickBooksOnline\Model\Synchronization\Creditmemo;
use Magenest\QuickBooksOnline\Model\Synchronization\PaymentMethods;
use Magenest\QuickBooksOnline\Model\Synchronization\TaxCode;


/**
 * Class MassResync
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Log
 */
class MassResync extends AbstractLog
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
     * @var Filter
     */
    protected $filter;
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
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * MassResync constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Customer $customer
     * @param Item $item
     * @param Order $order
     * @param Invoice $invoice
     * @param PaymentMethods $paymentMethods
     * @param Creditmemo $creditmemo
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Customer $customer,
        Item $item,
        Order $order,
        Invoice $invoice,
        PaymentMethods $paymentMethods,
        TaxCode $taxCode,
        Creditmemo $creditmemo
    ) {
        parent::__construct($context, $pageFactory, $collectionFactory);
        $this->filter = $filter;
        $this->customer = $customer;
        $this->item = $item;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->paymentMethods = $paymentMethods;
        $this->taxCode = $taxCode;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->_collectionFactory->create());
        /**
         * @var \Magenest\QuickBooksOnline\Model\Config $config
         */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        $logResync = 0;
        /**
         * @var \Magenest\QuickBooksOnline\Model\Log $log
         */
        foreach ($collection->getItems() as $log) {
            try {
                $typeToResync = $log->getType();
                if ($typeToResync == 'customer' && $config->isEnableByType('customer')) {
                    $logId = $log->getLogId();
                    $log->delete();
                    if (mb_substr($log->getTypeId(),0,6 )!= self::PREFIX_GUEST) {
                        $this->customer->sync($log->getTypeId(), true);
                        $logResync++;

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
                        $logResync++;

                    }
                } elseif ($typeToResync == 'item' && $config->isEnableByType('item')) {
                    $log->delete();
                    $this->item->sync($log->getTypeId(), true);
                    $logResync++;

                } elseif ($typeToResync == 'order' && $config->isEnableByType('order')) {
                    $log->delete();
                    $this->order->sync($log->getTypeId());
                    $logResync++;

                } elseif ($typeToResync == 'invoice' && $config->isEnableByType('invoice')) {
                    $log->delete();
                    $this->invoice->sync($log->getTypeId());
                    $logResync++;

                } elseif ($typeToResync == 'creditmemo' && $config->isEnableByType('creditmemo')) {
                    $log->delete();
                    $this->creditmemo->sync($log->getTypeId());
                    $logResync++;

                } elseif ($typeToResync == 'paymentmethod') {
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
                        $logResync++;

                    } catch (\Exception $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }

                } elseif ($typeToResync == 'taxcode') {
                    $taxId = $log->getTypeId();
                    $log->delete();
                    $taxList = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\ResourceModel\Tax\Collection')->addFieldToFilter('tax_id', $taxId);
                    foreach ($taxList as $listTax) {
                        try {
                            $this->taxCode->sync($listTax->getTaxId(), trim($listTax->getTaxCode()), $listTax->getRate());
                            $logResync++;
                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage($e->getMessage());
                        }
                    }
                }
            } catch
            (\Exception $exception) {
            }
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) are resynced.', $logResync)
        );

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }

    /**
     * Check ACL
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_QuickBooksOnline::logs');
    }
}

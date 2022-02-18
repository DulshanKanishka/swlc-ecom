<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class Creditmemo
 * @package Magenest\Xero\Model\Synchronization
 */
class CreditNote extends Synchronization
{
    /**
     * @var string
     */
    protected $type = 'CreditNote';

    protected $syncType = 'CreditNote';

    protected $syncIdKey = 'CreditNoteNumber';

    protected $syncTypeKey = 'CreditNote';
    /**
     * @var string
     */
    protected $saleAccountId = '';

    /**
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * @var Allocation
     */
    protected $syncAllocation;

    /**
     * @var Customer
     */
    protected $syncCustomer;

    /**
     * @var Order
     */
    protected $syncOrder;

    protected $collectionFactory;

    protected $payment;

    protected $productRepository;

    /**
     * CreditNote constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Account $accountConfig
     * @param Allocation $syncAllocation
     * @param Creditmemo $creditmemo
     * @param Customer $customer
     * @param Order $order
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     * @param Payment $payment
     * @param Helper $helper
     * @param ProductRepositoryInterface $productRepository
     * @throws \Exception
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Account $accountConfig,
        Allocation $syncAllocation,
        Creditmemo $creditmemo,
        Customer $customer,
        Order $order,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory,
        Synchronization\Payment $payment,
        Helper $helper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->syncCustomer = $customer;
        $this->syncOrder = $order;
        $this->syncAllocation = $syncAllocation;
        $this->creditmemo = $creditmemo;
        $this->saleAccountId = $accountConfig->getAccountId($accountConfig::SALE_ACC_TYPE);
        $this->limit = 1000;
        $this->id = 'increment_id';
        $this->collectionFactory = $collectionFactory;
        $this->payment = $payment;
        $this->productRepository = $productRepository;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * @param $creditmemo
     * @return string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addRecord($creditmemo)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
//        $creditmemo = $this->creditmemo->load($creditmemoId);
        $creditCreatedAt = date('Y-m-d', strtotime($creditmemo->getCreatedAt()));
        if (!$creditmemo->getId()) {
            return '';
        }

        $xml = '<CreditNote>';
        $xml .= '<Type>ACCRECCREDIT</Type>';
        $xml .= $this->syncOrder->getCustomerXml($creditmemo->getOrder());
        $xml .= '<Date>' . $creditCreatedAt . '</Date>';
        $xml .= '<Status>AUTHORISED</Status>';
        $xml .= $this->getItemsCreditmemoXml($creditmemo);
        $xml .= '<CurrencyCode>' . $creditmemo->getOrderCurrencyCode() . '</CurrencyCode>';
        $xml .= '<CreditNoteNumber>' . 'C' . $creditmemo->getIncrementId() . '</CreditNoteNumber>';
        $xml .= '<LineAmountTypes>'.$this->syncOrder->getTaxType().'</LineAmountTypes>';
        $xml .= '</CreditNote>';

        return $xml;
    }

    /**
     * @param $invoice
     * @throws \Exception
     */
    public function guestToXml($invoice) {
        $order = $invoice->getOrder();
        $this->syncOrder->guestToXml($order);
    }

    public function syncAllGuestToXero(){
        $this->syncOrder->syncAllGuestToXero();
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getItemsCreditmemoXml($creditmemo)
    {
        $listProduct = $this->syncOrder->getProductsOnXero();
        $subtotal = 0;
        $xml = '<LineItems>';
        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($creditmemo->getItems() as $item) {
            if ($this->syncOrder->getTaxType() == "Inclusive") {
                $lineAmount = $item->getRowTotalInclTax() - $item->getDiscountAmount();
            } else {
                $lineAmount = $item->getRowTotal() - $item->getDiscountAmount();
            }
            if ($item->getPrice() == 0 || $lineAmount == 0)
                continue;
            $orderItem = $item->getOrderItem();
            $product = $this->getProduct($orderItem);
            if ($this->helper->getConfig('magenest_xero_config/xero_init_data/enabled')) {
                if (!isset($listProduct[$this->helper->getScopeId()][$item->getSku()])) {
                    $this->syncOrder->setProductXml($product);
                }
            }
            $qty = $item->getQty();
            $name = strip_tags($item->getName());
            $sku = $item->getSku();
            $saleAccountId = $this->saleAccountId;
            $taxAmount = $item->getTaxAmount() ? $item->getTaxAmount() : 0;
            if ($orderItem->getProductType() == "configurable" || $orderItem->getProductType() == "bundle") {
                $options = $orderItem->getProductOptions();
                if (isset($options['simple_name']))
                    $name = strip_tags($options['simple_name']);
                if (isset($options['simple_sku']))
                    $sku = $options['simple_sku'];
            }
            $discountPercent = $this->helper->getDiscount($item);
            if ($product) {
                $sku = $product->getSku();
                if ($this->helper->isMultipleWebsiteEnable()) {
                    $saleAccountId = $this->getAttributeByWebsite($item->getOrderItem(), 'sale_id');
                } else {
                    $saleAccountId = empty($product->getData('sale_id')) ? $saleAccountId : $product->getData('sale_id');
                }
                $saleAccountId = $saleAccountId ? : $this->helper->getConfig(Account::ACC_PATH.'sale_id');
            }
            $xml .= '<LineItem>';
            $xml .= '<Description>' . $name . '</Description>';
            $xml .= '<ItemCode>' . $sku . '</ItemCode>';
            $xml .= '<Quantity>' . $qty . '</Quantity>';
            $xml .= '<UnitAmount>' . $this->getItemPrice($item) . '</UnitAmount>';
            $xml .= '<AccountCode>' . $saleAccountId . '</AccountCode>';
//            $xml .= '<TaxType>NONE</TaxType>';
            $xml .= '<TaxAmount>'.$taxAmount.'</TaxAmount>';
            if ($discountPercent > 0) {
                $xml .= '<DiscountRate>' . $discountPercent . '</DiscountRate>';
            }
            $subtotal += $lineAmount;
            $xml .= '<LineAmount>'. $lineAmount .'</LineAmount>';
            $xml .= '</LineItem>';
        }
//        $xml .= $this->getTaxXml($creditmemo);
        $xml .= $this->getShippingXml($creditmemo);
        if ($this->syncOrder->getTaxType() == "Inclusive") {
            $subtotal += $creditmemo->getShippingInclTax();
        } else {
            $subtotal += $creditmemo->getShippingAmount();
        }
//        $subtotal += $creditmemo->getTaxAmount();
        $xml .= '</LineItems>';
        $xml .= '<SubTotal>' . $subtotal . '</SubTotal>';
        return $xml;
    }

    public function getItemPrice($item)
    {
        if ($this->syncOrder->getTaxType() == "Inclusive") {
            return $item->getPriceInclTax();
        } else {
            return $item->getPrice();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return string
     */
    protected function getTaxXml($creditmemo)
    {
        $taxAmount = $creditmemo->getTaxAmount() > 0 ? $creditmemo->getTaxAmount() : 0.0;
        $saleAccountId = "";
        if ($this->helper->isMultipleWebsiteEnable()) {
            $saleAccountId = $this->helper->getConfig('magenest_xero_config/xero_account/sale_id');
        }
        $saleAccountId = $saleAccountId ? : $this->saleAccountId;
        $xml = '<LineItem>';
        $xml .= '<Description>Order Tax</Description>';
        $xml .= '<ItemCode>tax</ItemCode>';
        $xml .= '<Quantity>1</Quantity>';
        $xml .= '<UnitAmount>' . $taxAmount . '</UnitAmount>';
        $xml .= '<TaxType>NONE</TaxType>';
        $xml .= '<TaxAmount>0</TaxAmount>';
        $xml .= '<AccountCode>' . $saleAccountId . '</AccountCode>';
        $xml .= '<LineAmount>'.$taxAmount.'</LineAmount>';
        $xml .= '</LineItem>';

        return $xml;
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return string
     */
    protected function getShippingXml($creditmemo)
    {
        if ($this->syncOrder->getTaxType() == "Inclusive") {
            $shippingAmount = $creditmemo->getShippingInclTax();
        } else {
            $shippingAmount = $creditmemo->getShippingAmount() > 0 ? $creditmemo->getShippingAmount() : 0.0;
        }
        $amount = $creditmemo->getShippingTaxAmount() > 0 ? $creditmemo->getShippingTaxAmount() : 0.0;
        $saleAccountId = "";
        if ($this->helper->isMultipleWebsiteEnable()) {
            $saleAccountId = $this->helper->getConfig('magenest_xero_config/xero_account/sale_id');
        }
        $saleAccountId = $saleAccountId ? : $this->saleAccountId;

        $xml = '<LineItem>';
        $xml .= '<Description>Order Shipping Cost</Description>';
        $xml .= '<ItemCode>shipping</ItemCode>';
        $xml .= '<Quantity>1</Quantity>';
        $xml .= '<UnitAmount>' . $shippingAmount . '</UnitAmount>';
        $xml .= '<TaxAmount>'.$amount.'</TaxAmount>';
        $xml .= '<AccountCode>' . $saleAccountId . '</AccountCode>';
        $xml .= '<LineAmount>'.$shippingAmount.'</LineAmount>';
        $xml .= '</LineItem>';
        return $xml;
    }

    /**
     * Sync Creditmemo datas to Xero
     */
    public function syncAllCreditNotes()
    {
        $creditmemoCollections = $this->creditmemo->getCollection();
        $lastCreditmemoId = $creditmemoCollections->getLastItem()->getId();
        $count = 0;
        $xml = '';
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        foreach ($creditmemoCollections as $creditmemo) {
            $xml .= $this->addRecord($creditmemo->getId());
            $this->syncAllocation->addRecord($creditmemo->getId());
            $count++;
            if ($count >= 250 || $creditmemo->getId() == $lastCreditmemoId) {
                $xml = '<' . $this->type . 's>' . $xml . '</' . $this->type . 's>';
                $this->syncData($xml);
                $this->syncAllocation->syncAllocations();
                $this->syncAllocation->unsetRecords();
                $xml = '';
                $count = 0;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _additional($creditmemo)
    {
        return $this->setRefundXml($creditmemo);
    }

    /**
     * set xml string of credit memo refund
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return string
     */

    public function setRefundXml($creditmemo)
    {
        return $this->payment->addCreditRecord($creditmemo);
    }

    /**
     * sync refund
     */
    public function syncRefundXml($creditmemo)
    {
        $refundXml = $this->setRefundXml($creditmemo);
        $this->payment->syncPayments($refundXml);
    }

    /**
     * sync refund using creditmemo id
     * @param $creditmemoId
     */
    public function syncRefundXmlById($creditmemoId)
    {
        $creditmemo = $this->creditmemo->load($creditmemoId);
        $this->syncRefundXml($creditmemo);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function _additionalSync($additionalXml)
    {
        $this->payment->syncPayments($additionalXml);
        $this->syncAllocation->syncAllocations();
        $this->syncAllocation->unsetRecords();
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function loadProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * @param $item
     * @param $code
     * @return mixed
     */
    protected function getAttributeByWebsite($item, $code)
    {
        $product = $item->getProduct();
        return $product->getResource()->getAttributeRawValue($product->getId(), $code, $item->getStore());
    }

    /**
     * @param $xml
     * @param string $method
     * @return string
     * @throws \Exception
     */
    public function syncData($xml, $method = 'POST')
    {
        $this->syncOrder->syncMissingCustomer();
        $this->syncOrder->syncMissingProduct();
        return parent::syncData($xml, $method); // TODO: Change the autogenerated stub
    }
}

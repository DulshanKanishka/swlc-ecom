<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\TaxMapping;
use Magenest\Xero\Model\XeroClient;
use Magenest\Xero\Model\XmlLogFactory;
use Magento\Sales\Model\Order\InvoiceFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;
use Magenest\Xero\Model\TaxMappingFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as OrderItem;


/**
 * Class Invoice
 * @package Magenest\Xero\Model\Synchronization
 */
class Invoice extends Synchronization
{
    /**
     * @var string
     */
    protected $type = 'InvoiceToInvoice';

    protected $collectionFactory;

    protected $syncType = 'Invoice';

    protected $syncIdKey = 'InvoiceNumber';

    protected $syncTypeKey = 'Invoice';

    /**
     * @var Synchronization\Payment
     */
    protected $syncPayment;

    /**
     * @var \Magento\Sales\Model\Order\Invoice
     */
    protected $invoiceFactory;

    /**
     * @var \Magenest\Xero\Model\TaxMappingFactory
     */
    protected $taxMappingFactory;

    /**
     * @var string
     */
    protected $saleAccountId;

    /**
     * @var Order
     */
    protected $syncOrder;

    protected $customer = [];

    protected $customerXmlToSync = "";

    protected $orderToInvoiceFlag = false;

    protected $taxItem;

    protected $_xmlLogFactory;

    /**
     * Invoice constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Account $accountConfig
     * @param Order $syncOrder
     * @param InvoiceFactory $invoiceFactory
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param Payment $payment
     * @param CollectionFactory $collectionFactory
     * @param TaxMappingFactory $taxMappingFactory
     * @param OrderItem $item
     * @throws \Exception
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Account $accountConfig,
        Synchronization\Order $syncOrder,
        InvoiceFactory $invoiceFactory,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Synchronization\Payment $payment,
        CollectionFactory $collectionFactory,
        TaxMappingFactory $taxMappingFactory,
        OrderItem $item,
        Helper $helper,
        XmlLogFactory $xmlLogFactory
    ) {
        $this->syncOrder = $syncOrder;
        $this->saleAccountId = $accountConfig->getAccountId($accountConfig::SALE_ACC_TYPE);
        $this->invoiceFactory = $invoiceFactory;
        $this->syncPayment = $payment;
        $this->collectionFactory = $collectionFactory;
        $this->id = 'increment_id';
        $this->limit = 500;
        $this->taxMappingFactory = $taxMappingFactory;
        $this->taxItem = $item;
        $this->_xmlLogFactory = $xmlLogFactory;

        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * return an xml string of an invoice
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string
     * @throws
     */
    public function addRecord($invoice)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $updatedAt = date('Y-m-d', strtotime($invoice->getUpdatedAt()));
        $createdAt = date('Y-m-d', strtotime($invoice->getOrder()->getCreatedAt()));

        $xml = '<Invoice>';
        $xml .= '<InvoiceNumber>' . $invoice->getIncrementId() . '</InvoiceNumber>';
        $xml .= '<Reference>'.$invoice->getOrder()->getIncrementId().'</Reference>';
        $xml .= $this->syncOrder->getCustomerXml($invoice->getOrder());
        $xml .= '<Type>ACCREC</Type>';
        $xml .= '<CurrencyCode>' . $invoice->getOrderCurrencyCode() . '</CurrencyCode>';
        $xml .= '<Date>' . $createdAt . '</Date>';
        $xml .= '<DueDate>' . $updatedAt . '</DueDate>';
        $xml .= '<Status>AUTHORISED</Status>';
        $xml .= '<LineAmountTypes>'.$this->syncOrder->getTaxType().'</LineAmountTypes>';
        $xml .= $this->getItemsInvoiceXml($invoice);
        $xml .= '</Invoice>';

        $this->syncPayment->addRecord($invoice);
        return $xml;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string
     */
    protected function getItemsInvoiceXml($invoice)
    {
        $listProduct = $this->syncOrder->getProductsOnXero();
        $xml = '<LineItems>';
        $orderId = $invoice->getOrderId();
        $taxes = $this->taxItem->getTaxItemsByOrderId($orderId);
        $order = $invoice->getOrder();
        $items = $order->getItems();
        $taxByItemId = [];

        foreach ($taxes as $tax) {
            if ($tax['item_id']){
                $taxByItemId[$tax['item_id']] = $tax;
            }
        }

        foreach ($items as $item) {
            $product = $this->getProduct($item);

            if ($item->getProductType() == "configurable" || $item->getProductType() == "bundle") {
                continue;
            }
            if ($this->helper->getConfig('magenest_xero_config/xero_init_data/enabled')) {
                if (!isset($listProduct[$this->helper->getScopeId()][$item->getSku()])) {
                    $this->syncOrder->setProductXml($product);
                }
            }
            $parent = $item->getParentItem();
            $price = $this->getItemPrice($item);
            $taxAmount = $item->getTaxAmount() ? $item->getTaxAmount() : 0;
            $discountPercent = $this->helper->getDiscount($item);
            if ($parent && $parent->getProductType() == "configurable") {
                $price = $this->getItemPrice($parent);
                $taxAmount = $parent->getTaxAmount() > 0 ? $parent->getTaxAmount() : 0.0;
                $discountPercent = $this->helper->getDiscount($parent);
            }

            $sku = $item->getSku();
            $trackingCategory = null;
            $categoryName = '';
            $optionName = '';
            $saleAccountId = $this->saleAccountId;
            if ($product && $product->getTypeId() != "configurable") {
                $sku = $product->getSku();

                if ($this->helper->isMultipleWebsiteEnable()) {
                    $saleAccountId = $this->getAttributeByWebsite($item, 'sale_id');
                    $trackingCategory = $this->getAttributeByWebsite($item, 'tracking_category');
                } else {
                    $trackingCategory = $product->getTrackingCategory();
                    $saleAccountId = empty($product->getData('sale_id')) ? $saleAccountId : $product->getData('sale_id');
                }
                $saleAccountId = $saleAccountId ? : $this->helper->getConfig(Account::ACC_PATH.'sale_id');

                if ($trackingCategory) {
                    $array = explode('/', $trackingCategory);
                    if (count($array) == 2) {
                        list($categoryName, $optionName) = $array;
                    }
                }
            }
            $xml .= '<LineItem>';
            $xml .= '<Description>' . strip_tags($item->getName()) . '</Description>';
            $xml .= '<ItemCode>' . $sku . '</ItemCode>';
            $xml .= '<Quantity>' . $item->getQtyOrdered() . '</Quantity>';
            $xml .= '<UnitAmount>' . $price . '</UnitAmount>';
            $xml .= '<TaxAmount>'. $taxAmount .'</TaxAmount>';

            if (isset($taxByItemId[$item->getItemId()])) {
                $tax = $taxByItemId[$item->getItemId()];
                $mapping = $this->taxMappingFactory->create()->loadByTaxCode($tax['code']);
                if ($mapping && $mapping->getXeroTaxCode()) {
                    $xml .= '<TaxType>'.$mapping->getXeroTaxCode().'</TaxType>';
                }
            }

            $xml .= '<AccountCode>' . $saleAccountId . '</AccountCode>';
            if ($trackingCategory) {
                $xml .= '<Tracking>';
                $xml .= '<TrackingCategory>';
                $xml .= '<Name>'.$categoryName.'</Name>';
                $xml .= '<Option>'.$optionName.'</Option>';
                $xml .= '</TrackingCategory>';
                $xml .= '</Tracking>';
            }
            if ($discountPercent > 0) {
                $xml .= '<DiscountRate>' . $discountPercent . '</DiscountRate>';
            }
            $xml .= '</LineItem>';
        }

        $xml .= $this->getShippingXml($invoice);
        $xml .= '</LineItems>';

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

    protected function getContactsOnXero()
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('Contacts');
        $helper->setMethod();
        $helper->setParams();
        $url = $helper->getUri() . '?' . $helper->sign();
        $client = new \Zend_Http_Client($url, [
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['Contacts']['Contact'])) {
            return $parsedResponse['Contacts']['Contact'];
        }
        return [];
    }

    public function syncAllGuestToXero(){
//        $this->syncOrder->setCustomerXmlToSync($this->customerXmlToSync);
        $this->syncOrder->syncAllGuestToXero();
    }

    public function _additionalSync($additionalXml)
    {
        $this->syncPayments();
    }

    public function syncPayments()
    {
        $this->syncPayment->syncPayments();
        $this->syncPayment->unsetRecords();
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string
     */
    protected function getTaxXml($invoice)
    {
        $taxAmount = $invoice->getTaxAmount() > 0 ? $invoice->getTaxAmount() : 0.0;
        $xml = '<LineItem>';
        $xml .= '<Description>Order Tax</Description>';
        $xml .= '<ItemCode>tax</ItemCode>';
        $xml .= '<Quantity>1</Quantity>';
        $xml .= '<UnitAmount>' . $taxAmount . '</UnitAmount>';
        $xml .= '<TaxType>NONE</TaxType>';
        $xml .= '<TaxAmount>0</TaxAmount>';
        $xml .= '<AccountCode>' . $this->saleAccountId . '</AccountCode>';
        $xml .= '</LineItem>';

        return $xml;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string
     */
    protected function getShippingXml($invoice)
    {
        if ($this->syncOrder->getTaxType() == "Inclusive") {
            $shippingAmount = $invoice->getShippingInclTax();
        } else {
            $shippingAmount = $invoice->getShippingAmount() > 0 ? $invoice->getShippingAmount() : 0.0;
        }
        $amount = $invoice->getShippingTaxAmount() > 0 ? $invoice->getShippingTaxAmount() : 0.0;
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
        $xml .= '</LineItem>';

        return $xml;
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

    public function getItemPrice($item)
    {
        if ($this->syncOrder->getTaxType() == "Inclusive") {
            return $item->getPriceInclTax();
        } else {
            return $item->getPrice();
        }
    }
}

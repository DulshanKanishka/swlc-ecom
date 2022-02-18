<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Log\Status;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magenest\Xero\Model\TaxMappingFactory;
use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\XmlLogFactory;
use Magento\Customer\Model\CustomerFactory;

/**
 * Class Order
 * @package Magenest\Xero\Model\Synchronization
 */
class Order extends Synchronization
{

    /**
     * Purchase Order status
     */
    const STATUS_DELETED = 'DELETED';

    const STATUS_AUTHORISED = 'AUTHORISED';

    const STATUS_SUBMITTED = 'SUBMITTED';

    const STATUS_BILLED = 'BILLED';

    const STATUS_DRAFT = 'DRAFT';

    const STATUS_VOIDED = 'VOIDED';

    /**
     * @var string
     */
    protected $type = 'OrderToInvoice';

    protected $syncType = 'Invoice';

    protected $syncIdKey = 'InvoiceNumber';

    protected $syncTypeKey = 'Invoice';

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * @var array
     */
    protected $invoices = [];

    /**
     * @var Customer
     */
    protected $syncCustomer;

    /**
     * @var array
     */
    protected $contacts = [];

    /**
     * @var string
     */
    protected $saleAccountId;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    protected $customer = [];

    protected $existedInvoice = [];

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    protected $customerXmlToSync = "";

    protected $scopeConfig;

    /**
     * @var TaxItem
     */
    protected $taxItems;

    protected $taxMappingFactory;

    protected $orderToInvoiceFlag;

    protected $_xmlLogFactory;

    protected $_customerFactory;

    protected $_listProduct = [];

    protected $syncProduct;

    protected $_productXml = "";

    protected $_customerXml = "";

    protected $_taxType;
    /**
     * Mapping Order States with Purchase Order Status
     *
     * @var array
     */
    protected $status = [
        SalesOrder::STATE_NEW => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PENDING_PAYMENT => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PAYMENT_REVIEW => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PROCESSING => self::STATUS_AUTHORISED,
        SalesOrder::STATE_COMPLETE => self::STATUS_BILLED,
        SalesOrder::STATE_CANCELED => self::STATUS_DELETED,
        SalesOrder::STATE_CLOSED => self::STATUS_DELETED,
        SalesOrder::STATE_HOLDED => self::STATUS_SUBMITTED,
    ];

    /**
     * Mapping Order States with Purchase Order Status
     *
     * @var array
     */
    protected $invoiceStatus = [
        SalesOrder::STATE_NEW => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PENDING_PAYMENT => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PAYMENT_REVIEW => self::STATUS_SUBMITTED,
        SalesOrder::STATE_PROCESSING => self::STATUS_AUTHORISED,
        SalesOrder::STATE_COMPLETE => self::STATUS_BILLED,
        SalesOrder::STATE_CANCELED => self::STATUS_DELETED,
        SalesOrder::STATE_CLOSED => self::STATUS_DELETED,
        SalesOrder::STATE_HOLDED => self::STATUS_SUBMITTED,
    ];

    /**
     * Order constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Customer $syncCustomer
     * @param OrderFactory $orderFactory
     * @param RequestLogFactory $requestLogFactory
     * @param ScopeConfigInterface $configInterface
     * @param Payment $payment
     * @param Account $accountConfig
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     * @param ProductFactory $productFactory
     * @param TaxItem $item
     * @param TaxMappingFactory $taxMappingFactory
     * @param Helper $helper
     * @param XmlLogFactory $xmlLogFactory
     * @param CustomerFactory $customerFactory
     * @param Item $syncProduct
     * @throws \Exception
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Synchronization\Customer $syncCustomer,
        OrderFactory $orderFactory,
        RequestLogFactory $requestLogFactory,
        ScopeConfigInterface $configInterface,
        Payment $payment,
        Account $accountConfig,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory,
        ProductFactory $productFactory,
        TaxItem $item,
        TaxMappingFactory $taxMappingFactory,
        Helper $helper,
        XmlLogFactory $xmlLogFactory,
        CustomerFactory $customerFactory,
        Synchronization\Item $syncProduct
    ) {
        $this->syncCustomer = $syncCustomer;
        $this->orderFactory = $orderFactory;
        $this->payment = $payment;
        $this->orderToInvoiceFlag = $configInterface->getValue('magenest_xero_config/xero_order/order_invoice_enabled');
        $this->saleAccountId = $accountConfig->getAccountId($accountConfig::SALE_ACC_TYPE);
        $this->limit = 1000;
        $this->collectionFactory = $collectionFactory;
        $this->id = "increment_id";
        $this->taxItems = $item;
        $this->productFactory = $productFactory;
        $this->scopeConfig = $configInterface;
        $this->taxMappingFactory = $taxMappingFactory;
        $this->_xmlLogFactory = $xmlLogFactory;
        $this->_customerFactory = $customerFactory;
        $this->syncProduct = $syncProduct;
        $this->_taxType = $this->scopeConfig->getValue('magenest_xero_config/xero_order/tax_type') ? 'Exclusive' : 'Inclusive';
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * @param $status
     * @return mixed
     * @throws LocalizedException
     */
    public function convertToInvoiceStatus($status)
    {
        if (isset($this->invoiceStatus[$status])) {
            return $this->invoiceStatus[$status];
        } else {
            throw new LocalizedException(__('Doesn\'t exist order state!'));
        }
    }

    /**
     * @param $status
     * @return mixed
     * @throws LocalizedException
     */
    public function convertToPurchaseOrderStatus($status)
    {
        if (isset($this->status[$status])) {
            return $this->status[$status];
        } else {
            throw new LocalizedException(__('Doesn\'t exist order state!'));
        }
    }

    /**
     * return an xml string of orders
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     * @throws
     */
    public function addRecord($order)
    {
        $incrementId = $order->getIncrementId();

        $state = $order->getState();
        $status = $this->convertToInvoiceStatus($state);
        if ($state === SalesOrder::STATE_HOLDED) {
            return '';
        }
        if ($order->getTotalDue() == 0) {
            $status = "BILLED";
        }
        if ($this->helper->isMultipleWebsiteEnable())
            $invoiceExisted = $this->invoiceExistedMultipleWebsite($incrementId, $order->getStore()->getWebsiteId());
        else
            $invoiceExisted = $this->invoiceExisted($incrementId);
        /** if order is completed but there is no invoice created on Xero yet */
        if ($status == self::STATUS_BILLED && !$invoiceExisted) {
            $this->addPayment($order);
        } elseif ($status == self::STATUS_BILLED && $invoiceExisted && $invoiceExisted['Status'] == 'PAID') {
            $log = $this->logFactory->create();
            $log->addData([
                'type' => 'OrderToInvoice',
                'entity_id' => $incrementId,
                'dequeue_time' => time(),
                'status' => Status::SUCCESS_STATUS,
                'xero_id' => $invoiceExisted['InvoiceID'],
                'msg' => 'Order is already paid/closed, a record update process was not performed.',
                'xml_log_id' => $this->helper->getIdInCollectionByMagentoId($incrementId, $this->type)
            ])->save();
            return '';
        } elseif ($status == self::STATUS_BILLED && $invoiceExisted && $invoiceExisted['Status'] != self::STATUS_AUTHORISED) {
            $this->addPayment($order);
        } elseif ($status == self::STATUS_BILLED && $invoiceExisted) {
            $this->addPayment($order);
            return 'payment';
        } elseif ($status == self::STATUS_DELETED && !$invoiceExisted) {
            $log = $this->logFactory->create();
            $log->addData([
                'type' => 'OrderToInvoice',
                'entity_id' => $incrementId,
                'dequeue_time' => time(),
                'status' => Status::SUCCESS_STATUS,
                'xero_id' => 'NONE',
                'msg' => 'Order is already paid/closed, a record update process was not performed.',
                'xml_log_id' => $this->helper->getIdInCollectionByMagentoId($incrementId, $this->type)
            ])->save();
            return '';
        }

        if (isset($invoiceExisted['Status']) && $invoiceExisted['Status'] === 'PAID' && $status === self::STATUS_DELETED) {
            $log = $this->logFactory->create();
            $log->addData([
                'type' => 'OrderToInvoice',
                'entity_id' => $incrementId,
                'dequeue_time' => time(),
                'status' => Status::SUCCESS_STATUS,
                'xero_id' => $invoiceExisted['InvoiceID'],
                'msg' => 'Order is already paid/closed, a record update process was not performed.',
                'xml_log_id' => $this->helper->getIdInCollectionByMagentoId($incrementId)
            ])->save();
            return '';
        }

        return $this->addInvoiceRecord($order, $invoiceExisted);
    }

    protected function addInvoiceRecord($order, $invoiceExisted)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $status = $this->convertToInvoiceStatus($order->getState());
        if ($order->getTotalDue() == 0) {
            $status = "BILLED";
        }
        if ($status == self::STATUS_DELETED && $invoiceExisted['Status'] == self::STATUS_AUTHORISED) {
            $status = 'VOIDED';
        }
        if ($status==self::STATUS_BILLED && (!$invoiceExisted || $invoiceExisted['Status'] != self::STATUS_AUTHORISED)) {
            $status=self::STATUS_AUTHORISED;
        }
        $createdAt = date('Y-m-d', strtotime($order->getCreatedAt()));
        $datetime = new \DateTime($order->getUpdatedAt());
        $datetime->modify('+30 day');
        $updatedAt = $datetime->format('Y-m-d');
        $xml = '<Invoice>';
        $xml .= '<InvoiceNumber>' . $order->getIncrementId() . '</InvoiceNumber>';
//        $xml .= $this->syncCustomer->getContactXml($orderCollection);
        $xml .= $this->getCustomerXml($order);
        $xml .= '<Type>ACCREC</Type>';
        $xml .= '<CurrencyCode>' . $order->getOrderCurrencyCode() . '</CurrencyCode>';
        $xml .= '<Date>' . $createdAt . '</Date>';
        $xml .= '<DueDate>' . $updatedAt . '</DueDate>';
        $xml .= '<Status>'.$status.'</Status>';
        $xml .= '<LineAmountTypes>'.$this->_taxType.'</LineAmountTypes>';
        $xml .= $this->getItemsOrderXml($order);
        $xml .= '</Invoice>';

        return $xml;
    }
    protected function setContactList($order)
    {
        $websiteId = 0;
        if ($this->helper->isMultipleWebsiteEnable()) {
            $websiteId = $order->getStore()->getWebsiteId();
        }
        $contacts = $this->getContactsOnXero();
        foreach($contacts as $key => $contact) {
            if (!is_numeric($key)) {
                $contact = $contacts;
                if (isset($contact['ContactNumber']) && isset($contact['EmailAddress'])) {
                    $this->customer[$websiteId][$contact['EmailAddress']] = $contact['ContactNumber'];
                }
                break;
            }
            if (isset($contact['ContactNumber']) && isset($contact['EmailAddress'])) {
                $this->customer[$websiteId][$contact['EmailAddress']] = $contact['ContactNumber'];
            }
        }
    }
    /**
     * @param $order
     * @throws \Exception
     */
    public function guestToXml($order) {
        $email = $order->getCustomerEmail();
        $websiteId = 0;
        if ($this->helper->isMultipleWebsiteEnable()) {
            $websiteId = $order->getStore()->getWebsiteId();
        }
        if (isset($this->customer[$websiteId][$email])){
            return;
        }
        if (!isset($this->customer[$websiteId])){
            if ($this->helper->isMultipleWebsiteEnable()) {
                $this->setWebsiteScopeConfig($websiteId);
            }
            $this->setContactList($order);
        }

        if (!isset($this->customer[$websiteId][$email])) {
            if (!$order->getCustomerId()) {
                $address = $order->getShippingAddress();
                if (!$address) {
                    $address = $order->getBillingAddress();
                }
                $code = substr(md5($order->getCustomerEmail()), 0, 5);
                $customerXml = '<Contact>';
                $customerXml .= '<ContactNumber>' . $code . '</ContactNumber>';
                $customerXml .= '<Name>' . $address->getFirstname() . ' ' . $address->getLastname() . ' ' . $code . '</Name>';
                $customerXml .= '<FirstName>' . $address->getFirstname() . '</FirstName>';
                $customerXml .= '<LastName>' . $address->getLastname() . '</LastName>';
                $customerXml .= '<EmailAddress>' . $order->getCustomerEmail() . '</EmailAddress>';
                $customerXml .= $this->getAddressXml($order);
                $customerXml .= '</Contact>';
                if (!isset($this->customerXmlToSync[$websiteId])) {
                    if (!is_array($this->customerXmlToSync)) {
                        $this->customerXmlToSync = [];
                    }
                    $this->customerXmlToSync[$websiteId] = '';
                }
                $this->customerXmlToSync[$websiteId] .= $customerXml;
                $this->customer[$websiteId][$email] = $code;
                $this->_xmlLogFactory->create()->setData([
                    'magento_id' => $code,
                    'xml_log' => $customerXml,
                    'type' => 'Contact',
                    'scope' => $this->helper->getScope(),
                    'scope_id' => $this->helper->getScopeId()
                ])->save();
            } else {
                $this->setCustomerXml($order);
            }
        }
    }

    public function syncAllGuestToXero(){
        if (is_array($this->customerXmlToSync)) {
            foreach ($this->customerXmlToSync as $id => $xml) {
                if (empty($xml)) {
                    continue;
                }
                $this->setWebsiteScopeConfig($id);
                $xml = '<Contacts>'.$xml.'</Contacts>';
                $this->syncCustomer->syncData($xml);
            }
            $this->customerXmlToSync = [];
        } else {
            if (empty($this->customerXmlToSync)) {
                return;
            }
            $this->customerXmlToSync = '<Contacts>'.$this->customerXmlToSync.'</Contacts>';
            $this->syncCustomer->syncData($this->customerXmlToSync);
            $this->customerXmlToSync = "";
        }
    }

    public function setCustomerXmlToSync($xml) {
        $this->customerXmlToSync = $xml;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     * @throws \Exception
     */
    public function getCustomerXml($order){
        $websiteId = 0;
        if ($this->helper->isMultipleWebsiteEnable()) {
            $websiteId = $order->getStore()->getWebsiteId();
        }

        if (!isset($this->customer[$websiteId][$order->getCustomerEmail()])){
            $this->guestToXml($order);
        }

        $xml = '<Contact><ContactNumber>'.$this->customer[$websiteId][$order->getCustomerEmail()].'</ContactNumber>';
        $xml .= $this->getAddressXml($order);
        $xml .= '</Contact>';

        return $xml;
    }

    /**
     * @param $order
     */
    protected function addPayment($order)
    {
        $this->payment->addRecord($order);
    }

    /**
     * @param SalesOrder $order
     * @return string
     */
    protected function getItemsOrderXml($order)
    {
        $this->getProductsOnXero();
        $xml = '<LineItems>';
        $taxes = $this->taxItems->getTaxItemsByOrderId($order->getId());
        $taxByItemId = [];
        foreach ($taxes as $tax) {
            if ($tax['item_id']){
                $taxByItemId[$tax['item_id']] = $tax;
            }
        }
        $items = $order->getItems();
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->getProduct($item);
            if ($item->getProductType() == "configurable" || $item->getProductType()  == "bundle") {
                continue;
            }
            if ($this->scopeConfig->getValue('magenest_xero_config/xero_init_data/enabled')) {
                if (!isset($this->_listProduct[$this->helper->getScopeId()][$item->getSku()])) {
                    $this->setProductXml($product);
                }
            }
            $id = $item->getItemId();
            $parent = $item->getParentItem();
            $price = $this->getItemPrice($item);
            $taxAmount = $item->getTaxAmount() ? $item->getTaxAmount() : 0;
            $discountPercent = $this->helper->getDiscount($item);
            if ($parent && $parent->getProductType() == "configurable") {
                $id = $parent->getItemId();
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
            $xml .= '<Description>' . strip_tags($item->getName())  . '</Description>';
            $xml .= '<ItemCode>' . $sku . '</ItemCode>';
            $xml .= '<Quantity>' . $item->getQtyOrdered() . '</Quantity>';
            $xml .= '<UnitAmount>' . $price . '</UnitAmount>';
            $xml .= '<TaxAmount>'. $taxAmount .'</TaxAmount>';

            if (isset($taxByItemId[$id])) {
                $tax = $taxByItemId[$id];
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
        $xml .= $this->getShippingXml($order);
        $xml .= '</LineItems>';

        return $xml;
    }

    public function getProductsOnXero()
    {
        $websiteId = $this->helper->getScopeId();
        if (!isset($this->_listProduct[$websiteId])) {
            $helper = $this->xeroClient->getSignature();
            $helper->setUri('Items');
            $helper->setMethod();
            $helper->setParamsForSyncing();
            $url = $helper->getUri() . '?' . $helper->sign();
            $client = new \Zend_Http_Client($url,[
                'timeout' => 30,
                'useragent' => XeroClient::getUserAgent()
            ]);

            $response = $client->request()->getBody();
            $parsedResponse = $this->parseXML($response);
            if (isset($parsedResponse['Items']['Item'])) {
                $list = [];
                foreach ($parsedResponse['Items']['Item'] as $item) {
                    $list[$websiteId][$item['Code']] = $item;
                }
                $this->_listProduct = array_merge_recursive($this->_listProduct, $list);
                return $this->_listProduct;
            }
        }
        return $this->_listProduct;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    protected function getTaxXml($order)
    {
        $taxAmount = $order->getTaxAmount() > 0 ? $order->getTaxAmount() : 0.0;
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
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    protected function getShippingXml($order)
    {
        if ($this->_taxType == "Inclusive") {
            $shippingAmount = $order->getShippingInclTax();
        } else {
            $shippingAmount = $order->getShippingAmount() > 0 ? $order->getShippingAmount() : 0.0;
        }
        $amount = $order->getShippingTaxAmount() > 0 ? $order->getShippingTaxAmount() : 0.0;
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
     * Check if order existed on Xero
     *
     * @param $incrementId
     * @return mixed
     */
    protected function orderExisted($incrementId)
    {
        if (!$this->orders) {
            $this->orders = $this->getOrdersOnXero();
        }
        foreach ($this->orders as $key => $order) {
            if (isset($order['PurchaseOrderNumber']) && $order['PurchaseOrderNumber']==$incrementId) {
                unset($this->orders[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws
     */
    protected function getOrdersOnXero()
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('PurchaseOrders');
        $helper->setMethod();
        $helper->setParamsForSyncing();
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url,[
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['PurchaseOrders']['PurchaseOrder'])) {
            return $parsedResponse['PurchaseOrders']['PurchaseOrder'];
        }

        return [];
    }

    /**
     * Check if order existed on Xero
     *
     * @param $incrementId
     * @return mixed
     */
    protected function invoiceExisted($incrementId)
    {
        if (!$this->invoices) {
            $this->invoices = $this->getInvoicesOnXero();
            foreach($this->invoices as $key => $invoice) {
                if (isset($invoice['InvoiceNumber'])) {
                    $this->existedInvoice[$invoice['InvoiceNumber']] = $invoice;
                }
            }
        }
        if (isset($this->existedInvoice[$incrementId])){
            return $this->existedInvoice[$incrementId];
        }
        return false;
    }

    protected function invoiceExistedMultipleWebsite($incrementId, $id)
    {
        if (!isset($this->invoices[$id])) {
            $this->invoices[$id] = $this->getInvoicesOnXero();
            foreach($this->invoices[$id] as $key => $invoice) {
                if (isset($invoice['InvoiceNumber'])) {
                    $this->existedInvoice[$id][$invoice['InvoiceNumber']] = $invoice;
                }
            }
        }
        if (isset($this->existedInvoice[$id][$incrementId])){
            return $this->existedInvoice[$id][$incrementId];
        }
        return false;
    }

    /**
     * @return array
     * @throws
     */
    protected function getInvoicesOnXero()
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('Invoices');
        $helper->setMethod();
        $helper->setParamsForSyncing();
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url,[
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['Invoices']['Invoice'])) {
            return $parsedResponse['Invoices']['Invoice'];
        }

        return [];
    }

    /**
     * @return array
     * @throws
     */
    protected function getContactsOnXero()
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('Contacts');
        $helper->setMethod();
        $helper->setParamsForSyncing();
        $url = $helper->getUri() . '?' . $helper->sign();
        $client = new \Zend_Http_Client($url,[
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

    /**
     * @param string $additionalXml
     */
    public function _additionalSync($additionalXml)
    {
        $this->syncPayments();
    }

    public function syncPayments()
    {
        $this->payment->syncPayments();
        $this->payment->unsetRecords();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->syncType;
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

    public function getTaxType()
    {
        return $this->_taxType;
    }

    public function getAddressXml($order)
    {
        $xml = '<Addresses>';
        if ($billingAddress = $order->getBillingAddress()) {
            $billingPhone = $billingAddress->getTelephone();
            $xml .= '<Address>';
            $xml .= '<AddressType>POBOX</AddressType>';
            $xml .= '<AddressLine1>'.implode(",", $billingAddress->getStreet()).'</AddressLine1>';
            $xml .= '<City>' . $billingAddress->getCity() . '</City>';
            $xml .= '<Country>' . $this->helper->getCountryName($billingAddress->getCountryId()) . '</Country>';
            $xml .= '<PostalCode>' . $billingAddress->getPostcode() . '</PostalCode>';
            $xml .= '</Address>';
        }
        if ($shippingAddress = $order->getShippingAddress()) {
            $shippingPhone = $shippingAddress->getTelephone();
            $xml .= '<Address>';
            $xml .= '<AddressType>STREET</AddressType>';
            $xml .= '<AddressLine1>'.implode(",", $shippingAddress->getStreet()).'</AddressLine1>';
            $xml .= '<City>' . $shippingAddress->getCity() . '</City>';
            $xml .= '<Country>' . $this->helper->getCountryName($shippingAddress->getCountryId())  . '</Country>';
            $xml .= '<PostalCode>' . $shippingAddress->getPostcode() . '</PostalCode>';
            $xml .= '</Address>';
        }
        $xml .= '</Addresses>';
        if (isset($billingPhone) || isset($shippingPhone)) {
            $xml .= '<Phones>';
            if (isset($billingPhone)) {
                $xml .= '<Phone>';
                $xml .= '<PhoneType>DEFAULT</PhoneType>';
                $xml .= '<PhoneNumber>' . $billingPhone . '</PhoneNumber>';
                $xml .= '</Phone>';
            }
            if (isset($shippingPhone)) {
                $xml .= '<Phone>';
                $xml .= '<PhoneType>MOBILE</PhoneType>';
                $xml .= '<PhoneNumber>' . $shippingPhone . '</PhoneNumber>';
                $xml .= '</Phone>';
            }
            $xml .= '</Phones>';
        }
        return $xml;
    }

    public function getItemPrice($item)
    {
        if ($this->_taxType == "Inclusive") {
            return $item->getPriceInclTax();
        } else {
            return $item->getPrice();
        }
    }

    public function setCustomerXml($order)
    {
        $id = $order->getCustomerId();
        if ($this->scopeConfig->getValue('magenest_xero_config/xero_init_data/enabled')) {
            $customer = $this->_customerFactory->create()->load($id);
            $this->_customerXml .= $this->syncCustomer->addRecord($customer);
        }
        $this->customer[$this->helper->getScopeId()][$order->getCustomerEmail()]= $id;
    }

    /**
     * @throws \Exception
     */
    public function syncMissingCustomer()
    {
        if ($this->_customerXml) {
            $this->syncCustomer->syncData($this->_customerXml);
            $this->_customerXml = "";
        }
    }

    public function setProductXml($product)
    {
        $this->_productXml .= $this->syncProduct->addRecord($product);
    }

    /**
     * @throws \Exception
     */
    public function syncMissingProduct()
    {
        if ($this->_productXml) {
            $this->_productXml = "<Items>".$this->_productXml."</Items>";
            $this->syncProduct->syncData($this->_productXml);
            $this->_productXml = "";
        }
    }

    /**
     * @param $xml
     * @param string $method
     * @return string
     * @throws \Exception
     */
    public function syncData($xml, $method = 'POST')
    {
        $this->syncMissingCustomer();
        $this->syncMissingProduct();
        return parent::syncData($xml, $method);
    }
}

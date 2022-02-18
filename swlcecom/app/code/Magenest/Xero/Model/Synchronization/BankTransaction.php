<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ProductFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\ResourceModel\Queue\Collection;


/**
 * Class BankTransaction
 * @package Magenest\Xero\Model\Synchronization
 */
class BankTransaction extends Synchronization
{
    const TRANSACTION_CONTACT_CODE = 'magenest_xero';

    /**
     * @var string
     */
    protected $type = 'BankTransaction';

    protected $syncType = 'BankTransaction';

    protected $syncIdKey = 'CreditNoteNumber';

    protected $syncTypeKey = 'BankTransaction';

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var mixed|null
     */
    protected $items = null;

    protected $bankId;

    protected $inventoryId;

    protected $syncStock = false;

    /**
     * BankTransaction constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param ProductFactory $productFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param Account $account
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        ProductFactory $productFactory,
        ScopeConfigInterface $scopeConfig,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Account $account,
        Helper $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->bankId = $account->getAccountId($account::BANK_ACC_TYPE);
        $this->inventoryId = $account->getAccountId($account::INVENTORY_ACC_TYPE);
        $this->syncStock = $scopeConfig->getValue(Item::SYNC_STOCK_ENABLE);
        $this->limit = 1000;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    public function getItems()
    {
        if ($this->items == null) {
            $this->items = $this->getItemsOnXero();
        }
        return $this->items;
    }

    public function getItemsByWesite()
    {
        return $this->getItemsOnXero();
    }

    /**
     * @param $product
     * @return string
     */
    public function addRecord($product)
    {
        if (!$this->syncStock) {
            return '';
        }
        $product = $this->productFactory->create()->load($product->getId());
        $qtyInStock = $product->getExtensionAttributes()->getStockItem()->getQty();
        $price = $product->getCost() ? $product->getCost() : 0;
        if ($price == 0)
            $price = $product->getPrice() ? $product->getPrice() : 0;
        if (!$product->getId() || $price == 0 || !$qtyInStock) {
            return '';
        }
        if (count($this->getItems()) > 0) {
            if ($this->helper->isMultipleWebsiteEnable()) {
                $items = $this->getItemsByWesite();
            } else {
                $items = $this->getItems();
            }
            foreach ($items as $key => $item) {
                if (isset($item['Code']) && $item['Code'] == $product->getSku() && isset($item['QuantityOnHand'])) {
                    $qtyInStock = $qtyInStock - $item['QuantityOnHand'];
                    unset($items[$key]);
                    if ($qtyInStock <= 0) {
                        return '';
                    }
                    break;
                }
            }
        }
        $inventoryId = $this->helper->getConfig(Account::ACC_PATH.'inventory_id');
        $inventoryId = $inventoryId ? : $this->inventoryId;

        $xml = '<LineItem>';
        $xml .= '<Description>Update Quantity</Description>';
        $xml .= '<Quantity>' . $qtyInStock . '</Quantity>';
        $xml .= '<UnitAmount>' . $price . '</UnitAmount>';
        $xml .= '<ItemCode>' . $product->getSku() . '</ItemCode>';
        $xml .= '<AccountCode>' . $inventoryId . '</AccountCode>';
        $xml .= '</LineItem>';

        return $xml;
    }

    /**
     * Get all Items existed On Xero
     *
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     */
    protected function getItemsOnXero()
    {
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
            return $parsedResponse['Items']['Item'];
        }

        return [];
    }

    /**
     * Add other necessary tags to the request xml
     *
     * @param $xml
     * @return string
     */
    public function addOtherTags($xml)
    {
        if (!$this->syncStock) {
            return '';
        }
        if ($xml == '') {
            return '';
        }
        $bankId = $this->helper->getConfig(Account::ACC_PATH.'bank_id');
        $bankId = $bankId ? : $this->bankId;
        $xml = '<LineItems>' . $xml . '</LineItems>';
        $xml = '<Type>SPEND</Type>
                <Contact>
                <ContactNumber>'. self::TRANSACTION_CONTACT_CODE.'</ContactNumber>
                </Contact>' . $xml;
        $xml .= '<BankAccount>';
        $xml .= '<AccountID>' . $bankId . '</AccountID>';
        $xml .= '</BankAccount>';
        $xml = '<' . $this->syncType . '>' . $xml . '</' . $this->syncType . '>';

        return $xml;
    }
}

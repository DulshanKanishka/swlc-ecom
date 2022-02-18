<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magenest\Xero\Model\XmlLogFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magenest\Xero\Model\RequestLogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;


/**
 * Class Customer
 * @package Magenest\Xero\Model\Synchronization
 */
class Customer extends Synchronization
{
    /**
     * @var string
     */
    protected $type = 'Contact';

    protected $syncType = 'Contact';

    protected $syncIdKey = 'ContactNumber';

    protected $syncTypeKey = 'Contact';

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var array
     */
    protected $contacts = [];

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var ScopeConfigInterface
     */
    protected $_configInterface;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    protected $_xmlLogFactory;

    /**
     * Customer constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param Config $config
     * @param ScopeConfigInterface $configInterface
     * @param CustomerFactory $customerFactory
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     * @param XmlLogFactory $xmlLogFactory
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        Config $config,
        ScopeConfigInterface $configInterface,
        CustomerFactory $customerFactory,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory,
        Helper $helper,
        XmlLogFactory $xmlLogFactory
    ) {
        $this->_config = $config;
        $this->_configInterface = $configInterface;
        $this->customerFactory = $customerFactory;
        $this->limit = 1000;
        $this->collectionFactory = $collectionFactory;
        $this->id = "entity_id";
        $this->_xmlLogFactory = $xmlLogFactory;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }

    /**
     * @param $customer
     * @return string
     */
    public function addRecord($customer)
    {
        $xml = '<Contact>';
        $xml .= '<ContactNumber>' . $customer->getEntityId() . '</ContactNumber>';
        $xml .= '<Name>' . $customer->getName() .', '. $customer->getEntityId() . '</Name>';
        $xml .= '<FirstName>' . $customer->getFirstname() . '</FirstName>';
        $xml .= '<LastName>' . $customer->getLastname() . '</LastName>';
        $xml .= '<EmailAddress>' . $customer->getEmail() . '</EmailAddress>';

        if ($customer->getDefaultBillingAddress() || $customer->getDefaultShippingAddress()) {
            $xml .= '<Addresses>';
            if ($billingAddress = $customer->getDefaultBillingAddress()) {
                $billingPhone = $billingAddress->getTelephone();
                $xml .= '<Address>';
                $xml .= '<AddressType>POBOX</AddressType>';
                $xml .= '<AddressLine1>'.$billingAddress->getStreetFull().'</AddressLine1>';
                $xml .= '<City>' . $billingAddress->getCity() . '</City>';
                $xml .= '<Country>' . $billingAddress->getCountry() . '</Country>';
                $xml .= '<PostalCode>' . $billingAddress->getPostcode() . '</PostalCode>';
                $xml .= '</Address>';
            }
            if ($shippingAddress = $customer->getDefaultShippingAddress()) {
                $shippingPhone = $shippingAddress->getTelephone();
                $xml .= '<Address>';
                $xml .= '<AddressType>STREET</AddressType>';
                $xml .= '<AddressLine1>'.$shippingAddress->getStreetFull().'</AddressLine1>';
                $xml .= '<City>' . $shippingAddress->getCity() . '</City>';
                $xml .= '<Country>' . $shippingAddress->getCountry() . '</Country>';
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
        }

        $xml .= '</Contact>';

        return $xml;
    }

    /**
     * Check if a contact existed on Xero
     *
     * @param $email
     * @return bool|string
     */
    public function contactExisted($email)
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('Contacts');
        $helper->setMethod();
        $helper->setParamsForSyncing(['where'=>'emailaddress="'.$email.'"']);
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url,[
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();
        $parsedResponse = $this->parseXML($response);
        if (isset($parsedResponse['Contacts']['Contact']['ContactNumber'])) {
            return $parsedResponse['Contacts']['Contact']['ContactNumber'];
        }

        return false;
    }

    /**
     * @param $order
     * @throws \Exception
     */
    public function syncGuest($order)
    {
        $address = $order->getShippingAddress();
        if (!$address) {
            $address = $order->getBillingAddress();
        }
        $code = substr(md5($order->getCustomerEmail()), 0, 5);
        $customerXml = '<Contact>';
        $customerXml .= '<ContactNumber>' .$code. '</ContactNumber>';
        $customerXml .= '<Name>' . $address->getFirstname().' '.$address->getLastname() .' '.$code. '</Name>';
        $customerXml .= '<FirstName>' . $address->getFirstname() . '</FirstName>';
        $customerXml .= '<LastName>' . $address->getLastname() . '</LastName>';
        $customerXml .= '<EmailAddress>' . $order->getCustomerEmail() . '</EmailAddress>';
        $customerXml .= '</Contact>';
        $this->_xmlLogFactory->create()->setData([
            'magento_id' => $code,
            'xml_log' => $customerXml,
            'type' => 'Contact',
            'scope' => $this->helper->getScope(),
            'scope_id' => $this->helper->getScopeId()
        ])->save();
        $this->syncData($customerXml);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     * @throws LocalizedException
     */
    public function getContactXml($order)
    {
        $xml = '<Contact>';
        $xml .= '<ContactNumber>';
        if ($order->getCustomerId()) {
            $code = $order->getCustomerId();
        } else {
            $email = $order->getCustomerEmail();
            $code = $this->contactExisted($email);
            if (!$code) {
                $code = substr(md5($email), 0, 5);
                $this->syncGuest($order);
            }
        }

        if (!$code) {
            throw new LocalizedException(__('Can\'t get contact code in Xero'));
        }

        $xml .= $code;
        $xml .= '</ContactNumber>';
        $xml .= '</Contact>';

        return $xml;
    }

    public function getTransactionContactXml()
    {
        $customerXml = '<Contact>';
        $customerXml .= '<ContactNumber>' . BankTransaction::TRANSACTION_CONTACT_CODE. '</ContactNumber>';
        $customerXml .= '<Name>Xero Transaction Contact</Name>';
        $customerXml .= '<FirstName>Xero Transaction</FirstName>';
        $customerXml .= '<LastName>Contact</LastName>';
        $customerXml .= '<EmailAddress>xero@magento.com</EmailAddress>';
        $customerXml .= '</Contact>';
        return $customerXml;
    }
}

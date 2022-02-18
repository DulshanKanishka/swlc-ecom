<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\QueueFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\RequestLogFactory;

/**
 * Class Account
 * @package Magenest\Xero\Model\Synchronization
 */
class Account extends Synchronization
{
    const BANK_ACC_TYPE = 'BANK';
    const INVENTORY_ACC_TYPE = 'INVENTORY';
    const SALE_ACC_TYPE = 'SALES';
    const COGS_ACC_TYPE = 'DIRECTCOSTS';
    const ACC_PATH = 'magenest_xero_config/xero_account/';

    /**
     * @var string
     */
    protected $type = 'Accounts';

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var ScopeConfigInterface
     */
    protected $_configInterface;


    /**
     * Account constructor.
     *
     * @param XeroClient $xeroClient
     * @param Config $config
     * @param ScopeConfigInterface $configInterface
     * @param LogFactory $logFactory
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        XeroClient $xeroClient,
        Config $config,
        ScopeConfigInterface $configInterface,
        LogFactory $logFactory,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Helper $helper
    ) {
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
        $this->_config = $config;
        $this->_configInterface = $configInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord($id)
    {
        // TODO: Implement addRecord() method.
    }

    /**
     * Sync all bank account, inventory account, cogs account, sale account
     */
    public function syncAccounts()
    {
        // Get account config in configuration
        $configs = [
            'bank' => $this->getAccountConfig(self::BANK_ACC_TYPE),
//            'inventory' => $this->getAccountConfig(self::INVENTORY_ACC_TYPE),
//            'cogs' => $this->getAccountConfig(self::COGS_ACC_TYPE),
            'sales' => $this->getAccountConfig(self::SALE_ACC_TYPE),
        ];
        foreach ($configs as $config) {
            // Check if account exists on Xero
            $account = $this->getXeroAccountByName($config['name']);
            if (isset($account['Accounts']['Account'])) {
                // if exist then save config
                $account = $account['Accounts']['Account'];
                $this->saveAccount($account);
            } else {
                // if not exist then send request to create new account & save config
                $type = '';
                $code = '';
                // setup account info
                switch ($config['type']) {
                    case 'BANK':
                        $type = self::BANK_ACC_TYPE;
                        $code = substr(md5($config['name']), 0, 5);
                        break;
                    case 'SALES':
                        $type = self::SALE_ACC_TYPE;
                        $code = substr(md5($config['name']), 0, 5);
                        break;
                    case 'INVENTORY':
                        $type = self::INVENTORY_ACC_TYPE;
                        $code = substr(md5($config['name']), 0, 5);
                        break;
                    case 'DIRECTCOSTS':
                        $type = self::COGS_ACC_TYPE;
                        $code = substr(md5($config['name']), 0, 5);
                        break;
                    default:
                }
                $info = [
                    'name' => $config['name'],
                    'code' => $code,
                    'type' => $type
                ];
                $response = $this->syncAccount($info);
                if (isset($response['Accounts']['Account'])) {
                    // if request is completed
                    $account = $response['Accounts']['Account'];
                    $this->saveAccount($account);
                    $this->logResponse($this->type, $response);
                }
            }
        }
    }

    /**
     * @param array $info
     * @return array
     */
    public function syncAccount($info = [])
    {
        $xml = '<Account>';
        $xml .= '<Code>' . $info['code'] . '</Code>';
        $xml .= '<Name>' . $info['name'] . '</Name>';
        $xml .= '<Type>' . $info['type'] . '</Type>';
        if ($info['type'] == 'BANK') {
            $xml .= '<BankAccountNumber>1234567890</BankAccountNumber>';
            $xml .= '<BankAccountType>BANK</BankAccountType>';
        }
        $xml .= '</Account>';
        $response = parent::parseXML($this->syncData($xml, 'PUT'));

        return $response;
    }

    /**
     * @param $name
     * @return array
     * @throws \Exception
     */
    public function getXeroAccountByName($name)
    {
        $helper = $this->xeroClient->getSignature();
        $helper->setUri('Accounts');
        $helper->setMethod();
        $helper->setParams(['where' => 'name="' . $name . '"']);
        $url = $helper->getUri() . '?' . $helper->sign();

        $client = new \Zend_Http_Client($url, [
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->request()->getBody();

        return parent::parseXML($response);
    }

    /**
     * @param array $info
     */
    public function saveAccount($info = [])
    {
        if (isset($info['Type'])) {
            $path = $this->getConfigPath($info['Type']);
            if (!empty($path['id']) && isset($info['AccountID'])) {
                $this->_config->saveConfig($path['id'], $info['AccountID'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            }
            if (!empty($path['code']) && isset($info['Code'])) {
                $this->_config->saveConfig($path['code'], $info['Code'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            }
        }
    }

    /**
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getAccountConfig($type)
    {
        $path = $this->getConfigPath($type);
        $config = [
            'type' => $type,
            'name' => $this->_configInterface->getValue($path['name']),
            'id' => $this->_configInterface->getValue($path['id']),
            'code' => $this->_configInterface->getValue($path['code']),
        ];

        return $config;
    }

    /**
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getAccountId($type)
    {
        $path = $this->getConfigPath($type);

        return $this->_configInterface->getValue($path['id']);
    }

    /**
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getConfigPath($type)
    {
        $path = self::ACC_PATH;
        switch ($type) {
            case 'BANK':
                $namePath = $path . 'bank_name';
                $idPath = $path . 'bank_id';
                $codePath = $path . 'bank_code';
                break;
            case 'SALES':
            case 'REVENUE':
                $namePath = $path . 'sale_name';
                $idPath = $path . 'sale_id';
                $codePath = $path . 'sale_code';
                break;
            case 'INVENTORY':
                $namePath = $path . 'inventory_name';
                $idPath = $path . 'inventory_id';
                $codePath = $path . 'inventory_code';
                break;
            case 'DIRECTCOSTS':
                $namePath = $path . 'cogs_name';
                $idPath = $path . 'cogs_id';
                $codePath = $path . 'cogs_code';
                break;
            default:
                throw new \Exception(__('Account Type is incorrect, check all accounts setting'));
        }
        $configPath = ['name' => $namePath, 'id' => $idPath, 'code' => $codePath];

        return $configPath;
    }
}


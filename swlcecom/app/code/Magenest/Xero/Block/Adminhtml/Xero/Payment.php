<?php
namespace Magenest\Xero\Block\Adminhtml\Xero;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\PaymentMappingFactory;
use Magenest\Xero\Model\XeroClient;
/**
 * Class Payment
 * @package Magenest\Xero\Block\Adminhtml\Xero
 */
class Payment extends \Magento\Backend\Block\Widget
{

    protected $xeroClient;

    protected $acceptType = ['BANK'];

    protected $mappingFactory;

    protected $_cache;

    protected $_helper;

    /**
     * Payment constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param XeroClient $xeroClient
     * @param PaymentMappingFactory $mappingFactory
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        XeroClient $xeroClient,
        PaymentMappingFactory $mappingFactory,
        Helper $helper,
        array $data = []
    ) {
        $this->xeroClient = $xeroClient;
        $this->mappingFactory = $mappingFactory;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    public function getPaymentMethods()
    {
        $methods = $this->_scopeConfig->getValue('payment');
        foreach ($methods as $code => $method) {
            if (isset($method['active']) && $method['active'] == 0) {
                unset($methods[$code]);
            }
        }
        return $methods;
    }

    public function getWebsiteId()
    {
        return $this->getRequest()->getParam('website') ? : 0;
    }

    public function getAccounts()
    {
        $websiteId = $this->getWebsiteId();
        if ($websiteId > 0) {
            $this->_helper->setScope('websites');
            $this->_helper->setScopeId($websiteId);
        }
        $cacheData = $this->_cache->load('XERO_BANK_ACCOUNTS_'.$websiteId);
        $accounts = unserialize($cacheData);
        if (!$accounts) {
            if(!$this->_scopeConfig->getValue('magenest_xero_config/xero_api/is_connected')) {
                return '(Your Xero has been disconnected! Please connect to your Xero before configuring this mapping!)';
            }
            try {
                $helper = $this->xeroClient->getSignature();
                $helper->setUri('Accounts');
                $helper->setMethod();
                $helper->setParams(['where' => 'EnablePaymentsToAccount = true OR TYPE = "BANK"']);
                $url = $helper->getUri(). '?' . $helper->sign();
                $client = new \Zend_Http_Client($url, [
                    'timeout' => 30,
                    'useragent' => XeroClient::getUserAgent()
                ]);
                $response = $client->request()->getBody();
                if ( strpos($response, 'oauth_problem') !== false) {
                    return '(Invalid Token! Please check your Xero credential before configuring this mapping!)';
                }
                $parser = new \Magento\Framework\Xml\Parser();
                $parser->loadXML($response);
                $parsedResponse = $parser->xmlToArray();
                if (isset($parsedResponse['Response']['Accounts']['Account'])) {
                    $accounts = $parsedResponse['Response']['Accounts']['Account'];
                    $cacheData = serialize($accounts);
                    $this->_cache->save($cacheData, 'XERO_BANK_ACCOUNTS_'.$websiteId, ['config']);
                    return $accounts;
                }
                throw new \Exception('Can not get Xero Acconts. Response: '.$response);
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        return $accounts;
    }

    public function getSelectedMapping($paymentCode)
    {
        $mapping = $this->mappingFactory->create()->loadByPaymentCode($paymentCode);
        if ($mapping) {
            return $mapping->getBankAccountId();
        }
        return null;
    }
}

<?php
namespace Magenest\Xero\Block\Adminhtml\Xero;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\TaxMappingFactory;
use Magenest\Xero\Model\XeroClient;
use Magento\Tax\Model\Calculation\Rate;
/**
 * Class Payment
 * @package Magenest\Xero\Block\Adminhtml\Xero
 */
class Tax extends \Magento\Backend\Block\Widget
{

    protected $xeroClient;

    protected $taxModelConfig;

    protected $mappingFactory;

    protected $_cache;

    protected $_helper;

    /**
     * Tax constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param XeroClient $xeroClient
     * @param TaxMappingFactory $mappingFactory
     * @param Rate $taxModelConfig
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        XeroClient $xeroClient,
        TaxMappingFactory $mappingFactory,
        Rate $taxModelConfig,
        Helper $helper,
        array $data = []
    ) {
        $this->xeroClient = $xeroClient;
        $this->mappingFactory = $mappingFactory;
        $this->taxModelConfig = $taxModelConfig;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    public function getTaxRates()
    {
        $taxRates = $this->taxModelConfig->getCollection()->getData();
        $methods = array();
        foreach ($taxRates as $tax) {
            $methods[$tax['code']] = $tax;
        }
        return $methods;
    }

    public function getWebsiteId() {
        return $this->_request->getParam('website') ? : 0;
    }

    public function getTaxes()
    {
         $websiteId = $this->_request->getParam('website') ? : 0;
        if ($websiteId) {
            $this->_helper->setScope('websites');
            $this->_helper->setScopeId($websiteId);
        }
        $cacheData = $this->_cache->load('XERO_TAX_RATES_'.$websiteId);
        $accounts = unserialize($cacheData);
        if (!$accounts) {
            if(!$this->_helper->getConfig('magenest_xero_config/xero_api/is_connected')) {
                return '(Your Xero has been disconnected! Please connect to your Xero before configuring this mapping!)';
            }
            try {
                $helper = $this->xeroClient->getSignature();
                $helper->setUri('Taxrates');
                $helper->setMethod();
                $helper->setParamsForSyncing();
                $url = $helper->getUri() . '?' . $helper->sign();

                $client = new \Zend_Http_Client($url);
                $response = $client->request()->getBody();
                if ( strpos($response, 'oauth_problem') !== false) {
                    return '(Invalid Token! Please check your Xero credential before configuring this mapping!)';
                }
                $parser = new \Magento\Framework\Xml\Parser();
                $parser->loadXML($response);
                $parsedResponse = $parser->xmlToArray();
                if (isset($parsedResponse['Response']['TaxRates']['TaxRate'])) {
                    $taxes = $parsedResponse['Response']['TaxRates']['TaxRate'];
                    $cacheData = serialize($taxes);
                    $this->_cache->save($cacheData, 'XERO_TAX_RATES_'.$websiteId, ['config']);
                    return $taxes;
                }
                throw new \Exception('Can not get Xero Acconts. Response: '.$response);
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        return $accounts;
    }

    public function getSelectedMapping($taxCode)
    {
        $mapping = $this->mappingFactory->create()->loadByTaxCode($taxCode);
        if ($mapping) {
            return $mapping->getXeroTaxCode();
        }
        return null;
    }
}

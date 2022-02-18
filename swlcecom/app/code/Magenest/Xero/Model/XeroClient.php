<?php
/**
 * Copyright © 2015 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Magenest_Xero extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package  Magenest_Xero
 * @author   ThaoPV
 */
namespace Magenest\Xero\Model;

use Magenest\Xero\Model\Config\Source\AppMode;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\ZendClient;
use Magenest\Xero\Helper\Signature;
use Magento\Framework\UrlInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;

/**
 * Class XeroClient
 *
 * @package Magenest\Xero\Model
 */
class XeroClient extends ZendClient
{
    /**
     * @var Signature
     */
    protected $signature;

    /**
     * @var array
     */
    private $params;

    protected $url;

    protected $scopeConfig;

    protected $configModel;

    protected $_cache;

    protected $_helper;

    /**
     * XeroClient constructor.
     * @param Signature $signature
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigModel $configModel
     * @param CacheInterface $cache
     * @param Helper $helper
     * @param null $uri
     * @param null $config
     */
    public function __construct(
        Signature $signature,
        UrlInterface $urlInterface,
        ScopeConfigInterface $scopeConfig,
        ConfigModel $configModel,
        CacheInterface $cache,
        Helper $helper,
        $uri = null,
        $config = null
    )
    {
        $config['timeout'] = 30;
        parent::__construct($uri, $config);
        $this->signature = $signature;
        $this->url = $urlInterface;
        $this->scopeConfig = $scopeConfig;
        $this->configModel = $configModel;
        $this->_cache = $cache;
        $this->_helper = $helper;
        $this->config['useragent'] = self::getUserAgent();
    }

    public static function getUserAgent()
    {
        return 'MagenestMagento2Connector_' . ObjectManager::getInstance()->get(ScopeConfigInterface::class)->getValue(\Magenest\Xero\Model\Config::XML_PATH_XERO_CONSUMER_KEY);
    }

    /**
     * @return Signature
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Get Params
     *
     * @return array
     */
    private function getParams()
    {
        if ($this->params === null) {
            $this->params = [];
        }

        return $this->params;
    }

    /**
     * @param $params
     * @return $this
     */
    private function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function getRequestToken($callbackUrl)
    {
        $params = [
            'oauth_callback' => $callbackUrl
        ];
        $signature = $this->signature;
        $signature->setUri(Signature::REQUEST_TOKEN_PATH);
        $signature->setMethod();
        $signature->setParams($params);
        $uri = $signature->getUri() . '?' . $signature->sign();
        $client = new \Zend_Http_Client($uri, [
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->setUri($uri)->request()->getBody();
        parse_str($response, $responseArray);
        if (!isset($responseArray['oauth_token'])) {
            throw new \Exception('Could not get oauth token. Reponse: ' . $response);
        }
        return $responseArray;
    }

    public function getAccessToken($oauth)
    {
        $params = [
            'oauth_verifier' => $oauth['oauth_verifier'],
            'oauth_token' => $oauth['oauth_token'],
            'oauth_token_secret' => $oauth['oauth_token_secret']
        ];
        $signature = $this->signature;
        $signature->setUri(Signature::ACCESS_TOKEN_PATH);
        $signature->setMethod();
        $signature->setParams($params);
        $uri = $signature->getUri() . '?' . $signature->sign();
        $client = new \Zend_Http_Client($uri, [
            'timeout' => 30,
            'useragent' => XeroClient::getUserAgent()
        ]);
        $response = $client->setUri($uri)->request()->getBody();
        parse_str($response, $responseArray);
        if (!isset($responseArray['oauth_token'])) {
            throw new \Exception('Could not get oauth token. Reponse: ' . $response);
        }
        return $responseArray;
    }

    /**
     * Send Request to Xero Server
     *
     * @param $url
     * @param string $method
     * @param array $params
     * @return string
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function sendRequest($url, $method = 'GET', $params = [])
    {
        $this->setHeaders('Content-type', 'application/x-www-form-urlencoded;charset:UTF-8');
        $this->setUri($url);
        $method = strtoupper($method);
        switch ($method) {
            case 'DELETE':
            case 'GET':
                $this->setParameterGet($params);
                break;
            case 'PUT':
                if (isset($params['xml'])) {
                    $this->setRawData($params['xml']);
                }
                break;
            case 'POST':
                $this->setParameterPost($params);
                break;
            default:
                throw new \Exception(__('HTTP method is not supported'));
        }
        $response = $this->request($method)->getBody();
        $status = $this->getLastResponse()->getStatus();
        if ($status > 400) {
            $this->processResponse($response);
            throw new \Exception(urldecode($response));
        }

        return $response;
    }

    /**
     * @return $this
     */
    protected function _trySetCurlAdapter()
    {
        if (extension_loaded('curl')) {
            $this->setAdapter(new \Zend_Http_Client_Adapter_Curl());
        }

        return $this;
    }

    /**
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function checkConnect($params)
    {
        $this->setParams($params);
        try {
            $this->getUserInformation();
            $result = [
                'error' => false,
                'description' => 'You have connected to Xero using Private App.',
            ];

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get User Information
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getUserInformation()
    {
        $path = 'Organisations';
        $signatureHelper = $this->getSignature();
        $signatureHelper->setParamsForSyncing($this->getParams());
        $signatureHelper->setUri($path);
        $url = $signatureHelper->getUri() . '?' . $signatureHelper->sign();
        $xmlResponse = $this->sendRequest($url);
        $parser = new Parser;
        $response = $parser->parseXML($xmlResponse);
        if (isset($response['Organisations']['Organisation'])) {
            $response = $response['Organisations']['Organisation'];
            $info = [
                'Organisation' => $response['Name'],
                'Organisation Status' => $response['OrganisationStatus'],
                'Country' => $response['CountryCode'],
                'Version' => $response['Version']
            ];
            $response = $info;
        } else {
            header("Refresh:0");
            throw new \Exception('Could not retrieve Organization information');
        }
        return $response;
    }

    protected function processResponse($response)
    {
        parse_str($response, $arrResult);
        if ($arrResult && is_array($arrResult)) {
            if ($this->scopeConfig->getValue(Signature::PATH_APP_TYPE) == AppMode::PUBLIC_APP) {
                if (isset($arrResult['oauth_problem']) && $arrResult['oauth_problem'] == 'token_expired') {
                    $this->disconnectApp();
                }
            }
        }
    }

    public function disconnectApp()
    {
        $this->configModel->saveConfig(
            Signature::PATH_XERO_IS_CONNECTED,
            0,
            $this->_helper->getScope(),
            $this->_helper->getScopeId()
        );
        $this->configModel->saveConfig(
            Signature::PATH_OAUTH_TOKEN,
            null,
            $this->_helper->getScope(),
            $this->_helper->getScopeId()
        );
        $this->configModel->saveConfig(
            Signature::PATH_OAUTH_TOKEN_SECRET,
            null,
            $this->_helper->getScope(),
            $this->_helper->getScopeId()
        );
        Cache::refreshCache();
    }
}

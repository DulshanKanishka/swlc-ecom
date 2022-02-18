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
 * @author   ThaoPV <thaopw@gmail.com>
 */
namespace Magenest\Xero\Helper;

use Magenest\Xero\Model\Config\Source\AppMode;
use Magenest\Xero\Model\Helper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\CoreConfig;

/**
 * Class Signature
 *
 * @package Magenest\Xero\Helper
 */
class Signature
{
    protected $_version = null;

    /**
     * @var string
     */
    protected $signatureMethod;

    /**
     * @var string
     */
    protected $signature = null;

    /**
     * @var string
     */
    protected $uri;

    /**
     * Method using when sendRequest to Xero
     *
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var string
     */
    protected $headers;

    /**
     * @var string
     */
    protected $consumerKey = null;

    /**
     * @var string
     */
    protected $consumerSecret = null;

    protected $appType = null;

    protected $oauthSecret = null;

    protected $oauthToken = null;

    protected $_coreConfig;

    protected $_helper;

    /**
     * CONSTANT
     */
    const NONCE = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const DEFAULT_VERSION = '1.0';
    const DEFAULT_SIGNATURE = 'HMAC-SHA1';
    const SIGNATURE_PLAINTEXT = 'PLAINTEXT';
    const SIGNATURE_HMAC = 'HMAC-SHA1';
    const SIGNATURE_RSA = 'RSA-SHA1';
    const URL_XERO_API = 'https://api.xero.com/api.xro/2.0/';
    const PATH_CONSUMER_KEY = 'magenest_xero_config/xero_api/consumer_key';
    const PATH_CONSUMER_SECRET = 'magenest_xero_config/xero_api/consumer_secret';
    const PATH_APP_TYPE = 'magenest_xero_config/xero_api/app_mode';
    const PATH_OAUTH_TOKEN = 'magenest_xero_config/xero_api/oauth_token';
    const PATH_OAUTH_TOKEN_SECRET = 'magenest_xero_config/xero_api/oauth_token_secret';
    const PATH_XERO_IS_CONNECTED = 'magenest_xero_config/xero_api/is_connected';
    const REQUEST_TOKEN_PATH = 'RequestToken';
    const AUTHORIZE_PATH = 'Authorize';
    const ACCESS_TOKEN_PATH = 'AccessToken';
    const URL_OAUTH = 'https://api.xero.com/oauth/';

    /**
     * @var Signature\Rsa
     */
    protected $signatureRsa;

    /**
     * @var Signature\Hmac
     */
    protected $signatureHmac;

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;


    /**
     * Signature constructor.
     * @param Signature\Rsa $signatureRsa
     * @param Signature\Hmac $signatureHmac
     * @param Signature\Plaintext $signaturePlaintext
     * @param ScopeConfigInterface $config
     * @param CoreConfig $coreConfig
     * @param Helper $helper
     */
    public function __construct(
        Signature\Rsa $signatureRsa,
        Signature\Hmac $signatureHmac,
        Signature\Plaintext $signaturePlaintext,
        ScopeConfigInterface $config,
        CoreConfig $coreConfig,
        Helper $helper
    ) {
        $this->signatureRsa = $signatureRsa;
        $this->signatureHmac = $signatureHmac;
        $this->signaturePlaintext = $signaturePlaintext;
        $this->_coreConfig = $coreConfig;
        $this->_helper = $helper;
        $this->_config = $config;
    }

    /**
     * @return string
     */
    public function sign()
    {
        $this->params['oauth_signature'] = $this->_generateSignature();

        return $this->_normalized(true);
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method = self::METHOD_GET)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        if (!$this->method) {
            $this->method = self::METHOD_GET;
        }
        return $this->method;
    }

    /**
     * @param string $signMethod
     * @return $this
     */
    public function setSignatureMethod($signMethod = self::SIGNATURE_RSA)
    {
        $this->signatureMethod = $signMethod;
        return $this;
    }

    /**
     * @return string
     */
    protected function getSignatureMethod()
    {
        if (!$this->signatureMethod) {
            switch ($this->getAppType()) {
                case AppMode::PUBLIC_APP:
                    $this->setSignatureMethod(self::SIGNATURE_HMAC);
                    break;
                case AppMode::PARTNER_APP:
                case AppMode::PRIVATE_APP:
                default:
                    $this->setSignatureMethod();
                    break;
            }
        }

        return $this->signatureMethod;
    }


    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParamsForSyncing($params = [])
    {
        $this->params = [
            'oauth_consumer_key' => $this->_getConsumerKey(),
            'oauth_signature_method' => $this->getSignatureMethod(),
            'oauth_version' => self::DEFAULT_VERSION,
            'oauth_nonce' => $this->getNonce(),
            'oauth_timestamp' => $this->getTimestamp()
        ];
        $this->params = array_merge($this->params, $params);
        if ($this->getSignatureMethod() == self::SIGNATURE_RSA) {
            $this->params['oauth_token'] = $this->_getConsumerKey();
        }
        if ($this->_getOauthToken()) {
            $this->params['oauth_token'] = $this->_getOauthToken();
        }
        if (isset($this->params['oauth_token_secret'])) {
            $this->signatureHmac->setOauthSecret($this->params['oauth_token_secret']);
            unset($this->params['oauth_token_secret']);
        } else {
            $this->signatureHmac->setOauthSecret($this->getOauthSecret());
        }

        return $this;
    }

    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParams($params = [])
    {
        $this->params = [
            'oauth_consumer_key' => $this->getConsumerKey(),
            'oauth_signature_method' => $this->getSignatureMethod(),
            'oauth_version' => self::DEFAULT_VERSION,
            'oauth_nonce' => $this->getNonce(),
            'oauth_timestamp' => $this->getTimestamp()
        ];
        $this->params = array_merge($this->params, $params);
        if ($this->getSignatureMethod() == self::SIGNATURE_RSA) {
            $this->params['oauth_token'] = $this->getConsumerKey();
        }
        if ($this->getOauthToken()) {
            $this->params['oauth_token'] = $this->getOauthToken();
        }
        if (isset($this->params['oauth_token_secret'])) {
            $this->signatureHmac->setOauthSecret($this->params['oauth_token_secret']);
            unset($this->params['oauth_token_secret']);
        } else {
            $this->signatureHmac->setOauthSecret($this->getOauthSecret());
        }

        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setUri($path)
    {
        switch ($path) {
            case self::REQUEST_TOKEN_PATH:
            case self::ACCESS_TOKEN_PATH:
            case self::AUTHORIZE_PATH:
                $this->uri = self::URL_OAUTH . $path;
                break;
            default:
                $this->uri = self::URL_XERO_API . $path;
        }

        return $this;
    }

    /**
     *
     * @return mixed
     * @throws \Exception
     */
    protected function _generateHeaders()
    {
        if (empty($this->params['oauth_signature'])) {
            $this->_generateSignature();
        }
        $result = 'OAuth ';

        foreach ($this->params as $key => $value) {
            if (strpos($key, 'oauth_') !== 0) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $val) {
                    $result .= $key . '="' . $this->_escape($val) . '", ';
                }
            } else {
                $result .= $key . '="' . $this->_escape($value) . '", ';
            }
        }
        $this->headers = preg_replace('/, $/', '', $result);
        return $this;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->headers;
    }

    /**
     * Random string with lenght
     *
     * @param int $length
     * @return string
     */
    protected function getNonce($length = 5)
    {
        $tmp = str_split(self::NONCE);
        shuffle($tmp);

        return substr(implode('', $tmp), 0, $length);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return $this
     */
    protected function getTimestamp()
    {
        return time();
    }

    /**
     * @return string
     */
    public function signNeedEncode()
    {
        $url = $this->getUri();

        $signEncode = $this->getMethod()
            . '&' . $this->escape($url)
            . '&' . $this->escape($this->normalized());

        return $signEncode;
    }

    /**
     * @param bool $filter
     * @return string
     */
    protected function _normalized($filter = false)
    {
        $normalized = [];
        if (empty($this->params['oauth_nonce'])) {
            $this->params['oauth_nonce'] = $this->getNonce();
        }
        if (empty($this->params['oauth_timestamp'])) {
            $this->params['oauth_timestamp'] = $this->getTimestamp();
        }

        ksort($this->params);

        foreach ($this->params as $key => $value) {
            if ($key == 'xml') {
                if ($filter == true) {
                    continue;
                }
            }
            if (is_array($value)) {
                $sort = $value;
                sort($sort);
                foreach ($sort as $subkey => $subvalue) {
                    $normalized[] = $this->_escape($key) . '=' . $this->_escape($subvalue);
                }
            } else {
                $normalized[] = $this->_escape($key) . '=' . $this->_escape($value);
            }
        }

        return implode('&', $normalized);
    }

    /**
     * @param bool $filter
     * @return string
     */
    public function normalized($filter = false)
    {
        return $this->_normalized($filter);
    }

    /**
     * @param $string
     * @return mixed
     */
    protected function _escape($string)
    {
        if ($string === false) {
            return $string;
        } else {
            $string = rawurlencode($string);
            $string = str_replace('+', '%20', $string);
            $string = str_replace('!', '%21', $string);
            $string = str_replace('*', '%2A', $string);
            $string = str_replace('\'', '%27', $string);
            $string = str_replace('(', '%28', $string);
            $string = str_replace(')', '%29', $string);
            return $string;
        }
    }

    /**
     * @param $str
     * @return mixed
     */
    public function escape($str)
    {
        return $this->_escape($str);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function _generateSignature()
    {
        switch ($this->getSignatureMethod()) {
            case self::SIGNATURE_RSA:
                return $this->signatureRSA();
            case self::SIGNATURE_PLAINTEXT:
                return $this->signaturePlainText();
            case self::SIGNATURE_HMAC:
                return $this->signatureHmac();
            default:
                throw new \Exception('Unknown signature method for Signature');
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function signatureRSA()
    {
        $signEncode = $this->signNeedEncode();

        return $this->signatureRsa->signature($signEncode);
    }

    /**
     * @return string
     */
    protected function signaturePlainText()
    {
        $this->signaturePlaintext->setSignature($this);

        return $this->signaturePlaintext->signature();
    }

    /**
     * @return string
     */
    protected function signatureHmac()
    {
//        $this->signatureHmac->setSignature($this);
        $signEncode = $this->signNeedEncode();
        $this->signatureHmac->setSharedSecret($this->getConsumerSecret());

        return $this->signatureHmac->signature($signEncode);
    }

    /**
     * @return null|string
     */
    public function getConsumerKey()
    {
        if ($this->consumerKey == null) {
            $this->consumerKey = $this->_getConsumerKey();
        }
        return $this->consumerKey;
    }

    /**
     * @return null|string
     */
    public function getConsumerSecret()
    {
        if ($this->consumerSecret == null) {
            $this->consumerSecret = $this->_getConsumerSecret();
        }
        return $this->consumerSecret;
    }

    public function getOauthSecret()
    {
        if ($this->oauthSecret == null) {
            $this->oauthSecret = $this->_getOauthSecret();
        }
        return $this->oauthSecret;
    }

    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
    }

    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;
    }

    public function setOauthSecret($oauthSecret)
    {
        $this->oauthSecret = $oauthSecret;
    }

    public function getOauthToken()
    {
        if ($this->oauthToken == null) {
            $this->oauthToken = $this->_getOauthToken();
        }
        return $this->oauthToken;
    }

    public function getAppType()
    {
        if ($this->appType == null) {
            $this->appType = $this->_getAppType();
        }
        return $this->appType;
    }

    public function _getOauthToken()
    {
        return $this->_getStoreConfig(self::PATH_OAUTH_TOKEN);
    }

    protected function _getAppType()
    {
        return $this->_getStoreConfig(self::PATH_APP_TYPE);
    }

    protected function _getOauthSecret()
    {
        return $this->_getStoreConfig(self::PATH_OAUTH_TOKEN_SECRET);
    }

    /**
     * @return string
     */
    protected function _getConsumerKey()
    {
        return $this->_getStoreConfig(self::PATH_CONSUMER_KEY);
    }

    /**
     * @return string
     */
    protected function _getConsumerSecret()
    {
        return $this->_getStoreConfig(self::PATH_CONSUMER_SECRET);
    }

    /**
     * @param $xmlPath
     * @return mixed
     */
    protected function _getStoreConfig($xmlPath)
    {
        return $this->_coreConfig->getConfigValueByScope(
            $xmlPath,
            $this->_helper->getScope(),
            $this->_helper->getScopeId()
        );
    }
}

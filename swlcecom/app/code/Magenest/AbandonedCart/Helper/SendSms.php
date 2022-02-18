<?php

namespace Magenest\AbandonedCart\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SendSms extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_NEXMO_CONFIG_API_KEY    = 'abandonedcart/nexmo/api_key';
    const XML_PATH_NEXMO_CONFIG_API_SECRET = 'abandonedcart/nexmo/api_secret';
    const XML_PATH_NEXMO_CONFIG_FROM       = 'abandonedcart/nexmo/from';

    /** @var \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory */
    protected $zendClientFactory;

    /** @var ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /**
     * SendSms constructor.
     *
     * @param \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->zendClientFactory = $zendClientFactory;
        $this->scopeConfig       = $context->getScopeConfig();
        parent::__construct($context);
    }

    public function send($sms)
    {
        $apiKey    = $this->scopeConfig->getValue(self::XML_PATH_NEXMO_CONFIG_API_KEY, ScopeInterface::SCOPE_STORE);
        $apiSecret = $this->scopeConfig->getValue(self::XML_PATH_NEXMO_CONFIG_API_SECRET, ScopeInterface::SCOPE_STORE);
        $from      = $this->scopeConfig->getValue(self::XML_PATH_NEXMO_CONFIG_FROM, ScopeInterface::SCOPE_STORE);

        $client = $this->zendClientFactory->create();

        $url = 'https://rest.nexmo.com/sms/json';

        $content = $sms->getData('content');
        $to      = $sms->getData('recipient_adress');

        if (!$content || !$to) {
            throw new \Exception('no content or no recipient number');
        }

        $client->setUri($url);
        $client->setConfig(['timeout' => 300]);


        $client->setParameterPost('api_key', $apiKey);
        $client->setParameterPost('api_secret', $apiSecret);
        $client->setParameterPost('from', $from);
        $client->setParameterPost('api_key', $apiKey);
        $client->setParameterPost('to', $to);
        $client->setParameterPost('text', $content);


        $method   = \Zend_Http_Client::POST;
        $response = $client->request($method)->getBody();
        $response = json_decode($response, true);
        if (!isset($response['messages'][0]['status'])) {
            throw new \Exception('could not read response from nexmo');
        }
        return $response;
    }
}
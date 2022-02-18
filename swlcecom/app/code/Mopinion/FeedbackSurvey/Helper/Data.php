<?php
namespace Mopinion\FeedbackSurvey\Helper;

use Magento\Store\Model\Store;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Mopinion FeedbackSurvey data helper
 *
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_SCOPE = 'default';

    /**
     * Config paths for using throughout the code
     */
    const XML_PATH_ACTIVE = 'mopinion/feedbacksurvey/active';
    const XML_PATH_ORGANISATION_ID = 'mopinion/feedbacksurvey/id';
    const XML_PATH_MOPINIONKEY = 'mopinion/feedbacksurvey/deployment_key';
    const XML_PATH_ACCOUNT_CREATOR = 'mopinion/feedbacksurvey/account_creator';
    const XML_PATH_POSITION = 'mopinion/feedbacksurvey/position';

    /**
     * @param Context $context
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Mopinion\FeedbackSurvey\Model\Api $api
    ) {
        $this->_configWriter = $configWriter;
        $this->_storeManager = $storeManager;
        $this->_cacheManager = $cacheManager;
        $this->_scopeConfig = $scopeConfig;
        $this->api = $api;
        parent::__construct($context);
    }

    /**
     * Change
     *
     * @return $this
     */
    public function saveConfigData(array $configData)
    {
        foreach ($configData as $path => $value) {
            $this->_configWriter->save(
                $path,
                $value,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        // refresh cache config
        $this->_cacheManager->flush(['config']);
    }

    public function getStoreName()
    {
        $storeName = $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return !empty($storeName) ? $storeName : $this->_storeManager->getStore()->getName();
    }

    public function apiRequest(array $params)
    {
        $apiParams = (object) $params;
        $response = $this->api->post('/organisations', [
            'headers' => [],
            'body'    => [
                'organisation' => [
                    'name'=> $apiParams->siteName,
                    'country_code'=> $apiParams->country,

                ],
                'report' => [
                    'name'=> $apiParams->siteName,
                ],
                'user' => [
                    'firstname' => $apiParams->firstName,
                    'lastname' => $apiParams->lastName,
                    'password' => $apiParams->password,
                    'email' => $apiParams->email,
                ],
                'deployment' => [
                    'name' =>  $apiParams->siteName
                ],
            ],
            'query'   => ['direct_verify' => 1],
        ]);

        if (!$response) {
            throw new \Exception('UnexpectedError');
        }

        if (isset($response->error_code)) {
            $error = $response->title;
            if (property_exists($response, 'details')) {
                $error .= ' ('.implode(', ', $response->details).')';
            }
            throw new \Exception($error);
        }

        if ($response->_meta->code===201
            && !empty($response->organisation->id)
            && !empty($response->deployment->key)
        ) {
            $configParams = [
                self::XML_PATH_ORGANISATION_ID => $response->organisation->id,
                self::XML_PATH_MOPINIONKEY => $response->deployment->key,
                self::XML_PATH_ACCOUNT_CREATOR => $apiParams->email,
                self::XML_PATH_POSITION => 'mopinioninfooter'
            ];

            $this->saveConfigData($configParams);
            return true;
        }

        throw new \Exception('UnexpectedError');
    }

    /**
     * Whether mopinion is ready to use
     *
     * @return string
     */
    public function getOrganizationId()
    {
        $orgId = $this->_scopeConfig->getValue(
            self::XML_PATH_ORGANISATION_ID
        );
        return $orgId;
    }

    /**
     * Get mopinion key information
     *
     * @return string
     */
    public function getMopinionKey()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_MOPINIONKEY
        );
    }

    /**
     * Get mopinion script position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_POSITION
        );
    }

    /**
     * Get mopinion key information
     *
     * @return string
     */
    public function getMopinionAccountEmail()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_ACCOUNT_CREATOR
        );
    }
}

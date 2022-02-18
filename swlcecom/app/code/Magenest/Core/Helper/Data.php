<?php
/**
 * @author Magenest Team
 * @copyright Copyright (c) 2018 Magenest (https://www.magenest.com)
 * @package Magenest_Core
 */

namespace Magenest\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\CacheInterface;

class Data extends AbstractHelper
{
    const BASE_URL  = 'https://store.magenest.com/';
    const BASE_CHECKUPDATE_PATH = 'magenestcore/checkupdate';
    const BASE_CHECKNOTIFICATION_PATH = 'magenestcore/notification/get/module/';
    const CACHE_MODULE_IDENTIFIER = 'magenest_modules';
    const CACHE_TIME_IDENTIFIER = 'magenest_time';
    const SEC_DIFF = 86400;
    const CACHE_MODULE_NOTIFICATIONS_LASTCHECK = 'module_notifications_lastcheck';
    const XML_PATH_MAGENEST_CORE_NOTIFICATIONS_FREQUENCY = 'magenest_core/notifications/frequency';

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curlClient;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param CacheInterface $cache
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param StoreManagerInterface $storeManager
     * @param CurlFactory $curlFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        CacheInterface $cache,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        StoreManagerInterface $storeManager,
        CurlFactory $curlFactory,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->curlClient = $curl;
        $this->moduleList = $moduleList;
        $this->storeManager = $storeManager;
        $this->curlFactory = $curlFactory;
        $this->resource = $resource;
    }

    /**
     * Check update
     *
     * @return bool
     */
    public function checkUpdate()
    {
        $lastSt = $this->cache->load(self::CACHE_TIME_IDENTIFIER);
        if(!$lastSt){
            $this->cache->save(time(), self::CACHE_TIME_IDENTIFIER);
            return true;
        }
        if ((time() - intval($lastSt)) > self::SEC_DIFF){
            $this->cache->save(time(), self::CACHE_TIME_IDENTIFIER);
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get modules
     *
     * @return mixed
     */
    public function getModules()
    {
        $data = $this->cache->load(self::CACHE_MODULE_IDENTIFIER);
        if (!$data) {
            $data = $this->refreshModuleData();
        }
        $result = json_decode($data, true);

        return $result;
    }

    /**
     * Get curl client
     *
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function getCurlClient()
    {
        if (!$this->curlClient) {
            $this->curlClient = new \Magento\Framework\HTTP\Client\Curl();
        }
        $this->curlClient->setTimeout(2);
        return $this->curlClient;
    }

    /**
     * Get update url
     *
     * @return string
     */
    public static function getUpdateUrl(){
        return self::BASE_URL . self::BASE_CHECKUPDATE_PATH;
    }

    /**
     * Get module notification update url
     *
     * @return string
     */
    public static function getUpdateNotificationUrl(){
        return self::BASE_URL . self::BASE_CHECKNOTIFICATION_PATH;
    }

    /**
     * Refresh module data
     *
     * @return false|string
     */
    private function refreshModuleData()
    {
        $moduleInfo = $this->getModulesInfo();
        if($moduleInfo){
            $this->cache->save(json_encode($moduleInfo), self::CACHE_MODULE_IDENTIFIER);
        }
        return json_encode($moduleInfo);
    }

    /**
     * Get module info
     *
     * @return array
     */
    private function getModulesInfo()
    {
        $modulesOut = [];

        $modules = $this->moduleList->getAll();
        foreach ($modules as $module) {
            $moduleName = @$module['name'];
            if (strstr($moduleName, 'Magenest_') === false
                || $moduleName === 'Magenest_Core'
            ) {
                continue;
            }

            $modulePart = explode("_", $moduleName);
            $mName = @$modulePart[1];
            $modulesOut[] = $mName;
        }

        if (!empty($modulesOut)) {
            sort($modulesOut);
        }

        return $modulesOut;
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->cache->save(time(), self::CACHE_MODULE_NOTIFICATIONS_LASTCHECK);
        return $this;
    }

    /**
     * Get configuration setting value
     *
     * @param $path
     * @return mixed
     */
    public function getConfigValue($path) {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int|mixed
     */
    public function getFrequency()
    {
        return 24*3600;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->cache->load(self::CACHE_MODULE_NOTIFICATIONS_LASTCHECK);
    }

    /**
     * Check notification update
     *
     * @return $this
     */
    public function checkNotificationUpdate()
    {
        $modules = $this->getModules();
        $param = implode('-', $modules);

        $curl = $this->curlFactory->create();
        $curl->setConfig(['timeout'=> 2]);
        $curl->write(\Zend_Http_Client::GET, Data::getUpdateNotificationUrl() . $param);
        $data = $curl->read();

        if ($data !== false) {
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
            $curl->close();

            $data = json_decode($data, true);

            $count = count($data);
            if ($count) {
                foreach ($data as $value) {
                    $this->addNotification($value['severity'], $value['created_at'], $value['title'], $value['description'], $value['url']);
                }
            }
        }

        return $this;
    }

    /**
     * @param null $severity
     * @return array|mixed|null
     */
    public function getSeverities($severity = null)
    {
        $severities = [
            MessageInterface::SEVERITY_CRITICAL => __('critical'),
            MessageInterface::SEVERITY_MAJOR => __('major'),
            MessageInterface::SEVERITY_MINOR => __('minor'),
            MessageInterface::SEVERITY_NOTICE => __('notice'),
        ];

        if ($severity !== null) {
            if (isset($severities[$severity])) {
                return $severities[$severity];
            }
            return null;
        }

        return $severities;
    }

    /**
     * Save notifications (if not exists)
     *
     * @param $data
     */
    public function parse($data)
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $table = $this->resource->getTableName('adminnotification_inbox');

        foreach ($data as $item) {
            $select = $connection->select()->from($table)->where('title = ?', $item['title']);

            if (empty($item['url'])) {
                $select->where('url IS NULL');
            } else {
                $select->where('url = ?', $item['url']);
            }

            $row = $connection->fetchRow($select);

            if (!$row) {
                $connection->insert($table, $item);
            }
        }
    }

    /**
     * Add new message
     *
     * @param $severity
     * @param $date
     * @param $title
     * @param $description
     * @param string $url
     * @return $this
     */
    public function addNotification($severity, $date, $title, $description, $url = '')
    {
        if (!$this->getSeverities($severity)) {
            return $this;
        }
        if (is_array($description)) {
            $description = '<ul><li>' . implode('</li><li>', $description) . '</li></ul>';
        }
        $this->parse(
            [
                [
                    'severity' => $severity,
                    'date_added' => $date,
                    'title' => $title,
                    'description' => $description,
                    'url' => $url,
                ],
            ]
        );
        return $this;
    }

}

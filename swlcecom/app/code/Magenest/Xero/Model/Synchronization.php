<?php
namespace Magenest\Xero\Model;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditCollection;
/**
 * Class Synchronization
 * @package Magenest\Xero\Model
 */
abstract class Synchronization
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $syncType;

    /**
     * @var
     */
    protected $syncIdKey;

    /**
     * @var
     */
    protected $syncTypeKey;
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $xml;

    /**
     * @var string
     */
    protected $response = '';

    /**
     * @var XeroClient
     */
    protected $xeroClient;

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @var RequestLogFactory
     */
    protected $requestLogFactory;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    protected $id;

    protected $helper;

    /**
     * Synchronization constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param RequestLogFactory $requestLogFactory
     * @param QueueFactory $queueFactory
     * @param Helper $helper
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        RequestLogFactory $requestLogFactory,
        QueueFactory $queueFactory,
        Helper $helper
    ) {
        $this->requestLogFactory = $requestLogFactory;
        $this->queueFactory = $queueFactory;
        $this->xeroClient = $xeroClient;
        $this->logFactory = $logFactory;
        $this->helper = $helper;
    }

    public function getClient()
    {
        return $this->xeroClient;
    }

    /**
     * @param $id
     * @return string
     */
    abstract public function addRecord($id);

    /**
     * Sync by Cron Job
     * @param $cron bool
     */
    public function syncCronJobMode($cron = false)
    {
        $queueCollection = $this->queueFactory->create()
            ->getCollection()
            ->addFieldToFilter('type', $this->type);
        $queueCollection->setPageSize($this->limit);
        $pages = $queueCollection->getLastPageNumber();
        for ($page = 1; $page <= $pages; $page++) {
            $queueCollection->clear();
            $queueCollection->setCurPage(1);
            $queueCollection->load();
            $queueModel = $this->queueFactory->create();
            $connection = $queueModel->getResource()->getConnection();

            $ids = $queueCollection->getAllItemsIds();

            if (!empty($ids)) {
                if (!$this->helper->isXeroConnectedByIds($ids, $this->collectionFactory, $this->id)) {
                    return false;
                }
                if ($this->helper->isMultipleWebsiteEnable()) {
                    $this->addRecordsForMultipleWebsite($ids, $connection, $queueModel);
                } else {
                    $this->addRecords($ids, $connection, $queueModel);
                }
            }
            if ($cron == true) {
                return true;
            }
        }
        return true;
    }

    public function addRecordsForMultipleWebsite($ids, $connection, $queueModel)
    {
        $xmlArray = [];
        $additionalXmlArray = [];
        $count = 0;
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter($this->id, ["IN" => $ids]);
        $length = $collection->count();
        $deleteIds = [];
        $this->setWebsiteId($collection);

        if ($this->syncType == "Invoice" || $this->syncType == "CreditNote") {
            foreach ($collection as $model) {
                $count += 1;
                $this->guestToXml($model);
                if ($count % 250 == 0 || $count == $length) {
                    $this->syncAllGuestToXero();
                }
            }
        }

        $count = 0;
        foreach ($collection as $model) {
            $length = $length + count($model->getWebsiteIds()) - 1;
            foreach ($model->getWebsiteIds() as $websiteId) {
                if (!isset($xmlArray[$websiteId]))
                    $xmlArray[$websiteId] = '';
                if (!isset($additionalXmlArray[$websiteId]))
                    $additionalXmlArray[$websiteId] = '';
                $this->setWebsiteScopeConfig($websiteId);
                $tmp = $this->addRecord($model);
                if ($tmp == 'payment') {
                    $tmp = '';
                }
                $count += 1;
                $xmlArray[$websiteId] .= $tmp;
                $additionalXmlArray[$websiteId] .= $this->_additional($model);

                if ($this->syncType == "Invoice" || $this->syncType == "CreditNote") {
                    $deleteIds[] = $model->getIncrementId();
                } else {
                    $deleteIds[] = $model->getEntityId();
                }

                if ($count % 250 == 0 || $count == $length) {
                    foreach($xmlArray as $id => $xml) {
                        $this->setWebsiteScopeConfig($id);
                        if ($xml != '') {
                            $xml = '<' . $this->syncType . 's>' . $xml . '</' . $this->syncType . 's>';
                            $this->syncData($xml);
                        }
                        $this->_additionalSync($additionalXmlArray[$id]);
                    }

                    $additionalXmlArray = [];
                    $xmlArray = [];
                    $idsString = $connection->quoteInto('entity_id IN (?)', $deleteIds);
                    $typeString = $connection->quoteInto('type = ?', $this->type);
                    $connection->delete($queueModel->getResource()->getMainTable(), "{$idsString} AND {$typeString}");
                    $deleteIds = [];
                }
            }
        }
    }

    public function addRecords($ids, $connection, $queueModel)
    {
        $xml = '';
        $additionalXml = '';
        $count = 0;
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter($this->id, ["IN" => $ids]);
        $length = $collection->count();
        $deleteIds = [];

        if ($this->syncType == "Invoice") {
            foreach ($collection as $model) {
                $count += 1;
                $this->guestToXml($model);
                if ($count % 250 == 0 || $count == $length) {
                    $this->syncAllGuestToXero();
                }
            }
        }

        $count = 0;
        foreach ($collection as $model) {
            $count += 1;
            $tmp = $this->addRecord($model);
            if ($tmp == 'payment') {
                $tmp = '';
            }
            $xml .= $tmp;
            $additionalXml .= $this->_additional($model);
            if ($this->syncType == "Invoice" || $this->syncType == "CreditNote") {
                $deleteIds[] = $model->getIncrementId();
            } else {
                $deleteIds[] = $model->getEntityId();
            }
            if ($count % 250 == 0 || $count == $length) {
                if ($xml != '') {
                    $xml = '<' . $this->syncType . 's>' . $xml . '</' . $this->syncType . 's>';
                    $this->syncData($xml);
                }
                $this->_additionalSync($additionalXml);
                $additionalXml = '';
                $xml = '';
                $idsString = $connection->quoteInto('entity_id IN (?)', $deleteIds);
                $typeString = $connection->quoteInto('type = ?', $this->type);
                $connection->delete($queueModel->getResource()->getMainTable(), "{$idsString} AND {$typeString}");
                $deleteIds = [];
            }
        }
    }

    /**
     * May be override by Child Class
     *
     * @param $entityId
     * @return string
     */
    protected function _additional($entityId)
    {
        return '';
    }

    /**
     * May be override by Child Class
     *
     * @param string $additionalXml
     */
    protected function _additionalSync($additionalXml)
    {
    }

    /**
     * @param $xml
     * @param string $method
     * @return string
     * @throws \Exception
     */
    public function syncData($xml, $method = 'POST')
    {
        if ($xml == '') {
            return '';
        }
        try {
            $xml = trim($xml);
            $xml = str_replace('&', ' and ', $xml);
            $helper = $this->xeroClient->getSignature();
            $params['xml'] = $this->safeEncode($xml);

            $method = strtoupper($method);
            if ($method == 'POST') {
                $helper->setParamsForSyncing($params);
            } else {
                $helper->setParamsForSyncing();
            }
            $helper->setMethod($method);
            $helper->setUri($this->syncType.'s');
            $url = $helper->getUri() . '?' . $helper->sign();

            $this->response = $this->xeroClient->sendRequest($url, $method, $params);
            $this->logResponse($this->syncType, $this->type, $this->parseXML($this->response));
            $this->addRequest();
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug($e->getMessage());
            throw $e;
        }

        return $this->response;
    }

    /**
     * Encode xml string
     *
     * @param $data
     * @return string
     */
    protected function safeEncode($data)
    {
        if (is_array($data)) {
            return array_map([
                $this,
                'safe_encode'
            ], $data);
        } elseif (is_scalar($data)) {
            return str_ireplace([
                '+',
                '%7E'
            ], [
                ' ',
                '~'
            ], rawurlencode($data));
        } else {
            return '';
        }
    }

    /**
     * Parse XML string to an Array
     *
     * @param $xml
     * @return array
     */
    public function parseXML($xml)
    {
        $parser = new Parser();
        $result = $parser->parseXML($xml);
        
        return $result;
    }

    /**
     * @param string $type
     * @param array $response
     */
    protected function logResponse($syncType, $type, $response)
    {
        $log = $this->logFactory->create();
        $log->logResponse($syncType, $type, $response);
    }

    /**
     * add request
     */
    protected function addRequest()
    {
        $requestModel = $this->requestLogFactory->create();
        $request = $requestModel->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('date', date('Y-m-d'))
            ->getLastItem();
        if (!$request->getId()) {
            $requestModel->setData([
                'date' => date('Y-m-d'),
                'request' => 1
            ]);
            $requestModel->save();
        } else {
            $requestCount = $request->getData('request') + 1;
            $request->setData('request', $requestCount);
            $request->save();
        }
    }

    public function getSyncIdKey()
    {
        return $this->syncIdKey;
    }

    public function getSyncTypeKey()
    {
        return $this->syncTypeKey;
    }

    public function getType()
    {
        return $this->syncType;
    }

    protected function setCustomerWebsiteIds($collection) {
        foreach ($collection as $model) {
            $model->setWebsiteIds(array($model->getWebsiteId()));
        }
    }

    protected function setOrderWebsiteIds($collection) {
        foreach ($collection as $model) {
            $model->setWebsiteIds(array($model->getStore()->getWebsiteId()));
        }
    }

    protected function setWebsiteId($collection) {
        if ($collection instanceof CustomerCollection) {
            $this->setCustomerWebsiteIds($collection);
        } else if ($collection instanceof OrderCollection
            || $collection instanceof InvoiceCollection
            || $collection instanceof CreditCollection
        ) {
            $this->setOrderWebsiteIds($collection);
        }
    }

    protected function setWebsiteScopeConfig($websiteId)
    {
        if ($this->helper->isMultipleWebsiteEnable()) {
            $this->helper->setScope('websites');
            $this->helper->setScopeId($websiteId);
        }
    }

    public function getProduct($item)
    {
        $product = $item->getProduct();
        if (!$product) {
            $product = $this->helper->createProduct($item);
        }
        return $product;
    }
}

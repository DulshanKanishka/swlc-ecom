<?php

namespace Dulshan\QuickBooksOnline\Model;

class Client extends \Magenest\QuickBooksOnline\Model\Client
{

    public function sendRequest($method, $path, $params = [])
    {
        $url       = $this->getRequestUrl($path);
        if ($path == 'invoice' || $path == 'creditmemo') {
            $url .= '?minorversion=1';
        }
        $companyId = $this->scopeConfig->getValue('qbonline/connection/company_id');
        $model     = $this->oauthModel->getCollection()
            ->addFieldToFilter('qb_realm', $companyId)
            ->getLastItem();

        $client = $this->getZendClient()->setUri($url);
        $header = [
            "Authorization: Bearer " . trim($model->getOauthAccessToken()),
            "Content-type: application/json",
            "Accept: application/json",
        ];
        $client->setHeaders($header);
        $client->setConfig(['timeout' => 300]);

        if (!empty($params)) {
            $dataBody = json_encode($params);
            if ($path == 'taxservice/taxcode') {
                $string1 = str_replace('"TaxRateDetails":', '"TaxRateDetails":[', $dataBody);
                $string2 = str_replace('"Sales"}', '"Sales"}]', $string1);
                $client->setRawData($string2);
            } else {
                $client->setRawData($dataBody);
            }
        }

        if ($this->isDebugEnabled()) {
            $this->log($url, 'REQUEST URL');
            if (!empty($params)) {
                $this->log($params, 'REQUEST PARAMETERS');
            }
        }
        $response      = $client->request($method)->getBody();
        $responseArray = json_decode($response, true);
        if ($client->getLastResponse()->getStatus() >= 400) {
            $errorMsg = __($response);
            if (isset($responseArray['Fault']['Error'][0]['Detail'])) {
                $errorMsg = __($responseArray['Fault']['Error'][0]['Detail']);
            }
            throw new LocalizedException($errorMsg);
        }

        if ($this->isDebugEnabled()) {
            $this->log($responseArray, 'RESPONSE');
        }

        return $responseArray;
    }

    private function log($data, $title = '')
    {
        if ($data instanceof \Magento\Framework\DataObject) {
            $data = $data->getData();
        }
        if (is_array($data)) {
            $data = print_r($data, true);
        }
        if (is_string($data)) {
            $this->_logger->debug("{$title}: {$data}");
        }
    }

}

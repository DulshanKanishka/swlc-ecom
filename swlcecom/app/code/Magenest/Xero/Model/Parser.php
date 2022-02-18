<?php
namespace Magenest\Xero\Model;

/**
 * Class Parser
 * @package Magenest\Xero\Model
 */
class Parser
{

    /**
     * Parse XML string to an Array
     *
     * @param $xml
     * @return array|bool
     */
    public static function parseXML($xml)
    {
        try {
            $parser = new \Magento\Framework\Xml\Parser();
            $parser->loadXML($xml);
            $parsedXml = $parser->xmlToArray();
            if ($parsedXml) {
                if (isset($parsedXml['Response'])) {
                    $parsedXml = $parsedXml['Response'];
                }
                if (isset($parsedXml['ApiException'])) {
                    $parsedXml = $parsedXml['ApiException'];
                }

                return $parsedXml;
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug($e->getMessage());
        }

        return false;
    }
}

<?php
namespace Magenest\Xero\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
/**
 * Class Queue
 * @package Magenest\Xero\Model
 */
class CoreConfig extends \Magento\Framework\Model\AbstractModel
{
    /**
     *  Init
     */
    protected function _construct()
    {
        $this->_init('Magenest\Xero\Model\ResourceModel\CoreConfig');
    }

    /**
     * @param $path
     * @param $scope
     * @param $scopeId
     * @return null | int
     */
    public function getConfigValueByScope($path, $scope, $scopeId)
    {
        if ($scope != ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            && $scope != ScopeInterface::SCOPE_STORES
            && $scope != ScopeInterface::SCOPE_WEBSITES
        ) {
            return null;
        }
        $collection = $this->getCollection()
            ->addFieldToFilter('scope', $scope)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('path', $path);
        return $collection->getFirstItem()->getValue() ? : 0;
    }
}

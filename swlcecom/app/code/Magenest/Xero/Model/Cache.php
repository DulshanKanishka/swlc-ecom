<?php
namespace Magenest\Xero\Model;

class Cache
{
    public static function refreshCache()
    {
        $_cacheTypeList = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\Framework\App\Cache\TypeListInterface::class);
        $_cacheFrontendPool = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\Framework\App\Cache\Frontend\Pool::class);
        $types = ['config','full_page'];
        foreach ($types as $type) {
            $_cacheTypeList->cleanType($type);
        }
        foreach ($_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}

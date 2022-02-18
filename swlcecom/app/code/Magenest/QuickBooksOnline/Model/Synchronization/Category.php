<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Synchronization;

/**
 * Class Category
 *
 * @package Magenest\QuickBooksOnline\Model\Synchronization
 */
class Category extends Synchronization
{
    /**
     * @return bool|array
     * @throws \Exception
     */
    public function sync($params)
    {
        $response = $this->sendRequest(\Zend_Http_Client::POST, 'item?minorversion=5', $params);
        
        return $response;
    }
}

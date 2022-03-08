<?php

namespace Dulshan\Custom;

class DataObject extends \Magento\Framework\DataObject
{
    public function hasData($key = '')
    {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }
        return isset($this->_data[$key]);
    }
}

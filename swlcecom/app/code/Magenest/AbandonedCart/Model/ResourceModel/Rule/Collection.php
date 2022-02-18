<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\Rule;

use Magento\Framework\DB\Select;
use Magenest\AbandonedCart\Model\Config\Source\Mail;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName    = 'id';

    protected $isPrepareSelect = false;

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\Rule', 'Magenest\AbandonedCart\Model\ResourceModel\Rule');
    }
}
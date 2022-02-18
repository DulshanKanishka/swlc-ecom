<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class PaymentMapping
 * @package Magenest\Xero\Model\ResourceModel
 */
class PaymentMapping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_xero_payment_account_mapping', 'id');
    }
}

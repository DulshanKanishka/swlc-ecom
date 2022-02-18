<?php
namespace Magenest\Xero\Model\Config\Account;

/**
 * Class BankAccount
 * @package Magenest\Xero\Model\Config\Account
 */
class BankAccount extends XeroAccount
{
    protected $types = ['BANK'];

    public function toOptionArray()
    {
        if (!is_array(self::$accounts)) {
            return $this->_options;
        }
        foreach (self::$accounts as $account) {
            if (isset($account['Type']) && in_array($account['Type'], $this->types)) {
                $value = $this->useCode ? $account['Code'] : $account['AccountID'];
                $name = $account['Name'];
                $code = isset($account['Code']) ? '['.$account['Code'].']' : '';
                $this->_options[$value] = $name.$code;
            }
        }
        return $this->_options;
    }
}

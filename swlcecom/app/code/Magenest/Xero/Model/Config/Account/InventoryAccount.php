<?php
namespace Magenest\Xero\Model\Config\Account;

/**
 * Class InventoryAccount
 * @package Magenest\Xero\Model\Config\Account
 */
class InventoryAccount extends XeroAccount
{
    protected $types = ['INVENTORY'];

    protected $useCode = true;
}

<?php
namespace Magenest\Xero\Model\Config\Account;

/**
 * Class COGSAccount
 * @package Magenest\Xero\Model\Config\Account
 */
class COGSAccount extends XeroAccount
{
    protected $types = ['DIRECTCOSTS'];

    protected $useCode = true;
}

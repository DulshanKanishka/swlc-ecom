<?php
/**
 * Copyright © 2015 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Magenest_Xero extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package  Magenest_Xero
 * @author   ThaoPV
 */
namespace Magenest\Xero\Helper\Signature;

use Magenest\Xero\Helper\Signature;

/**
 * Class Plaintext
 * @package Magenest\Xero\Helper\Signature
 */
class Plaintext
{
    /**
     * @var Signature
     */
    protected $signature;

    /**
     * @param Signature $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function signature()
    {
        //TODO
    }
}

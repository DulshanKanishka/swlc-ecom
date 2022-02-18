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
 * Class Hmac
 * @package Magenest\Xero\Helper\Signature
 */
class Hmac
{
    /**
     * @var Signature
     */
    protected $signature;

    protected $sharedSecret = null;

    protected $oauthSecret = null;

    protected $action = 'GET';

    protected $sbs;

    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    public function setSharedSecret($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;
    }

    public function setOauthSecret($oauthSecret)
    {
        $this->oauthSecret = $oauthSecret;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function signature($sign = '')
    {
        $secretKey = '';
        if ($this->sharedSecret != null) {
            $secretKey = $this->_oauthEscape($this->sharedSecret);
        }
        $secretKey .= '&';
        if ($this->oauthSecret != null) {
            $secretKey .= $this->_oauthEscape($this->oauthSecret);
        }

        $this->sbs = $sign;

        return base64_encode(hash_hmac('sha1', $this->sbs, $secretKey, true));
    }

    protected function _oauthEscape($string)
    {
        if ($string === 0) {
            return 0;
        }
        if (empty($string)) {
            return '';
        }
        if (is_array($string)) {
            throw new \Exception('Array passed to _oauthEscape');
        }
        $string = rawurlencode($string);
        $string = str_replace('+', '%20', $string);
        $string = str_replace('!', '%21', $string);
        $string = str_replace('*', '%2A', $string);
        $string = str_replace('\'', '%27', $string);
        $string = str_replace('(', '%28', $string);
        $string = str_replace(')', '%29', $string);
        return $string;
    }
}

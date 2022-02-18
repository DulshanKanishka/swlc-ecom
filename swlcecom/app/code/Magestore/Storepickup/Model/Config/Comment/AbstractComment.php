<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Model\Config\Comment;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
abstract class AbstractComment implements \Magento\Config\Model\Config\CommentInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Google constructor.
     *
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(\Magento\Framework\UrlInterface $url)
    {
        $this->_url = $url;
    }

    /**
     * Retrieve element comment by element value.
     *
     * @param string $elementValue
     *
     * @return string
     */
    abstract public function getCommentText($elementValue);
}

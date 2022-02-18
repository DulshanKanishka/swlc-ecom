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
class Google extends \Magestore\Storepickup\Model\Config\Comment\AbstractComment
{
    /**
     * Retrieve element comment by element value.
     *
     * @param string $elementValue
     *
     * @return string
     */
    public function getCommentText($elementValue)
    {
        return __(
            'To register a Google Map API key, please follow the guide <a href="%1">here</a>',
            $this->_url->getUrl('storepickupadmin/guide')
        );
    }
}

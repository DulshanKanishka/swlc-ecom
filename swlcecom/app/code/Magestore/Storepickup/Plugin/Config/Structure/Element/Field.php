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

namespace Magestore\Storepickup\Plugin\Config\Structure\Element;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Field
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * mapping path for field comment.
     *
     * @var array
     */
    protected $_mapPathFieldComments = [
        'storepickup/service/google_api_key' => 'Magestore\Storepickup\Model\Config\Comment\Google',
        'storepickup/service/facebook_api_key' => 'Magestore\Storepickup\Model\Config\Comment\Facebook',
    ];

    /**
     * Field constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Retrieve comment.
     *
     * @param string $currentValue
     *
     * @return string
     */
    public function aroundGetComment(
        \Magento\Config\Model\Config\Structure\Element\Field $field,
        \Closure $proceed,
        $currentValue = ''
    ) {
        if (isset($this->_mapPathFieldComments[$field->getPath()])) {
            /** @var \Magento\Config\Model\Config\CommentInterface $commentModel */
            $commentModel = $this->_objectManager->create($this->_mapPathFieldComments[$field->getPath()]);

            return $commentModel->getCommentText($currentValue);
        }

        return $proceed($currentValue);
    }
}

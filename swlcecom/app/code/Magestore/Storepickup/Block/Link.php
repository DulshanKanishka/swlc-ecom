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

namespace Magestore\Storepickup\Block;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Link extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \Magestore\Storepickup\Model\SystemConfig
     */
    protected $_systemConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Url                      $customerUrl
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magestore\Storepickup\Model\SystemConfig $systemConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_systemConfig = $systemConfig;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $check = ($this->_systemConfig->isEnableFrontend() && $this->_systemConfig->isShowTopLink());

        return $check ? parent::_toHtml() : '';
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('storepickup/index/index');
    }
}

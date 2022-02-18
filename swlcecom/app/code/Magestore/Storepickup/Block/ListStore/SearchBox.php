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

namespace Magestore\Storepickup\Block\ListStore;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class SearchBox extends \Magestore\Storepickup\Block\AbstractBlock
{
    protected $_template = 'Magestore_Storepickup::liststore/searchbox.phtml';

    /**
     * @var \Magento\Config\Model\Config\Source\Locale\Country
     */
    protected $_localCountry;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magestore\Storepickup\Block\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Config\Model\Config\Source\Locale\Country $localCountry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_directoryHelper = $directoryHelper;
        $this->_localCountry = $localCountry;
    }

    /**
     * @return string
     */
    public function getRegionJson()
    {
        return $this->_directoryHelper->getRegionJson();
    }

    /**
     * get tag icon.
     *
     * @param \Magestore\Storepickup\Model\Tag $tag
     *
     * @return string
     */
    public function getTagIcon(\Magestore\Storepickup\Model\Tag $tag)
    {
        return $tag->getTagIcon() ? $this->_imageHelper->getMediaUrlImage($tag->getTagIcon())
        : $this->getViewFileUrl('Magestore_Storepickup::images/Hospital_icon.png');
    }

    /**
     * @param \Magestore\Storepickup\Model\Tag $tag
     *
     * @return string
     */
    public function getTagHtml(\Magestore\Storepickup\Model\Tag $tag)
    {
        $tagFormat = '<li data-tag-id="%s" class="tag-icon icon-filter text-center">';
        $tagFormat .= '<img src="%s" class="img-responsive"/><p>%s</p></li>';

        return sprintf($tagFormat, $tag->getId(), $this->getTagIcon($tag), $tag->getTagName());
    }

    /**
     * @return array
     */
    public function getCountryOption()
    {
        return $this->_localCountry->toOptionArray();
    }
}

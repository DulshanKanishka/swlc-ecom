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

namespace Magestore\Storepickup\Model;

use Magento\Cms\Api\Data\PageInterface;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class StoreUrlPathGenerator implements StoreUrlPathGeneratorInterface
{
    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $_filterManager;

    /**
     * StoreUrlPathGenerator constructor.
     *
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     */
    public function __construct(
        \Magento\Framework\Filter\FilterManager $filterManager
    ) {
        $this->_filterManager = $filterManager;
    }

    /**
     * @param \Magestore\Storepickup\Model\Store $store
     *
     * @return string
     */
    public function getUrlPath(\Magestore\Storepickup\Model\Store $store)
    {
        $urlKey = $store->getRewriteRequestPath();

        return $urlKey === '' || $urlKey === null ? $store->getStoreName() : $urlKey;
    }

    /**
     * Get canonical store url path.
     *
     * @param \Magestore\Storepickup\Model\Store $store
     *
     * @return string
     */
    public function getCanonicalUrlPath(\Magestore\Storepickup\Model\Store $store)
    {
        return 'storepickup/index/view/storepickup_id/' . $store->getId();
    }

    /**
     * Generate store view page url key based on rewrite_request_path entered by merchant or store name.
     *
     * @param PageInterface $store
     * @return string
     * @api
     */
    public function generateUrlKey(\Magestore\Storepickup\Model\Store $store)
    {
        return $this->_filterManager->translitUrl(
            $this->getUrlPath($store)
        );
    }
}

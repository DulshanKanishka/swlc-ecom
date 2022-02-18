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


interface StoreUrlPathGeneratorInterface
{
    /**
     * @param \Magestore\Storepickup\Model\Store $store
     *
     * @return string
     */
    public function getUrlPath(\Magestore\Storepickup\Model\Store $store);

    /**
     * Get canonical store url path.
     *
     * @param \Magestore\Storepickup\Model\Store $store
     *
     * @return string
     */
    public function getCanonicalUrlPath(\Magestore\Storepickup\Model\Store $store);

    /**
     * Generate store view page url key based on rewrite_request_path entered by merchant or store name
     *
     * @param \Magestore\Storepickup\Model\Store $store
     * @return string
     * @api
     */
    public function generateUrlKey(\Magestore\Storepickup\Model\Store $store);
}
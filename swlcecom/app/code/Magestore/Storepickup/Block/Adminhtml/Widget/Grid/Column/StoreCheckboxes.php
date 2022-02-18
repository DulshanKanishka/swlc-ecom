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
 *
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
namespace Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column;

use Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column\AbstractCheckboxes;

/**
 * @category Magestore
 * @module   Storepickup
 *
 * @author   Magestore Developer
 */
class StoreCheckboxes extends AbstractCheckboxes
{
    /**
     * {@inheritdoc}
     */
    public function getSelectedValues()
    {
        return $this->_storepickupHelper->getTreeSelectedStores();
    }
}

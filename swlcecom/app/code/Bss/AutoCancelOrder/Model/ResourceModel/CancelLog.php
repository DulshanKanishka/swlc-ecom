<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Model\ResourceModel;
 
class CancelLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Init model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bss_cancel_order_log', 'log_id');
    }
}

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
namespace Bss\AutoCancelOrder\Model\ResourceModel\CancelOrderStatusByState;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Status\Collection
{
    /**
     * Get order status by order state
     *
     * @param string $states
     * @return $this
     */
    public function getStatusByState($states)
    {
        $sqlCondition = 'state_table.state=';
        
        if (is_array($states)) {
            foreach ($states as $state) {
                $sqlCondition = $sqlCondition.'"'.$state.'"';
                $sqlCondition = $sqlCondition.' OR state_table.state=';
            }
            $sqlCondition = rtrim($sqlCondition, " OR state_table.state=");
            $this->joinStates();
            $this->getSelect()->where($sqlCondition);
        }
        return $this;
    }
}

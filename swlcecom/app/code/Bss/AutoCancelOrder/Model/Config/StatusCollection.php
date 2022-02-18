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
namespace Bss\AutoCancelOrder\Model\Config;

class StatusCollection implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $_options;

    /**
     * @var \Bss\AutoCancelOrder\Model\ResourceModel\CancelOrderStatusByState\Collection
     */
    protected $statusCollection;

    /**
     * StatusCollection constructor.
     *
     * @param \Bss\AutoCancelOrder\Model\ResourceModel\CancelOrderStatusByState\Collection $statusCollection
     */
    public function __construct(
        \Bss\AutoCancelOrder\Model\ResourceModel\CancelOrderStatusByState\Collection $statusCollection
    ) {
        $this->statusCollection = $statusCollection;
    }

    /**
     * Statuses to array format
     *
     * @return []
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = $this->statusCollection->getStatusByState(['new', 'pending_payment'])
                                                        ->toOptionArray();
        }

        return $this->_options;
    }
}

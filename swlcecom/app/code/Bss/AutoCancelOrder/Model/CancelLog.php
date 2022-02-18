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
namespace Bss\AutoCancelOrder\Model;

use Bss\AutoCancelOrder\Api\Data\CancelLogInterface;
 
class CancelLog extends \Magento\Framework\Model\AbstractModel implements CancelLogInterface
{

    /**
     * Init model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bss\AutoCancelOrder\Model\ResourceModel\CancelLog::class);
    }

    /**
     * Get log id
     *
     * @return string
     */
    public function getId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * Get log content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CANCEL_ORDER_CONTENT);
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set log id
     *
     * @param int|string $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::LOG_ID, $id);
    }

    /**
     * Set log content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setData(self::CANCEL_ORDER_CONTENT, $content);
    }

    /**
     * Set log created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}

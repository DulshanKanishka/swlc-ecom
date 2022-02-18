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
namespace Bss\AutoCancelOrder\Api\Data;

interface CancelLogInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const LOG_ID = 'log_id';
    const CANCEL_ORDER_CONTENT = 'cancel_order_content';
    const CREATED_AT = 'created_at';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set ID
     *
     * @param int $id
     * @return CancelLogInterface
     */
    public function setId($id);

    /**
     * Set content
     *
     * @param string $content
     * @return CancelLogInterface
     */
    public function setContent($content);

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return CancelLogInterface
     */
    public function setCreatedAt($createdAt);
}

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
namespace Bss\AutoCancelOrder\Api;

interface CancelLogRepositoryInterface
{
    /**
     * Save log.
     *
     * @param \Bss\AutoCancelOrder\Api\Data\CancelLogInterface $log
     * @return \Bss\AutoCancelOrder\Api\Data\CancelLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\CancelLogInterface $log);

    /**
     * Retrieve log.
     *
     * @param int $logId
     * @return \Bss\AutoCancelOrder\Api\Data\CancelLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($logId);

    /**
     * Delete log.
     *
     * @param \Bss\AutoCancelOrder\Api\Data\CancelLogInterface $log
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\CancelLogInterface $log);

    /**
     * Delete log by ID.
     *
     * @param int $logId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($logId);
}

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

use Bss\AutoCancelOrder\Api\Data;
use Bss\AutoCancelOrder\Api\CancelLogRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Bss\AutoCancelOrder\Model\ResourceModel\CancelLog as ResourceCancelLog;

class CancelLogRepository implements CancelLogRepositoryInterface
{
    /**
     * @var ResourceCancelLog
     */
    protected $resource;

    /**
     * @var CancelLogFactory
     */
    protected $cancelLogFactory;

    /**
     * Initialize dependencies.
     *
     * @param ResourceCancelLog $resource
     * @param CancelLogFactory $cancelLogFactory
     */
    public function __construct(
        ResourceCancelLog $resource,
        CancelLogFactory $cancelLogFactory
    ) {
        $this->resource = $resource;
        $this->cancelLogFactory = $cancelLogFactory;
    }

    /**
     * Save CancelLog data
     *
     * @param \Bss\AutoCancelOrder\Api\Data\CancelLogInterface $cancelLog
     * @return CancelLog
     * @throws CouldNotSaveException
     */
    public function save(Data\CancelLogInterface $cancelLog)
    {
        try {
            $this->resource->save($cancelLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $cancelLog;
    }

    /**
     * Load CancelLog data by given CancelLog Identity
     *
     * @param string $cancelLogId
     * @return CancelLog
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($cancelLogId)
    {
        $cancelLog = $this->cancelLogFactory->create();
        $this->resource->load($cancelLog, $cancelLogId);
        if (!$cancelLog->getId()) {
            throw new NoSuchEntityException(__('CancelLog with id "%1" does not exist.', $cancelLogId));
        }
        return $cancelLog;
    }

    /**
     * Delete CancelLog
     *
     * @param \Bss\AutoCancelOrder\Api\Data\CancelLogInterface $cancelLog
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CancelLogInterface $cancelLog)
    {
        try {
            $this->resource->delete($cancelLog);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete CancelLog by given CancelLog Identity
     *
     * @param string $cancelLogId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($cancelLogId)
    {
        return $this->delete($this->getById($cancelLogId));
    }
}

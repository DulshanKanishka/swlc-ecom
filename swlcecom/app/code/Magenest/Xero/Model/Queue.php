<?php
namespace Magenest\Xero\Model;

/**
 * Class Queue
 * @package Magenest\Xero\Model
 */
class Queue extends \Magento\Framework\Model\AbstractModel
{
    /**
     *  Init
     */
    protected function _construct()
    {
        $this->_init('Magenest\Xero\Model\ResourceModel\Queue');
    }

    /**
     * @param $type
     * @param $entityId
     * @return bool
     */
    public function queueExisted($type, $entityId)
    {
        $existedQueue = $this->getCollection()
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('entity_id', $entityId)
            ->getFirstItem();
        if ($existedQueue->getId()) {
            /** existed in queue */
            $queue = $this->load($existedQueue->getId());
            $queue->setEnqueueTime(time());
            $queue->save();
            return true;
        }

        return false;
    }

    /**
     * @param $type
     * @param $entityId
     */
    public function enqueue($type, $entityId)
    {
        $data = [
            'type' => $type,
            'entity_id' => $entityId,
            'enqueue_time' => time(),
            'priority' => 1,
        ];
        $this->setData($data);
        $this->save();
    }
}

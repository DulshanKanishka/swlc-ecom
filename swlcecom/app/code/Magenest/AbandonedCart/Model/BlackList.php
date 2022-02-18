<?php

namespace Magenest\AbandonedCart\Model;

class BlackList extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\ResourceModel\BlackList');
    }

    public function insertMultiple($dataArr)
    {
        $this->getResource()->getConnection()->insertMultiple(
            $this->getResource()->getMainTable(),
            $dataArr
        );
    }

    public function deleteMultiple($dataArr)
    {
        $size = count($dataArr);
        if (!is_array($dataArr) && $size == 0) return;
        $collectionIds = implode(', ', $dataArr);
        $this->getResource()->getConnection()->delete(
            $this->getResource()->getMainTable(),
            "{$this->getIdFieldName()} in ({$collectionIds})"
        );
    }
}
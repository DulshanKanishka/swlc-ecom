<?php

namespace Magenest\AbandonedCart\Model;

class AbandonedCart extends \Magento\Framework\Model\AbstractModel
{

    // the cart is not abandoned
    const STATUS_NOT_ABANDONED = -1;

    // the cart is abandoned
    const  STATUS_ABANDONED = 0;

    // the cart i recovered after abandoned
    const  STATUS_RECOVERED = 1;

    // the cart is converted to order
    const  STATUS_CONVERTED = 2;

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart');
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
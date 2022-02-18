<?php
namespace Magenest\Xero\Model;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function getAllItemsIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $idsSelect->columns('entity_id', 'main_table');
        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}
<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Promotionsnotification;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Promotionsnotification', 'Magebees\Promotionsnotification\Model\ResourceModel\Promotionsnotification');
    }
    
    public function categoryFilter($categoryId)
    {
        $this->getSelect()->join(
            ['category' => $this->getTable('magebees_notification_category')],
            'main_table.notification_id = category.notification_id',
            'category.category_ids'
        )
            //->where('category.category_ids = ?', $categoryId);
            ->where('category.category_ids IN (0,?)', $categoryId);
        return $this;
    }
    
    public function productFilter($product_sku)
    {
        $this->getSelect()->join(
            ['product' => $this->getTable('magebees_notification_product')],
            'main_table.notification_id = product.notification_id',
            'product.product_sku'
        )
            ->where('product.product_sku = ?', $product_sku);
        return $this;
    }
    
    public function pageFilter($pageId)
    {
        $this->getSelect()->join(
            ['page' => $this->getTable('magebees_notification_page')],
            'main_table.notification_id = page.notification_id',
            'page.pages'
        )
            ->where('page.pages IN (0,?)', $pageId);
        return $this;
    }
    
    public function storeFilter($storeId)
    {
	    $this->getSelect()->join(
            ['store' => $this->getTable('magebees_notification_store')],
            'main_table.notification_id = store.notification_id',
            'store.store_ids'
        )
            ->where('store.store_ids IN (0,?)', $storeId);
        return $this;
    }
	
    public function customerFilter($customerId)
    {
        $this->getSelect()->join(
            ['customer' => $this->getTable('magebees_notification_customer')],
            'main_table.notification_id = customer.notification_id',
            'customer.customer_ids'
        )
            ->where('customer.customer_ids = ?', $customerId);
        return $this;
    }

    public function toOptionArray()
    {
        $result = array();
        $options = array(array('value' => '', 'label' => 'Please Select'));
        $result = array_merge($options,parent::_toOptionArray('unique_code', 'title'));
        return $result;
    }
}

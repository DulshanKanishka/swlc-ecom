<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel;

/**
 * Review resource model
 */
class Promotionsnotification extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_storeFactory;
    protected $_customerFactory;
    protected $_pageFactory;
    protected $_productFactory;
    protected $_categoryFactory;
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magebees\Promotionsnotification\Model\StoreFactory $storeFactory,
        \Magebees\Promotionsnotification\Model\CustomerFactory $customerFactory,
        \Magebees\Promotionsnotification\Model\PageFactory $pageFactory,
        \Magebees\Promotionsnotification\Model\ProductFactory $productFactory,
        \Magebees\Promotionsnotification\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($context);
        $this->_storeFactory = $storeFactory;
        $this->_customerFactory = $customerFactory;
        $this->_pageFactory = $pageFactory;
        $this->_productFactory = $productFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->request = $request;
    }
    
    /**
     * Define main table. Define other tables name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magebees_promotionsnotification', 'notification_id');
    }
    
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($this->request->getActionName()=='massStatus') {
            return $this;
        }
        $id = $object->getId();
        
        //save store ids
        $store_model = $this->_storeFactory->create();
        if ($id) {
            $store_data = $store_model->getCollection()
                            ->addFieldToFilter('notification_id', $id);
            $store_data->walk('delete');
        }
        
        foreach ($object->getStores() as $store) {
            $data_store['notification_id'] = $object->getId();
            $data_store['store_ids'] = $store;
            $store_model->setData($data_store);
            $store_model->save();
        }
        
        //save customer groups
        $customer_model = $this->_customerFactory->create();
        if ($id) {
            $cust_data = $customer_model->getCollection()
                            ->addFieldToFilter('notification_id', $id);
            $cust_data->walk('delete');
        }
        foreach ($object->getCustomerGroupIds() as $customer) {
            $data_customer['notification_id'] = $object->getId();
            $data_customer['customer_ids'] = $customer;
            $customer_model->setData($data_customer);
            $customer_model->save();
        }
        
        //save cms pages
        $page_model = $this->_pageFactory->create();
        if ($id) {
            $page_data = $page_model->getCollection()
                            ->addFieldToFilter('notification_id', $id);
            $page_data->walk('delete');
        }
        
        if ($object->getPages()) {
            foreach ($object->getPages() as $page) {
                    $data_page['notification_id'] = $object->getId();
                    $data_page['pages'] = $page;
                    $page_model->setData($data_page);
                    $page_model->save();
                
            }
        }
        
        //save product skus
        $product_model = $this->_productFactory->create();
        if ($id) {
            $prd_data = $product_model->getCollection()
                            ->addFieldToFilter('notification_id', $id);
            $prd_data->walk('delete');
        }
        if ($object->getProductSku()) {
            foreach ($object->getProductSku() as $product) {
                if ($product) {
                    $data_prd['notification_id'] = $object->getId();
                    $data_prd['product_sku'] = trim($product);
                    $product_model->setData($data_prd);
                    $product_model->save();
                }
            }
        }
        
        //save category ids
        $categoryIds = $object->getCategoryIds();
        $cate_model = $this->_categoryFactory->create();
        if ($id) {
            $cate_data = $cate_model->getCollection()
                            ->addFieldToFilter('notification_id', $id);
            $cate_data->walk('delete');
        }
        if ($object->getCategoryIds()) {
            foreach ($object->getCategoryIds() as $category_id) {
                //if ($category_id) {
                    $data_cate['notification_id'] = $object->getId();
                    $data_cate['category_ids'] = $category_id;
                    $cate_model->setData($data_cate);
                    $cate_model->save();
               // }
            }
        }
        
        return parent::_afterSave($object);
    }
}

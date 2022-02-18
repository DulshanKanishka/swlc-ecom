<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class Product extends Extended
{
    protected $_coreRegistry = null;
    protected $_linkFactory;
    protected $_setsFactory;
    protected $_productFactory;
    protected $_type;
    protected $_status;
    protected $_visibility;
    protected $moduleManager;
    protected $_notificationproduct;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magebees\Promotionsnotification\Model\Product $notificationproduct,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {

        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_type = $type;
        $this->_status = $status;
        $this->_visibility = $visibility;
        $this->_coreRegistry = $coreRegistry;
        $this->moduleManager = $moduleManager;
        $this->_notificationproduct = $notificationproduct;
        parent::__construct($context, $backendHelper, $data);
    }
 
    protected function _construct()
    {
        parent::_construct();
        $this->setId('product_section');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->setDefaultFilter(['in_products' => 1]);
    }
 
    public function getSelectedProducts()
    {
        $products = $this->getProductsSlider();
        
        $model = $this->_coreRegistry->registry('notification_data');
            $id = $this->getRequest()->getParam('id');
        if ($id) {
            if ($id) {
                $id = $id;
            } else {
                $id = $model->getId();
            }
                
            $product_model = $this->_notificationproduct->getCollection()
            ->addFieldToFilter('notification_id', ['eq' => $id]);
            $product_val = [];
            foreach ($product_model as $product_data) {
                $product_val[] = $product_data->getData('product_sku');
            }
        }

        if ($id) {
            if (!empty($products)) {
                $products = array_merge($product_val, $products);
                return $products;
            } else {
                $products = $product_val;
                return $products;
            }
        } else {
            return $products;
        }
    }
    
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    
    protected function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*');
      
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id'
            );
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner'
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner'
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner'
            );
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left');
          
       
 
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
 
    public function _prepareColumns()
    {
        $this->addColumn(
            'in_products',
            [
                    'type' => 'checkbox',
                    'name' => 'in_products',
                    'values' => $this->getSelectedProducts(),
                    'align' => 'center',
                    'index' => 'entity_id',
                    'header_css_class' => 'col-select',
                    'column_css_class' => 'col-select'
                ]
        );
                        
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'name',
            [
                        'header' => __('Name'),
                        'index' => 'name',
                        'header_css_class' => 'col-name',
                        'column_css_class' => 'col-name'
                ]
        );
        $this->addColumn(
            'type',
            [
                        'header' => __('Type'),
                        'index' => 'type_id',
                        'type' => 'options',
                        'options' => $this->_type->getOptionArray(),
                        'header_css_class' => 'col-type',
                        'column_css_class' => 'col-type'
                ]
        );
        $sets = $this->_setsFactory->create()->setEntityTypeFilter(
            $this->_productFactory->create()->getResource()->getTypeId()
        )->load()->toOptionHash();
 
        $this->addColumn(
            'set_name',
            [
                        'header' => __('Attribute Set'),
                        'index' => 'attribute_set_id',
                        'type' => 'options',
                        'options' => $sets,
                        'header_css_class' => 'col-attr-name',
                        'column_css_class' => 'col-attr-name'
                ]
        );
 
        $this->addColumn(
            'visibility',
            [
                        'header' => __('Visibility'),
                        'index' => 'visibility',
                        'type' => 'options',
                        'options' => $this->_visibility->getOptionArray(),
                        'header_css_class' => 'col-visibility',
                        'column_css_class' => 'col-visibility'
                ]
        );
 
        $this->addColumn(
            'sku',
            [
                        'header' => __('SKU'),
                        'index' => 'sku',
                        'header_css_class' => 'col-sku',
                        'column_css_class' => 'col-sku'
                ]
        );
 
        $this->addColumn(
            'price',
            [
                        'header' => __('Price'),
                        'type' => 'currency',
                        'currency_code' => (string)$this->_scopeConfig->getValue(
                            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        ),
                        'index' => 'price',
                        'header_css_class' => 'col-price',
                        'column_css_class' => 'col-price'
                ]
        );
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/productgrid', ['_current' => true]);
    }
}

<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab;

class General extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_systemStore;
    protected $_store;
    protected $_customer;
    protected $_customerGroup;
       
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magebees\Promotionsnotification\Model\Store $store,
        \Magebees\Promotionsnotification\Model\Customer $customer,
        \Magento\Customer\Model\Group $customerGroup,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_store = $store;
        $this->_customer = $customer;
        $this->_customerGroup = $customerGroup;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    
 
    protected function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('notification_data');
          
        $form = $this->_formFactory->create();
       // $form->setHtmlIdPrefix('page_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General')]);

        if ($model->getId()) {
            $fieldset->addField('notification_id', 'hidden', ['name' => 'notification_id']);
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Notification Title'),
                'title' => __('Notification Title'),
                'required' => true,
            ]
        );

        //for posh theme
       
            $fieldset->addField(
            'unique_code',
            'text',
            [
                'name' => 'unique_code',
                'label' => __('Notification Identifier'),
                'title' => __('Notification Identifier'),
                'required' => false
                
            ]
            ); 
        
        //for posh theme
        
        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);
 
        $notification_content = $fieldset->addField(
            'notification_content',
            'editor',
            [
                'name' => 'notification_content',
                'label' => __('Notification Content'),
                'title' => __('Notification Content'),
                'style' => 'height:24em;',
                'required' => true,
                'config' => $wysiwygConfig
            ]
        );
 
        $renderer = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element'
        )->setTemplate(
            'Magento_Cms::page/edit/form/renderer/content.phtml'
        );
        $notification_content->setRenderer($renderer);
         
        
      
         
        $fieldset->addField(
            'background_color',
            'text',
            [
                'name' => 'background_color',
                'label' => __('Background Color'),
                'title' => __('Background Color'),
                'required' => false,
                'class' => 'color',
            ]
        );
        
        $fieldset->addField(
            'notification_style',
            'select',
            [
                'name' => 'notification_style',
                'label' => __('Display Notification in'),
                'title' => __('Display Notification'),
                'values' => [
                    'bar' => __('Bar'),
                    'popup' => __('Popup'),
                ],
            ]
        );
        
        $dateFormat = $this->_localeDate->getDateFormat(
            \IntlDateFormatter::SHORT
        );
      
        $from_date = $fieldset->addField(
            'from_date',
            'date',
            [
                'name' => 'from_date',
                'label' => __('From Date'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'required' => true,
                'class' => 'date-range-custom_theme-from'
            ]
        );
        
        $to_date = $fieldset->addField(
            'to_date',
            'date',
            [
                'name' => 'to_date',
                'label' => __('To Date'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'required' => true,
                'class' => 'date-range-custom_theme-from'
            ]
        );
        
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'values' => [
                    '1' => __('Enabled'),
                    '0' => __('Disabled'),
                ],
            ]
        );
        
        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'required' => false,
                'class'=> 'validate-digits',
                'note'      => __('0 = High Priority'),
            ]
        );
        
        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'stores',
                'multiselect',
                [
                'name' => 'stores[]',
                'label' => __('Store View'),
                'title' => __('Store View'),
                'required' => true,
                'style' => 'height:15em;',
                'values' => $this->_systemStore->getStoreValuesForForm(false, true),
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        } else {
            $fieldset->addField(
                'stores',
                'hidden',
                ['name' => 'stores[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $model->setStoreId($this->_storeManager->getStore(true)->getId());
        }
        
        $fieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
                'name' => 'customer_group_ids[]',
                'label' => __('Customer Groups'),
                'title' => __('Customer Groups'),
                'required' => true,
                'style' => 'height:15em;',
                'values' => $this->_customerGroup->getCollection()->toOptionArray(),
            ]
        );
            
        $model_data = $model->getData();
        
        $store_model = $this->_store->getCollection()->addFieldToFilter('notification_id', ['eq' => $model->getId()]);
        $store_data = [];
        foreach ($store_model as $store) {
            $store_data[] = $store->getData('store_ids');
        }
        array_push($model_data, $model_data['stores'] = $store_data);
        
        $customer_model = $this->_customer->getCollection()->addFieldToFilter('notification_id', ['eq' => $model->getId()]);
        $customer_data = [];
        foreach ($customer_model as $customer) {
            $customer_data[] = $customer->getData('customer_ids');
        }
        array_push($model_data, $model_data['customer_group_ids'] = $customer_data);
        
        
        /* Code Start For Set Custom Div In the form*/
    
        $fieldset->addType(
            'preview_content',
            '\Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Renderer\Preview'
        );
        $fieldset->addField(
            'preview',
            'preview_content',
            [
                'name'  => 'preview',
                'label' => __(''),
                'title' => __(''),

            ]
        );
        
        
        $form->setValues($model_data);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}

<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification;

use \Magento\Reports\Block\Adminhtml\Sales\Grid\Column\Renderer\Date;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_notificationFactory;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magebees\Promotionsnotification\Model\PromotionsnotificationFactory $notificationFactory,
        array $data = []
    ) {
        $this->_notificationFactory = $notificationFactory;
        parent::__construct($context, $backendHelper, $data);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->setId('promotionsGrid');
        $this->setDefaultSort('notification_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = $this->_notificationFactory->create()->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
        
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('notification_id');
        $this->getMassactionBlock()->setFormFieldName('notification');
        
        $this->getMassactionBlock()->addItem(
            'display',
            [
                        'label' => __('Delete'),
                        'url' => $this->getUrl('promotions/*/massdelete'),
                        'confirm' => __('Are you sure?'),
                        'selected'=>true
                ]
        );
        
        $status = [
            ['value' => 1, 'label'=>__('Enabled')],
            ['value' => 0, 'label'=>__('Disabled')],
        ];

        array_unshift($status, ['label'=>'', 'value'=>'']);
        $this->getMassactionBlock()->addItem(
            'status',
            [
                'label' => __('Change status'),
                'url' => $this->getUrl('promotions/*/massStatus', ['_current' => true]),
                'additional' => [
                    'visibility' => [
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Status'),
                        'values' => $status
                    ]
                ]
            ]
        );
        return $this;
    }
        
    protected function _prepareColumns()
    {
        $this->addColumn(
            'finder_id',
            [
                'header' => __('Notification ID'),
                'type' => 'number',
                'index' => 'notification_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'title',
            [
                'header' => __('Title'),
                'index' => 'title',
            ]
        );
        
        $this->addColumn(
            'notification_style',
            [
                'header' => __('Display Notification in'),
                'index' => 'notification_style',
                'type' => 'options',
                'options' => [ 'bar' => 'Bar', 'popup' => 'Popup'],
            ]
        );
        
        $this->addColumn(
            'sort_order',
            [
                'header' => __('Sort Order'),
                'index' => 'sort_order',
            ]
        );
        
        $this->addColumn(
            'from_date',
            [
                'header' => __('Start DateTime'),
                'type' => 'date',
                'index' => 'from_date',
                //'format'=> 'dd/MM/yyyy HH:mm:ss',
            ]
        );
        
        $this->addColumn(
            'to_date',
            [
                'header' => __('End DateTime'),
                'type' => 'date',
                'index' => 'to_date',
                //'format'=> 'dd/MM/yyyy HH:mm:ss',
            ]
        );
        
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'frame_callback' => [$this, 'decorateStatus'],
                'type' => 'options',
                'options' => [ '0' => 'Disabled', '1' => 'Enabled'],
            ]
        );
        
        $this->addColumn(
            'edit_notification',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit Notification'),
                        'url' => [
                            'base' => '*/*/edit',
                            'params' => ['store' => $this->getRequest()->getParam('store')]
                        ],
                        'field' => 'id'
                    ]
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );
        
        return parent::_prepareColumns();
    }
    
    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            ['id' => $row->getId()]
        );
    }

    public function decorateStatus($value, $row, $column, $isExport)
    {
        if ($value=="Enabled") {
            $cell = '<span class="grid-severity-notice"><span>Enabled</span></span>';
        } else {
            $cell = '<span class="grid-severity-minor"><span>Disabled</span></span>';
        }
        return $cell;
    }
}

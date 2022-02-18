<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab\TestCampaign;


use Magenest\AbandonedCart\Model\LogContentFactory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /** @var \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry = null;

    /** @var \Magenest\AbandonedCart\Model\TestCampaignFactory $_testCampaignFactory */
    protected $_testCampaignFactory;

    /** @var LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /**
     * Grid constructor.
     *
     * @param LogContentFactory $logContentFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magenest\AbandonedCart\Model\TestCampaignFactory $testCampaignFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magenest\AbandonedCart\Model\TestCampaignFactory $testCampaignFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_logContentFactory   = $logContentFactory;
        $this->_coreRegistry        = $coreRegistry;
        $this->_testCampaignFactory = $testCampaignFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        $this->setSortable(false);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(true);

        $this->setUseAjax(true);
        $this->setEmptyText(__('There are no record in TestCampaign.'));
        parent::_construct();
    }

    /**
     * Prepare collection for grid
     * @return $this
     */
    protected function _prepareCollection()
    {
        $ruleModel = $this->_coreRegistry->registry('abandonedcart_rule');
        /** @var \Magenest\AbandonedCart\Model\ResourceModel\TestCampaign\Collection $collection */
        $collection = $this->_logContentFactory->create()->getCollection()
            ->addFieldToFilter('rule_id', $ruleModel->getId())
            ->addFieldToFilter('type', 'Campaign');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    public function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'index'  => 'id'
            ]
        );
        $this->addColumn(
            'quote_id',
            [
                'header' => __('Quote Id'),
                'index'  => 'quote_id',
                'type'   => 'text',
                'width'  => '160'
            ]
        );
//        $this->addColumn(
//            'coupon_generated',
//            [
//                'header' => __('Coupon Used'),
//                'index' => 'coupon_generated',
//                'type' => 'text',
//                'align' => 'center',
//                'width' => '160'
//            ]
//        );
        $this->addColumn(
            'recipient_adress',
            [
                'header' => __('Email'),
                'index'  => 'recipient_adress',
                'type'   => 'text',
                'width'  => '160'
            ]
        );
//        $this->addColumn(
//            'cart_subtotal',
//            [
//                'header' => __('Subtotal'),
//                'index'  => 'cart_subtotal',
//                'type'   => 'text',
//                'align'  => 'center',
//                'width'  => '160'
//            ]
//        );
//        $this->addColumn(
//            'status_cart',
//            [
//                'header' => __('Status Cart'),
//                'index' => 'status_cart',
//                'type' => 'text',
//                'align' => 'center',
//                'width' => '160'
//            ]
//        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'index'  => 'created_at',
                'type'   => 'date',
                'width'  => '160'
            ]
        );
        $this->addColumn(
            'updated_at',
            [
                'header' => __('Updated At'),
                'index'  => 'updated_at',
                'type'   => 'date',
                'width'  => '160'
            ]
        );
//        $this->addColumn(
//            'sent_date',
//            [
//                'header' => __('Send Date'),
//                'index'  => 'sent_date',
//                'type'   => 'date',
//                'align'  => 'center',
//                'width'  => '160'
//            ]
//        );
        $this->addColumn(
            'status',
            [
                'header'   => __('Is Send'),
                'index'    => 'status',
                'renderer' => 'Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab\TestCampaign\Renderer\IsSend',
                'width'    => '160'
            ]
        );

        $this->addColumn(
            'action',
            [
                'header'   => __('Action'),
                'type'     => 'action',
                'index'    => 'id',
                'renderer' => 'Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab\TestCampaign\Renderer\Action',
                'filter'   => false,
                'sortable' => false

            ]
        );
        $block = $this->getLayout()->getBlock('grid.bottom.links');

        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }
        parent::_prepareColumns();
        return $this;
    }

    /**
     * Get grid url
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('abandonedcart/*/testCampaignGrid', ['_current' => true]);
    }

    public function _prepareFilterButtons()
    {
        parent::_prepareFilterButtons();
        $ruleModel = $this->_coreRegistry->registry('abandonedcart_rule');

        $this->setChild(
            'abandonedcart_collecting',
            $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'label'   => __('Collect Abandoned Cart From Quote'),
                    'onclick' => 'setLocation("' . $this->getUrl('abandonedcart/rule/collect', ['_current' => true, 'id' => $ruleModel->getId()]) . '")',
                    'class'   => 'action-default action-reset action-tertiary',
                ]
            )->setDataAttribute(['action' => 'emulate_collecting_redirect'])
        );
    }

    public function getMainButtonsHtml()
    {
        $html = $this->getEmulateButtonsHtml();
        return $html;
    }

    protected function getEmulateButtonsHtml()
    {
        return $this->getChildHtml('abandonedcart_collecting');
    }

}
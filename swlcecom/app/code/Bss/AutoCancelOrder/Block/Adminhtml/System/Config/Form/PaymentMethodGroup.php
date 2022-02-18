<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Block\Adminhtml\System\Config\Form;

class PaymentMethodGroup extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var []
     */
    protected $_columns = [];

    /**
     * @var \Bss\AutoCancelOrder\Block\Adminhtml\System\Config\Form\PaymentMethodActiveList
     */
    protected $paymentMethodGroupRenderer;

    /**
     * @var \Bss\AutoCancelOrder\Block\Adminhtml\System\Config\Form\PaymentUnit
     */
    protected $unitRenderer;

    /**
     * @var bool
     */
    protected $_addAfter = true;

    /**
     * @var string
     */
    protected $_addButtonLabel;

    /**
     * Init function
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Get PaymentUnit renderer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return PaymentUnit|\Magento\Framework\View\Element\BlockInterface
     */
    protected function _getUnitRenderer()
    {
        if (!$this->unitRenderer) {
            $this->unitRenderer = $this->getLayout()->createBlock(
                PaymentUnit::class,
                '',
                [
                    'data' => [
                        'is_render_to_js_template' => true
                    ]
                ]
            );
        }
        
        return $this->unitRenderer;
    }

    /**
     * Get payment method active list
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return PaymentMethodActiveList|\Magento\Framework\View\Element\BlockInterface
     */
    protected function _getPaymentMethodGroupRenderer()
    {
        if (!$this->paymentMethodGroupRenderer) {
            $this->paymentMethodGroupRenderer = $this->getLayout()->createBlock(
                PaymentMethodActiveList::class,
                '',
                [
                    'data' => [
                        'is_render_to_js_template' => true
                    ]
                ]
            );
        }

        return $this->paymentMethodGroupRenderer;
    }

    /**
     * Add columns
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'payment_method_group_id',
            [
                'label' => __('Payment Group'),
                'renderer' => $this->_getPaymentMethodGroupRenderer()
            ]
        );
        $this->addColumn('duration', ['label' => __('Duration')]);
        $this->addColumn('unit_id', ['label' => __('PaymentUnit'), 'renderer' => $this->_getUnitRenderer()]);
    }

    /**
     * Prepare row data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @param \Magento\Framework\DataObject $row
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_'
                    . $this->_getPaymentMethodGroupRenderer()
                            ->calcOptionHash($row->getData('payment_method_group_id'))
        ] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getUnitRenderer()->calcOptionHash($row->getData('unit_id'))] =
            'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }

    /**
     * Render cell html
     *
     * @param string $columnName
     * @throws \Exception
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == "duration") {
            $this->_columns[$columnName]['class'] =
                'input-text validate-number validate-greater-than-zero required-entry';
            $this->_columns[$columnName]['style'] = 'width:50px';
        }

        return parent::renderCellTemplate($columnName);
    }
}

<?php

namespace NeoSolax\QuickBooksOnline\Block\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field as ConfigFormField;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Button extends ConfigFormField
{

    protected $_template = 'NeoSolax_QuickBooksOnline::system/config/button.phtml';

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxSyncUrl()
    {
        return $this->getUrl('neoqb/update/product');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'synchronize_button',
                'label' => __('Synced Products Stocks'),
            ]
        );

        return $button->toHtml();
    }
}

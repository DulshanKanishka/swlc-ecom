<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Input;
use Magento\Framework\DataObject;
use Meetanshi\PaymentRestriction\Helper\Data;

class Methods extends Input
{
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function render(DataObject $row)
    {
        $methods = $row->getData('methods');
        if (!$methods) {
            return __('Allows All');
        }
        $methods = explode(',', $methods);

        $html = '';
        foreach ($this->helper->getAllMethods() as $row) {
            if (in_array($row['value'], $methods)) {
                $html .= $row['label'] . "<br />";
            }
        }
        return $html;
    }
}

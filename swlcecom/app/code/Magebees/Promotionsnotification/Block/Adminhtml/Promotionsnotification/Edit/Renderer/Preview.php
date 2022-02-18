<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class Preview extends \Magento\Framework\Data\Form\Element\AbstractElement
{
 
    public function getElementHtml()
    {
        $preview_content = "<div id='preview-content'></div>";
        return $preview_content;
    }
}

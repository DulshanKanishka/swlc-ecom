<?php
namespace Magebees\Promotionsnotification\Model;

class Position implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' =>'top', 'label' => __('Top')],
            ['value' =>'bottom', 'label' => __('Bottom')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['top'=> __('Top'), 'bottom'=> __('Bottom')];
    }
}

<?php
namespace Magebees\Promotionsnotification\Model;

class Allorone implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' =>'all', 'label' => __('All')],
            ['value' =>'one', 'label' => __('One')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    
    
    public function toArray()
    {
        return ['all'=> __('All'), 'one'=> __('One')];
    }
}

<?php
namespace Magebees\Promotionsnotification\Model;

class Order implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' =>'sort_order', 'label' => __('By Sort Order')],
            ['value' =>'random', 'label' => __('Randomly')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    
    
    public function toArray()
    {
        return ['sort_order'=> __('By Sort Order'), 'random'=> __('Randomly')];
    }
}

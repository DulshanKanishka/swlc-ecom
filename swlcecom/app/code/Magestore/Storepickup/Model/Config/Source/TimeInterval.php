<?php
namespace Magestore\Storepickup\Model\Config\Source;

class TimeInterval implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => '15', 'label' => __('15 mins')],
            ['value' => '30', 'label' => __('30 mins')],
            ['value' => '45', 'label' => __('45 mins')]
        ];
    }
}

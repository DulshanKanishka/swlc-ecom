<?php
namespace Magestore\Storepickup\Model\Config\Source;

class FormatTime implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('24h')],
            ['value' => '1', 'label' => __('12h')]
        ];
    }
}

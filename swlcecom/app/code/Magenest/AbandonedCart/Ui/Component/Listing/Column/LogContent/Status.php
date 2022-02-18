<?php

namespace Magenest\AbandonedCart\Ui\Component\Listing\Column\LogContent;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class Status extends AbstractSource implements SourceInterface, OptionSourceInterface
{
    /**
     * Retrieve option array
     * @return string[]
     */
    public function getOptionArray()
    {
        return [
            EmailStatus::STATUS_QUEUED    => __('QUEUED'),
            EmailStatus::STATUS_SENT      => __('SENT'),
            EmailStatus::STATUS_FAILED    => __('FAILED'),
            EmailStatus::STATUS_CANCELLED => __('CANCELLED')
        ];
    }

    /**
     * Retrieve option array with empty value
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach ($this->getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }
}
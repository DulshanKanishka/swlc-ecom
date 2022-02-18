<?php

namespace Magenest\AbandonedCart\Ui\Component\Listing\Column\LogContent;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Stores extends AbstractSource implements SourceInterface, OptionSourceInterface
{

    /** @var \Magento\Store\Model\System\Store $_systemStore */
    protected $_systemStore;

    /** @var \Magento\Framework\Escaper $escaper */
    protected $escaper;

    /**
     * Stores constructor.
     *
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->_systemStore = $systemStore;
        $this->escaper      = $escaper;
    }

    /**
     * Retrieve option array
     * @return string[]
     */
    public function getOptionArray()
    {
        return $this->_systemStore->getStoreCollection();
    }

    /**
     * Retrieve option array with empty value
     * @return string[]
     */
    public function getAllOptions()
    {
        $stores                    = [];
        $stores['All Store Views'] = [
            'label' => 'All Store Views',
            'value' => "0"
        ];
        foreach ($this->getOptionArray() as $store) {
            $name                   = $this->escaper->escapeHtml($store->getName());
            $stores[$name]['label'] = $name;
            $stores[$name]['value'] = $store->getId();
        }
        return $stores;
    }
}
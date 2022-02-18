<?php
namespace Magenest\Xero\Model\Config\Source;

/**
 * Class SyncMode
 * @package Magenest\Xero\Model\Config\Source
 */
class SyncMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = [ 1 => 'Cron Job', 2 => 'Immediately'];

    /**
     * Return options array
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->_options;

        return $options;
    }
}

<?php
namespace Magenest\Xero\Model\Config\Source;

/**
 * Class AppMode
 * @package Magenest\Xero\Model\Config\Source
 */
class AppMode implements \Magento\Framework\Option\ArrayInterface
{
    const PUBLIC_APP = 0;
    const PRIVATE_APP = 1;
    const PARTNER_APP = 2;
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = [ self::PUBLIC_APP => 'Public', self::PRIVATE_APP => 'Private', self::PARTNER_APP => 'Partner'];

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

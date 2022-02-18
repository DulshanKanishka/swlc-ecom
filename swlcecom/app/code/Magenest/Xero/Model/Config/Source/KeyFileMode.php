<?php
namespace Magenest\Xero\Model\Config\Source;

/**
 * Class AppMode
 * @package Magenest\Xero\Model\Config\Source
 */
class KeyFileMode implements \Magento\Framework\Option\ArrayInterface
{
    const COPY_PASTE = 0;
    const UPLOAD_FILE = 1;
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = [ self::COPY_PASTE => 'Copy paste', self::UPLOAD_FILE => 'Upload files'];

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

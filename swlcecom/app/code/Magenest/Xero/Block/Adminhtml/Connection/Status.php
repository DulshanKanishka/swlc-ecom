<?php
namespace Magenest\Xero\Block\Adminhtml\Connection;

use Magento\Backend\Block\Template;
use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\CoreConfig;
use Magenest\Xero\Helper\Signature;

/**
 * Class Status
 * @package Magenest\Xero\Block\Adminhtml\Connection
 */
class Status extends Template
{

    /**
     * Set Template
     *
     * @var string
     */
    protected $_template = 'system/config/connection/status.phtml';

    protected $_coreConfig;

    protected $_helper;

    public function __construct(
        Template\Context $context,
        CoreConfig $coreConfig,
        Helper $helper,
        array $data = []
    )
    {
        $this->_coreConfig = $coreConfig;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Check connected with xero
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->_coreConfig->getConfigValueByScope(
            Signature::PATH_XERO_IS_CONNECTED,
            $this->_helper->getScope(),
            $this->_helper->getScopeId()
        );
    }
}

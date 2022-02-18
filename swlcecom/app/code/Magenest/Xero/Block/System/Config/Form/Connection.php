<?php
namespace Magenest\Xero\Block\System\Config\Form;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field as ConfigFormField;
use Magenest\Xero\Block\Adminhtml\Connection\Status as ConnectionStatus;
use Magenest\Xero\Model\CoreConfig;
use Magento\Backend\Block\Template\Context;
use Magenest\Xero\Helper\Signature;
use Magento\Store\Model\ScopeInterface;
use Magenest\Xero\Model\Helper;

/**
 * Class Connection
 * @package Magenest\Xero\Block\System\Config\Form
 */
class Connection extends ConfigFormField
{
    protected $_config;

    protected $_helper;

    public function __construct(
        CoreConfig $config,
        Context $context,
        Helper $helper,
        array $data = []
    )
    {
        $this->_config = $config;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Create element for Access token field in store configuration
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setInherit(false);
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        $element->setCanRestoreToDefault(false);
        $element->setValue($this->isConnected());
        return parent::render($element); // TODO: Change the autogenerated stub
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $connectionHtml = $this->getLayout()->createBlock(ConnectionStatus::class)->toHtml();
        return $element->getElementHtml() . $connectionHtml;
    }

    protected function isConnected()
    {
        $websiteId = $this->getRequest()->getParam('website');
        if ($websiteId) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $this->_helper->setScope($scope);
            $this->_helper->setScopeId($websiteId);
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $websiteId = 0;
        }

        return $this->_config->getConfigValueByScope(
            Signature::PATH_XERO_IS_CONNECTED,
            $scope,
            $websiteId
            );
    }
}

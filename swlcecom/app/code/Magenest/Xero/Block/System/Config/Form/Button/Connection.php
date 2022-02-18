<?php
namespace Magenest\Xero\Block\System\Config\Form\Button;

use Magenest\Xero\Helper\Signature;
use Magento\Backend\Block\Template\Context;

/**
 * Class Connection
 * @package Magenest\Xero\Block\System\Config\Form\Button
 */
class Connection extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Test Button Label
     *
     * @var string
     */
    protected $_testButtonLabel = 'Connect Private App Now';

    /**
     * @var Signature
     */
    protected $signature;

    /**
     * Connection constructor.
     * @param Signature $signature
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Signature $signature,
        Context $context,
        array $data = []
    ) {
        $this->signature = $signature;
        parent::__construct($context, $data);
    }

    /**
     * Set Test Button Label
     *
     * @param string $label
     * @return \Magenest\Xero\Block\System\Config\Form\TestButton
     */
    public function setTestButtonLabel($label)
    {
        $this->_testButtonLabel = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getConnectUrl()
    {
        return $this->getUrl('xero/app/connect', ['website' => $this->getRequest()->getParam('website')]);
    }

    /**
     * Set template
     *
     * @return \Magenest\Xero\Block\System\Config\Form\TestButton
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/connection/button.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_testButtonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId()
            ]
        );

        return $this->_toHtml();
    }

    public function isConsumerKeyExist()
    {
        return !empty($this->_scopeConfig->getValue(Signature::PATH_CONSUMER_KEY));
    }
}

<?php
namespace Magenest\Xero\Block\System\Config\Version;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\PackageInfo;

class Info extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;

    protected $_packageInfo;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        PackageInfo $packageInfo,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_packageInfo = $packageInfo;
        $this->_layoutFactory = $layoutFactory;
        $this->_scopeConfig = $context->getScopeConfig();
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $html .= $this->_getVersion();
        $html .= $this->_getSupportLinks();
        $html .= $this->_getPowerBy();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $layout = $this->_layoutFactory->create();

            $this->_fieldRenderer = $layout->createBlock(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }

        return $this->_fieldRenderer;
    }

    protected function _getPowerBy()
    {
        $value = '<table style="margin-left: 200px;">';
        $value .= '<tr>';
        $value .= '<td style="width: 200px; font-weight: bold">Powered by</td>';
        $value .= '<td><a href="https://magenest.com" target="_blank">Magenest</a></td>';
        $value .= '</tr>';
        $value .= '</table>';

        return $value;
    }

    protected function _getVersion()
    {
        $value = '<table style="margin-left: 200px;">';
        $value .= '<tr>';
        $value .= '<td style="width: 200px; font-weight: bold">Version</td>';
        $value .= '<td>'. $this->_packageInfo->getVersion('Magenest_Xero') .'</td>';
        $value .= '</tr>';
        $value .= '</table>';

        return $value;
    }

    protected function _getSupportLinks()
    {
        $supportPortal = [
            'Installation Guide' => 'http://www.confluence.izysync.com/display/DOC/1.+Xero+Integration+Installation+Guides',
            'User Guide' => 'http://www.confluence.izysync.com/display/DOC/2.+Xero+Integration+User+Guides',
            'Support Portal' => 'http://servicedesk.izysync.com/servicedesk/customer/portal/26/user/login?destination=portal%2F26',
        ];
        $value = '<table style="margin-left: 200px;">';
        $value .= '<tr>';
        $value .= '<td style="width: 200px; font-weight: bold">Support Links</td>';

        $value .= '<td><table>';
        foreach ($supportPortal as $k => $v) {
            $value .=
                '<tr>' .
                '<td style="width: 100px;padding: 0">' . $k . '</td>' .
                '<td style="width: 400px; padding: 0"><a target="_blank" href="' . $v. '">Go to '.$k.'</a></td>' .
                '</tr>';
        }
        $value .= '</table></td>';
        $value .= '</tr>';
        $value .= '</table>';

        return $value;
    }
}

<?php
namespace Magenest\Xero\Block\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magenest\Xero\Model\XeroClient;
use Magento\Store\Model\ScopeInterface;
use Magenest\Xero\Model\CoreConfig;
use Magenest\Xero\Model\Helper;
/**
 * Class Information
 * @package Magenest\Xero\Block\System\Config\Form
 */
class Information extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var array
     */
    protected $userInfo;

    /**
     * @var XeroClient
     */
    protected $xeroClient;

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'system/config/information.phtml';

    protected $_coreConfig;

    protected $_helper;

    /**
     * Information constructor.
     * @param Context $context
     * @param XeroClient $xeroClient
     * @param CoreConfig $coreConfig
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        XeroClient $xeroClient,
        CoreConfig $coreConfig,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->xeroClient = $xeroClient;
        $this->_coreConfig = $coreConfig;
        $this->_helper = $helper;
    }


    /**
     * Get User Information
     *
     * @return array
     */
    public function getUserInfo()
    {
        if (!$this->userInfo) {
            $this->userInfo = $this->_getUserInfo();
        }

        return $this->userInfo;
    }

    /**
     * Retrieve users info on xero
     *
     * @return array
     */
    protected function _getUserInfo()
    {
        $userInfo = $this->xeroClient->getUserInformation();
        if (is_array($userInfo)) {
            if (isset($userInfo[0])) {
                foreach ($userInfo as $key => $value) {
                    if ($value['IsSubscriber'] == 'true') {
                        return $userInfo[$key];
                    }
                }
            } else {
                return $userInfo;
            }
        }

        return [];
    }

    /**
     * Get Disconnect Url
     *
     * @return string
     */
    public function getDisconnectUrl()
    {
        return $this->getUrl('xero/app/disconnect', ['_secure' => true]);
    }

    /**
     * Get Element Html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        try {
            if ($this->_coreConfig->getConfigValueByScope(
                'magenest_xero_config/xero_api/is_connected',
                $this->_helper->getScope(),
                $this->_helper->getScopeId()
            )) {
                return $this->_toHtml();
            }
//            if ($this->_scopeConfig->isSetFlag('magenest_xero_config/xero_api/is_connected')) {
//                return $this->_toHtml();
//            }

            return '';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}

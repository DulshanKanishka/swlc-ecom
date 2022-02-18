<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magenest\QuickBooksOnline\Model\Config\Source\AppMode as AppModeSource;
use Magenest\QuickBooksOnline\Model\Config;

/**
 * Class Mode
 *
 * @package Magenest\QuickBooksOnline\Helper
 */
class Mode
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var int
     */
    protected $mode;

    /**
     * Parameters constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
     * @return int
     */
    protected function getMode()
    {
        if ($mode = $this->scopeConfig->getValue(Config::XML_PATH_QBONLINE_APP_MODE)) {
            $this->mode = $mode;
        }
        
        $modeParam = (int)$this->request->getParam('qbo_mode');
        if ($modeParam > 0) {
            $this->mode = $modeParam;
        }

        return $this->mode;
    }

    /**
     * @return mixed
     */
    public function getWebsiteId()
    {
        return $this->request->getParam('website');
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        if ($this->getMode() == AppModeSource::PRODUCTION_MODE) {
            return $this->getProductionClientId();
        } else {
            return $this->getSandboxClientId();
        }
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        if ($this->getMode() == AppModeSource::PRODUCTION_MODE) {
            return $this->getProductionClientSecret();
        } else {
            return $this->getSandboxClientSecret();
        }
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function getConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }

   /**
     * @return string
     */
    protected function getProductionClientId()
    {
        return $this->getConfig(Config::XML_PATH_PRODUCTION_CLIENT_ID);
    }

    /**
     * @return string
     */
    protected function getProductionClientSecret()
    {
        return $this->getConfig(Config::XML_PATH_PRODUCTION_CLIENT_SECRET);
    }

    /**
     * @return string
     */
    protected function getSandboxClientId()
    {
        return $this->getConfig(Config::XML_PATH_SANDBOX_CLIENT_ID);
    }

    /**
     * @return string
     */
    protected function getSandboxClientSecret()
    {
        return $this->getConfig(Config::XML_PATH_SANDBOX_CLIENT_SECRET);
    }
}

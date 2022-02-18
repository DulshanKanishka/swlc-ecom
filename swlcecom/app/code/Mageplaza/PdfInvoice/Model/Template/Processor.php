<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_PdfInvoice
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PdfInvoice\Model\Template;

use Magento\Email\Model\Template;
use Magento\Email\Model\Template\Config;
use Magento\Email\Model\Template\FilterFactory;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\PdfInvoice\Helper\Data;

/**
 * Class Processor
 * @package Mageplaza\PdfInvoice\Model\Template
 */
class Processor extends Template
{
    /**
     * @var $storeId
     */
    private $storeId;

    /**
     * @var $designConfig
     */
    private $designConfig;

    /**
     * @var $templateHtml
     */
    private $templateHtml;

    /**
     * @var $variable
     */
    public $variable;

    /**
     * @var Data
     */
    public $checkVersion;

    /**
     * Processor constructor.
     *
     * @param Data $checkVersion
     * @param Context $context
     * @param DesignInterface $design
     * @param Registry $registry
     * @param Emulation $appEmulation
     * @param StoreManagerInterface $storeManager
     * @param Repository $assetRepo
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $emailConfig
     * @param TemplateFactory $templateFactory
     * @param FilterManager $filterManager
     * @param UrlInterface $urlModel
     * @param FilterFactory $filterFactory
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Data $checkVersion,
        Context $context,
        DesignInterface $design,
        Registry $registry,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        Repository $assetRepo,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        Config $emailConfig,
        TemplateFactory $templateFactory,
        FilterManager $filterManager,
        UrlInterface $urlModel,
        FilterFactory $filterFactory,
        array $data = [],
        Json $serializer = null
    ) {
        $this->checkVersion = $checkVersion;
        parent::__construct(
            $context,
            $design,
            $registry,
            $appEmulation,
            $storeManager,
            $assetRepo,
            $filesystem,
            $scopeConfig,
            $emailConfig,
            $templateFactory,
            $filterManager,
            $urlModel,
            $filterFactory,
            $data,
            $serializer
        );
    }

    /**
     * Set template html
     *
     * @param $html
     */
    public function setTemplateHtml($html)
    {
        $this->templateHtml = $html;
    }

    /**
     * Set store
     *
     * @param $storeId
     */
    public function setStore($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Set variable
     *
     * @param $data
     *
     * @return $this
     */
    public function setVariable($data)
    {
        $this->variable = $data;

        return $this;
    }

    /**
     * Get template html
     * @return mixed
     */
    public function getTemplateHtml()
    {
        return $this->templateHtml;
    }

    /**
     * Process template
     * @return string
     */
    public function processTemplate()
    {
        $store           = $this->variable['store'];
        $this->storeId   = $store->getId();
        $isDesignApplied = $this->applyDesignConfig();

        if ($this->checkVersion->versionCompare('2.3.5')) {
            $processor = $this->getTemplateFilter()
                ->setPlainTemplateMode($this->isPlain())
                ->setIsChildTemplate($this->isChildTemplate())
                ->setTemplateProcessor([$this, 'getTemplateContent']);
        } else {
            $processor = $this->getTemplateFilter()
                ->setUseSessionInUrl(false)
                ->setPlainTemplateMode($this->isPlain())
                ->setIsChildTemplate($this->isChildTemplate())
                ->setTemplateProcessor([$this, 'getTemplateContent']);
        }

        $processor->setStrictMode(false);
        $storeId = $store->getId();
        $processor->setDesignParams($this->getDesignParams());
        $variables         = $this->addEmailVariables($this->variable, $storeId);
        $variables['this'] = $this;
        $processor->setVariables($variables);
        $this->setUseAbsoluteLinks(true);
        $html = $processor->setStoreId($storeId)
            ->setDesignParams([0])
            ->filter(__($this->getTemplateHtml()));

        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }

        return $html;
    }

    /**
     * Get design params
     * @return array
     */
    public function getDesignParams()
    {
        return [
            'area'       => $this->getDesignConfig()->getArea(),
            'theme'      => $this->design->getDesignTheme()->getCode(),
            'themeModel' => $this->design->getDesignTheme(),
            'locale'     => $this->design->getLocale(),
        ];
    }

    /**
     * Get design configuration data
     *
     * @return DataObject
     */
    public function getDesignConfig()
    {
        if ($this->designConfig === null) {
            $this->designConfig = new DataObject(
                ['area' => Area::AREA_FRONTEND, 'store' => $this->storeId]
            );
        }

        return $this->designConfig;
    }
}

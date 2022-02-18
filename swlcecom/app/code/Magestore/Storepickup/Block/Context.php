<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Block;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Context extends \Magento\Framework\View\Element\Template\Context
{
    /**
     * @var \Magestore\Storepickup\Model\SystemConfig
     */
    protected $_systemConfig;

    /**
     * @var \Magestore\Storepickup\Helper\Image
     */
    protected $_imageHelper;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory
     */
    protected $_storeCollectionFactory;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Tag\CollectionFactory
     */
    protected $_tagCollectionFactory;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Context constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                 $request
     * @param \Magento\Framework\View\LayoutInterface                 $layout
     * @param \Magento\Framework\Event\ManagerInterface               $eventManager
     * @param \Magento\Framework\UrlInterface                         $urlBuilder
     * @param \Magento\Framework\App\CacheInterface                   $cache
     * @param \Magento\Framework\View\DesignInterface                 $design
     * @param \Magento\Framework\Session\SessionManagerInterface      $session
     * @param \Magento\Framework\Session\SidResolverInterface         $sidResolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig
     * @param \Magento\Framework\View\Asset\Repository                $assetRepo
     * @param \Magento\Framework\View\ConfigInterface                 $viewConfig
     * @param \Magento\Framework\App\Cache\StateInterface             $cacheState
     * @param \Psr\Log\LoggerInterface                                $logger
     * @param \Magento\Framework\Escaper                              $escaper
     * @param \Magento\Framework\Filter\FilterManager                 $filterManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface    $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface      $inlineTranslation
     * @param \Magento\Framework\Filesystem                           $filesystem
     * @param \Magento\Framework\View\FileSystem                      $viewFileSystem
     * @param \Magento\Framework\View\TemplateEnginePool              $enginePool
     * @param \Magento\Framework\App\State                            $appState
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param \Magento\Framework\View\Page\Config                     $pageConfig
     * @param \Magento\Framework\View\Element\Template\File\Resolver  $resolver
     * @param \Magento\Framework\View\Element\Template\File\Validator $validator
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\View\TemplateEnginePool $enginePool,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Framework\View\Element\Template\File\Resolver $resolver,
        \Magento\Framework\View\Element\Template\File\Validator $validator,
        \Magestore\Storepickup\Model\SystemConfig $systemConfig,
        \Magestore\Storepickup\Helper\Image $imageHelper,
        \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        \Magestore\Storepickup\Model\ResourceModel\Tag\CollectionFactory $tagCollectionFactory,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct(
            $request,
            $layout,
            $eventManager,
            $urlBuilder,
            $cache,
            $design,
            $session,
            $sidResolver,
            $scopeConfig,
            $assetRepo,
            $viewConfig,
            $cacheState,
            $logger,
            $escaper,
            $filterManager,
            $localeDate,
            $inlineTranslation,
            $filesystem,
            $viewFileSystem,
            $enginePool,
            $appState,
            $storeManager,
            $pageConfig,
            $resolver,
            $validator
        );

        $this->_systemConfig = $systemConfig;
        $this->_imageHelper = $imageHelper;
        $this->_storeCollectionFactory = $storeCollectionFactory;
        $this->_tagCollectionFactory = $tagCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @return \Magestore\Storepickup\Model\SystemConfig
     */
    public function getSystemConfig()
    {
        return $this->_systemConfig;
    }

    /**
     * @return \Magestore\Storepickup\Helper\Image
     */
    public function getImageHelper()
    {
        return $this->_imageHelper;
    }

    /**
     * @return \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory
     */
    public function getStoreCollectionFactory()
    {
        return $this->_storeCollectionFactory;
    }

    /**
     * @return \Magestore\Storepickup\Model\ResourceModel\Tag\CollectionFactory
     */
    public function getTagCollectionFactory()
    {
        return $this->_tagCollectionFactory;
    }

    /**
     * @return \Magento\Framework\Registry
     */
    public function getCoreRegistry()
    {
        return $this->_coreRegistry;
    }
}

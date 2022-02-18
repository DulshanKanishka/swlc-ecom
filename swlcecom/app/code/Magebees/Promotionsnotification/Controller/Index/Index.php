<?php
namespace Magebees\Promotionsnotification\Controller\Index;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Action\Action;

class Index extends Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
        /**
         * @var \Magento\Store\Model\StoreManagerInterface
         */
    protected $storeManager;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->scopeConfig->isSetFlag('promotions/general/enable_link', ScopeInterface::SCOPE_STORE) || !$this->scopeConfig->isSetFlag('promotions/general/enabled', ScopeInterface::SCOPE_STORE)) {
            throw new NotFoundException(__('Page not found.'));
        }
        return parent::dispatch($request);
    }
    
    
    public function execute()
    {
        $this->_view->loadLayout();
        $page_layout = $this->scopeConfig->getValue('promotions/general/page_layout', ScopeInterface::SCOPE_STORE);
        $this->_view->getPage()->getConfig()->setPageLayout($page_layout);
        $this->_view->getPage()->getConfig()->getTitle()->set("All Notifications");
        $this->_view->renderLayout();
    }
}

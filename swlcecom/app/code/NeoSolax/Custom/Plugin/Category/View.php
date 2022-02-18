<?php

namespace NeoSolax\Custom\Plugin\Category;

use Magento\Framework\App\Action\Context;

class View
{
    protected $resultRedirectFactory;

    public function __construct(Context $context)
    {
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
    }

    public function aroundExecute(\Magento\Catalog\Controller\Category\View $subject, \Closure $method)
    {
        $response = $method();
        if ($subject->getRequest()->getParam('p')>1 && $subject->getRequest()->getParam('product_list_limit') == 'all') {
            $limit = $subject->getRequest()->getParam('p');
            $word = '?p=' . $limit . '&';
            $requestUri = $subject->getRequest()->getRequestUri();
            $requestUri = str_replace($word, '?', $requestUri);
            $subject->getRequest()->setRequestUri($requestUri);
            return $this->resultRedirectFactory->create()->setUrl($requestUri);
        }
        return $response;
    }
}

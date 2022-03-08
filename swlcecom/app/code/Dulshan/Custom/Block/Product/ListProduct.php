<?php

namespace Dulshan\Custom\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Session;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    public function __construct(
        Context $context,
        Http $request,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;
        $this->customerSession =$customerSession;
        $this->productRepository = $productRepository;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    public function protectedPid($productId)
    {
        $q = $this->request->getParams();
        $usecategorypassword= $this->scopeConfig->getValue('se_categorypassword/categorypassword/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($usecategorypassword && isset($q['q'])) {

            $core_write = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('category_password');
            $catalog_category_product_index = $this->resource->getTableName('catalog_category_product_index');
            $password = "";
            $category_password = "";
            $categories = [];

            $session_passed_category = $this->customerSession->getData('passed_category');
            if (!isset($session_passed_category)) {
                $session_passed_category = [];
            }
            $product = $this->productRepository->getById($productId);
            // Fetch the 'category_ids' attribute from the Data Model.
            $categoryIds = $product->getCustomAttribute('category_ids');
            if ($categoryIds->getValue()) {
                foreach ($categoryIds->getValue() as $categoryId) {
                    $categories[] = $categoryId;
                }
            }
            if ($categories) {
                foreach ($categories as $category_id) {
                    $category_password = "";
                    $selectsql = "select * from `" . $tableName . "` where category_id='" . $category_id . "'";
                    $category_passwordfeach = $core_write->fetchAll($selectsql);
                    if (count($category_passwordfeach) > 0) {
                        foreach ($category_passwordfeach as $categorypassword) {
                            $category_password = $categorypassword['password'];
                        }
                    }
                    if ($category_password) {
                        return true;
                        break;
                    }
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

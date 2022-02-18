<?php
/**
 *
 * SM CartQuickPro - Version 1.1.0
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: http://www.magentech.com
 */
 
namespace  Sm\CartQuickPro\Controller\Product\Compare;

use Magento\Framework\Exception\NoSuchEntityException;

class Remove extends \Magento\Catalog\Controller\Product\Compare\Remove
{
    /**
     * Remove item from compare list
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('product');
		$result = [];
		$params = $this->getRequest()->getParams();
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }

            if ($product) {
                /** @var $item \Magento\Catalog\Model\Product\Compare\Item */
                $item = $this->_compareItemFactory->create();
                if ($this->_customerSession->isLoggedIn()) {
                    $item->setCustomerId($this->_customerSession->getCustomerId());
                } elseif ($this->_customerId) {
                    $item->setCustomerId($this->_customerId);
                } else {
                    $item->addVisitorId($this->_customerVisitor->getId());
                }

                $item->loadByProduct($product);
                /** @var $helper \Magento\Catalog\Helper\Product\Compare */
                $helper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare');
                if ($item->getId()) {
                    $item->delete();
                    $productName = $this->_objectManager->get('Magento\Framework\Escaper')
                        ->escapeHtml($product->getName());
                    $this->messageManager->addSuccess(
                        __('You removed product %1 from the comparison list.', $productName)
                    );
                    $this->_eventManager->dispatch(
                        'catalog_product_compare_remove_product',
                        ['product' => $item]
                    );
                    $helper->calculate();
					if (isset($params['isComparePage'])){
						$_layout  = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
						$_layout->getUpdate()->load(['cartquickpro_product_compare_remove']);
						$_layout->generateXml();
						$_output = $_layout->getOutput();
						$result['content'] = $_output;
						$result['isComparePageContent'] =  true;
					}
					$result['messages'] =  __('You removed product %1 from the comparison list.', $productName);
					$result['success'] = true;
					
                }
            }
        }

		$compare = $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare');
        $result['isCompareBtn'] =   (!isset($params['isComparePage']) && $compare->getItemCount()) ? true : false ;
		return $this->_jsonResponse($result);
    }
	
	protected function _jsonResponse($result)
    {
        return $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}

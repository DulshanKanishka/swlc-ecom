<?php
/**
 *
 * SM CartQuickPro - Version 1.1.0
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: http://www.magentech.com
 */
 
namespace Sm\CartQuickPro\Controller\Product\Compare;

use Magento\Framework\Controller\ResultFactory;

class Clear extends \Magento\Catalog\Controller\Product\Compare\Clear
{
    /**
     * Remove all items from comparison list
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection $items */
        $result = [];
		$items = $this->_itemCollectionFactory->create();
		$params = $this->getRequest()->getParams();
        if ($this->_customerSession->isLoggedIn()) {
            $items->setCustomerId($this->_customerSession->getCustomerId());
        } elseif ($this->_customerId) {
            $items->setCustomerId($this->_customerId);
        } else {
            $items->setVisitorId($this->_customerVisitor->getId());
        }

        try {
            $items->clear();

            $this->messageManager->addSuccess(__('You cleared the comparison list.'));
            $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare')->calculate();
			$result['messages'] = __('You cleared the comparison list.');
			$result['success'] = true;
			if (isset($params['isComparePage'])){
				$_layout  = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
				$_layout->getUpdate()->load(['cartquickpro_product_compare_remove']);
				$_layout->generateXml();
				$_output = $_layout->getOutput();
				$result['content'] = $_output;
				$result['isComparePageContent'] =  true;
			}
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
			$result['messages'] = $e->getMessage();
			$result['success'] = false;
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong  clearing the comparison list.'));
			$result['messages'] = __('Something went wrong  clearing the comparison list.');
			$result['success'] = false;
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

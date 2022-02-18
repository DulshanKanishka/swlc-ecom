<?php
/**
 *
 * SM CartQuickPro - Version 1.1.0
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: http://www.magentech.com
 */
 
namespace Sm\CartQuickPro\Controller\Cart;

class UpdateItemOptions extends \Magento\Checkout\Controller\Cart
{
    /**
     * Update product configuration for a cart item
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        //var_dump($this->_request); die;
		$id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();
		$result = [];
        if (!isset($params['options'])) {
            $params['options'] = [];
        }
        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $quoteItem = $this->cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the quote item.'));
            }

            $item = $this->cart->updateItem($id, new \Magento\Framework\DataObject($params));
            if (is_string($item)) {
                throw new \Magento\Framework\Exception\LocalizedException(__($item));
            }
            if ($item->getHasError()) {
                throw new \Magento\Framework\Exception\LocalizedException(__($item->getMessage()));
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_update_item_complete',
                ['item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        '%1 was updated in your shopping cart.',
                        $this->_objectManager->get('Magento\Framework\Escaper')
                            ->escapeHtml($item->getProduct()->getName())
                    );
					
                    $this->messageManager->addSuccess($message);
					$result['success'] = true;
					$result['messages'] =  $message;
					if (isset($params['isCheckoutPage'])){
						$_layout  = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
						$_layout->getUpdate()->load([ 'cartquickpro_checkout_cart_index', 'checkout_cart_item_renderers','checkout_item_price_renderers']);
						$_layout->generateXml();
						$_output = $_layout->getOutput();
						$result['content'] = $_output;
						$result['isPageCheckoutContent'] =  true;
					}
					
                }
               // return $this->_goBack($this->_url->getUrl('checkout/cart'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice($e->getMessage());
				$result['messages'] =  $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage());
				$result['success'] = true;
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError($message);
                }
				$result['messages'] =  join(',', $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($messages));
				$result['success'] = false;
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
			$result['url']  =  $url;
           
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the item right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
			$result['messages'] =  __('We can\'t update the item right now.');
			$result['success'] = false;
           
        }
		$result['isAddToCartBtn'] =   (!isset($params['isCheckoutPage']) && $this->cart->getItemsCount()) ? true : false ;
		return $this->_jsonResponse($result);
    }
	
	protected function _jsonResponse($result)
    {
        return $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}

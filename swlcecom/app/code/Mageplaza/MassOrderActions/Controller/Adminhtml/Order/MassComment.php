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
 * @package     Mageplaza_MassOrderActions
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\MassOrderActions\Controller\Adminhtml\Order;

use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\History;
use Mageplaza\MassOrderActions\Block\Adminhtml\Result;

/**
 * Class MassComment
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order
 */
class MassComment extends AbstractMassAction
{
    /**
     * @return ResponseInterface|ResultInterface|mixed
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        if (!$request->isAjax()) {
            return $this->_resultFwFactory->create()->forward('noroute');
        }
        if (!$request->isPost()) {
            throw new NotFoundException(__('Page not found.'));
        }

        return parent::execute();
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return $this|mixed
     */
    protected function massAction($collection)
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPost('comment');

        /** @var Result $resultBlock */
        $resultBlock = $this->_layout
            ->createBlock(Result::class)
            ->setTemplate('Mageplaza_MassOrderActions::result.phtml');

        foreach ($collection->getItems() as $order) {
            /** @var Order $order */
            $processedData = $this->_prepareData($data);
            $processedData['status'] = $processedData['status'] ?: $order->getStatus();
            $orderStatus = $this->_getOrderStatus($order, $processedData['status']);
            if ($processedData['is_empty_comment']
                && (!$orderStatus || $orderStatus === $order->getDataByKey('status'))
            ) {
                $this->_orderNonComment++;
                continue;
            }

            if (!$orderStatus || $orderStatus === $order->getDataByKey('status')) {
                $this->_statusNonUpdated++;
            }
            try {
                /** @var History $history */
                $history = $order->addStatusHistoryComment($processedData['comment'], $orderStatus);
                $history->setIsCustomerNotified($processedData['is_customer_notified']);
                $history->setIsVisibleOnFront($processedData['is_visible_on_front']);
                $this->_historyResource->save($history);
                $comment = trim(strip_tags($processedData['comment']));
                $this->_orderResource->save($order);
                $this->_orderComment++;
                $this->_orderCommentSender->send($order, $processedData['is_customer_notified'], $comment);
            } catch (Exception $e) {
                $resultBlock->addError($e->getMessage());
            }
        }

        return $this->_resultJson->setData($this->_addAjaxResult($resultBlock));
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function _prepareData($data)
    {
        $processedData = $data;

        $processedData['is_empty_comment'] = false;
        if (empty($processedData['comment'])) {
            $processedData['comment'] = __('Order Status Changed');
            $processedData['is_empty_comment'] = true;
        }
        $processedData['is_customer_notified'] = isset($processedData['is_customer_notified'])
            ? $processedData['is_customer_notified'] : false;
        $processedData['is_visible_on_front'] = isset($processedData['is_visible_on_front'])
            ? $processedData['is_visible_on_front'] : false;

        return $processedData;
    }
}

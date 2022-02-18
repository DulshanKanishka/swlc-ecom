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
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;

/**
 * Class MassStatus
 * @package Mageplaza\MassOrderActions\Controller\Adminhtml\Order
 */
class MassStatus extends AbstractMassAction
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
        if (!$request->isPost()) {
            throw new NotFoundException(__('Page not found.'));
        }

        return parent::execute();
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return Redirect
     */
    protected function massAction($collection)
    {
        $status = $this->getRequest()->getParam('status');
        $isDifferentState = $this->_helperData->getConfigGeneral('different_state');

        foreach ($collection->getItems() as $order) {
            try {
                /** @var Order $order */
                if ($isDifferentState) {
                    if (in_array($status, $this->_helperData->getStatusByState($order->getState()), true)) {
                        $this->_statusUpdated = $this->_saveOrderStatus($order, $status, $this->_statusUpdated);
                    }
                } else {
                    $orderData = $this->_orderModel->load($order->getId());
                    $orderData->setState($status)->setStatus($status);
                    $this->_statusUpdated = $this->_saveOrderStatus($order, $status, $this->_statusUpdated);
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(
                    $e,
                    __('Something went wrong while updating status for Order #%1.', $order->getId())
                );
            }
        }
        $this->_statusNonUpdated = $collection->getSize() - $this->_statusUpdated;
        if ($this->_statusNonUpdated && $this->_statusUpdated) {
            $this->messageManager
                ->addErrorMessage(__('%1 order(s) cannot be changed status.', $this->_statusNonUpdated));
        } elseif ($this->_statusNonUpdated) {
            $this->messageManager->addErrorMessage(
                __('You cannot change the status of the order(s). Quantity %1', $this->_statusNonUpdated)
            );
        }
        if ($this->_statusUpdated) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) status have been updated.', $this->_statusUpdated)
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/*/');

        return $resultRedirect;
    }

    /**
     * @param Order $order
     * @param string $status
     * @param int $statusUpdated
     *
     * @return mixed
     * @throws Exception
     */
    protected function _saveOrderStatus($order, $status, $statusUpdated)
    {
        if ($order->getStatus() !== $status) {
            $order->addStatusToHistory($status, __('Order Status Changed'));
            $this->_orderResource->save($order);
            $statusUpdated++;
        }

        return $statusUpdated;
    }
}

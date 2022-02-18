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

namespace Mageplaza\MassOrderActions\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusColFact;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\MassOrderActions\Model\Config\Source\System\Actions;

/**
 * Class Data
 * @package Mageplaza\MassOrderActions\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH       = 'mpmassorderactions';
    const XML_PATH_INVOICE_ACTION  = 'invoice_action';
    const XML_PATH_SHIPMENT_ACTION = 'shipment_action';

    /**
     * @var OrderStatusColFact
     */
    protected $_orderStatusColFact;

    /**
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var Actions
     */
    protected $_massActions;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param OrderStatusColFact $orderStatusColFact
     * @param AuthorizationInterface $authorization
     * @param Actions $massAction
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        OrderStatusColFact $orderStatusColFact,
        AuthorizationInterface $authorization,
        Actions $massAction
    ) {
        $this->_orderStatusColFact = $orderStatusColFact;
        $this->_authorization      = $authorization;
        $this->_massActions        = $massAction;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param string $state
     *
     * @return array
     */
    public function getStatusByState($state)
    {
        $validStatus = [];

        /** @var Collection $statusCollection */
        $statusCollection = $this->_orderStatusColFact->create()
            ->joinStates()
            ->addStateFilter($state);
        foreach ($statusCollection as $status) {
            /** @var Status $status */
            $validStatus[] = $status->getStatus();
        }

        return $validStatus;
    }

    /**
     * @param string $code
     * @param null $storeId
     *
     * @return string
     */
    public function getInvoiceConfig($code = '', $storeId = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getModuleConfig(self::XML_PATH_INVOICE_ACTION . $code, $storeId);
    }

    /**
     * @param string $code
     * @param null $storeId
     *
     * @return string
     */
    public function getShipmentConfig($code = '', $storeId = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getModuleConfig(self::XML_PATH_SHIPMENT_ACTION . $code, $storeId);
    }

    /**
     * Add load tracking button html
     *
     * @param string $url
     *
     * @return string
     */
    public function getLoadTrackingHtml($url)
    {
        return '<button type="button" class="mp-load-tracking" id="mp-load-tracking"
                 onclick="mpMassOrderAction.loadTracking(event);this.disabled=true;">
                <span>' . __('Add Tracking Table') . '</span>
                <div class="mp-tracking-loader">
                    <div class="loader">
                        <img src="' . $url . '"
                           alt="' . __('Loading...') . '">
                    </div>
                </div></button>';
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     *
     * @return bool
     */
    public function isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        return $this->_moduleManager->isEnabled($moduleName);
    }

    /**
     * @return array
     */
    public function getActionsConfig()
    {
        $selectedActions = [];
        $actionPositions = [];
        $massActions     = $this->_massActions->toOptionArray();
        $actionConfig    = self::jsonDecode($this->getConfigGeneral('actions'));
        foreach ($massActions as $key => $action) {
            $selectedActions[$action['type']] = '1';
            $actionPositions[$action['type']] = $key;
        }

        if ($actionConfig) {
            $selectedActions = [];
            if (isset($actionConfig['selected'])) {
                $selectedActions = $actionConfig['selected'];
            }
            $actionPositions = $actionConfig['position'];
        }

        $result = [
            'selected_actions' => $selectedActions,
            'action_positions' => $actionPositions
        ];

        return $result;
    }

    /**
     * @param null $storeId
     *
     * @return array|mixed
     */
    public function getTrackingCarrierDefault($storeId = null)
    {
        return $this->getConfigGeneral('tracking_carrier_default', $storeId);
    }
}

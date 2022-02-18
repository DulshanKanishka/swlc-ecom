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

namespace Mageplaza\MassOrderActions\Plugin\Component;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Ui\Component\MassAction as ComponentMassAction;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use Mageplaza\MassOrderActions\Model\Config\Source\System\Actions;

/**
 * Class MassAction
 * @package Mageplaza\MassOrderActions\Plugin\Component
 */
class MassAction
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var CollectionFactory
     */
    protected $_orderStatusColFact;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Actions
     */
    protected $_massActions;

    /**
     * MassAction constructor.
     *
     * @param RequestInterface $request
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $url
     * @param HelperData $helperData
     * @param Actions $massAction
     */
    public function __construct(
        RequestInterface $request,
        CollectionFactory $collectionFactory,
        UrlInterface $url,
        HelperData $helperData,
        Actions $massAction
    ) {
        $this->_request            = $request;
        $this->_orderStatusColFact = $collectionFactory;
        $this->_url                = $url;
        $this->_helperData         = $helperData;
        $this->_massActions        = $massAction;
    }

    /**
     * @param ComponentMassAction $massAction
     */
    public function afterPrepare(ComponentMassAction $massAction)
    {
        if ($this->_helperData->isEnabled() && $this->_request->getFullActionName() === 'sales_order_index') {
            $orderStatusCol = $this->_orderStatusColFact->create();
            $actions        = [];
            /** @var array $orderStatusCol */
            foreach ($orderStatusCol as $orderStatus) {
                $actions[] = [
                    'type'  => $orderStatus->getStatus(),
                    'label' => $orderStatus->getLabel(),
                    'url'   => $this->_url->getUrl(
                        'mpmassorderactions/order/massStatus',
                        [
                            'state'  => $orderStatus->getState(),
                            'status' => $orderStatus->getStatus(),
                        ]
                    ),
                ];
            }
            $massActions       = $this->_massActions->toOptionArray();
            $additionalActions = [];
            foreach ($massActions as $action) {
                $additionalActions[$action['type']] = [
                    'component' => 'uiComponent',
                    'type'      => $action['type'],
                    'label'     => $action['label']
                ];
                if ($action['value'] === Actions::CHANGE_ORDER_STATUS) {
                    $additionalActions[$action['type']]['actions'] = $actions;
                }
                if ($action['value'] === Actions::SEND_TRACKING_INFORMATION) {
                    $sendTrackingUrl = $this->_url->getUrl('mpmassorderactions/order/massSendTracking');
                    $additionalActions[$action['type']]['url'] = $sendTrackingUrl;
                }
            }

            $config = $massAction->getData('config');
            if (isset($config['actions']) && $config['actions']) {
                $this->addMassActions(
                    $config,
                    $additionalActions,
                    $massAction
                );
            }
        }
    }

    /**
     * @param array $config
     * @param array $additionalActions
     * @param ComponentMassAction $massAction
     */
    public function addMassActions($config, $additionalActions, $massAction)
    {
        $isAllowInvoice  = $this->_helperData->isAllowedAction('Magento_Sales::invoice');
        $isAllowShip     = $this->_helperData->isAllowedAction('Magento_Sales::ship');
        $actionsConfig   = $this->_helperData->getActionsConfig();
        $selectedActions = $actionsConfig['selected_actions'];
        $actionPositions = $actionsConfig['action_positions'];
        foreach (array_keys($selectedActions) as $selectedAction) {
            if ((!$isAllowInvoice
                    && in_array($selectedAction, [Actions::CREATE_INVOICE, Actions::INVOICE_AND_SHIPMENT], true))
                || (!$isAllowShip
                    && in_array($selectedAction, [Actions::CREATE_SHIPMENT, Actions::INVOICE_AND_SHIPMENT], true))
            ) {
                continue;
            }
            $config['actions'][] = $additionalActions[$selectedAction];
        }
        uasort($actionPositions, function ($oldArray, $newArray) {
            return $oldArray - $newArray;
        });
        $count = 0;
        /** @var array $actionPositions */
        foreach ($actionPositions as $actionType => $position) {
            if (!isset($selectedActions[$actionType])) {
                continue;
            }
            /** @var $config mixed[][] */
            foreach ($config['actions'] as $key => $action) {
                if ($action['type'] === $actionType) {
                    $newPosition = max((int) $position, $count);
                    $this->moveElement($config['actions'], $key, $newPosition);
                    break;
                }
            }
            $count++;
        }
        $massAction->setData('config', $config);
    }

    /**
     * @param array $actions
     * @param int $oldPosition
     * @param int $newPosition
     */
    public function moveElement(&$actions, $oldPosition, $newPosition)
    {
        $outputArray = array_splice($actions, $oldPosition, 1);
        array_splice($actions, $newPosition, 0, $outputArray);
    }
}

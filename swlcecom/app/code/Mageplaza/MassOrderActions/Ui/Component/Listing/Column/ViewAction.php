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

namespace Mageplaza\MassOrderActions\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use Mageplaza\MassOrderActions\Model\Config\Source\System\Actions;

/**
 * Class ViewAction
 * @package Mageplaza\MassOrderActions\Ui\Component\Listing\Column
 */
class ViewAction extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Actions
     */
    protected $_massActions;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param HelperData $helperData
     * @param Actions $massActions
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        HelperData $helperData,
        Actions $massActions,
        OrderInterface $orderInterface,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_helperData = $helperData;
        $this->_massActions = $massActions;
        $this->orderInterface = $orderInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            /** @var  $dataSource array[][] */
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $item[$this->getData('name')] = $this->_helperData->isEnabled()
                        ? $this->checkAvailableActions($item) : $this->getActionList($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param array $item
     *
     * @return array
     */
    public function getActionList($item)
    {
        $listActions = [
            'view' => [
                'href' => $this->urlBuilder->getUrl(
                    $this->getData('config/viewUrlPath') ?: '#',
                    [$this->getData('config/urlEntityParamName') ?: 'entity_id' => $item['entity_id']]
                ),
                'label' => __('View')
            ]
        ];

        return $listActions;
    }

    /**
     * @param array $item
     *
     * @return array
     */
    public function checkAvailableActions($item)
    {
        $additionalActions = [];
        $allActions = $this->_massActions->toOptionArray();
        $actionsConfig = $this->_helperData->getActionsConfig();
        $selectedActions = $actionsConfig['selected_actions'];
        $isAllowInvoice = $this->_helperData->isAllowedAction('Magento_Sales::invoice');
        $isAllowShip = $this->_helperData->isAllowedAction('Magento_Sales::ship');
        $order = $this->orderInterface->load($item['entity_id']);

        foreach ($allActions as $action) {
            if (($isAllowInvoice
                    && $action['value'] === Actions::CREATE_INVOICE
                    && isset($selectedActions[$this->_massActions->toArray()['1']["type"]])
                    && $order->canInvoice())
                || ($isAllowShip
                    && $action['value'] === Actions::CREATE_SHIPMENT
                    && isset($selectedActions[$this->_massActions->toArray()['2']["type"]])
                    && $order->canShip())) {
                $additionalActions[$action['type']] = [
                    'label' => ($action['type'] === 'mp_create_invoice') ? __('Invoice') : __('Ship')
                ];
            }
        }

        $listActions = $this->getActionList($item);

        return array_merge($listActions, $additionalActions);
    }
}

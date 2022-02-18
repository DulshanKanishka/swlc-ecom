<?php
namespace Magenest\Xero\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magenest\Xero\Model\Log\Status as LogStatus;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Link
 * @package Magenest\Xero\Ui\Component\Listing\Columns
 */
class Scope extends Column
{
    protected $_storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ){
        $this->_storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!!$item['scope'] && !!$item['scope_id']) {
                    $item['scope_id'] = $this->_storeManager->getWebsite($item['scope_id'])->getName();
                } else {
                    $item['scope_id'] = "Default Website";
                }
            }
        }
        return $dataSource;
    }
}

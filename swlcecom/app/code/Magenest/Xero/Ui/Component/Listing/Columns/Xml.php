<?php
namespace Magenest\Xero\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class Link
 * @package Magenest\Xero\Ui\Component\Listing\Columns
 */
class Xml extends Column
{
    protected $_url;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $url,
        array $components = [],
        array $data = []
    ){
        $this->_url = $url;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item['xml_log_id']) {
                    $htmlLink = $this->_url->getUrl('xero/xml/index', ['xml_log_id' => $item['xml_log_id']]);
                    $item['xml_log_id'] = '<a href="' . $htmlLink .'" target="_blank">View XML Log</a>';
                }
            }
        }
        return $dataSource;
    }
}

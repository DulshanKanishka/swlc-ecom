<?php

namespace Magenest\AbandonedCart\Ui\Component\Listing\Column\Rule;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Escaper;

class ViewAction extends \Magento\Ui\Component\Listing\Columns\Column
{

    /** @var \Magento\Framework\UrlInterface $_urlBuilder */
    protected $_urlBuilder;

    /** @var Escaper $escaper */
    private $escaper;

    /**
     * ViewAction constructor.
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->escaper     = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $storeId = $this->context->getFilterParam('store_id');
            foreach ($dataSource['data']['items'] as &$item) {
                $title                                  = $this->escaper->escapeHtml($item['name']);
                $item[$this->getData('name')]['edit']   = [
                    'href'   => $this->_urlBuilder->getUrl(
                        'abandonedcart/rule/edit',
                        ['id' => $item['id'], 'store' => $storeId]
                    ),
                    'label'  => __('Edit'),
                    'hidden' => false,
                ];
                $item[$this->getData('name')]['delete'] = [
                    'href'    => $this->_urlBuilder->getUrl(
                        'abandonedcart/rule/delete',
                        ['id' => $item['id'], 'store' => $storeId]
                    ),
                    'label'   => __('Delete'),
                    'hidden'  => false,
                    'confirm' => [
                        'title'   => __('Delete %1', $title),
                        'message' => __('Are you sure you want to delete a %1 record?', $title)
                    ],
                ];
            }
        }
        return $dataSource;
    }
}
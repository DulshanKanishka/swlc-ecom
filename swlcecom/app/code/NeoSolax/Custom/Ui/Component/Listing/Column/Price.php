<?php

declare(strict_types=1);

namespace NeoSolax\Custom\Ui\Component\Listing\Column;


use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Price extends \Magento\Sales\Ui\Component\Listing\Column\Price
{
    protected $priceFormatter;

    private $currencyy;

    protected $_storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = [],
        Currency $currencyy = null,
        Currency $currency = null
    )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context,$uiComponentFactory,$priceFormatter,$components,$data,$currency);
        $this->currencyy = $currencyy ?: \Magento\Framework\App\ObjectManager::getInstance()->create(Currency::class);

    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $currencyCode = isset($item['base_currency_code']) ? $item['base_currency_code'] : null;
                $cCode = $this->getBaseCurrencyCode();
                $basePurchaseCurrency = $this->currencyy->load($cCode);
                $item[$this->getData('name')] = $basePurchaseCurrency
                    ->format($item[$this->getData('name')], [], false);
            }
        }

        return $dataSource;
    }
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }
}

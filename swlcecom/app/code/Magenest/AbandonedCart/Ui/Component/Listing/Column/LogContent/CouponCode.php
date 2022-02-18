<?php

namespace Magenest\AbandonedCart\Ui\Component\Listing\Column\LogContent;

class CouponCode extends \Magento\Ui\Component\Listing\Columns\Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['coupon_code'] = json_decode($item['coupon_code'], true);
            }
        }
        return $dataSource;
    }
}
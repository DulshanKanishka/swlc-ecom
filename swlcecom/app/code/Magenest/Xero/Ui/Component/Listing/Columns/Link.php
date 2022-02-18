<?php
namespace Magenest\Xero\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Link
 * @package Magenest\Xero\Ui\Component\Listing\Columns
 */
class Link extends Column
{
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
                if ($item['xero_id'] && $item['type'] && $item['xero_id'] != "NONE") {
                    $htmlLink = $this->renderLink($item['type'], $item['xero_id']);
                    $item['xero_id'] = '<a href="' . $htmlLink .'" target="_blank">View on Xero</a>';
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get Link in Xero
     *
     * @param $type
     * @param $xeroId
     * @return string
     */
    private function renderLink($type, $xeroId)
    {
        switch ($type) {
            case 'Contact':
                $url = 'https://go.xero.com/Contacts/View/';
                break;
            case 'Item':
                $url = 'https://go.xero.com/Accounts/Inventory/';
                break;
            case 'BankTransaction':
                $url = 'https://go.xero.com/Bank/ViewTransaction.aspx?bankTransactionID=';
                break;
            case 'InvoiceToInvoice':
            case 'OrderToInvoice':
                $url = 'https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID=';
                break;
            case 'CreditNote':
                $url = 'https://go.xero.com/AccountsReceivable/ViewCreditNote.aspx?creditNoteID=';
                break;
            case 'Payment':
                $url = 'https://go.xero.com/Bank/ViewTransaction.aspx?bankTransactionID=';
                break;
            default:
                $url = '';
        }
        $url .= $xeroId;

        return $url;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: thang
 * Date: 26/06/2018
 * Time: 16:02
 */

namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Synchronization;

/**
 * Class PreferenceSetting
 * @package Magenest\QuickBooksOnline\Model\Synchronization
 */
class PreferenceSetting extends Synchronization
{
    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPreferenceSetting()
    {
        $query = "SELECT * FROM Preferences";

        return $this->query($query);
    }

    /**
     * @return bool
     */
    public function getShippingAllow()
    {
        try {
            $query             = "SELECT SalesFormsPrefs.AllowShipping FROM Preferences";
            $preferenceSetting = $this->query($query);
            $allowShipping     = $preferenceSetting['SalesFormsPrefs']['AllowShipping'];

            return $allowShipping;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Model\Config\Backend;

class CheckDateTime extends \Magento\Framework\App\Config\Value
{
    /**
     * Plugin before Save
     *
     * @return $this
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function beforeSave()
    {
        if (!$this->validateDate($this->getValue())) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Please enter a valid date.'));
        }

        $this->setValue($this->getValue());
        return parent::beforeSave();
    }

    public function validateDate($date, $format = 'd-m-Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE
        // for any integer with any number of digits so changing
        //the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}
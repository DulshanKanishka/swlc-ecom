<?php

namespace Magenest\AbandonedCart\Model\BlackList;

interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR_INVALID_TITLE    = 'InvalidValueTITLE';
    const ERROR_MESSAGE_IS_EMPTY = 'EmptyMessage';

    /**
     * Initialize validator
     * @return $this
     */
    public function init($context);
}
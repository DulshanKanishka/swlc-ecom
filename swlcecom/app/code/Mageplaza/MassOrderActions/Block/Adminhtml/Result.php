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

namespace Mageplaza\MassOrderActions\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Element\Messages;

/**
 * Class Result
 * @package Mageplaza\MassOrderActions\Block\Adminhtml
 */
class Result extends Template
{
    /**
     * Validation messages.
     *
     * @var array
     */
    protected $_messages = ['error' => [], 'success' => [], 'notice' => []];

    /**
     * Add error message.
     *
     * @param string|array $message Error message
     *
     * @return $this
     */
    public function addError($message)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addError($row);
            }
        } else {
            $this->_messages['error'][] = $message;
        }

        return $this;
    }

    /**
     * Add notice message.
     *
     * @param string[]|string $message Message text
     *
     * @return $this
     */
    public function addNotice($message)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addNotice($row);
            }
        } else {
            $this->_messages['notice'][] = $message;
        }

        return $this;
    }

    /**
     * Add success message.
     *
     * @param string[]|string $message Message text
     *
     * @return $this
     */
    public function addSuccess($message)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addSuccess($row);
            }
        } else {
            $this->_messages['success'][] = $message;
        }

        return $this;
    }

    /**
     * Messages getter.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Messages rendered HTML getter.
     *
     * @return string
     */
    public function getMessagesHtml()
    {
        /** @var $messagesBlock Messages */
        $messagesBlock = $this->_layout->createBlock(Messages::class);

        /**
         * @var string $priority
         * @var array[] $messages
         */
        foreach ($this->_messages as $priority => $messages) {
            $method = "add{$priority}";

            foreach ($messages as $message) {
                $messagesBlock->{$method}($message);
            }
        }

        return $messagesBlock->toHtml();
    }
}

<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Synchronization;
use Magento\Config\Model\Config as ConfigModel;
use Magenest\QuickBooksOnline\Model\PaymentMethodsFactory;
use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Log;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Action\Context;

/**
 * Class PaymentMethods
 * @package Magenest\QuickBooksOnline\Model\Sync
 */
class PaymentMethods extends Synchronization
{
    /**
     * @var PaymentMethodsFactory
     */
    protected $_paymentMethodsFactory;

    /**
     * PaymentMethods constructor.
     *
     * @param Client $client
     * @param Log $log
     * @param PaymentMethodsFactory $paymentMethodsFactory
     * @param Context $context
     */
    public function __construct(
        Client $client,
        Log $log,
        PaymentMethodsFactory $paymentMethodsFactory,
        Context $context
    ) {
        parent::__construct($client, $log, $context);
        $this->_paymentMethodsFactory = $paymentMethodsFactory;
        $this->type = 'paymentmethod';
    }

    /**
     * @param $title
     * @param $code
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function sync($title, $code)
    {
        $data = [
            'title' => $title,
            'payment_code' => $code
        ];

        $params = [
            'Name' => $title,
            'Active' => true
        ];
        $check = $this->checkPaymentMethods($title);
        if (!empty($check)) {
            $data = array_replace_recursive($data, $check);
            $data['qbo_id'] = $check['Id'];
            $this->addLog($code, $data['qbo_id'],  'The Payment Method already exists in QuickBooks Online', 'skip'  );
        } else {
            try {
                $response = $this->sendRequest(\Zend_Http_Client::POST, 'paymentmethod', $params);
                if (isset($response['PaymentMethod']['Id'])) {
                    $data = array_replace_recursive($data, $response['PaymentMethod']);
                    $data['qbo_id'] = $response['PaymentMethod']['Id'];
                    $this->addLog($code, $data['qbo_id'] );
//                    $this->addLog($code,$response['PaymentMethod']['Id']);

                } else {
                    throw new LocalizedException(
                        __('We can\'t sync the Payment Method with name like "%1"', $title)
                    );
                }
            } catch (LocalizedException $e) {
                $this->addLog($code, null, $e->getMessage());
            }
        }
        try {
            $model = $this->_paymentMethodsFactory->create();
            $model->loadByCode($code);
            $model->addData($data);
            $model->save();
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('We can\'t save the mapping with code like '. $title)
            );
        }
    }

    /**
     * Check PaymentMethod
     *
     * @param  $name
     * @return array
     */
    public function checkPaymentMethods($name)
    {
        $query = "SELECT Id, SyncToken FROM paymentmethod WHERE Name='{$name}'";

        return $this->query($query);
    }
}

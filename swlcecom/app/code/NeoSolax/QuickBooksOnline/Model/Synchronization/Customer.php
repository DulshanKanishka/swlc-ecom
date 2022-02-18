<?php

namespace NeoSolax\QuickBooksOnline\Model\Synchronization;

use Magento\Framework\Exception\LocalizedException;

class Customer extends \Magenest\QuickBooksOnline\Model\Synchronization\Customer
{

    public function syncGuest($bill, $ship, $update = false)
    {
        try {

            $firstName = trim($bill->getFirstname());
            $lastName  = trim($bill->getLastname());
//            $qboId     = $bill->getQboId();
//            if (empty($qboId)) {
            $email    = trim($bill->getEmail());
            $customer = $this->checkCustomerByEmail($email);
//            } else {
//                $customer = $this->checkCustomerByQbIdAndEmail($qboId, trim($bill->getEmail()));
//            }
            if (!empty($customer) and $update == false) {
                return $customer['Id'];
            }
            $suffix             = time();
            $displayName        = $firstName . ' ' . $lastName . '     Ref- ' . $suffix;
            $params             = [
                'DisplayName'      => $displayName,
                'GivenName'        => $firstName,
                'FamilyName'       => $lastName,
                'Suffix'           => $suffix,
                'PrimaryEmailAddr' => ['Address' => $bill->getEmail()],
                'PrimaryPhone'     => ['FreeFormNumber' => mb_substr(str_replace(' ', '', $bill->getTelephone()), 0, 30)],
                'CompanyName'      => $bill->getCompany(),
            ];
            $params['BillAddr'] = $this->getAddressNeo($bill);
            if ($ship !== null) {
                $params['ShipAddr'] = $this->getAddressNeo($ship);
            }
            $response = $this->sendRequest(\Zend_Http_Client::POST, 'customer', $params);

            if (isset($response['Customer']['Id'])) {
                $qboId = $response['Customer']['Id'];
                $this->addLog(self::PREFIX_GUEST . $bill->getParentId(), $qboId);
            }

            if (is_array($response)) {
                return $response['Customer']['Id'];
            } else {
                throw new LocalizedException(__('Can\'t sync guest to QuickBooks Online'));
            }
        } catch (\Exception $exception) {
            $this->addLog(self::PREFIX_GUEST . $bill->getParentId(), null, $exception->getMessage());
            throw new LocalizedException(__('Can\'t sync guest to QuickBooks Online'));
        }
    }
    protected function prepareParams()
    {
        $model = $this->getModel();

        $givenName  = mb_substr(trim($model->getFirstname()), 0, 25);
        $familyName = mb_substr(trim($model->getLastname()), 0, 100);
        $suffix     = mb_substr((string)$this->getModel()->getId(), 0, 10);

        $params = [
            'DisplayName'      => $givenName . ' ' . $familyName . '     Ref- ' . $suffix,
            'GivenName'        => $givenName,
            'FamilyName'       => $familyName,
            'Suffix'           => $suffix,
            'PrimaryEmailAddr' => ['Address' => mb_substr($model->getEmail(), 0, 100)]
        ];
        $this->setParameter($params);

        // set currency
        $this->setCurrencyParams();

        // set billing address
        $this->setBillingAddressParams();

        // set shipping address
        $this->setShippingAddressParams();

        return $this;
    }
    public function setBillingAddressParams()
    {
        $billAddress = $this->getModel()->getDefaultBillingAddress();
        if ($billAddress) {
            $params = [
                'PrimaryPhone' => ['FreeFormNumber' => mb_substr(str_replace(' ', '', $billAddress->getTelephone()), 0, 30)],
                'CompanyName'  => mb_substr($billAddress->getCompany(), 0, 100),
                'BillAddr'     => $this->getAddressNeo($billAddress)
            ];
        } else {
            $params = [
                'PrimaryPhone' => ['FreeFormNumber' => ''],
                'CompanyName'  => '',
                'BillAddr'     => [
                    'Line1'                  => '',
                    'Line2'                  => '',
                    'Line3'                  => '',
                    'City'                   => '',
                    'Country'                => '',
                    'CountrySubDivisionCode' => '',
                    'PostalCode'             => ''
                ]
            ];
        }
        $this->setParameter($params);

        return $this;
    }
    public function setShippingAddressParams()
    {
        $shipAddress = $this->getModel()->getDefaultShippingAddress();
        if ($shipAddress) {
            $params = [
                'ShipAddr' => $this->getAddressNeo($shipAddress)
            ];
        } else {
            $params = [
                'ShipAddr' => [
                    'Line1'                  => '',
                    'Line2'                  => '',
                    'Line3'                  => '',
                    'City'                   => '',
                    'Country'                => '',
                    'CountrySubDivisionCode' => '',
                    'PostalCode'             => ''
                ]
            ];
        }
        $this->setParameter($params);

        return $this;
    }
    protected function getAddressNeo($address)
    {
        return [
            'Line1'                  => $address->getStreetLine(1),
            'Line2'                => $address->getStreetLine(2),
            'Line3'                 => $address->getTelephone(),
            'City'                   => $address->getCity(),
            'Country'                => $address->getCountryId(),
            'CountrySubDivisionCode' => $address->getRegion(),
            'PostalCode'             => $address->getPostcode()
        ];
    }
}

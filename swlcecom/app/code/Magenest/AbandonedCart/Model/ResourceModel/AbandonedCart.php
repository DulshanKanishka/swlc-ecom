<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

use Magento\Framework\DB\Select;

class AbandonedCart extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $currentDbTime = null;

    public function _construct()
    {
        $this->_init('magenest_abacar_list', 'id');
    }

    public function getUpperLimit($modify)
    {
        $modify = '-' . $modify . ' minutes';
        $now    = new \DateTime($this->getCurrentTime());

        $now->modify($modify);

        $upperLimit = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        return $upperLimit;
    }

    public function getCurrentTime()
    {
        if (is_null($this->currentDbTime)) {
            $row                 = $this->getConnection()->fetchRow('select now()');
            $this->currentDbTime = array_pop($row);
        }

        return $this->currentDbTime;
    }

    public function getLowerLimit($modify)
    {
        $now    = new \DateTime($this->getCurrentTime());
        $modify = $modify <= 240 ? 480 : $modify * 2;
        $modify = '-' . $modify . ' minutes';
        $now->modify($modify);

        $lowerLimit = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        return $lowerLimit;
    }

    public function getAbandonedCartForInsertOperation($minuteLimit, $tableName = 'magenest_abacar_list')
    {
        $upperLimit         = $this->getUpperLimit($minuteLimit);
        $lowerLimit         = $this->getLowerLimit($minuteLimit);
        $mainTable          = $this->getTable('quote');
        $abandonedCartTable = $this->getTable($tableName);
        $select             = $this->getConnection()->select()->from(
            ['m' => $mainTable]
        )->joinLeft(
            ['a' => $abandonedCartTable],
            'm.entity_id = a.quote_id'
        )->where(
            'a.quote_id is null AND m.is_active = 1 AND m.customer_id  is not null AND m.customer_email is not null  AND m.items_count != 0'
        )->where(
            '(m.updated_at < "' . $upperLimit . '" AND m.updated_at > "' . $lowerLimit . '")'
            . ' OR (m.created_at < "' . $upperLimit . '"  AND m.updated_at ="0000-00-00 00:00:00")'
        )->__toString();
        $results            = $this->getConnection()->fetchAll($select);
        return $results;
    }

    public function getAbandonedCartOfMemberForTestCampaign($minuteLimit, $tableName = 'magenest_abacar_list')
    {
        $upperLimit         = $this->getUpperLimit($minuteLimit);
        $lowerLimit         = $this->getLowerLimit($minuteLimit);
        $mainTable          = $this->getTable('quote');
        $abandonedCartTable = $this->getTable($tableName);
        $select             = $this->getConnection()->select()->from(
            ['m' => $mainTable]
        )->joinLeft(
            ['a' => $abandonedCartTable],
            'm.entity_id = a.quote_id'
        )->where(
            'm.is_active = 1 AND m.customer_id  is not null AND m.customer_email is not null  AND m.items_count != 0'
        )->where(
            '(m.updated_at < "' . $upperLimit . '" AND m.updated_at > "' . $lowerLimit . '")'
            . ' OR (m.created_at < "' . $upperLimit . '"  AND m.updated_at ="0000-00-00 00:00:00")'
        )->__toString();
        $results            = $this->getConnection()->fetchAll($select);
        return $results;
    }

    public function getAbandonedCartOfGuest($minuteLimit, $tableName = 'magenest_abacar_list')
    {
        $upperLimit         = $this->getUpperLimit($minuteLimit);
        $lowerLimit         = $this->getLowerLimit($minuteLimit);
        $mainTable          = $this->getTable('quote');
        $abandonedCartTable = $this->getTable($tableName);
        $guestTable         = $this->getTable('magenest_abacar_guest_capture');

        $select  = $this->getConnection()->select()->from(
            ['m' => $mainTable]
        )->join(
            ['a' => $guestTable],
            'm.entity_id = a.quote_id'
        )->joinLeft(
            ['b' => $abandonedCartTable],
            'm.entity_id = b.quote_id'
        )->where(
            'a.quote_id is not null AND b.quote_id is null AND m.is_active = 1 AND m.customer_id  is  null  AND m.items_count != 0'
        )->where(
            '(m.updated_at < "' . $upperLimit . '" AND m.updated_at > "' . $lowerLimit . '")' .
            ' OR (m.created_at > "' . $lowerLimit . '" AND m.created_at < "' . $upperLimit . '"  AND m.updated_at ="0000-00-00 00:00:00")'
        )->columns(
            ['entity_id', 'email' => 'a.email', 'subtotal']
        )->__toString();
        $results = $this->getConnection()->fetchAll($select);
        return $results;
    }

    public function getAllTestCampaign($id)
    {
        $mainTable = $this->getTable('magenest_abacar_testcampaign');
        $select    = $this->getConnection()->select()->from(
            ['m' => $mainTable]
        )->where("m.rule_id = $id");
        $results   = $this->getConnection()->fetchAll($select);
        $arr       = [];
        if (count($results)) {
            foreach ($results as $result) {
                $arr[] = $result['quote_id'];
            }
        }
        return $arr;
    }

    public function getAbandonedCartOfGuestForTestCampaign($minuteLimit, $tableName = 'magenest_abacar_list')
    {
        $upperLimit         = $this->getUpperLimit($minuteLimit);
        $lowerLimit         = $this->getLowerLimit($minuteLimit);
        $mainTable          = $this->getTable('quote');
        $abandonedCartTable = $this->getTable($tableName);
        $guestTable         = $this->getTable('magenest_abacar_guest_capture');

        $select  = $this->getConnection()->select()->from(
            ['m' => $mainTable]
        )->join(
            ['a' => $guestTable],
            'm.entity_id = a.quote_id'
        )->joinLeft(
            ['b' => $abandonedCartTable],
            'm.entity_id = b.quote_id'
        )->where(
            'a.quote_id is not null AND m.is_active = 1 AND m.customer_id  is  null  AND m.items_count != 0'
        )->where(
            '(m.updated_at < "' . $upperLimit . '" AND m.updated_at > "' . $lowerLimit . '")' .
            ' OR (m.created_at > "' . $lowerLimit . '" AND m.created_at < "' . $upperLimit . '"  AND m.updated_at ="0000-00-00 00:00:00")'
        )->columns(
            ['entity_id', 'email' => 'a.email', 'subtotal']
        )->__toString();
        $results = $this->getConnection()->fetchAll($select);
        return $results;
    }
}
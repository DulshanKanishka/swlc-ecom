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
 * @category  Mageplaza
 * @package   Mageplaza_MassOrderActions
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\MassOrderActions\Test\Unit\Model\Source\System;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mageplaza\MassOrderActions\Model\Config\Source\System\Actions;
use PHPUnit\Framework\TestCase;

/**
 * Class ActionsTest
 * @package Mageplaza\MassOrderActions\Test\Unit\Model\Source\System
 */
class ActionsTest extends TestCase
{
    /**
     * @var Actions
     */
    protected $model;

    protected function setUp()
    {
        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(
            Actions::class
        );
    }

    /**
     * Test to actions option array
     */
    public function testToOptionArray()
    {
        $expectResult = [
            [
                'value' => 1,
                'label' => __('Change Order Status')
            ],
            [
                'value' => 2,
                'label' => __('Create Invoice')
            ],
            [
                'value' => 3,
                'label' => __('Create Shipment')
            ],
            [
                'value' => 4,
                'label' => __('Create Invoice and Shipment')
            ],
            [
                'value' => 5,
                'label' => __('Add Order Comments')
            ],
        ];
        $actualResult = $this->model->toOptionArray();
        $this->assertEquals($expectResult, $actualResult);
    }
}

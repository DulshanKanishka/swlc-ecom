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

namespace Mageplaza\MassOrderActions\Test\Unit\Helper;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusColFact;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class DataTest
 * @package Mageplaza\MassOrderActions\Test\Unit\Helper
 */
class DataTest extends TestCase
{
    /**
     * @var OrderStatusColFact|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_orderSttColFactMock;

    /**
     * @var AuthorizationInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_authorizationMock;

    /**
     * @var HelperData
     */
    protected $model;

    protected function setUp()
    {
        $this->_orderSttColFactMock = $this->getMockBuilder(OrderStatusColFact::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->_authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(
            HelperData::class,
            [
                '_orderStatusColFact' => $this->_orderSttColFactMock,
                '_authorization' => $this->_authorizationMock
            ]
        );
    }

    /**
     * Test get load tracking html
     */
    public function testGetLoadTrackingHtml()
    {
        $url = 'sample_url';
        $expectResult = '<button type="button" class="mp-load-tracking" id="mp-load-tracking"
                 onclick="mpMassOrderAction.loadTracking(event);this.disabled=true;">
                <span>Add Tracking Table</span>
                <div class="mp-tracking-loader">
                    <div class="loader">
                        <img src="sample_url"
                             alt="Loading...">
                    </div>
                </div></button>';
        $actualResult = $this->model->getLoadTrackingHtml($url);

        $this->assertEquals($expectResult, $actualResult);
    }
}

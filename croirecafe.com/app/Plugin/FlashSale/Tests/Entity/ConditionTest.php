<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Tests\Entity;

use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Entity\Condition;

abstract class ConditionTest extends EccubeTestCase
{
    /**
     * @var Condition
     */
    protected $condition;

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid_Json($expected)
    {
        $actual = $this->condition->rawData(json_encode($expected));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid($expected)
    {
        $this->condition->setId($expected['id']);
        $this->condition->setOperator($expected['operator']);
        $this->condition->setValue($expected['value']);
        $actual = $this->condition->rawData();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [];
    }
}

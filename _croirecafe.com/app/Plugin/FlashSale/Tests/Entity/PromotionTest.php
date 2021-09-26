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
use Plugin\FlashSale\Entity\Promotion;

abstract class PromotionTest extends EccubeTestCase
{
    /**
     * @var Promotion
     */
    protected $promotion;

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid_Json($expected)
    {
        $actual = $this->promotion->rawData(json_encode($expected));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid($expected)
    {
        $this->promotion->setId($expected['id']);
        $this->promotion->setValue($expected['value']);
        $actual = $this->promotion->rawData();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [];
    }
}

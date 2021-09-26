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

namespace Plugin\FlashSale\Tests\Service\Operator;

use Eccube\Entity\Order;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Tests\Service\AbstractOperatorTest;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Tests\Entity\Condition as ConditionTest;

class AllOperatorTest extends AbstractOperatorTest
{
    /**
     * @var Operator\AllOperator
     */
    protected $operator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->operator = new Operator\AllOperator();
    }

    public function testMatch_Invalid()
    {
        $this->assertEquals(false, $this->operator->match(null, new \stdClass()));
        $this->assertEquals(false, $this->operator->match([new \stdClass()], new \stdClass()));
    }

    /**
     * @param $Conditions
     * @param $Order
     * @param $expected
     * @dataProvider dataProvider_testMatch_Valid_CartRule
     * @dataProvider dataProvider_testMatch_Valid_ProductClassRule
     */
    public function testMatch_Valid($Conditions, $Order, $expected)
    {
        /** @var Condition $Condition */
        foreach ($Conditions as $Condition) {
            $Condition->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));
        }

        $actual = $this->operator->match($Conditions, $Order);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testMatch_Valid_CartRule($testMethod = null, $orderSubtotal = 12345)
    {
        $data = [];

        // Cart
        $tmp = ConditionTest\CartTotalConditionTest::dataProvider_testMatch_Valid(null, $orderSubtotal, 0);
        $conditionDataSet = [];
        foreach ($tmp as $tmpData) {
            list($conditionData, , $expected) = $tmpData;
            $condition = new Condition\CartTotalCondition();
            $condition->setId(rand());
            $condition->setOperator($conditionData[0]);
            $condition->setValue($conditionData[1]);
            $conditionDataSet[] = [$condition, $expected];
        }
        $Order = new Order();
        $Order->setSubtotal($orderSubtotal);
        for ($i = 0; $i < count($conditionDataSet); $i++) {
            for ($j = $i; $j < count($conditionDataSet); $j++) {
                list($conditionI, $expectedI) = $conditionDataSet[$i];
                list($conditionJ, $expectedJ) = $conditionDataSet[$j];
                $data[] = [[$conditionI, $conditionJ], $Order, $expectedI && $expectedJ];
            }
        }

        return $data;
    }

    public static function dataProvider_testMatch_Valid_ProductClassRule($testMethod = null, $productClassId = 1, $categoryId = 2)
    {
        $data = [];

        // Product Class
        $tmp = ConditionTest\ProductClassIdConditionTest::dataProvider_testMatch_Valid(null, $productClassId);
        $conditionDataSet = [];
        foreach ($tmp as $tmpData) {
            list($conditionData, , $expected) = $tmpData;
            $condition = new Condition\ProductClassIdCondition();
            $condition->setId(rand());
            $condition->setOperator($conditionData[0]);
            $condition->setValue($conditionData[1]);
            $conditionDataSet[] = [$condition, $expected];
        }
        $tmp = ConditionTest\ProductCategoryIdConditionTest::dataProvider_testMatch_Valid(null, $categoryId);
        foreach ($tmp as $tmpData) {
            list($conditionData, , $expected) = $tmpData;
            $condition = new Condition\ProductCategoryIdCondition();
            $condition->setId(rand());
            $condition->setOperator($conditionData[0]);
            $condition->setValue($conditionData[1]);
            $conditionDataSet[] = [$condition, $expected];
        }
        $ProductCategory = new ProductCategory();
        $ProductCategory->setCategoryId($categoryId);
        $Product = new Product();
        $Product->addProductCategory($ProductCategory);
        $ProductClass = new ProductClass();
        $ProductClass->setPropertiesFromArray(['id' => $productClassId]);
        $ProductClass->setProduct($Product);
        for ($i = 0; $i < count($conditionDataSet); $i++) {
            for ($j = $i; $j < count($conditionDataSet); $j++) {
                list($conditionI, $expectedI) = $conditionDataSet[$i];
                list($conditionJ, $expectedJ) = $conditionDataSet[$j];
                $data[] = [[$conditionI, $conditionJ], $ProductClass, $expectedI && $expectedJ];
            }
        }

        return $data;
    }
}

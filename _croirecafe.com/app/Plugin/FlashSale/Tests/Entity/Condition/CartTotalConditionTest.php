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

namespace Plugin\FlashSale\Tests\Entity\Condition;

use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Exception\ConditionException;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Tests\Entity\ConditionTest;
use Plugin\FlashSale\Tests\Service\Operator as OperatorTest;

/**
 * Class ProductClassIdConditionTest
 */
class CartTotalConditionTest extends ConditionTest
{
    /**
     * @var CartTotalCondition
     */
    protected $condition;

    protected $qb;

    public function setUp()
    {
        parent::setUp();
        $this->condition = new CartTotalCondition();
        $this->condition->setOperatorFactory($this->container->get(OperatorFactory::class));
        $this->qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [
            [['id' => 1, 'type' => 'condition_cart_total', 'operator' => 'operator_equal', 'value' => 10]],
            [['id' => 2, 'type' => 'condition_cart_total', 'operator' => 'operator_greater_than', 'value' => 20]],
            [['id' => 3, 'type' => 'condition_cart_total', 'operator' => 'operator_less_than', 'value' => 30]],
        ];
    }

    public function testGetOperatorTypes()
    {
        $this->expected = [
            Operator\GreaterThanOperator::TYPE,
            Operator\EqualOperator::TYPE,
            Operator\LessThanOperator::TYPE,
        ];
        $this->actual = $this->condition->getOperatorTypes();
        $this->verify();
    }

    public function testMatch_Invalid()
    {
        $actual = $this->condition->match(new \stdClass());
        $this->assertEquals(false, $actual);
    }

    /**
     * @param $conditionData
     * @param $object
     * @param $expected
     *  @dataProvider dataProvider_testMatch_Valid
     */
    public function testMatch_Valid($conditionData, $object, $expected)
    {
        list($conditionOperator, $conditionValue) = $conditionData;
        $this->condition->setId(rand());
        $this->condition->setValue($conditionValue);
        $this->condition->setOperator($conditionOperator);

        $actual = $this->condition->match($object);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testMatch_Valid($testMethod = null, $orderSubtotal = 12345, $itemFlashSaleDiscount = 2)
    {
        $data = [];
        foreach (OperatorTest\EqualOperatorTest::dataProvider_testMatch($orderSubtotal) as $operatorData) {
            list($conditionValue, $orderSubtotal, $expected) = $operatorData;
            $Order = new Order();
            $Order->setSubtotal($orderSubtotal);

            $data[] = [['operator_equal', (string) $conditionValue], $Order, $expected];
        }

        foreach (OperatorTest\GreaterThanOperatorTest::dataProvider_testMatch($orderSubtotal - $itemFlashSaleDiscount) as $operatorData) {
            list($conditionValue, $orderSubtotal, $expected) = $operatorData;
            $Cart = new Cart();
            $Cart->setTotal($orderSubtotal);
            $ProductClass = new ProductClass();
            $ProductClass->addFlashSaleDiscount(rand(), $itemFlashSaleDiscount);
            $CartItem = new CartItem();
            $CartItem->setProductClass($ProductClass);
            $Cart->getCartItems()->add($CartItem);

            $data[] = [['operator_greater_than', (string) $conditionValue], $Cart, $expected];
        }

        foreach (OperatorTest\LessThanOperatorTest::dataProvider_testMatch($orderSubtotal - ($itemFlashSaleDiscount * 2)) as $operatorData) {
            list($conditionValue, $orderSubtotal, $expected) = $operatorData;
            $Order = new Order();
            $Order->setSubtotal($orderSubtotal);
            $ProductClass = new ProductClass();
            $ProductClass->addFlashSaleDiscount(rand(), $itemFlashSaleDiscount);
            $OrderItem = new OrderItem();
            $OrderItem->setQuantity(2);
            $OrderItem->setProductClass($ProductClass);
            $Order->getOrderItems()->add($OrderItem);
            $data[] = [['operator_less_than', (string) $conditionValue], $Order, $expected];
        }

        return $data;
    }

    /**
     * @param $operatorRule
     * @param $operatorCondition
     * @throws ConditionException
     * @dataProvider dataProvider_testCreateQueryBuilder_Exception
     */
    public function testCreateQueryBuilder_Exception($operatorRule, $operatorCondition)
    {
        $this->expectException(ConditionException::class);
        $this->condition->createQueryBuilder($this->qb, $operatorRule, $operatorCondition);
    }

    public function dataProvider_testCreateQueryBuilder_Exception()
    {
        return [
            [new Operator\AllOperator(), new Operator\InOperator()],
            [new Operator\AllOperator(), new Operator\NotInOperator()],
        ];
    }

    /**
     * @param $operatorRule
     * @param $operatorCondition
     * @throws ConditionException
     * @dataProvider dataProvider_testCreateQueryBuilder_Valid
     */
    public function testCreateQueryBuilder_Valid($operatorRule, $operatorCondition)
    {
        $qb = $this->condition->createQueryBuilder($this->qb, $operatorRule, $operatorCondition);
        $this->assertEquals($this->qb, $qb);
    }

    public function dataProvider_testCreateQueryBuilder_Valid()
    {
        return [
            [new Operator\AllOperator(), new Operator\EqualOperator()],
            [new Operator\AllOperator(), new Operator\GreaterThanOperator()],
            [new Operator\AllOperator(), new Operator\LessThanOperator()],
        ];
    }
}

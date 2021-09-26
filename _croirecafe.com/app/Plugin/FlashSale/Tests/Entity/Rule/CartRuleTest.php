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

namespace Plugin\FlashSale\Tests\Entity\Rule;

use Plugin\FlashSale\Entity\Discount;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Condition as Condition;
use Plugin\FlashSale\Entity\Promotion as Promotion;
use Plugin\FlashSale\Tests\Entity\Condition as ConditionTest;
use Plugin\FlashSale\Tests\Service\Operator as OperatorTest;
use Plugin\FlashSale\Tests\Entity\Promotion as PromotionTest;
use Plugin\FlashSale\Tests\Entity\RuleTest;

/**
 * Class ProductClassRuleTest
 */
class CartRuleTest extends RuleTest
{
    /**
     * @var CartRule
     */
    protected $rule;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->rule = new CartRule();
    }

    public static function dataProvider_testRawData_Valid()
    {
        $data = [];
        $promotionDataSet = PromotionTest\CartTotalPercentPromotionTest::dataProvider_testRawData_Valid();
        foreach ($promotionDataSet as $promotionData) {
            $dataCase = [
                'id' => rand(),
                'type' => 'rule_cart',
                'operator' => array_rand(['operator_all' => 1, 'operator_or' => 1]),
                'promotion' => $promotionData[0],
                'conditions' => [],
            ];
            $conditionDataSet = ConditionTest\CartTotalConditionTest::dataProvider_testRawData_Valid();
            foreach ($conditionDataSet as $conditionData) {
                $dataCase['conditions'][] = $conditionData[0];
            }
            $data[] = [$dataCase];
        }

        return $data;
    }

    public function testGetOperatorTypes()
    {
        $this->expected = [
            Operator\OrOperator::TYPE,
            Operator\AllOperator::TYPE,
        ];
        $this->actual = $this->rule->getOperatorTypes();
        $this->verify();
    }

    public function testGetConditionTypes()
    {
        $this->expected = [
            Condition\CartTotalCondition::TYPE,
        ];
        $this->actual = $this->rule->getConditionTypes();
        $this->verify();
    }

    public function testGetPromotionTypes()
    {
        $this->expected = [
            Promotion\CartTotalPercentPromotion::TYPE,
            Promotion\CartTotalAmountPromotion::TYPE,
        ];
        $this->actual = $this->rule->getPromotionTypes();
        $this->verify();
    }

    public function testMatch_Invalid()
    {
        $this->assertEquals(false, $this->rule->match(new \stdClass()));
    }

    /**
     * @param $ruleData
     * @param $Conditions
     * @param $object
     * @param $expected
     * @dataProvider dataProvider_testMatch_Valid
     */
    public function testMatch_Valid($ruleData, $Conditions, $object, $expected)
    {
        $this->rule->setId(rand());
        $this->rule->setOperator($ruleData[0]);
        $this->rule->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));

        foreach ($Conditions as $Condition) {
            $Condition->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));
            $this->rule->addConditions($Condition);
        }

        $actual = $this->rule->match($object);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testMatch_Valid($testMethod = null, $orderSubtotal = 12345)
    {
        $data = [];
        $operatorDataSet = OperatorTest\AllOperatorTest::dataProvider_testMatch_Valid_CartRule(null, $orderSubtotal);
        foreach ($operatorDataSet as $operatorData) {
            list($Conditions, $object, $expected) = $operatorData;
            $data[] = [['operator_all'], $Conditions, $object, $expected];
        }

        $operatorDataSet = OperatorTest\OrOperatorTest::dataProvider_testMatch_Valid_CartRule(null, $orderSubtotal);
        foreach ($operatorDataSet as $operatorData) {
            list($Conditions, $object, $expected) = $operatorData;
            $data[] = [['operator_or'], $Conditions, $object, $expected];
        }

        return $data;
    }

    public function testGetDiscount_Invalid()
    {
        $this->rule->setId(rand());
        $actual = $this->rule->getDiscount(new \stdClass());
        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals(0, $actual->getValue());
        $this->assertEquals($this->rule->getId(), $actual->getRuleId());
    }

    /**
     * @param $Promotion
     * @param $object
     * @param $expectedValue
     * @dataProvider dataProvider_testGetDiscount_Valid
     */
    public function testGetDiscount_Valid($Promotion, $object, $expectedValue)
    {
        $this->rule->setId(rand());
        $this->rule->setPromotion($Promotion);
        $actual = $this->rule->getDiscount($object);

        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals($expectedValue, $actual->getValue());
        $this->assertEquals($this->rule->getId(), $actual->getRuleId());
    }

    public function dataProvider_testGetDiscount_Valid($testMethod = null, $orderSubtotal = 12345)
    {
        $data = [];
        foreach (PromotionTest\CartTotalAmountPromotionTest::dataProvider_testGetDiscount_Valid() as $promotionData) {
            list($promotionValue, $Order, $promotionExpected) = $promotionData;

            $Promotion = new Promotion\CartTotalAmountPromotion();
            $Promotion->setId(rand());
            $Promotion->setValue($promotionValue);

            $data[] = [$Promotion, $Order, $promotionExpected];
        }

        foreach (PromotionTest\CartTotalPercentPromotionTest::dataProvider_testGetDiscount_Valid(null, $orderSubtotal) as $promotionData) {
            list($promotionValue, $Order, $promotionExpected) = $promotionData;

            $Promotion = new Promotion\CartTotalPercentPromotion();
            $Promotion->setId(rand());
            $Promotion->setValue($promotionValue);

            $data[] = [$Promotion, $Order, $promotionExpected];
        }

        return $data;
    }
}

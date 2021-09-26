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

use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Condition as Condition;
use Plugin\FlashSale\Entity\Promotion as Promotion;
use Plugin\FlashSale\Tests\Entity\RuleTest;
use Plugin\FlashSale\Entity\Discount;
use Plugin\FlashSale\Tests\Service\Operator as OperatorTest;
use Plugin\FlashSale\Tests\Entity\Promotion as PromotionTest;
use Plugin\FlashSale\Tests\Entity\Condition as ConditionTest;

/**
 * Class ProductClassRuleTest
 */
class ProductClassRuleTest extends RuleTest
{
    /**
     * @var ProductClassRule
     */
    protected $rule;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->rule = new ProductClassRule();
    }

    public static function dataProvider_testRawData_Valid()
    {
        $data = [];
        $promotionDataSet = PromotionTest\ProductClassPriceAmountPromotionTest::dataProvider_testRawData_Valid();
        foreach ($promotionDataSet as $promotionData) {
            $dataCase = [
                'id' => rand(),
                'type' => 'rule_product_class',
                'operator' => array_rand(['operator_all' => 1, 'operator_or' => 1]),
                'promotion' => $promotionData[0],
                'conditions' => [],
            ];
            $conditionDataSet = ConditionTest\ProductClassIdConditionTest::dataProvider_testRawData_Valid();
            foreach ($conditionDataSet as $conditionData) {
                $dataCase['conditions'][] = $conditionData[0];
            }
            $data[] = [$dataCase];
        }
        $promotionDataSet = PromotionTest\ProductClassPricePercentPromotionTest::dataProvider_testRawData_Valid();
        foreach ($promotionDataSet as $promotionData) {
            $dataCase = [
                'id' => rand(),
                'type' => 'rule_product_class',
                'operator' => array_rand(['operator_all' => 1, 'operator_or' => 1]),
                'promotion' => $promotionData[0],
                'conditions' => [],
            ];
            $conditionDataSet = ConditionTest\ProductCategoryIdConditionTest::dataProvider_testRawData_Valid();
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
            Condition\ProductClassIdCondition::TYPE,
            Condition\ProductCategoryIdCondition::TYPE,
        ];
        $this->actual = $this->rule->getConditionTypes();
        $this->verify();
    }

    public function testGetPromotionTypes()
    {
        $this->expected = [
            Promotion\ProductClassPricePercentPromotion::TYPE,
            Promotion\ProductClassPriceAmountPromotion::TYPE,
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
     * @param $ProductClass
     * @param $expected
     * @dataProvider dataProvider_testMatch_Valid
     */
    public function testMatch_Valid($ruleData, $Conditions, $ProductClass, $expected)
    {
        $this->rule->setId(rand());
        $this->rule->setOperator($ruleData[0]);
        $this->rule->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));

        /** @var Condition $Condition */
        foreach ($Conditions as $Condition) {
            $Condition->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));
            $this->rule->addConditions($Condition);
        }

        $actual = $this->rule->match($ProductClass);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testMatch_Valid($testMethod = null, $productClassId = 1, $categoryId = 2)
    {
        $data = [];
        $operatorDataSet = OperatorTest\AllOperatorTest::dataProvider_testMatch_Valid_ProductClassRule(null, $productClassId, $categoryId);
        foreach ($operatorDataSet as $operatorData) {
            list($Conditions, $ProductClass, $expected) = $operatorData;
            $data[] = [['operator_all'], $Conditions, $ProductClass, $expected];
        }

        $operatorDataSet = OperatorTest\OrOperatorTest::dataProvider_testMatch_Valid_ProductClassRule(null, $productClassId, $categoryId);
        foreach ($operatorDataSet as $operatorData) {
            list($Conditions, $ProductClass, $expected) = $operatorData;
            $data[] = [['operator_or'], $Conditions, $ProductClass, $expected];
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
     * @param $ProductClass
     * @param $expectedValue
     *
     * @dataProvider dataProvider_testGetDiscount_Valid
     */
    public function testGetDiscount_Valid($Promotion, $ProductClass, $expectedValue)
    {
        $this->rule->setId(rand());
        $this->rule->setPromotion($Promotion);

        $actual = $this->rule->getDiscount($ProductClass);

        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals($expectedValue, $actual->getValue());
        $this->assertEquals($this->rule->getId(), $actual->getRuleId());
    }

    public function dataProvider_testGetDiscount_Valid($testMethod = null, $productPrice = 4567)
    {
        $data = [];
        foreach (PromotionTest\ProductClassPriceAmountPromotionTest::dataProvider_testGetDiscount_Valid() as $promotionData) {
            list($promotionValue, $ProductClass, $promotionExpected) = $promotionData;
            $Promotion = new Promotion\ProductClassPriceAmountPromotion();
            $Promotion->setId(rand());
            $Promotion->setValue($promotionValue);

            $data[] = [$Promotion, $ProductClass, $promotionExpected];
        }

        foreach (PromotionTest\ProductClassPricePercentPromotionTest::dataProvider_testGetDiscount_Valid(null, $productPrice) as $promotionData) {
            list($promotionValue, $ProductClass, $promotionExpected) = $promotionData;
            $Promotion = new Promotion\ProductClassPricePercentPromotion();
            $Promotion->setId(rand());
            $Promotion->setValue($promotionValue);

            $data[] = [$Promotion, $ProductClass, $promotionExpected];
        }

        return $data;
    }
}

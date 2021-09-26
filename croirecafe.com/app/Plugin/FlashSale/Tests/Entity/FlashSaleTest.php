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

use Eccube\Entity\ProductClass;
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Service\Promotion\PromotionFactory;
use Plugin\FlashSale\Service\Condition\ConditionFactory;
use Plugin\FlashSale\Service\Rule\RuleFactory;
use Plugin\FlashSale\Tests\Entity\Rule\CartRuleTest;
use Plugin\FlashSale\Tests\Entity\Rule\ProductClassRuleTest;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Entity\Discount;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Tests\Entity\Rule as RuleTest;
use Plugin\FlashSale\Tests\Entity\Promotion as PromotionTest;

class FlashSaleTest extends EccubeTestCase
{
    /**
     * @var FlashSale
     */
    protected $flashSale;

    public function setUp()
    {
        parent::setUp();
        $this->flashSale = new FlashSale();
    }

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid_Json($expected)
    {
        $actual = $this->flashSale->rawData(json_encode($expected['rules']));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid($expected)
    {
        foreach ($expected['rules'] as $ruleData) {
            $rule = RuleFactory::createFromArray(['type' => $ruleData['type']]);

            $rule->setId($ruleData['id']);
            $rule->setOperator($ruleData['operator']);

            /** @var Promotion $promotion */
            $promotion = $this->container->get(PromotionFactory::class)->createFromArray(['type' => $ruleData['promotion']['type']]);
            $promotion->setId($ruleData['promotion']['id']);
            $promotion->setValue($ruleData['promotion']['value']);
            $rule->setPromotion($promotion);

            foreach ($ruleData['conditions'] as $conditionData) {
                /** @var Condition $condition */
                $condition = $this->container->get(ConditionFactory::class)->createFromArray(['type' => $conditionData['type']]);
                $condition->setId($conditionData['id']);
                $condition->setOperator($conditionData['operator']);
                $condition->setValue($conditionData['value']);
                $rule->addConditions($condition);
            }

            $this->flashSale->addRule($rule);
        }

        $actual = $this->flashSale->rawData();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testRawData_Valid()
    {
        $data = [];
        $dataCase = ['rules' => []];
        foreach (CartRuleTest::dataProvider_testRawData_Valid() as $ruleData) {
            $dataCase['rules'] = $ruleData;
        }
        foreach (ProductClassRuleTest::dataProvider_testRawData_Valid() as $ruleData) {
            $dataCase['rules'] = $ruleData;
        }
        $data[] = [$dataCase];

        return $data;
    }

    /**
     * @param $rules
     * @param $object
     * @dataProvider dataProvider_testGetDiscount_Invalid
     */
    public function testGetDiscount_Invalid($rules, $object)
    {
        foreach ($rules as $rule) {
            $this->flashSale->addRule($rule);
        }
        $actual = $this->flashSale->getDiscount($object);
        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals(0, $actual->getValue());
    }

    public static function dataProvider_testGetDiscount_Invalid()
    {
        return [
            [[], new \stdClass()],
            [[new Rule\CartRule()], new ProductClass()],
            [[new Rule\ProductClassRule()], new Cart()],
            [[new Rule\ProductClassRule()], new Order()],
        ];
    }

    /**
     * @param $Rules
     * @param $object
     * @param $expectedValue
     * @dataProvider dataProvider_testGetDiscount_Valid_CartRule
     * @dataProvider dataProvider_testGetDiscount_Valid_ProductClassRule
     */
    public function testGetDiscount_Valid($Rules, $object, $expectedValue)
    {
        $this->flashSale->setId(rand());

        /** @var Rule $Rule */
        foreach ($Rules as $Rule) {
            foreach ($Rule->getConditions() as $Condition) {
                $Condition->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));
            }
            $Rule->setOperatorFactory($this->container->get(Operator\OperatorFactory::class));
            $this->flashSale->addRule($Rule);
        }

        $actual = $this->flashSale->getDiscount($object);
        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals($expectedValue, $actual->getValue());
    }

    public static function dataProvider_testGetDiscount_Valid_CartRule($testMethod = null, $orderSubtotal = 12345)
    {
        $data = [];

        $promotionAmountDataSet = PromotionTest\CartTotalAmountPromotionTest::dataProvider_testGetDiscount_Valid();
        $promotionPercentDataSet = PromotionTest\CartTotalPercentPromotionTest::dataProvider_testGetDiscount_Valid(null, $orderSubtotal);

        $count = 0;
        $Rules = [];
        foreach (RuleTest\CartRuleTest::dataProvider_testMatch_Valid(null, $orderSubtotal) as $ruleData) {
            $count++;
            list($rule, $Conditions, $Order, $ruleExpected) = $ruleData;

            $Rule = new CartRule();
            $Rule->setId(rand());
            $Rule->setOperator($rule[0]);
            foreach ($Conditions as $Condition) {
                $Rule->addConditions($Condition);
            }

            if ($ruleExpected) {
                $Rules[] = $Rule;
            }

            if ($count % 2 == 0) {
                $i = array_rand($promotionAmountDataSet, 1);
                list($promotionValue, , $promotionExpected) = $promotionAmountDataSet[$i];
                $Promotion = new Promotion\CartTotalAmountPromotion();
                $Promotion->setId(rand());
                $Promotion->setValue($promotionValue);
                $Rule->setPromotion($Promotion);
                $data[] = [[$Rule], $Order, $ruleExpected ? $promotionExpected : 0];
            } else {
                $i = array_rand($promotionPercentDataSet, 1);
                list($promotionValue, , $promotionExpected) = $promotionPercentDataSet[$i];
                $Promotion = new Promotion\CartTotalPercentPromotion();
                $Promotion->setId(rand());
                $Promotion->setValue($promotionValue);
                $Rule->setPromotion($Promotion);
                $data[] = [[$Rule], $Order, $ruleExpected ? $promotionExpected : 0];
            }
        }

        foreach (array_reverse($data) as $dataSet) {
            list(, $Order, $expectedValue) = $dataSet;
            if ($expectedValue) {
                $data[] = [$Rules, $Order, $expectedValue];
                break;
            }
        }

        return $data;
    }

    public static function dataProvider_testGetDiscount_Valid_ProductClassRule($testMethod = null, $productClassId = 1, $categoryId = 2, $productClassPrice = 34567)
    {
        $data = [];
        $priceAmountDataSet = PromotionTest\ProductClassPriceAmountPromotionTest::dataProvider_testGetDiscount_Valid();
        $pricePercentDataSet = PromotionTest\ProductClassPricePercentPromotionTest::dataProvider_testGetDiscount_Valid(null, $productClassPrice);
        $count = 0;
        $Rules = [];
        foreach (RuleTest\ProductClassRuleTest::dataProvider_testMatch_Valid(null, $productClassId, $categoryId) as $ruleData) {
            $count++;
            list($rule, $Conditions, $ProductClass, $ruleExpected) = $ruleData;

            $Rule = new Rule\ProductClassRule();
            $Rule->setId(rand());
            $Rule->setOperator($rule[0]);
            foreach ($Conditions as $Condition) {
                $Rule->addConditions($Condition);
            }

            if ($ruleExpected) {
                $Rules[] = $Rule;
            }

            if ($count % 2 == 0) {
                $i = array_rand($priceAmountDataSet, 1);
                list($promotionValue, , $promotionExpected) = $priceAmountDataSet[$i];

                $Promotion = new Promotion\ProductClassPriceAmountPromotion();
                $Promotion->setId(rand());
                $Promotion->setValue($promotionValue);
                $Rule->setPromotion($Promotion);

                $data[] = [[$Rule], $ProductClass, $ruleExpected ? $promotionExpected : 0];
            } else {
                $i = array_rand($pricePercentDataSet, 1);
                list($promotionValue, $promoProductClass, $promotionExpected) = $pricePercentDataSet[$i];

                $Promotion = new Promotion\ProductClassPricePercentPromotion();
                $Promotion->setId(rand());
                $Promotion->setValue($promotionValue);
                $Rule->setPromotion($Promotion);

                $ProductClass->setPrice02IncTax($promoProductClass->getPrice02IncTax());

                $data[] = [[$Rule], $ProductClass, $ruleExpected ? $promotionExpected : 0];
            }
        }

        foreach (array_reverse($data) as $dataSet) {
            list(, $Order, $expectedValue) = $dataSet;
            if ($expectedValue) {
                $data[] = [$Rules, $Order, $expectedValue];
                break;
            }
        }

        return $data;
    }
}

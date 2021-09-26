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

namespace Plugin\FlashSale\Tests\Service\Condition;

use Plugin\FlashSale\Service\Condition\ConditionFactory;
use Plugin\FlashSale\Tests\Service\AbstractServiceTestCase;
use Plugin\FlashSale\Entity\Condition as Condition;

class ConditionFactoryTest extends AbstractServiceTestCase
{
    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->conditionFactory = new ConditionFactory();
    }

    public function testCreateFromArray_Scenario0()
    {
        $this->expectExceptionMessage('$data[type] must be required');
        $this->conditionFactory::createFromArray([]);
    }

    public function testCreateFromArray_Scenario1()
    {
        $this->expectExceptionMessage('promotion_test_only unsupported');
        $this->conditionFactory::createFromArray(['type' => 'promotion_test_only']);
    }

    /**
     * @param $type
     * @param $operator
     * @param $value
     * @param $expectedClass
     * @dataProvider dataProvider_testCreateFromArray
     */
    public function testCreateFromArray_Scenario2($type, $operator, $value, $expectedClass)
    {
        $actual = $this->conditionFactory::createFromArray(['type' => $type, 'value' => $value, 'operator' => $operator]);
        $this->assertEquals($expectedClass, get_class($actual));
        $this->assertEquals($value, $actual->getValue());
        $this->assertEquals($operator, $actual->getOperator());
    }

    public function dataProvider_testCreateFromArray()
    {
        $operators = [
            'operator_all',
            'operator_or',
            'operator_in',
            'operator_not_in',
            'operator_equal',
            'operator_not_equal',
            'operator_greater_than',
            'operator_less_than',
        ];

        return [
            [Condition\CartTotalCondition::TYPE, array_rand(array_flip($operators)), rand(), Condition\CartTotalCondition::class],
            [Condition\ProductCategoryIdCondition::TYPE, array_rand(array_flip($operators)), rand(), Condition\ProductCategoryIdCondition::class],
            [Condition\ProductClassIdCondition::TYPE, array_rand(array_flip($operators)), rand(), Condition\ProductClassIdCondition::class],
        ];
    }
}

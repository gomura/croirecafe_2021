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

namespace Plugin\FlashSale\Service\Rule;

use Plugin\FlashSale\Tests\Service\AbstractServiceTestCase;
use Plugin\FlashSale\Entity\Rule as Rule;

class RuleFactoryTest extends AbstractServiceTestCase
{
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->ruleFactory = new RuleFactory();
    }

    public function testCreateFromArray_Scenario0()
    {
        $this->expectExceptionMessage('$data[type] must be required');
        $this->ruleFactory::createFromArray([]);
    }

    public function testCreateFromArray_Scenario1()
    {
        $this->expectExceptionMessage('rule_test_only unsupported');
        $this->ruleFactory::createFromArray(['type' => 'rule_test_only']);
    }

    /**
     * @param $type
     * @param $operator
     * @param $expectedClass
     * @dataProvider dataProvider_testCreateFromArray
     */
    public function testCreateFromArray_Scenario2($type, $operator, $expectedClass)
    {
        $actual = $this->ruleFactory::createFromArray(['type' => $type, 'operator' => $operator]);
        $this->assertEquals($expectedClass, get_class($actual));
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
            [Rule\CartRule::TYPE, array_rand(array_flip($operators)), Rule\CartRule::class],
            [Rule\ProductClassRule::TYPE, array_rand(array_flip($operators)), Rule\ProductClassRule::class],
        ];
    }
}

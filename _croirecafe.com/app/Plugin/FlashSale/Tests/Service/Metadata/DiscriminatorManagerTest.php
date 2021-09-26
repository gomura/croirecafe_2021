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

namespace Plugin\FlashSale\Tests\Service\Metadata;

use Plugin\FlashSale\Service\Metadata\Discriminator;
use Plugin\FlashSale\Service\Metadata\DiscriminatorManager;
use Plugin\FlashSale\Tests\Service\AbstractServiceTestCase;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Condition as Condition;
use Plugin\FlashSale\Entity\Rule as Rule;
use Plugin\FlashSale\Entity\Promotion as Promotion;

class DiscriminatorManagerTest extends AbstractServiceTestCase
{
    /**
     * @var DiscriminatorManager
     */
    protected $discriminatorManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->discriminatorManager = new DiscriminatorManager();
    }

    public function testCreate_Scenario0()
    {
        $this->expectExceptionMessage('Unsupported foo type');
        $this->discriminatorManager->create('foo');
    }

    /**
     * @param $type
     * @param $expectedClass
     * @dataProvider dataProvider_testCreate_Scenario1
     */
    public function testCreate_Scenario1($type, $expectedClass)
    {
        $actual = $this->discriminatorManager->create($type);

        $this->assertEquals(Discriminator::class, get_class($actual));
        $this->assertEquals($expectedClass, $actual->getClass());
        $this->assertEquals($type, $actual->getType());
    }

    public function dataProvider_testCreate_Scenario1()
    {
        return [
            [Operator\AllOperator::TYPE, Operator\AllOperator::class],
            [Operator\OrOperator::TYPE, Operator\OrOperator::class],
            [Operator\InOperator::TYPE, Operator\InOperator::class],
            [Operator\NotInOperator::TYPE, Operator\NotInOperator::class],
            [Operator\EqualOperator::TYPE, Operator\EqualOperator::class],
            [Operator\NotEqualOperator::TYPE, Operator\NotEqualOperator::class],
            [Operator\GreaterThanOperator::TYPE, Operator\GreaterThanOperator::class],
            [Operator\LessThanOperator::TYPE, Operator\LessThanOperator::class],
            [Promotion\ProductClassPriceAmountPromotion::TYPE, Promotion\ProductClassPriceAmountPromotion::class],
            [Promotion\ProductClassPricePercentPromotion::TYPE, Promotion\ProductClassPricePercentPromotion::class],
            [Promotion\CartTotalAmountPromotion::TYPE, Promotion\CartTotalAmountPromotion::class],
            [Promotion\CartTotalPercentPromotion::TYPE, Promotion\CartTotalPercentPromotion::class],
            [Condition\CartTotalCondition::TYPE, Condition\CartTotalCondition::class],
            [Condition\ProductClassIdCondition::TYPE, Condition\ProductClassIdCondition::class],
            [Condition\ProductCategoryIdCondition::TYPE, Condition\ProductCategoryIdCondition::class],
            [Rule\CartRule::TYPE, Rule\CartRule::class],
            [Rule\ProductClassRule::TYPE, Rule\ProductClassRule::class],
        ];
    }

    /**
     * @param $type
     * @param $expectedClass
     * @dataProvider dataProvider_testCreate_Scenario1
     */
    public function testGet($type, $expectedClass)
    {
        $this->discriminatorManager->create($type);
        $this->assertEquals($this->discriminatorManager->create($type), $this->discriminatorManager->get($type));
    }
}

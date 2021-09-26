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

namespace Plugin\FlashSale\Service\Promotion;

use Plugin\FlashSale\Entity\Promotion as Promotion;
use Plugin\FlashSale\Tests\Service\AbstractServiceTestCase;

class PromotionFactoryTest extends AbstractServiceTestCase
{
    /**
     * @var PromotionFactory
     */
    protected $promotionFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->promotionFactory = new PromotionFactory();
    }

    public function testCreateFromArray_Scenario0()
    {
        $this->expectExceptionMessage('$data[type] must be required');
        $this->promotionFactory::createFromArray([]);
    }

    public function testCreateFromArray_Scenario1()
    {
        $this->expectExceptionMessage('promotion_test_only unsupported');
        $this->promotionFactory::createFromArray(['type' => 'promotion_test_only']);
    }

    /**
     * @param $type
     * @param $value
     * @param $expectedClass
     * @dataProvider dataProvider_testCreateFromArray
     */
    public function testCreateFromArray_Scenario2($type, $value, $expectedClass)
    {
        $actual = $this->promotionFactory::createFromArray(['type' => $type, 'value' => $value]);
        $this->assertEquals($expectedClass, get_class($actual));
        $this->assertEquals($value, $actual->getValue());
    }

    public function dataProvider_testCreateFromArray()
    {
        return [
            [Promotion\CartTotalPercentPromotion::TYPE, rand(), Promotion\CartTotalPercentPromotion::class],
            [Promotion\CartTotalAmountPromotion::TYPE, rand(), Promotion\CartTotalAmountPromotion::class],
            [Promotion\ProductClassPriceAmountPromotion::TYPE, rand(), Promotion\ProductClassPriceAmountPromotion::class],
            [Promotion\ProductClassPricePercentPromotion::TYPE, rand(), Promotion\ProductClassPricePercentPromotion::class],
        ];
    }
}

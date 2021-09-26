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

namespace Plugin\FlashSale\Tests\Entity\Promotion;

use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Entity\Discount;
use Plugin\FlashSale\Entity\Promotion\ProductClassPriceAmountPromotion;
use Plugin\FlashSale\Tests\Entity\PromotionTest;

class ProductClassPriceAmountPromotionTest extends PromotionTest
{
    /**
     * @var ProductClassPriceAmountPromotion
     */
    protected $promotion;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->promotion = new ProductClassPriceAmountPromotion();
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [
            [['id' => 1, 'type' => 'promotion_product_class_price_amount', 'value' => 1000]],
        ];
    }

    public function testGetDiscount_Invalid()
    {
        $this->promotion->setId(rand());
        $actual = $this->promotion->getDiscount(new \stdClass());
        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals($this->promotion->getId(), $actual->getPromotionId());
        $this->assertEquals(0, $actual->getValue());
    }

    /**
     * @param $promotionValue
     * @param $ProductClass
     * @param $expectedValue
     * @dataProvider dataProvider_testGetDiscount_Valid
     */
    public function testGetDiscount_Valid($promotionValue, $ProductClass, $expectedValue)
    {
        $this->promotion->setId(rand());
        $this->promotion->setValue($promotionValue);

        $actual = $this->promotion->getDiscount($ProductClass);

        $this->assertEquals(Discount::class, Discount::class);
        $this->assertEquals($actual->getPromotionId(), $actual->getPromotionId());
        $this->assertEquals($actual->getValue(), $expectedValue);
    }

    public static function dataProvider_testGetDiscount_Valid()
    {
        return [
            [10, new ProductClass(), 10],
            [222, new ProductClass(), 222],
        ];
    }
}

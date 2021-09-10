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

use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Plugin\FlashSale\Entity\Discount;
use Plugin\FlashSale\Entity\Promotion\CartTotalAmountPromotion;
use Plugin\FlashSale\Tests\Entity\PromotionTest;

class CartTotalAmountPromotionTest extends PromotionTest
{
    /**
     * @var CartTotalAmountPromotion
     */
    protected $promotion;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->promotion = new CartTotalAmountPromotion();
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [
            [['id' => 1, 'type' => 'promotion_cart_total_amount', 'value' => 1000]],
        ];
    }

    public function testGetDiscount_Invalid()
    {
        $this->promotion->setId(rand());
        /** @var Discount $actual */
        $actual = $this->promotion->getDiscount(new \stdClass());
        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals(0, $actual->getValue());
    }

    /**
     * @param $promotionValue
     * @param $object
     * @param $expected
     * @dataProvider dataProvider_testGetDiscount_Valid
     */
    public function testGetDiscount_Valid($promotionValue, $object, $expected)
    {
        $this->promotion->setId(rand());
        $this->promotion->setValue($promotionValue);

        $actual = $this->promotion->getDiscount($object);

        $this->assertEquals(Discount::class, get_class($actual));
        $this->assertEquals($expected, $actual->getValue());
    }

    public static function dataProvider_testGetDiscount_Valid()
    {
        return [
            [100, new Order(), 100],
            [1000, new Cart(), 1000],
        ];
    }
}

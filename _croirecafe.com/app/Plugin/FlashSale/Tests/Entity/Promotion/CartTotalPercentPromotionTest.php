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
use Plugin\FlashSale\Entity\Promotion\CartTotalPercentPromotion;
use Plugin\FlashSale\Tests\Entity\PromotionTest;

/**
 * AbstractEntity test cases.
 *
 * @author Kentaro Ohkouchi
 */
class CartTotalPercentPromotionTest extends PromotionTest
{
    /**
     * @var CartTotalPercentPromotion
     */
    protected $promotion;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->promotion = new CartTotalPercentPromotion();
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [
            [['id' => 1, 'type' => 'promotion_cart_percent_amount', 'value' => 10]],
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
     * /**
     * @param $promotionValue
     * @param $object
     * @param $expectedValue
     * @dataProvider dataProvider_testGetDiscount_Valid
     */
    public function testGetDiscount_Valid($promotionValue, $object, $expectedValue)
    {
        $this->promotion->setId(rand());
        $this->promotion->setValue($promotionValue);

        $actual = $this->promotion->getDiscount($object);
        $this->assertEquals(get_class($actual), Discount::class);
        $this->assertEquals($this->promotion->getId(), $actual->getPromotionId());
        $this->assertEquals($expectedValue, $actual->getValue());
    }

    public static function dataProvider_testGetDiscount_Valid($testMethod = null, $orderSubtotal = 12345)
    {
        $Order = new Order();
        $Order->setSubtotal($orderSubtotal);

        $Cart = new Cart();
        $Cart->setTotal($orderSubtotal);

        return [
            [10, $Order, floor(10 * $orderSubtotal / 100)],
            [51, $Cart, floor(51 * $orderSubtotal / 100)],
        ];
    }
}

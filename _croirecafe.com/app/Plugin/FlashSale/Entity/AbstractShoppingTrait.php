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

namespace Plugin\FlashSale\Entity;

/**
 * Trait AbstractShoppingTrait
 *
 * @uses \FSCartTrait
 * @uses \FSOrderTrait
 */
trait AbstractShoppingTrait
{
    /**
     * @var array
     */
    protected $flashSaleDiscount = [];

    /**
     * Clean discount from flash sale
     *
     * @return $this
     */
    public function cleanFlashSaleDiscount()
    {
        $this->flashSaleDiscount = [];

        return $this;
    }

    /**
     * Add an discount
     *
     * @param int $ruleId
     * @param int $discountValue
     *
     * @return $this
     */
    public function addFlashSaleDiscount(int $ruleId, int $discountValue)
    {
        $this->flashSaleDiscount[$ruleId] = $discountValue;

        return $this;
    }

    /**
     * Get $flashSaleTotalDiscount
     *
     * @return string
     */
    public function getFlashSaleTotalDiscount()
    {
        $totalDiscount = 0;
        foreach ($this->getItems() as $item) {
            $totalDiscount += $item->getFlashSaleTotalDiscount();
        }

        $sum = array_sum($this->flashSaleDiscount) + $totalDiscount;

        if ($sum > $this->getTotal()) {
            return $this->getTotal();
        }

        return $sum;
    }

    /**
     * Get total discount price
     *
     * @return int
     */
    public function getFlashSaleTotalDiscountPrice()
    {
        return (int) ($this->getTotalPrice() - $this->getFlashSaleTotalDiscount());
    }
}

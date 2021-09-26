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

use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\ProductClass")
 */
trait FSProductClassTrait
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
     * Add flash sale discount
     *
     * @param $ruleId
     * @param $discountValue
     *
     * @return $this
     */
    public function addFlashSaleDiscount($ruleId, $discountValue)
    {
        $this->flashSaleDiscount[$ruleId] = $discountValue;

        return $this;
    }

    /**
     * Get $flashSaleDiscount
     *
     * @return int
     */
    public function getFlashSaleDiscount()
    {
        $sum = array_sum($this->flashSaleDiscount);

        return ($sum > $this->getPrice02IncTax()) ? $this->getPrice02IncTax() : $sum;
    }

    /**
     * Get discount price
     *
     * @return int
     */
    public function getFlashSaleDiscountPrice()
    {
        return (int) ($this->getPrice02IncTax() - $this->getFlashSaleDiscount());
    }

    /**
     * Get discount percent
     *
     * @return int
     */
    public function getFlashSaleDiscountPercent()
    {
        return (int) ceil($this->getFlashSaleDiscount() * 100 / $this->getPrice02IncTax());
    }
}

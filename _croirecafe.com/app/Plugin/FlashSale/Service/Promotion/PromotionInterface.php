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

use Plugin\FlashSale\Entity\DiscountInterface;

interface PromotionInterface
{
    /**
     * Calculate discount item
     *
     * @param $object
     *
     * @return DiscountInterface
     */
    public function getDiscount($object);
}

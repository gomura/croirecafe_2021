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

namespace Plugin\FlashSale\Entity\Promotion;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Plugin\FlashSale\Service\Promotion\PromotionInterface;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\DiscountInterface;
use Plugin\FlashSale\Entity\Discount;

/**
 * @ORM\Entity
 */
class CartTotalAmountPromotion extends Promotion implements PromotionInterface
{
    const TYPE = 'promotion_cart_total_amount';

    /**
     * {@inheritdoc}
     *
     * @param $object
     *
     * @return DiscountInterface
     */
    public function getDiscount($object)
    {
        $discount = new Discount();
        $discount->setPromotionId($this->getId());

        if (!$object instanceof Cart && !$object instanceof Order) {
            return $discount;
        }

        $discount->setValue($this->getValue());

        return $discount;
    }
}

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

use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Entity\Promotion\ProductClassPriceAmountPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalPercentPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalAmountPromotion;

class PromotionFactory
{
    /**
     * Create Promotion from array
     *
     * @param array $data
     *
     * @return PromotionInterface
     */
    public static function createFromArray(array $data)
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('$data[type] must be required');
        }

        switch ($data['type']) {
            case ProductClassPricePercentPromotion::TYPE:
                $Promotion = new ProductClassPricePercentPromotion();
                break;
            case ProductClassPriceAmountPromotion::TYPE:
                $Promotion = new ProductClassPriceAmountPromotion();
                break;
            case CartTotalPercentPromotion::TYPE:
                $Promotion = new CartTotalPercentPromotion();
                break;
            case CartTotalAmountPromotion::TYPE:
                $Promotion = new CartTotalAmountPromotion();
                break;

            default:
                throw new \InvalidArgumentException($data['type'].' unsupported');
        }

        if (isset($data['value'])) {
            $Promotion->setValue($data['value']);
        }

        return $Promotion;
    }
}

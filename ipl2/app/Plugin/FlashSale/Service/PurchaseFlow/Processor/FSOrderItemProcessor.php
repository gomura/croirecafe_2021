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

namespace Plugin\FlashSale\Service\PurchaseFlow\Processor;

use Eccube\Annotation;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\Processor\AbstractPurchaseProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * @Annotation\ShoppingFlow()
 */
class FSOrderItemProcessor extends AbstractPurchaseProcessor
{
    /**
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        if (!$target instanceof Order) {
            return;
        }

        foreach ($target->getProductOrderItems() as $productOrderItem) {
            if ($productOrderItem->isProduct()) {
                $productOrderItem->setFsPrice($productOrderItem->getFlashSaleTotalDiscountPrice());
            }
        }
    }
}

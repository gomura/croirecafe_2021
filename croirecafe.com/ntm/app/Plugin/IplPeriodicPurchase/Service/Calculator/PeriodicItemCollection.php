<?php

/*
 * RepeatCube for EC-CUBE4
 * Copyright(c) 2019 IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * This program is not free software.
 * It applies to terms of service.
 *
 */

namespace Plugin\IplPeriodicPurchase\Service\Calculator;

use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\ItemInterface;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;

class PeriodicItemCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    protected $type;

    public function __construct($OrderItems, $type = null)
    {
        // $OrderItems が Collection だったら toArray(); する
        $this->type = is_null($type) ? PeriodicPurchase::class : $type;
        parent::__construct($OrderItems);
    }

    public function reduce(\Closure $func, $initial = null)
    {
        return array_reduce($this->toArray(), $func, $initial);
    }

    // 明細種別ごとに返すメソッド作る
    public function getProductClasses()
    {
        return $this->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem->isProduct();
            });
    }

    public function getDeliveryFees()
    {
        return $this->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem->isDeliveryFee();
            });
    }

    public function getCharges()
    {
        return $this->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem->isCharge();
            });
    }

    public function getDiscounts()
    {
        return $this->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem->isDiscount() || $OrderItem->isPoint();
            });
    }

    /**
     * 同名の明細が存在するかどうか.
     *
     * TODO 暫定対応. 本来は明細種別でチェックする.
     */
    public function hasProductByName($productName)
    {
        $OrderItems = $this->filter(
            function (ItemInterface $OrderItem) use ($productName) {
                /* @var OrderItem $OrderItem */
                return $OrderItem->getProductName() == $productName;
            });

        return !$OrderItems->isEmpty();
    }

    /**
     * 指定した受注明細区分の明細が存在するかどうか
     *
     * @param OrderItemType $OrderItemType 受注区分
     *
     * @return boolean
     */
    public function hasItemByOrderItemType($OrderItemType)
    {
        $filteredItems = $this->filter(function (ItemInterface $OrderItem) use ($OrderItemType) {
            /* @var OrderItem $OrderItem */
            return $OrderItem->getOrderItemType() && $OrderItem->getOrderItemType()->getId() == $OrderItemType->getId();
        });

        return !$filteredItems->isEmpty();
    }

    public function getType()
    {
        return $this->type;
    }
}

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
namespace Plugin\IplPeriodicPurchase\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;
use Plugin\IplPeriodicPurchase\Service\PurchaseFlow\PeriodicDiscountProcessor;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{

    /**
     * 定期割引明細かどうか.
     *
     * @return boolean 定期割引明細の場合 true
     */
    public function isPeriodicDiscount()
    {
        return $this->getProcessorName() === PeriodicDiscountProcessor::class;
    }

}

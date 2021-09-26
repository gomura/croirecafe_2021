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
namespace Plugin\IplPeriodicPurchase\Repository;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Repository\AbstractRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PeriodicDiscountRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PeriodicDiscount::class);
    }

    /**
     * 定期回数別商品金額割引を削除する.
     *
     * @param  PeiodicDiscount $PeiodicDiscount 削除対象の定期回数別商品金額割引
     *
     * @throws ForeignKeyConstraintViolationException 外部キー制約違反の場合
     * @throws DriverException SQLiteの場合, 外部キー制約違反が発生すると, DriverExceptionをthrowします.
     */
    public function delete($PeiodicDiscount)
    {
        $em = $this->getEntityManager();
        $em->remove($PeiodicDiscount);
        $em->flush($PeiodicDiscount);
    }
}

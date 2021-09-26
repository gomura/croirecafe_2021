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
use Plugin\IplPeriodicPurchase\Entity\Cycle;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CycleRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Cycle::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }

    /**
     * 定期サイクルを削除する.
     *
     * @param  PeiodicDiscount $Cycle 削除対象の定期サイクル
     *
     * @throws ForeignKeyConstraintViolationException 外部キー制約違反の場合
     * @throws DriverException SQLiteの場合, 外部キー制約違反が発生すると, DriverExceptionをthrowします.
     */
    public function delete($Cycle)
    {
        $em = $this->getEntityManager();
        $em->remove($Cycle);
        $em->flush($Cycle);
    }
}

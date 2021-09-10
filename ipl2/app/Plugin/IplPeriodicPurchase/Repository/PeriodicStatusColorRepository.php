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

use Eccube\Repository\AbstractRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatusColor;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PeriodicStatusColorRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PeriodicStatusColor::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }
}

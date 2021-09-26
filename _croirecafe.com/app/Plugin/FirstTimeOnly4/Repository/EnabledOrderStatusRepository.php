<?php
/**
 * This file is part of FirstTimeOnly4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 *  https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FirstTimeOnly4\Repository;


use Eccube\Repository\AbstractRepository;
use Plugin\FirstTimeOnly4\Entity\EnabledOrderStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class EnabledOrderStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EnabledOrderStatus::class);
    }
}

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

use Doctrine\ORM\Mapping as ORM;

/**
 * PeriodicStatusColor
 *
 * @ORM\Table(name="plg_ipl_periodic_purchase_mtb_periodic_status_color")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository")
 */
class PeriodicStatusColor extends \Eccube\Entity\Master\AbstractMasterEntity
{
}

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
 * PeriodicStatus
 *
 * @ORM\Table(name="plg_ipl_periodic_purchase_mtb_periodic_status")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository")
 */
class PeriodicStatus extends \Eccube\Entity\Master\AbstractMasterEntity
{
    /** 継続. */
    const PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE = 1;
    /** 休止. */
    const PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND = 2;
    /** 解約. */
    const PLG_IPLPERIODICPURCHASE_STATUS_CANCEL = 3;
    /** 解約（再開期限切れ）. */
    const PLG_IPLPERIODICPURCHASE_STATUS_CANCEL_OVER_RESUME_PERIOD = 4;
    /** 決済エラー. */
    const PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR = 5;
    /** システムエラー. */
    const PLG_IPLPERIODICPURCHASE_STATUS_SYSTEM_ERROR = 6;
    /** 再決済待ち. */
    const PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT = 7;

    /**
     * 受注一覧画面で, ステータスごとの受注件数を表示するかどうか
     *
     * @var bool
     *
     * @ORM\Column(name="display_order_count", type="boolean", options={"default":false})
     */
    private $display_order_count;

    /**
     * @return bool
     */
    public function isDisplayOrderCount()
    {
        return $this->display_order_count;
    }

    /**
     * @param bool $display_order_count
     */
    public function setDisplayOrderCount($display_order_count = false)
    {
        $this->display_order_count = $display_order_count;
    }
}

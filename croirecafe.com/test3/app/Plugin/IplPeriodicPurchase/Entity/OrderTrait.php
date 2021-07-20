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

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{

    /**
     * @var integer
     * 
     * @ORM\Column(name="plg_ipl_periodic_purchase_cycle_week", type="integer", nullable=true)
     */
    private $plg_ipl_periodic_purchase_cycle_week;

    /**
     * @var integer
     * 
     * @ORM\Column(name="plg_ipl_periodic_purchase_cycle_day", type="integer", nullable=true)
     */
    private $plg_ipl_periodic_purchase_cycle_day;

    /**
     * @var Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase", inversedBy="Orders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="plg_ipl_periodic_purchase_periodic_purchase_id", referencedColumnName="id")
     * })
     */
    private $PeriodicPurchase;

    /**
     * @var Plugin\IplPeriodicPurchase\Entity\Cycle
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\Cycle")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="plg_ipl_periodic_purchase_cycle_id", referencedColumnName="id")
     * })
     */
    private $Cycle;

    /**
     * {@inheritdoc}
     */
    public function setCycleWeek($plg_ipl_periodic_purchase_cycle_week)
    {
        $this->plg_ipl_periodic_purchase_cycle_week = $plg_ipl_periodic_purchase_cycle_week;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCycleWeek()
    {
        return $this->plg_ipl_periodic_purchase_cycle_week;
    }

    /**
     * {@inheritdoc}
     */
    public function setCycleDay($plg_ipl_periodic_purchase_cycle_day)
    {
        $this->plg_ipl_periodic_purchase_cycle_day = $plg_ipl_periodic_purchase_cycle_day;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCycleDay()
    {
        return $this->plg_ipl_periodic_purchase_cycle_day;
    }

    /**
     * Set periodicPurchase.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase|null $PeriodicPurchase
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicPurchase(\Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase $PeriodicPurchase = null)
    {
        $this->PeriodicPurchase = $PeriodicPurchase;

        return $this;
    }

    /**
     * Get PeriodicPurchase.
     *
     * @return \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase|null
     */
    public function getPeriodicPurchase()
    {
        return $this->PeriodicPurchase;
    }

    /**
     * Set cycle.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\Cycle|null $cycle
     *
     * @return PeriodicPurchase
     */
    public function setCycle(\Plugin\IplPeriodicPurchase\Entity\Cycle $cycle = null)
    {
        $this->Cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle.
     *
     * @return \Plugin\IplPeriodicPurchase\Entity\Cycle|null
     */
    public function getCycle()
    {
        return $this->Cycle;
    }

}

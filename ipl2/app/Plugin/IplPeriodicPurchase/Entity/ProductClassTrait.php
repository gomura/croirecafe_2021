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

use Eccube\Annotation as Eccube;
use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{

    /**
     * @var \Doctrine\Common\Collections\Collection|Cycle[]
     *
     * @ORM\ManyToMany(targetEntity="Plugin\IplPeriodicPurchase\Entity\Cycle", mappedBy="ProductClasses")
     * @ORM\JoinTable(name="dtb_product_class",
     *   joinColumns={@ORM\JoinColumn(name="cycle_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="product_class_id", referencedColumnName="id")})
     * @Eccube\FormAppend(
     *   auto_render=true,
     *   type="Plugin\IplPeriodicPurchase\Form\Type\Admin\ProductCycleType"
     * )
     */
    private $Cycles;

    /**
     * @var Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="periodic_discount_id", referencedColumnName="id")
     * })
     * @Eccube\FormAppend(
     *   auto_render=true,
     *   type="Plugin\IplPeriodicPurchase\Form\Type\Admin\ProductPeriodicDiscountType",
     *   )
     */
    private $PeriodicDiscount;


    private $cycle_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Cycles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add cycle.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\Cycle $cycle
     *
     * @return PrductClass
     */
    public function addCycle(\Plugin\IplPeriodicPurchase\Entity\Cycle $cycle)
    {
        $this->Cycles[] = $cycle;
        $cycle->addProductClass($this);

        return $this;
    }
    /**
     * Remove cycle.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\Cycle $cycle
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCycle(\Plugin\IplPeriodicPurchase\Entity\Cycle $cycle)
    {
        $cycle->removeProductClass($this);

        return $this->Cycles->removeElement($cycle);
    }
    /**
     * Get cycles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCycles()
    {
        return $this->Cycles;
    }

    /**
    /**
     * Set periodicDiscount.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount|null $periodicDiscount
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicDiscount(\Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount $periodicDiscount = null)
    {
        $this->PeriodicDiscount = $periodicDiscount;

        return $this;
    }

    /**
     * Get periodicDiscount.
     *
     * @return \Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount|null
     */
    public function getPeriodicDiscount()
    {
        return $this->PeriodicDiscount;
    }
}

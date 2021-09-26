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
use Eccube\Entity\AbstractEntity;
use Plugin\IplPeriodicPurchase\Entity\UseConvenience;

/**
 * Cycle
 *
 * @ORM\Table(name="plg_ipl_periodic_purchase_dtb_periodic_purchase_cycle")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\CycleRepository")
 */
class Cycle extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="cycle_type", type="integer", nullable=true, options={"unsigned":true})
     */
    private $cycle_type;

    /**
     * @var string
     *
     * @ORM\Column(name="cycle_unit", type="integer", nullable=true, options={"unsigned":true})
     */
    private $cycle_unit;

    /**
     * @var text
     *
     * @ORM\Column(name="display_name", type="text", nullable=true)
     */
    private $display_name;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sort_no", type="smallint", nullable=true, options={"unsigned":true})
     */
    private $sort_no;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="create_date", type="datetimetz", nullable=true)
     */
    private $create_date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="update_date", type="datetimetz", nullable=true)
     */
    private $update_date;

    /**
     * @var \Doctrine\Common\Collections\Collection|ProductClass[]
     *
     * @ORM\ManyToMany(targetEntity="Eccube\Entity\ProductClass", inversedBy="Cycles", cascade={"persist"})
     */
    private $ProductClasses;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    // array_columnでprivateプロパティを対象とするのに下記マジックメソッドの実装が必要
    // @See https://www.php.net/manual/ja/function.array-column.php
    public function __get($prop)
    {
        return $this->$prop;
    }

    public function __isset($prop)
    {
        return isset($this->$prop);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cycleType.
     *
     * @param int|null $cycleType
     *
     * @return Cycle
     */
    public function setCycleType($cycleType = null)
    {
        $this->cycle_type = $cycleType;

        return $this;
    }

    /**
     * Get cycleType.
     *
     * @return int|null
     */
    public function getCycleType()
    {
        return $this->cycle_type;
    }

    /**
     * Set cycleUnit.
     *
     * @param int|null $cycleUnit
     *
     * @return Cycle
     */
    public function setCycleUnit($cycleUnit = null)
    {
        $this->cycle_unit = $cycleUnit;

        return $this;
    }

    /**
     * Get cycleUnit.
     *
     * @return int|null
     */
    public function getCycleUnit()
    {
        return $this->cycle_unit;
    }

    /**
     * Set displayName.
     *
     * @param string|null $displayName
     *
     * @return Cycle
     */
    public function setDisplayName($displayName = null)
    {
        $this->display_name = $displayName;

        return $this;
    }

    /**
     * Get displayName.
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->display_name;
    }

    /**
     * Set sortNo.
     *
     * @param int|null $sortNo
     *
     * @return Payment
     */
    public function setSortNo($sortNo = null)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    /**
     * Get sortNo.
     *
     * @return int|null
     */
    public function getSortNo()
    {
        return $this->sort_no;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime|null $createDate
     *
     * @return Cycle
     */
    public function setCreateDate($createDate = null)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime|null
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime|null $updateDate
     *
     * @return Cycle
     */
    public function setUpdateDate($updateDate = null)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime|null
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Add productClass.
     *
     * @param \Eccube\Entity\ProductClass $productClass
     *
     * @return Product
     */
    public function addProductClass(\Eccube\Entity\ProductClass $productClass)
    {
        $this->ProductClasses[] = $productClass;

        return $this;
    }

    /**
     * Remove productClass.
     *
     * @param \Eccube\Entity\ProductClass $productClass
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProductClass(\Eccube\Entity\ProductClass $productClass)
    {
        return $this->ProductClasses->removeElement($productClass);
    }

    /**
     * Get productClasses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductClasses()
    {
        return $this->ProductClasses;
    }
}

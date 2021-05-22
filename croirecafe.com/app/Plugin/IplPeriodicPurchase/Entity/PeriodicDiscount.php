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
 * PeriodicDiscount
 * 
 * @ORM\Table(name="plg_ipl_periodic_purchase_dtb_periodic_purchase_discount")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\PeriodicDiscountRepository")
 */
class PeriodicDiscount extends AbstractEntity
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
     * @var Plugin\IplPeriodicPurchase\Entity\Config
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\Config", inversedBy="PeriodicDiscounts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     * })
     */
    private $Config;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_from_count_2", type="integer", nullable=true, options={"unsigned":true})
     */
    private $discount_from_count_2;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_from_count_3", type="integer", nullable=true, options={"unsigned":true})
     */
    private $discount_from_count_3;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_rate_1", type="integer", nullable=true, options={"unsigned":true})
     */
    private $discount_rate_1;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_rate_2", type="integer", nullable=true, options={"unsigned":true})
     */
    private $discount_rate_2;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_rate_3", type="integer", nullable=true, options={"unsigned":true})
     */
    private $discount_rate_3;

    /**
     * Constructor
     */
    public function __construct()
    {
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
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->Config;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(Config $config = null)
    {
        $this->Config = $config;

        return $this;
    }

    /**
     * Set discount_from_count_2.
     *
     * @param int|null $discount_from_count_2
     *
     * @return Cycle
     */
    public function setDiscountFromCount2($discount_from_count_2 = null)
    {
        $this->discount_from_count_2 = $discount_from_count_2;

        return $this;
    }

    /**
     * Get discount_from_count_2.
     *
     * @return int|null
     */
    public function getDiscountFromCount2()
    {
        return $this->discount_from_count_2;
    }

    /**
     * Set discount_from_count_3.
     *
     * @param int|null $discount_from_count_3
     *
     * @return Cycle
     */
    public function setDiscountFromCount3($discount_from_count_3 = null)
    {
        $this->discount_from_count_3 = $discount_from_count_3;

        return $this;
    }

    /**
     * Get discount_from_count_3.
     *
     * @return int|null
     */
    public function getDiscountFromCount3()
    {
        return $this->discount_from_count_3;
    }

    /**
     * Set discount_rate_1.
     *
     * @param int|null $discount_rate_1
     *
     * @return Cycle
     */
    public function setDiscountRate1($discount_rate_1 = null)
    {
        $this->discount_rate_1 = $discount_rate_1;

        return $this;
    }

    /**
     * Get discount_rate_1.
     *
     * @return int|null
     */
    public function getDiscountRate1()
    {
        return $this->discount_rate_1;
    }

    /**
     * Set discount_rate_2.
     *
     * @param int|null $discount_rate_2
     *
     * @return Cycle
     */
    public function setDiscountRate2($discount_rate_2 = null)
    {
        $this->discount_rate_2 = $discount_rate_2;

        return $this;
    }

    /**
     * Get discount_rate_2.
     *
     * @return int|null
     */
    public function getDiscountRate2()
    {
        return $this->discount_rate_2;
    }

    /**
     * Set discount_rate_3.
     *
     * @param int|null $discount_rate_3
     *
     * @return Cycle
     */
    public function setDiscountRate3($discount_rate_3 = null)
    {
        $this->discount_rate_3 = $discount_rate_3;

        return $this;
    }

    /**
     * Get discount_rate_3.
     *
     * @return int|null
     */
    public function getDiscountRate3()
    {
        return $this->discount_rate_3;
    }

}

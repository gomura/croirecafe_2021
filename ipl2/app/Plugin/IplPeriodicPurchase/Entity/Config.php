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
 * Config
 * 
 * @ORM\Table(name="plg_ipl_periodic_purchase_config")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
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
     * @ORM\Column(name="reception_address", type="string", length=255, nullable=true)
     */
    private $reception_address;

    /**
     * @var string
     * 
     * @ORM\Column(name="mypage_process", type="string", length=1024, nullable=true)
     */
    private $mypage_process;

    /**
     * @var integer
     * 
     * @ORM\Column(name="can_cancel_count", type="integer", nullable=true)
     */
    private $can_cancel_count;

    /**
     * @var integer
     * 
     * @ORM\Column(name="can_suspend_count", type="integer", nullable=true)
     */
    private $can_suspend_count;

    /**
     * @var integer
     * 
     * @ORM\Column(name="shipping_date_change_range", type="integer", nullable=true)
     */
    private $shipping_date_change_range;

    /**
     * @var integer
     * 
     * @ORM\Column(name="point_rate", type="integer", nullable=true)
     */
    private $point_rate;

    /**
     * @var integer
     * 
     * @ORM\Column(name="first_shipping_date", type="integer", nullable=true)
     */
    private $first_shipping_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="resume_next_shipping_date", type="integer", nullable=true)
     */
    private $resume_next_shipping_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="resettlement_next_shipping_date", type="integer", nullable=true)
     */
    private $resettlement_next_shipping_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="cutoff_date", type="integer", nullable=true)
     */
    private $cutoff_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="can_resume_date", type="integer", nullable=true)
     */
    private $can_resume_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="pre_information_date", type="integer", nullable=true)
     */
    private $pre_information_date;

    /**
     * @var string
     * 
     * @ORM\Column(name="notification_periodic_time", type="string", length=1024, nullable=true)
     */
    private $notification_periodic_time;

    /**
     * @var \Doctrine\Common\Collections\Collection|PeriodicDiscount[]
     *
     * @ORM\OneToMany(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount", mappedBy="Config", cascade={"persist"})
     */
    private $PeriodicDiscounts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->PeriodicDiscounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getReceptionAddress()
    {
        return $this->reception_address;
    }

    /**
     * {@inheritdoc}
     */
    public function setReceptionAddress($reception_address)
    {
        $this->reception_address = $reception_address;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMypageProcess()
    {
        return $this->mypage_process;
    }

    /**
     * {@inheritdoc}
     */
    public function setMypageProcess($mypage_process)
    {
        $this->mypage_process = $mypage_process;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanCancelCount()
    {
        return $this->can_cancel_count;
    }

    /**
     * {@inheritdoc}
     */
    public function setCanCancelCount($can_cancel_count)
    {
        $this->can_cancel_count = $can_cancel_count;

        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCanSuspendCount()
    {
        return $this->can_suspend_count;
    }

    /**
     * {@inheritdoc}
     */
    public function setCanSuspendCount($can_suspend_count)
    {
        $this->can_suspend_count = $can_suspend_count;

        return $this;
    }

        /**
     * {@inheritdoc}
     */
    public function getShippingDateChangeRange()
    {
        return $this->shipping_date_change_range;
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingDateChangeRange($shipping_date_change_range)
    {
        $this->shipping_date_change_range = $shipping_date_change_range;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPointRate()
    {
        return $this->point_rate;
    }

    /**
     * {@inheritdoc}
     */
    public function setPointRate($point_rate)
    {
        $this->point_rate = $point_rate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstShippingDate()
    {
        return $this->first_shipping_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstShippingDate($first_shipping_date)
    {
        $this->first_shipping_date = $first_shipping_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResumeNextShippingDate()
    {
        return $this->resume_next_shipping_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setResumeNextShippingDate($resume_next_shipping_date)
    {
        $this->resume_next_shipping_date = $resume_next_shipping_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResettlementNextShippingDate()
    {
        return $this->resettlement_next_shipping_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setResettlementNextShippingDate($resettlement_next_shipping_date)
    {
        $this->resettlement_next_shipping_date = $resettlement_next_shipping_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCutoffDate()
    {
        return $this->cutoff_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setCutoffDate($cutoff_date)
    {
        $this->cutoff_date = $cutoff_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanResumeDate()
    {
        return $this->can_resume_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setCanResumeDate($can_resume_date)
    {
        $this->can_resume_date = $can_resume_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreInformationDate()
    {
        return $this->pre_information_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setPreInformationDate($pre_information_date)
    {
        $this->pre_information_date = $pre_information_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationPeriodicTime()
    {
        $notification_periodic_time = unserialize($this->notification_periodic_time);

        if (!is_array($notification_periodic_time)) {
            $notification_periodic_time = array();
        }

        return $notification_periodic_time;
    }

    /**
     * {@inheritdoc}
     */
    public function setNotificationPeriodicTime($notification_periodic_time)
    {
        $notification_periodic_time = serialize($notification_periodic_time);
        if (is_bool($notification_periodic_time)) {
            $notification_periodic_time = '';
        }

        $this->notification_periodic_time = $notification_periodic_time;

        return $this;
    }

    /**
     * Add periodicDiscount.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount $periodicDiscount
     *
     * @return Order
     */
    public function addPeriodicDiscount(\Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount $periodicDiscount)
    {
        $this->PeriodicDiscounts[] = $periodicDiscount;

        return $this;
    }

    /**
     * Remove periodicDiscount.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount $periodicDiscount
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePeriodicDiscount(\Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount $periodicDiscount)
    {
        return $this->PeriodicDiscounts->removeElement($periodicDiscount);
    }

    /**
     * Get PeriodicDiscounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeriodicDiscount()
    {
        return $this->PeriodicDiscounts;
    }

}

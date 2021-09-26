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
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\PurchaseInterface;
use Eccube\Service\PurchaseFlow\ItemCollection;
use Plugin\IplPeriodicPurchase\Service\Calculator\PeriodicItemCollection;
use Eccube\Entity\AbstractEntity;
use Plugin\IplPeriodicPurchase\Entity\UseConvenience;

/**
 * PeriodicPurchase
 * 
 * @ORM\Table(name="plg_ipl_periodic_purchase_dtb_periodic_purchase")
 * @ORM\Entity(repositoryClass="Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository")
 */
class PeriodicPurchase extends AbstractEntity implements PurchaseInterface, ItemHolderInterface
{

    const CARD_CHANGE_MAIL_STATUS_UNSENT = 1;
    const CARD_CHANGE_MAIL_STATUS_SENT = 2;

    use \Eccube\Entity\NameTrait, \Eccube\Entity\PointTrait;

    /**
     * 同じ規格の商品の個数をまとめた受注明細を取得
     *
     * @return PeriodicPurchaseItem[]
     */
    public function getMergedProductPeriodicItems()
    {
        $ProductPeriodicItems = $this->getProductPeriodicItems();
        $periodicItemArray = [];
        /** @var PeriodicPurchaseItem $ProductPeriodicItem */
        foreach ($ProductPeriodicItems as $ProductPeriodicItem) {
            $productClassId = $ProductPeriodicItem->getProductClass()->getId();
            if (array_key_exists($productClassId, $periodicItemArray)) {
                // 同じ規格の商品がある場合は個数をまとめる
                /** @var ItemInterface $PeriodicPurchaseItem */
                $PeriodicPurchaseItem = $periodicItemArray[$productClassId];
                $quantity = $PeriodicPurchaseItem->getQuantity() + $ProductPeriodicItem->getQuantity();
                $PeriodicPurchaseItem->setQuantity($quantity);
            } else {
                // 新規規格の商品は新しく追加する
                $PeriodicPurchaseItem = new PeriodicPurchaseItem();
                $PeriodicPurchaseItem
                ->setProduct($ProductPeriodicItem->getProduct())
                ->setProductName($ProductPeriodicItem->getProductName())
                ->setProductCode($ProductPeriodicItem->getProductCode())
                ->setClassCategoryName1($ProductPeriodicItem->getClassCategoryName1())
                ->setClassCategoryName2($ProductPeriodicItem->getClassCategoryName2())
                ->setPrice($ProductPeriodicItem->getPrice())
                ->setTax($ProductPeriodicItem->getTax())
                ->setQuantity($ProductPeriodicItem->getQuantity());
                $periodicItemArray[$productClassId] = $PeriodicPurchaseItem;
            }
        }

        return array_values($periodicItemArray);
    }

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
     * @ORM\Column(name="billing_agreement_id", type="string", length=255, nullable=true)
     */
    private $billing_agreement_id;

    /**
     * @var string
     *
     * @ORM\Column(name="cycle_week", type="integer", nullable=true, options={"unsigned":true})
     */
    private $cycle_week;

    /**
     * @var string
     *
     * @ORM\Column(name="cycle_day", type="integer", nullable=true, options={"unsigned":true})
     */
    private $cycle_day;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="standard_next_shipping_date", type="datetimetz", nullable=true)
     */
    private $standard_next_shipping_date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="next_shipping_date", type="datetimetz", nullable=true)
     */
    private $next_shipping_date;

    /**
     * @var string
     *
     * @ORM\Column(name="next_shipping_time_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $next_shipping_time_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="next_delivery_time", type="string", length=255, nullable=true)
     */
    private $next_shipping_delivery_time;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="shipping_date", type="datetimetz", nullable=true)
     */
    private $shipping_date;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_time_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $shipping_time_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="delivery_time", type="string", length=255, nullable=true)
     */
    private $shipping_delivery_time;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="card_change_date", type="datetimetz", nullable=true)
     */
    private $card_change_date;

    /**
     * @var string
     *
     * @ORM\Column(name="skip_flg", type="integer", nullable=true, options={"unsigned":true})
     */
    private $skip_flg;

    /**
     * @var string
     *
     * @ORM\Column(name="periodic_point_rate", type="integer", nullable=true, options={"unsigned":true})
     */
    private $periodic_point_rate;

    /**
     * @var string
     *
     * @ORM\Column(name="periodic_purchase_count", type="integer", nullable=true, options={"unsigned":true})
     */
    private $periodic_purchase_count;

    /**
     * @var string|null
     *
     * @ORM\Column(name="first_order_id", type="string", length=255, nullable=true)
     */
    private $first_order_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_order_id", type="string", length=255, nullable=true)
     */
    private $last_order_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="string", length=4000, nullable=true)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name01", type="string", length=255)
     */
    private $name01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name02", type="string", length=255)
     */
    private $name02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana01", type="string", length=255, nullable=true)
     */
    private $kana01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana02", type="string", length=255, nullable=true)
     */
    private $kana02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    private $company_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone_number", type="string", length=14, nullable=true)
     */
    private $phone_number;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postal_code", type="string", length=8, nullable=true)
     */
    private $postal_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr01", type="string", length=255, nullable=true)
     */
    private $addr01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr02", type="string", length=255, nullable=true)
     */
    private $addr02;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="birth", type="datetimetz", nullable=true)
     */
    private $birth;

    /**
     * @var string
     *
     * @ORM\Column(name="subtotal", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $subtotal;

    /**
     * @var string
     *
     * @ORM\Column(name="discount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $discount;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_fee_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $delivery_fee_total;

    /**
     * @var string
     *
     * @ORM\Column(name="charge", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $charge = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tax", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     *
     * @deprecated 明細ごとに集計した税額と差異が発生する場合があるため非推奨
     */
    private $tax = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $total = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $payment_total = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="payment_method", type="string", length=255, nullable=true)
     */
    private $payment_method;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="string", length=4000, nullable=true)
     */
    private $note;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="payment_date", type="datetimetz", nullable=true)
     */
    private $payment_date;

    /**
     * @var string
     *
     * @ORM\Column(name="gmo_card_seq_no", type="integer", nullable=true, options={"unsigned":true})
     */
    private $gmo_card_seq_no;

    /**
     * @var \Doctrine\Common\Collections\Collection|PeriodicPurchaseItem[]
     *
     * @ORM\OneToMany(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem", mappedBy="PeriodicPurchase", cascade={"persist","remove"})
     */
    private $PeriodicPurchaseItems;

    /**
     * @var \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping
     *
     * @ORM\OneToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping", mappedBy="PeriodicPurchase", cascade={"persist","remove"})
     */
    private $PeriodicPurchaseShipping;

    /**
     * @var \Doctrine\Common\Collections\Collection|Order[]
     *
     * @ORM\OneToMany(targetEntity="Eccube\Entity\Order", mappedBy="PeriodicPurchase")
     */
    private $Orders;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="PeriodicPurchases")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var \Eccube\Entity\Master\Country
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $Country;

    /**
     * @var \Eccube\Entity\Master\Pref
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pref_id", referencedColumnName="id")
     * })
     */
    private $Pref;

    /**
     * @var \Eccube\Entity\Master\Sex
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Sex")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sex_id", referencedColumnName="id")
     * })
     */
    private $Sex;

    /**
     * @var \Eccube\Entity\Master\Job
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Job")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="job_id", referencedColumnName="id")
     * })
     */
    private $Job;

    /**
     * @var \Eccube\Entity\Payment
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     * })
     */
    private $Payment;

    /**
     * OrderStatusより先にプロパティを定義しておかないとセットされなくなる
     *
     * @var \Plugin\IplPeriodicPurchase\Entity\PeriodicStatusColor
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicStatusColor")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="periodic_status_id", referencedColumnName="id")
     * })
     */
    private $PeriodicStatusColor;

    /**
     * @var \Plugin\IplPeriodicPurchase\Entity\PeriodicStatus
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="periodic_status_id", referencedColumnName="id")
     * })
     */
    private $PeriodicStatus;

    /**
     * @var Plugin\IplPeriodicPurchase\Entity\Cycle
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\Cycle")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cycle_id", referencedColumnName="id")
     * })
     */
    private $Cycle;

    /**
     * @var Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount
     *
     * @ORM\ManyToOne(targetEntity="Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="perodic_discount_id", referencedColumnName="id")
     * })
     */
    private $PeriodicDiscount;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Orders = new \Doctrine\Common\Collections\ArrayCollection();
        $this->PeriodicPurchaseItems = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set billingAgreementId.
     *
     * @param string|null $billingAgreementId
     *
     * @return PeriodicPurchase
     */
    public function setBillingAgreementId($billingAgreementId = null)
    {
        $this->billing_agreement_id = $billingAgreementId;

        return $this;
    }

    /**
     * Get billingAgreementId.
     *
     * @return string|null
     */
    public function getBillingAgreementId()
    {
        return $this->billing_agreement_id;
    }

    /**
     * Set cycleWeek.
     *
     * @param int|null $cycleWeek
     *
     * @return PeriodicPurchase
     */
    public function setCycleWeek($cycleWeek = null)
    {
        $this->cycle_week = $cycleWeek;

        return $this;
    }

    /**
     * Get cycleWeek.
     *
     * @return int|null
     */
    public function getCycleWeek()
    {
        return $this->cycle_week;
    }

    /**
     * Set cycleDay.
     *
     * @param int|null $cycleDay
     *
     * @return PeriodicPurchase
     */
    public function setCycleDay($cycleDay = null)
    {
        $this->cycle_day = $cycleDay;

        return $this;
    }

    /**
     * Get cycleDay.
     *
     * @return int|null
     */
    public function getCycleDay()
    {
        return $this->cycle_day;
    }

    /**
     * Set standardNextShippingDate.
     *
     * @param \DateTime|null $standardNextShippingDate
     *
     * @return PeriodicPurchase
     */
    public function setStandardNextShippingDate($standardNextShippingDate = null)
    {
        $this->standard_next_shipping_date = $standardNextShippingDate;

        return $this;
    }

    /**
     * Get standardNextShippingDate.
     *
     * @return \DateTime|null
     */
    public function getStandardNextShippingDate()
    {
        return $this->standard_next_shipping_date;
    }

    /**
     * Set nextShippingDate.
     *
     * @param \DateTime|null $nextShippingDate
     *
     * @return PeriodicPurchase
     */
    public function setNextShippingDate($nextShippingDate = null)
    {
        $this->next_shipping_date = $nextShippingDate;

        return $this;
    }

    /**
     * Get nextShippingDate.
     *
     * @return \DateTime|null
     */
    public function getNextShippingDate()
    {
        return $this->next_shipping_date;
    }

    /**
     * Set nextShippingTimeId.
     *
     * @param int|null $nextShippingTimeId
     *
     * @return PeriodicPurchase
     */
    public function setNextShippingTimeId($nextShippingTimeId = null)
    {
        $this->next_shipping_time_id = $nextShippingTimeId;

        return $this;
    }

    /**
     * Get nextShippingTimeId.
     *
     * @return int|null
     */
    public function getNextShippingTimeId()
    {
        return $this->next_shipping_time_id;
    }

    /**
     * Set nextShippingDeliveryTime.
     *
     * @param string|null $nextShippingDeliveryTime
     *
     * @return Shipping
     */
    public function setNextShippingDeliveryTime($nextShippingDeliveryTime = null)
    {
        $this->next_shipping_delivery_time = $nextShippingDeliveryTime;

        return $this;
    }

    /**
     * Get nextShippingDeliveryTime.
     *
     * @return string|null
     */
    public function getNextShippingDeliveryTime()
    {
        return $this->next_shipping_delivery_time;
    }

    /**
     * Set shippingDate.
     *
     * @param \DateTime|null $shippingDate
     *
     * @return PeriodicPurchase
     */
    public function setShippingDate($shippingDate = null)
    {
        $this->shipping_date = $shippingDate;

        return $this;
    }

    /**
     * Get shippingDate.
     *
     * @return \DateTime|null
     */
    public function getShippingDate()
    {
        return $this->shipping_date;
    }

    /**
     * Set shippingTimeId.
     *
     * @param int|null $shippingTimeId
     *
     * @return PeriodicPurchase
     */
    public function setShippingTimeId($shippingTimeId = null)
    {
        $this->shipping_time_id = $shippingTimeId;

        return $this;
    }

    /**
     * Get shippingTimeId.
     *
     * @return int|null
     */
    public function getShippingTimeId()
    {
        return $this->shipping_time_id;
    }

    /**
     * Set shippingDeliveryTime.
     *
     * @param string|null $shippingDeliveryTime
     *
     * @return Shipping
     */
    public function setShippingDeliveryTime($shippingDeliveryTime = null)
    {
        $this->shipping_delivery_time = $shippingDeliveryTime;

        return $this;
    }

    /**
     * Get shippingDeliveryTime.
     *
     * @return string|null
     */
    public function getShippingDeliveryTime()
    {
        return $this->shipping_delivery_time;
    }

    /**
     * Set cardChangeDate.
     *
     * @param \DateTime|null $cardChangeDate
     *
     * @return PeriodicPurchase
     */
    public function setCardChangeDate($cardChangeDate = null)
    {
        $this->card_change_date = $cardChangeDate;

        return $this;
    }

    /**
     * Get cardChangeDate.
     *
     * @return \DateTime|null
     */
    public function getCardChangeDate()
    {
        return $this->card_change_date;
    }

    /**
     * Set skipFlg.
     *
     * @param int|null $skipFlg
     *
     * @return PeriodicPurchase
     */
    public function setSkipFlg($skipFlg = null)
    {
        $this->skip_flg = $skipFlg;

        return $this;
    }

    /**
     * Get skipFlg.
     *
     * @return int|null
     */
    public function getSkipFlg()
    {
        return $this->skip_flg;
    }

    /**
     * Set periodicPointRate.
     *
     * @param int|null $periodicPointRate
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicPointRate($periodicPointRate = null)
    {
        $this->periodic_point_rate = $periodicPointRate;

        return $this;
    }

    /**
     * Get periodicPointRate.
     *
     * @return int|null
     */
    public function getPeriodicPointRate()
    {
        return $this->periodic_point_rate;
    }

    /**
     * Set periodicPurchaseCount.
     *
     * @param int|null $periodicPurchaseCount
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicPurchaseCount($periodicPurchaseCount = null)
    {
        $this->periodic_purchase_count = $periodicPurchaseCount;

        return $this;
    }

    /**
     * Get periodicPurchaseCount.
     *
     * @return int|null
     */
    public function getPeriodicPurchaseCount()
    {
        return $this->periodic_purchase_count;
    }

    /**
     * Set firstOrderId.
     *
     * @param string|null $firstOrderId
     *
     * @return PeriodicPurchase
     */
    public function setFirstOrderId($firstOrderId = null)
    {
        $this->first_order_id = $firstOrderId;

        return $this;
    }

    /**
     * Get firstOrderId.
     *
     * @return string|null
     */
    public function getFirstOrderId()
    {
        return $this->first_order_id;
    }

    /**
     * Set lastOrderId.
     *
     * @param string|null $lastOrderId
     *
     * @return PeriodicPurchase
     */
    public function setLastOrderId($lastOrderId = null)
    {
        $this->last_order_id = $lastOrderId;

        return $this;
    }

    /**
     * Get lastOrderId.
     *
     * @return string|null
     */
    public function getLastOrderId()
    {
        return $this->last_order_id;
    }

    /**
     * Set message.
     *
     * @param string|null $message
     *
     * @return PeriodicPurchase
     */
    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set name01.
     *
     * @param string $name01
     *
     * @return PeriodicPurchase
     */
    public function setName01($name01)
    {
        $this->name01 = $name01;

        return $this;
    }

    /**
     * Get name01.
     *
     * @return string
     */
    public function getName01()
    {
        return $this->name01;
    }

    /**
     * Set name02.
     *
     * @param string $name02
     *
     * @return PeriodicPurchase
     */
    public function setName02($name02)
    {
        $this->name02 = $name02;

        return $this;
    }

    /**
     * Get name02.
     *
     * @return string
     */
    public function getName02()
    {
        return $this->name02;
    }

    /**
     * Set kana01.
     *
     * @param string|null $kana01
     *
     * @return PeriodicPurchase
     */
    public function setKana01($kana01 = null)
    {
        $this->kana01 = $kana01;

        return $this;
    }

    /**
     * Get kana01.
     *
     * @return string|null
     */
    public function getKana01()
    {
        return $this->kana01;
    }

    /**
     * Set kana02.
     *
     * @param string|null $kana02
     *
     * @return PeriodicPurchase
     */
    public function setKana02($kana02 = null)
    {
        $this->kana02 = $kana02;

        return $this;
    }

    /**
     * Get kana02.
     *
     * @return string|null
     */
    public function getKana02()
    {
        return $this->kana02;
    }

    /**
     * Set companyName.
     *
     * @param string|null $companyName
     *
     * @return PeriodicPurchase
     */
    public function setCompanyName($companyName = null)
    {
        $this->company_name = $companyName;

        return $this;
    }

    /**
     * Get companyName.
     *
     * @return string|null
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return PeriodicPurchase
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phoneNumber.
     *
     * @param string|null $phoneNumber
     *
     * @return PeriodicPurchase
     */
    public function setPhoneNumber($phoneNumber = null)
    {
        $this->phone_number = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber.
     *
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * Set postalCode.
     *
     * @param string|null $postalCode
     *
     * @return PeriodicPurchase
     */
    public function setPostalCode($postalCode = null)
    {
        $this->postal_code = $postalCode;

        return $this;
    }

    /**
     * Get postalCode.
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * Set addr01.
     *
     * @param string|null $addr01
     *
     * @return PeriodicPurchase
     */
    public function setAddr01($addr01 = null)
    {
        $this->addr01 = $addr01;

        return $this;
    }

    /**
     * Get addr01.
     *
     * @return string|null
     */
    public function getAddr01()
    {
        return $this->addr01;
    }

    /**
     * Set addr02.
     *
     * @param string|null $addr02
     *
     * @return PeriodicPurchase
     */
    public function setAddr02($addr02 = null)
    {
        $this->addr02 = $addr02;

        return $this;
    }

    /**
     * Get addr02.
     *
     * @return string|null
     */
    public function getAddr02()
    {
        return $this->addr02;
    }

    /**
     * Set birth.
     *
     * @param \DateTime|null $birth
     *
     * @return PeriodicPurchase
     */
    public function setBirth($birth = null)
    {
        $this->birth = $birth;

        return $this;
    }

    /**
     * Get birth.
     *
     * @return \DateTime|null
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * Set subtotal.
     *
     * @param string $subtotal
     *
     * @return PeriodicPurchase
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * Get subtotal.
     *
     * @return string
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * Set discount.
     *
     * @param string $discount
     *
     * @return PeriodicPurchase
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return string
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set deliveryFeeTotal.
     *
     * @param string $deliveryFeeTotal
     *
     * @return PeriodicPurchase
     */
    public function setDeliveryFeeTotal($deliveryFeeTotal)
    {
        $this->delivery_fee_total = $deliveryFeeTotal;

        return $this;
    }

    /**
     * Get deliveryFeeTotal.
     *
     * @return string
     */
    public function getDeliveryFeeTotal()
    {
        return $this->delivery_fee_total;
    }

    /**
     * Set charge.
     *
     * @param string $charge
     *
     * @return PeriodicPurchase
     */
    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * Get charge.
     *
     * @return string
     */
    public function getCharge()
    {
        return $this->charge;
    }

    /**
     * Set tax.
     *
     * @param string $tax
     *
     * @return PeriodicPurchase
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * Get tax.
     *
     * @return string
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Set total.
     *
     * @param string $total
     *
     * @return PeriodicPurchase
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total.
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set paymentTotal.
     *
     * @param string $paymentTotal
     *
     * @return PeriodicPurchase
     */
    public function setPaymentTotal($paymentTotal)
    {
        $this->payment_total = $paymentTotal;

        return $this;
    }

    /**
     * Get paymentTotal.
     *
     * @return string
     */
    public function getPaymentTotal()
    {
        return $this->payment_total;
    }

    /**
     * Set paymentMethod.
     *
     * @param string|null $paymentMethod
     *
     * @return PeriodicPurchase
     */
    public function setPaymentMethod($paymentMethod = null)
    {
        $this->payment_method = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod.
     *
     * @return string|null
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set note.
     *
     * @param string|null $note
     *
     * @return PeriodicPurchase
     */
    public function setNote($note = null)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return PeriodicPurchase
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return PeriodicPurchase
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set paymentDate.
     *
     * @param \DateTime|null $paymentDate
     *
     * @return PeriodicPurchase
     */
    public function setPaymentDate($paymentDate = null)
    {
        $this->payment_date = $paymentDate;

        return $this;
    }

    /**
     * Get paymentDate.
     *
     * @return \DateTime|null
     */
    public function getPaymentDate()
    {
        return $this->payment_date;
    }

    /**
     * Set gmoCardSeqNo.
     *
     * @param int|null $gmoCardSeqNo
     *
     * @return PeriodicPurchase
     */
    public function setGmoCardSeqNo($gmoCardSeqNo = null)
    {
        $this->gmo_card_seq_no = $gmoCardSeqNo;

        return $this;
    }

    /**
     * Get gmoCardSeqNo.
     *
     * @return int|null
     */
    public function getGmoCardSeqNo()
    {
        return $this->gmo_card_seq_no;
    }

    /**
     * 商品の受注明細を取得
     *
     * @return PeriodicPurchaseItem[]
     */
    public function getProductPeriodicItems()
    {
        $sio = new PeriodicItemCollection($this->PeriodicPurchaseItems->toArray());

        return array_values($sio->getProductClasses()->toArray());
    }

    /**
     * 商品の受注明細を取得
     *
     * @return PeriodicPurchaseItem[]
     */
    public function getItems()
    {
        return (new ItemCollection($this->getPeriodicPurchaseItems()))->sort();

    }

    public function getQuantity()
    {
        $quantity = 0;
        foreach ($this->getItems() as $item) {
            $quantity += $item->getQuantity();
        }

        return $quantity;
    }

    /**
     * @param ItemInterface $item
     */
    public function addItem(ItemInterface $item)
    {
        $this->OrderItems->add($item);
    }

    /**
     * Add periodicPurchaseItem.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem $periodicPurchaseItem
     *
     * @return PeriodicPurchase
     */
    public function addPeriodicPurchaseItem(\Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem $periodicPurchaseItem)
    {
        $this->PeriodicPurchaseItems[] = $periodicPurchaseItem;

        return $this;
    }

    /**
     * Remove periodicPurchaseItem.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem $periodicPurchaseItem
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePeriodicPurchaseItem(\Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem $periodicPurchaseItem)
    {
        return $this->PeriodicPurchaseItems->removeElement($periodicPurchaseItem);
    }

    /**
     * Get periodicPurchaseItems.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeriodicPurchaseItems()
    {
        return $this->PeriodicPurchaseItems;
    }

    /**
     * Set periodicPurchaseShipping.
     *
     * @param \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping|null $periodicPurchaseShipping
     *
     * @return OrderItem
     */
    public function setPeriodicPurchaseShipping(\Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping $periodicPurchaseShipping = null)
    {
        $this->PeriodicPurchaseShipping = $periodicPurchaseShipping;

        return $this;
    }

    /**
     * Get periodicPurchaseShipping.
     *
     * @return \Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping|null
     */
    public function getPeriodicPurchaseShipping()
    {
        return $this->PeriodicPurchaseShipping;
    }

    /**
     * Add order.
     *
     * @param \Eccube\Entity\Order $Order
     *
     * @return PeriodicPurchase
     */
    public function addOrder(\Eccube\Entity\Order $Order)
    {
        $this->Orders[] = $Order;

        return $this;
    }

    /**
     * Remove order.
     *
     * @param \Eccube\Entity\Order $Order
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrder(\Eccube\Entity\Order $Order)
    {
        return $this->Orders->removeElement($Order);
    }

    /**
     * Get orders.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrders()
    {
        return $this->Orders;
        // return (new ItemCollection($this->Orders))->sort();
    }

    /**
     * Set customer.
     *
     * @param \Eccube\Entity\Customer|null $customer
     *
     * @return PeriodicPurchase
     */
    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return \Eccube\Entity\Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set country.
     *
     * @param \Eccube\Entity\Master\Country|null $country
     *
     * @return Order
     */
    public function setCountry(\Eccube\Entity\Master\Country $country = null)
    {
        $this->Country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return \Eccube\Entity\Master\Country|null
     */
    public function getCountry()
    {
        return $this->Country;
    }

    /**
     * Set pref.
     *
     * @param \Eccube\Entity\Master\Pref|null $pref
     *
     * @return Order
     */
    public function setPref(\Eccube\Entity\Master\Pref $pref = null)
    {
        $this->Pref = $pref;

        return $this;
    }

    /**
     * Get pref.
     *
     * @return \Eccube\Entity\Master\Pref|null
     */
    public function getPref()
    {
        return $this->Pref;
    }

    /**
     * Set sex.
     *
     * @param \Eccube\Entity\Master\Sex|null $sex
     *
     * @return Order
     */
    public function setSex(\Eccube\Entity\Master\Sex $sex = null)
    {
        $this->Sex = $sex;

        return $this;
    }

    /**
     * Get sex.
     *
     * @return \Eccube\Entity\Master\Sex|null
     */
    public function getSex()
    {
        return $this->Sex;
    }

    /**
     * Set job.
     *
     * @param \Eccube\Entity\Master\Job|null $job
     *
     * @return Order
     */
    public function setJob(\Eccube\Entity\Master\Job $job = null)
    {
        $this->Job = $job;

        return $this;
    }

    /**
     * Get job.
     *
     * @return \Eccube\Entity\Master\Job|null
     */
    public function getJob()
    {
        return $this->Job;
    }

    /**
     * Set payment.
     *
     * @param \Eccube\Entity\Payment|null $payment
     *
     * @return PeriodicPurchase
     */
    public function setPayment(\Eccube\Entity\Payment $payment = null)
    {
        $this->Payment = $payment;

        return $this;
    }

    /**
     * Get payment.
     *
     * @return \Eccube\Entity\Payment|null
     */
    public function getPayment()
    {
        return $this->Payment;
    }

    /**
     * Set periodicStatusColor.
     *
     * @param \Eccube\Entity\Master\PeriodicStatusColor|null $periodicStatusColor
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicStatusColor(\Plugin\IplPeriodicPurchase\Entity\PeriodicStatusColor $periodicStatusColor = null)
    {
        $this->PeriodicStatusColor = $periodicStatusColor;

        return $this;
    }

    /**
     * Get periodicStatusColor.
     *
     * @return \Eccube\Entity\Master\PeriodicStatusColor|null
     */
    public function getPeriodicStatusColor()
    {
        return $this->PeriodicStatusColor;
    }

    /**
     * Set periodicStatus.
     *
     * @param \Eccube\Entity\Master\PeriodicStatus|null $periodicStatus
     *
     * @return PeriodicPurchase
     */
    public function setPeriodicStatus(\Plugin\IplPeriodicPurchase\Entity\PeriodicStatus $periodicStatus = null)
    {
        $this->PeriodicStatus = $periodicStatus;

        return $this;
    }

    /**
     * Get periodicStatus.
     *
     * @return \Eccube\Entity\Master\PeriodicStatus|null
     */
    public function getPeriodicStatus()
    {
        return $this->PeriodicStatus;
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


    /**
     * Get OrderStatus.
     *
     * @return null
     */
    public function getOrderStatus()
    {
        // OrderPurchaseFlowでの未定義エラー回避
        return null;
    }
}

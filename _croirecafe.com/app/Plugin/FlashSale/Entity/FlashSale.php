<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Service\Rule\RuleFactory;
use Plugin\FlashSale\Service\Promotion\PromotionFactory;
use Plugin\FlashSale\Service\Condition\ConditionFactory;
use Plugin\FlashSale\Service\Rule\RuleInterface;

/**
 * FlashSale
 *
 * @ORM\Table(name="plg_flash_sale_flash_sale")
 * @ORM\Entity(repositoryClass="Plugin\FlashSale\Repository\FlashSaleRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FlashSale
{
    const STATUS_DRAFT = 0;
    const STATUS_ACTIVATED = 1;
    const STATUS_DELETED = 2;

    public static $statusList = [
        self::STATUS_DRAFT => 'flash_sale.admin.list.status.draft',
        self::STATUS_ACTIVATED => 'flash_sale.admin.list.status.activated',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="from_time", type="datetimetz", nullable=true)
     */
    private $from_time;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="to_time", type="datetimetz", nullable=true)
     */
    private $to_time;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"default":true})
     */
    private $status;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=true)
     */
    private $created_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="update_at", type="datetimetz", nullable=true)
     */
    private $updated_at;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     * })
     */
    private $created_by;

    /**
     * @var ArrayCollection Rule
     *
     * @ORM\OneToMany(targetEntity=Rule::class, mappedBy="FlashSale", indexBy="id", cascade={"persist","remove"})
     * @ORM\OrderBy({"sort_no" = "ASC"})
     */
    private $Rules;

    /**
     * FlashSale constructor.
     */
    public function __construct()
    {
        $this->Rules = new ArrayCollection();

        // set default for date
        $this->from_time = new \DateTime();
        $this->to_time = (new \DateTime())->modify('+1 day');
    }

    /**
     * @param array $criteria
     *
     * @return DoctrineCollection
     */
    public function getRules(array $criteria = []): DoctrineCollection
    {
        if (isset($criteria['type'])) {
            return $this->Rules->filter(function ($Rule) use ($criteria) {
                return $Rule::TYPE === $criteria['type'];
            });
        }

        return $this->Rules;
    }

    /**
     * @param Rule $rule
     */
    public function addRule(Rule $rule): void
    {
        $this->Rules->add($rule);
    }

    public function removeRule(Rule $rule)
    {
        $this->Rules->removeElement($rule);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \DateTime|null
     */
    public function getFromTime()
    {
        return $this->from_time;
    }

    /**
     * @param $from_time
     */
    public function setFromTime($from_time)
    {
        $this->from_time = $from_time;
    }

    /**
     * @return \DateTime|null
     */
    public function getToTime()
    {
        return $this->to_time;
    }

    /**
     * @param $to_time
     */
    public function setToTime($to_time)
    {
        $this->to_time = $to_time;
    }

    /**
     * @return mixed
     */
    public function getStatusText()
    {
        return FlashSale::$statusList[$this->status];
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param $updated_at
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return \Eccube\Entity\Member
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param $created_by
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
    }

    /**
     * Get data as array
     *
     * @param $data
     *
     * @return array
     */
    public function rawData($data = null)
    {
        $result = [];
        if ($data) {
            $jsonData = json_decode($data, true);
            if (is_array($result) && !isset($result['id'])) {
                $result['rules'] = $jsonData;
            }
        } else {
            $result['rules'] = [];
            /** @var Rule $Rule */
            foreach ($this->getRules() as $Rule) {
                $result['rules'][] = $Rule->rawData();
            }
        }

        return $result;
    }

    /**
     * Extract Rule from normalize data
     *
     * @param $data
     */
    public function updateFromArray($data)
    {
        foreach ($data['rules'] as $key => $rule) {
            if (!empty($rule['id'])) {
                $Rule = $this->getRules()->get($rule['id']);
                if ($Rule::TYPE != $rule['type']) {
                    $this->getRules()->remove($Rule->getId());
                    $this->removed[] = $Rule;
                    $this->removed[] = $Rule->getPromotion();
                    foreach ($Rule->getConditions() as $Condition) {
                        $this->removed[] = $Condition;
                    }
                    $Rule = RuleFactory::createFromArray($rule);
                    $this->getRules()->add($Rule);
                }
            } else {
                $Rule = RuleFactory::createFromArray($rule);
                $this->getRules()->add($Rule);
            }
            $Rule->modified = true;
            $Rule->setFlashSale($this);
            $Rule->setOperator($rule['operator']);
            $Rule->setSortNo($key);

            if (!empty($rule['promotion'])) {
                $Promotion = $Rule->getPromotion();
                if (!$Promotion) {
                    $Promotion = PromotionFactory::createFromArray($rule['promotion']);
                } elseif ($Promotion::TYPE != $rule['promotion']['type']) {
                    $this->removed[] = $Promotion;
                    $Promotion = PromotionFactory::createFromArray($rule['promotion']);
                }
                $Promotion->modified = true;
                $Promotion->setValue($rule['promotion']['value']);
                $Promotion->setRule($Rule);
                $Rule->setPromotion($Promotion);
            }
            if (isset($rule['conditions'])) {
                if (!$Rule->getConditions()) {
                    $Rule->setConditions(new ArrayCollection());
                }
                foreach ($rule['conditions'] as $num => $condition) {
                    if (!empty($condition['id']) && $Rule->getConditions()->containsKey($condition['id'])) {
                        $Condition = $Rule->getConditions()->get($condition['id']);
                        if ($Condition::TYPE != $condition['type']) {
                            $Rule->getConditions()->remove($Condition->getId());
                            $this->removed[] = $Condition;
                            $Condition = ConditionFactory::createFromArray($condition);
                            $Rule->getConditions()->add($Condition);
                        }
                    } else {
                        $Condition = ConditionFactory::createFromArray($condition);
                        $Rule->getConditions()->add($Condition);
                    }
                    $Condition->modified = true;
                    $Condition->setOperator($condition['operator']);
                    $Condition->setValue($condition['value']);
                    $Condition->setSortNo($num);
                    $Condition->setRule($Rule);
                }
            }
        }
    }

    /**
     * Get discount items of flashsale
     *
     * @param $object
     *
     * @return DiscountInterface
     */
    public function getDiscount($object)
    {
        $discount = new Discount();
        $Rules = [];

        /* @var $Rules RuleInterface[] */
        if ($object instanceof ProductClass) {
            $Rules = array_reverse($this->getRules(['type' => ProductClassRule::TYPE])->toArray());
        } elseif ($object instanceof Cart || $object instanceof Order) {
            $Rules = array_reverse($this->getRules(['type' => CartRule::TYPE])->toArray());
        }

        foreach ($Rules as $Rule) {
            if (!$Rule->match($object)) {
                continue;
            }

            $discount = $Rule->getDiscount($object);
            if ($discount->getValue()) {
                return $discount;
            }
        }

        return $discount;
    }
}

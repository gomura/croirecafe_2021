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
use Eccube\Entity\AbstractEntity;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Service\Metadata\DiscriminatorInterface;
use Plugin\FlashSale\Service\Operator;
use Plugin\FlashSale\Service\Promotion\PromotionInterface;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Service\Rule\RuleInterface;

/**
 * @ORM\Table("plg_flash_sale_rule")
 * @ORM\Entity(repositoryClass="Plugin\FlashSale\Repository\RuleRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\DiscriminatorMap({ProductClassRule::TYPE=ProductClassRule::class, CartRule::TYPE=CartRule::class})
 * @ORM\HasLifecycleCallbacks()
 */
abstract class Rule extends AbstractEntity implements RuleInterface
{
    const TYPE = 'rule';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", length=32, nullable=false)
     */
    protected $operator;

    /**
     * @var FlashSale
     *
     * @ORM\ManyToOne(targetEntity=FlashSale::class, inversedBy="Rules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="flash_sale_id", referencedColumnName="id")
     * })
     */
    protected $FlashSale;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity=Condition::class, mappedBy="Rule", indexBy="id", cascade={"persist"})
     * @ORM\OrderBy({"sort_no" = "ASC"})
     */
    protected $Conditions;

    /**
     * @var Promotion
     * @ORM\OneToOne(targetEntity=Promotion::class, mappedBy="Rule", cascade={"persist"})
     */
    protected $Promotion;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", nullable=true)
     */
    protected $sort_no;

    /**
     * @var DiscriminatorInterface
     */
    protected $discriminator;

    /**
     * @var Operator\OperatorFactory
     */
    protected $operatorFactory;

    /**
     * Rule constructor.
     */
    public function __construct()
    {
        $this->Conditions = new ArrayCollection();
    }

    /**
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->Conditions;
    }

    /**
     * @param Condition $condition
     */
    public function addConditions(Condition $condition): void
    {
        $this->Conditions->add($condition);
    }

    /**
     * @param Condition $condition
     *
     * @return bool
     */
    public function removeCondition(Condition $condition)
    {
        return $this->Conditions->removeElement($condition);
    }

    public function getFlashSale()
    {
        return $this->FlashSale;
    }

    /**
     * Set FlashSale
     *
     * @param $FlashSale
     *
     * @return $this
     */
    public function setFlashSale(FlashSale $FlashSale)
    {
        $this->FlashSale = $FlashSale;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * @return PromotionInterface
     */
    public function getPromotion()
    {
        return $this->Promotion;
    }

    /**
     * @param Promotion $Promotion
     */
    public function setPromotion(Promotion $Promotion): void
    {
        $this->Promotion = $Promotion;
    }

    /**
     * @return int
     */
    public function getSortNo(): int
    {
        return $this->sort_no;
    }

    /**
     * @param int $sort_no
     */
    public function setSortNo(int $sort_no)
    {
        $this->sort_no = $sort_no;
    }

    /**
     * Get $discriminator
     *
     * @return DiscriminatorInterface
     */
    public function getDiscriminator(): DiscriminatorInterface
    {
        return $this->discriminator;
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
        if ($data) {
            $result = json_decode($data, true);
        } else {
            $result = [
                'id' => intval($this->getId()),
                'type' => static::TYPE,
                'operator' => $this->getOperator(),
            ];
            /** @var Condition $Condition */
            foreach ($this->getConditions() as $Condition) {
                $result['conditions'][] = $Condition->rawData();
            }
            $result['promotion'] = $this->getPromotion()->rawData();
        }

        return $result;
    }

    /**
     * Set $operatorFactory
     *
     * @param Operator\OperatorFactory $operatorFactory
     *
     * @return CartRule
     * @required
     */
    public function setOperatorFactory(Operator\OperatorFactory $operatorFactory)
    {
        $this->operatorFactory = $operatorFactory;

        return $this;
    }

    /**
     * @return Operator\OperatorFactory
     */
    public function getOperatorFactory()
    {
        return $this->operatorFactory;
    }
}

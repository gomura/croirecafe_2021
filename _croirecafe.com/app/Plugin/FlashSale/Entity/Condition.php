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

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\FlashSale\Repository\ConditionRepository;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\Condition\ProductCategoryIdCondition;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;
use Plugin\FlashSale\Service\Condition\ConditionInterface;
use Plugin\FlashSale\Service\Operator;

/**
 * @ORM\Table("plg_flash_sale_condition")
 * @ORM\Entity(repositoryClass=ConditionRepository::class)
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\DiscriminatorMap({
 *     ProductClassIdCondition::TYPE=ProductClassIdCondition::class,
 *     ProductCategoryIdCondition::TYPE=ProductCategoryIdCondition::class,
 *     CartTotalCondition::TYPE=CartTotalCondition::class,
 * })
 */
abstract class Condition extends AbstractEntity implements ConditionInterface
{
    const TYPE = 'condition';

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
     * @var string
     *
     * @ORM\Column(name="value", type="string", nullable=false)
     */
    protected $value;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", nullable=true)
     */
    protected $sort_no;

    /**
     * @var Rule
     *
     * @ORM\ManyToOne(targetEntity=Rule::class, inversedBy="Conditions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rule_id", referencedColumnName="id")
     * })
     */
    protected $Rule;

    /**
     * @var Operator\OperatorFactory
     */
    protected $operatorFactory;

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
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
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
     * @return Rule
     */
    public function getRule(): Rule
    {
        return $this->Rule;
    }

    /**
     * @param Rule $Rule
     */
    public function setRule(Rule $Rule): void
    {
        $this->Rule = $Rule;
    }

    /**
     * Get data as array
     *
     * @param null $data
     *
     * @return array
     */
    public function rawData($data = null)
    {
        $result = [];
        if ($data) {
            $result = json_decode($data, true);
        } else {
            $result['id'] = intval($this->getId());
            $result['type'] = static::TYPE;
            $result['operator'] = $this->getOperator();
            $result['value'] = $this->getValue();
        }

        return $result;
    }

    /**
     * @param Operator\OperatorFactory $operatorFactory
     *
     * @return $this
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
    public function getOperatorFactory(): Operator\OperatorFactory
    {
        return $this->operatorFactory;
    }
}

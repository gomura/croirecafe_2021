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

namespace Plugin\FlashSale\Entity\Rule;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\Condition\ProductCategoryIdCondition;
use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Entity\Promotion\ProductClassPriceAmountPromotion;
use Plugin\FlashSale\Exception\RuleException;
use Plugin\FlashSale\Service\Operator\OperatorInterface;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Service\Metadata\DiscriminatorManager;
use Plugin\FlashSale\Entity\DiscountInterface;
use Plugin\FlashSale\Entity\Discount;

/**
 * @ORM\Entity
 */
class ProductClassRule extends Rule
{
    const TYPE = 'rule_product_class';

    /**
     * @var array
     */
    protected $cached;

    /**
     * @var DiscriminatorManager
     */
    protected $discriminatorManager;

    /**
     * @param DiscriminatorManager $discriminatorManager
     *
     * @return $this
     * @required
     */
    public function setDiscriminatorManager(DiscriminatorManager $discriminatorManager)
    {
        $this->discriminatorManager = $discriminatorManager;
        $this->discriminator = $discriminatorManager->get(static::TYPE);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getOperatorTypes(): array
    {
        return [
            Operator\OrOperator::TYPE,
            Operator\AllOperator::TYPE,
        ];
    }

    /**
     * @param QueryBuilder $qb
     * @param OperatorInterface $operatorRule
     *
     * @return QueryBuilder
     *
     * @throws RuleException
     * @throws \Plugin\FlashSale\Exception\ConditionException
     */
    public function createQueryBuilder(QueryBuilder $qb, OperatorInterface $operatorRule): QueryBuilder
    {
        if (!in_array($operatorRule->getType(), $this->getOperatorTypes())) {
            throw new RuleException(trans('flash_sale.rule.exception', ['%operator%' => $operatorRule->getType()]));
        }

        $qb->join('p.ProductClasses', 'pc');

        // build with each condition
        foreach ($this->getConditions() as $condition) {
            $operatorName = $condition->getOperator();
            $operatorCondition = $this->getOperatorFactory()->createByType($operatorName);
            $qb = $condition->createQueryBuilder($qb, $operatorRule, $operatorCondition);
        }

        $qb->groupBy('p')
        ->andWhere('p.Status = :status')
        ->setParameter('status', ProductStatus::DISPLAY_SHOW)
        ->andWhere('pc.visible = true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getConditionTypes(): array
    {
        return [
            ProductClassIdCondition::TYPE,
            ProductCategoryIdCondition::TYPE,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getPromotionTypes(): array
    {
        return [
            ProductClassPricePercentPromotion::TYPE,
            ProductClassPriceAmountPromotion::TYPE,
        ];
    }

    /**
     * Check a product class is matching condition
     *
     * @param ProductClass $ProductClass
     *
     * @return bool
     */
    public function match($ProductClass): bool
    {
        if (!$ProductClass instanceof ProductClass) {
            return false;
        }

        if (isset($this->cached[__METHOD__.$ProductClass->getId()])) {
            return $this->cached[__METHOD__.$ProductClass->getId()]; // @codeCoverageIgnore
        }

        $this->cached[__METHOD__.$ProductClass->getId()] = $this->getOperatorFactory()
            ->createByType($this->getOperator())->match($this->getConditions(), $ProductClass);

        return $this->cached[__METHOD__.$ProductClass->getId()];
    }

    /**
     * {@inheritdoc}
     *
     * @param $object
     *
     * @return DiscountInterface
     */
    public function getDiscount($object): DiscountInterface
    {
        if (!$object instanceof ProductClass) {
            $discount = new Discount();
            $discount->setRuleId($this->getId());

            return $discount;
        }

        return $this->getDiscountFromProductClass($object);
    }

    /**
     * Get discount items of productClass
     *
     * @param ProductClass $ProductClass
     *
     * @return DiscountInterface
     */
    protected function getDiscountFromProductClass(ProductClass $ProductClass)
    {
        $discount = new Discount();
        $discount->setRuleId($this->getId());

        if (isset($this->cached[__METHOD__.$ProductClass->getId()])) {
            return $this->cached[__METHOD__.$ProductClass->getId()]; // @codeCoverageIgnore
        }

        $discount = $this->getPromotion()->getDiscount($ProductClass);
        $discount->setRuleId($this->getId());
        $this->cached[__METHOD__.$ProductClass->getId()] = $discount;

        return $this->cached[__METHOD__.$ProductClass->getId()];
    }
}

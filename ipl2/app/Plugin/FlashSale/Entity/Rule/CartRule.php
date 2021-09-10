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
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Exception\RuleException;
use Plugin\FlashSale\Service\Operator;
use Plugin\FlashSale\Service\Metadata\DiscriminatorManager;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;
use Plugin\FlashSale\Entity\Promotion\CartTotalPercentPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalAmountPromotion;
use Plugin\FlashSale\Entity\DiscountInterface;
use Plugin\FlashSale\Entity\Discount;

/**
 * @ORM\Entity
 */
class CartRule extends Rule
{
    const TYPE = 'rule_cart';

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
     * {@inheritdoc}
     *
     * @return array
     */
    public function getConditionTypes(): array
    {
        return [
            CartTotalCondition::TYPE,
        ];
    }

    /**
     * Todo: implement late
     *
     * {@inheritdoc} createQueryBuilder
     */
    public function createQueryBuilder(QueryBuilder $qb, Operator\OperatorInterface $operatorRule): QueryBuilder
    {
        if (!in_array($operatorRule->getType(), $this->getOperatorTypes())) {
            throw new RuleException(trans('flash_sale.rule.exception', ['%operator%' => $operatorRule->getType()]));
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getPromotionTypes(): array
    {
        return [
            CartTotalPercentPromotion::TYPE,
            CartTotalAmountPromotion::TYPE,
        ];
    }

    /**
     * Check a product class is matching condition
     *
     * @param $object
     *
     * @return bool
     */
    public function match($object): bool
    {
        if (!$object instanceof Order && !$object instanceof Cart) {
            return false;
        }

        $cachedId = $object instanceof Order
            ? __METHOD__.'-O-'.$object->getId().'-'.$object->getSubtotal()
            : __METHOD__.'-C-'.$object->getId().'-'.$object->getTotal();

        if (isset($this->cached[$cachedId])) {
            return $this->cached[$cachedId]; // @codeCoverageIgnore
        }

        $this->cached[$cachedId] = $this->getOperatorFactory()
            ->createByType($this->getOperator())->match($this->getConditions(), $object);

        return $this->cached[$cachedId];
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
        $discount = new Discount();
        $discount->setRuleId($this->getId());

        if (!$object instanceof Order && !$object instanceof Cart) {
            return $discount;
        }

        $cachedId = $object instanceof Order
            ? __METHOD__.'-O-'.$object->getId().'-'.$object->getSubtotal()
            : __METHOD__.'-C-'.$object->getId().'-'.$object->getTotal();

        if (isset($this->cached[$cachedId])) {
            return $this->cached[$cachedId]; // @codeCoverageIgnore
        }

        $discount = $this->getPromotion()->getDiscount($object);
        $discount->setRuleId($this->getId());
        $this->cached[$cachedId] = $discount;

        return $discount;
    }
}

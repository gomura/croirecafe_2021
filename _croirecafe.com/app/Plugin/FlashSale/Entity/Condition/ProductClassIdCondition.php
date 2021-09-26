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

namespace Plugin\FlashSale\Entity\Condition;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\ProductClass;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Exception\ConditionException;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Service\Operator\OperatorInterface;

/**
 * @ORM\Entity
 */
class ProductClassIdCondition extends Condition
{
    const TYPE = 'condition_product_class_id';

    /**
     * {@inheritdoc}
     *
     * @param $data
     *
     * @return bool
     */
    public function match($ProductClass)
    {
        /** @var ProductClass $ProductClass */
        if (!$ProductClass instanceof ProductClass) {
            return false;
        }

        return $this->getOperatorFactory()->createByType($this->getOperator())->match($this->value, $ProductClass->getId());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param OperatorInterface $operatorRule
     * @param OperatorInterface $operatorCondition
     *
     * @return QueryBuilder
     *
     * @throws ConditionException
     */
    public function createQueryBuilder(QueryBuilder $queryBuilder, OperatorInterface $operatorRule, OperatorInterface $operatorCondition): QueryBuilder
    {
        // Check is support
        if (!in_array($operatorCondition->getType(), $this->getOperatorTypes())) {
            throw new ConditionException(trans('flash_sale.condition.exception', ['%operator%' => $operatorCondition->getType()]));
        }

        // rule check
        switch ($operatorRule->getType()) {
            case Operator\OrOperator::TYPE:
                $this->createOrRule($queryBuilder, $operatorCondition);
                break;

            case Operator\AllOperator::TYPE:
                $this->createAllRule($queryBuilder, $operatorCondition);
                break;
        }

        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getOperatorTypes(): array
    {
        return [
            Operator\InOperator::TYPE,
            Operator\NotInOperator::TYPE,
        ];
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param OperatorInterface $operatorCondition
     */
    private function createOrRule(QueryBuilder $queryBuilder, OperatorInterface $operatorCondition): void
    {
        // condition
        switch ($operatorCondition->getType()) {
            case Operator\InOperator::TYPE:
                $queryBuilder->orWhere($queryBuilder->expr()->in('pc.id', $this->getValue()));
                break;

            case Operator\NotInOperator::TYPE:
                $queryBuilder->orWhere($queryBuilder->expr()->notIn('pc.id', $this->getValue()));
                break;
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param OperatorInterface $operatorCondition
     */
    private function createAllRule(QueryBuilder $queryBuilder, OperatorInterface $operatorCondition): void
    {
        // condition
        switch ($operatorCondition->getType()) {
            case Operator\InOperator::TYPE:
                $queryBuilder->andWhere($queryBuilder->expr()->in('pc.id', $this->getValue()));
                break;

            case Operator\NotInOperator::TYPE:
                $queryBuilder->andWhere($queryBuilder->expr()->notIn('pc.id', $this->getValue()));
                break;
        }
    }
}

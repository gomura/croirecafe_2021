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

namespace Plugin\FlashSale\Service\Condition;

use Doctrine\ORM\QueryBuilder;
use Plugin\FlashSale\Exception\ConditionException;
use Plugin\FlashSale\Service\Operator\OperatorInterface;

interface ConditionInterface
{
    /**
     * Validate condition
     *
     * @param $data
     *
     * @return bool
     */
    public function match($data);

    /**
     * Get allowed operator
     *
     * @return array
     */
    public function getOperatorTypes(): array;

    /**
     * This function for create query builder to get list
     *
     * @param QueryBuilder $queryBuilder
     * @param OperatorInterface $operatorRule this is operator of rule
     * @param OperatorInterface $operatorCondition this is operator of condition
     *
     * @return QueryBuilder
     *
     * @throws ConditionException
     */
    public function createQueryBuilder(QueryBuilder $queryBuilder, OperatorInterface $operatorRule, OperatorInterface $operatorCondition): QueryBuilder;
}

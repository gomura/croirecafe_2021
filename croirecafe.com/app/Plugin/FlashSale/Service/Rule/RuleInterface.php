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

namespace Plugin\FlashSale\Service\Rule;

use Doctrine\ORM\QueryBuilder;
use Plugin\FlashSale\Exception\RuleException;
use Plugin\FlashSale\Service\Metadata\DiscriminatorInterface;
use Plugin\FlashSale\Service\Operator\OperatorInterface;
use Plugin\FlashSale\Entity\DiscountInterface;

interface RuleInterface
{
    /**
     * Get discriminator type
     *
     * @return DiscriminatorInterface
     */
    public function getDiscriminator(): DiscriminatorInterface;

    /**
     * Get operator types
     *
     * @return array
     */
    public function getOperatorTypes(): array;

    /**
     * Get condition types
     *
     * @return array
     */
    public function getConditionTypes(): array;

    /**
     * Get promotion types
     *
     * @return array
     */
    public function getPromotionTypes(): array;

    /**
     * Check a object match conditions of rule
     *
     * @param $object
     *
     * @return bool
     */
    public function match($object): bool;

    /**
     * Get discount item
     *
     * @param $object
     *
     * @return DiscountInterface
     */
    public function getDiscount($object): DiscountInterface;

    /**
     * create list
     *
     * @param QueryBuilder $qb
     * @param OperatorInterface $operatorRule this is operator of rule
     *
     * @return QueryBuilder
     *
     * @throws RuleException
     */
    public function createQueryBuilder(QueryBuilder $qb, OperatorInterface $operatorRule): QueryBuilder;
}

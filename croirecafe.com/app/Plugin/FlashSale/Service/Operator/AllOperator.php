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

namespace Plugin\FlashSale\Service\Operator;

use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Plugin\FlashSale\Service\Condition\ConditionInterface;

class AllOperator implements OperatorInterface
{
    const TYPE = 'operator_all';

    /**
     * {@inheritdoc}
     *
     * @param $condition
     * @param $data
     *
     * @return bool
     */
    public function match($condition, $data)
    {
        if (!$condition instanceof DoctrineCollection && !is_array($condition)) {
            return false;
        }

        foreach ($condition as $cond) {
            if (!$cond instanceof ConditionInterface) {
                return false;
            }

            if (!$cond->match($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return trans('flash_sale.admin.form.rule.operator.is_all_of');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }
}

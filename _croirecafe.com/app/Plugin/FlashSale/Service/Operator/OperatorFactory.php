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

class OperatorFactory
{
    /**
     * Create operator
     *
     * @param $type
     *
     * @return OperatorInterface
     */
    public function createByType($type)
    {
        switch ($type) {
            case AllOperator::TYPE:
                return new AllOperator();

            case OrOperator::TYPE:
                return new OrOperator();

            case InOperator::TYPE:
                return new InOperator();

            case NotInOperator::TYPE:
                return new NotInOperator();

            case EqualOperator::TYPE:
                return new EqualOperator();

            case NotEqualOperator::TYPE:
                return new NotEqualOperator();

            case GreaterThanOperator::TYPE:
                return new GreaterThanOperator();

            case LessThanOperator::TYPE:
                return new LessThanOperator();
        }

        throw new \InvalidArgumentException('Not found operator have type '.$type);
    }
}

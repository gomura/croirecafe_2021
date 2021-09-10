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

use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;

class ConditionFactory
{
    /**
     * Create Condition from array
     *
     * @param array $data
     *
     * @return Condition
     */
    public static function createFromArray(array $data)
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('$data[type] must be required');
        }

        switch ($data['type']) {
            case Condition\ProductClassIdCondition::TYPE:
                $Condition = new Condition\ProductClassIdCondition();
                break;
            case Condition\ProductCategoryIdCondition::TYPE:
                $Condition = new Condition\ProductCategoryIdCondition();
                break;
            case CartTotalCondition::TYPE:
                $Condition = new CartTotalCondition();
                break;
            default:
                throw new \InvalidArgumentException($data['type'].' unsupported');
        }

        if (isset($data['value'])) {
            $Condition->setValue($data['value']);
        }
        if (isset($data['operator'])) {
            $Condition->setOperator($data['operator']);
        }

        return $Condition;
    }
}

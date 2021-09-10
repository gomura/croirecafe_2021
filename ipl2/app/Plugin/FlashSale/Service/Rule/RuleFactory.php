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

use Plugin\FlashSale\Entity\Rule;

class RuleFactory
{
    /**
     * Create Rule from array
     *
     * @param array $data
     *
     * @return Rule
     */
    public static function createFromArray(array $data)
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('$data[type] must be required');
        }

        switch ($data['type']) {
            case Rule\ProductClassRule::TYPE:
                $Rule = new Rule\ProductClassRule();
                break;
            case Rule\CartRule::TYPE:
                $Rule = new Rule\CartRule();
                break;
            default:
                throw new \InvalidArgumentException($data['type'].' unsupported');
        }
        if (isset($data['operator'])) {
            $Rule->setOperator($data['operator']);
        }

        return $Rule;
    }
}

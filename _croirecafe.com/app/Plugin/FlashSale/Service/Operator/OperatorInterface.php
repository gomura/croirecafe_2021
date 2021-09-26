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

use Plugin\FlashSale\Entity\Condition;

interface OperatorInterface
{
    /**
     * Implement validate logic
     *
     * @param $condition
     * @param $data
     *
     * @return bool
     */
    public function match($condition, $data);

    /**
     * @return string
     */
    public function getType();
}

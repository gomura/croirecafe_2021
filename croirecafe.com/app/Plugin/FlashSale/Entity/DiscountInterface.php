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

namespace Plugin\FlashSale\Entity;

interface DiscountInterface
{
    /**
     * Get rule id
     *
     * @return int
     */
    public function getRuleId();

    /**
     * Set rule id
     *
     * @param $ruleId
     *
     * @return DiscountInterface
     */
    public function setRuleId($ruleId);

    /**
     * Get promotion id
     *
     * @return int
     */
    public function getPromotionId();

    /**
     * Set promotion id
     *
     * @param $promotionId
     *
     * @return DiscountInterface
     */
    public function setPromotionId($promotionId);

    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Set value
     *
     * @param $value
     *
     * @return DiscountInterface
     */
    public function setValue($value);
}

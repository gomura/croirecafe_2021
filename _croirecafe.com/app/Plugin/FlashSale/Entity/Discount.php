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

class Discount implements DiscountInterface
{
    /**
     * @var int
     */
    protected $ruleId;

    /**
     * @var int
     */
    protected $promotionId;

    /**
     * @var mixed
     */
    protected $value = 0;

    /**
     * {@inheritdoc}
     *
     * @param $ruleId
     *
     * @return DiscountInterface
     */
    public function setRuleId($ruleId)
    {
        $this->ruleId = $ruleId;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getRuleId()
    {
        return $this->ruleId;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getPromotionId()
    {
        return $this->promotionId;
    }

    /**
     * {@inheritdoc}
     *
     * @param $promotionId
     *
     * @return DiscountInterface
     */
    public function setPromotionId($promotionId)
    {
        $this->promotionId = $promotionId;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @param $value
     *
     * @return DiscountInterface
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}

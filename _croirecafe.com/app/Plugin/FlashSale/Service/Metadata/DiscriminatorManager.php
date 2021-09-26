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

namespace Plugin\FlashSale\Service\Metadata;

use Plugin\FlashSale\Entity\Condition\ProductCategoryIdCondition;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Promotion\ProductClassPriceAmountPromotion;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;
use Plugin\FlashSale\Entity\Promotion\CartTotalPercentPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalAmountPromotion;

class DiscriminatorManager
{
    /**
     * @var array
     */
    protected $container;

    /**
     * Create a discriminator
     *
     * @param $discriminatorType
     *
     * @return DiscriminatorInterface
     */
    public function create($discriminatorType)
    {
        switch ($discriminatorType) {
            case Operator\AllOperator::TYPE:
                $this->container[Operator\AllOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\AllOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_all_of'))
                    ->setClass(Operator\AllOperator::class)
                    ->setDescription('');

                return $this->container[Operator\AllOperator::TYPE];

            case Operator\OrOperator::TYPE:
                $this->container[Operator\OrOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\OrOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_one_of'))
                    ->setClass(Operator\OrOperator::class)
                    ->setDescription('');

                return $this->container[Operator\OrOperator::TYPE];

            case Operator\InOperator::TYPE:
                $this->container[Operator\InOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\InOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.condition.operator.is_one_of'))
                    ->setClass(Operator\InOperator::class)
                    ->setDescription('');

                return $this->container[Operator\InOperator::TYPE];

            case Operator\NotInOperator::TYPE:
                $this->container[Operator\NotInOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\NotInOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_not_equal_to'))
                    ->setClass(Operator\NotInOperator::class)
                    ->setDescription('');

                return $this->container[Operator\NotInOperator::TYPE];

            case Operator\EqualOperator::TYPE:
                $this->container[Operator\EqualOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\EqualOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_equal_to'))
                    ->setClass(Operator\EqualOperator::class)
                    ->setDescription('');

                return $this->container[Operator\EqualOperator::TYPE];

            case Operator\NotEqualOperator::TYPE:
                $this->container[Operator\NotEqualOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\NotEqualOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_not_equal_to'))
                    ->setClass(Operator\NotEqualOperator::class)
                    ->setDescription('');

                return $this->container[Operator\NotEqualOperator::TYPE];

            case Operator\GreaterThanOperator::TYPE:
                $this->container[Operator\GreaterThanOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\GreaterThanOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_greater_than_to'))
                    ->setClass(Operator\GreaterThanOperator::class)
                    ->setDescription('');

                return $this->container[Operator\GreaterThanOperator::TYPE];

            case Operator\LessThanOperator::TYPE:
                $this->container[Operator\LessThanOperator::TYPE] = (new Discriminator())
                    ->setType(Operator\LessThanOperator::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.operator.is_less_than_to'))
                    ->setClass(Operator\LessThanOperator::class)
                    ->setDescription('');

                return $this->container[Operator\LessThanOperator::TYPE];

            case ProductClassRule::TYPE:
                $this->container[ProductClassRule::TYPE] = (new Discriminator())
                    ->setType(ProductClassRule::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.product_class_rule'))
                    ->setClass(ProductClassRule::class)
                    ->setDescription('');

                return $this->container[ProductClassRule::TYPE];

            case CartRule::TYPE:
                $this->container[CartRule::TYPE] = (new Discriminator())
                    ->setType(CartRule::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.cart_rule'))
                    ->setClass(CartRule::class)
                    ->setDescription('');

                return $this->container[CartRule::TYPE];

            case ProductClassIdCondition::TYPE:
                $this->container[ProductClassIdCondition::TYPE] = (new Discriminator())
                    ->setType(ProductClassIdCondition::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.condition.product_class_id_condition'))
                    ->setClass(ProductClassIdCondition::class)
                    ->setDescription('');

                return $this->container[ProductClassIdCondition::TYPE];

            case ProductCategoryIdCondition::TYPE:
                $this->container[ProductCategoryIdCondition::TYPE] = (new Discriminator())
                    ->setType(ProductCategoryIdCondition::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.condition.product_category_id_condition'))
                    ->setClass(ProductCategoryIdCondition::class)
                    ->setDescription('');

                return $this->container[ProductCategoryIdCondition::TYPE];

            case CartTotalCondition::TYPE:
                $this->container[CartTotalCondition::TYPE] = (new Discriminator())
                    ->setType(CartTotalCondition::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.condition.cart_total_condition'))
                    ->setClass(CartTotalCondition::class)
                    ->setDescription('');

                return $this->container[CartTotalCondition::TYPE];

            case ProductClassPricePercentPromotion::TYPE:
                $this->container[ProductClassPricePercentPromotion::TYPE] = (new Discriminator())
                    ->setType(ProductClassPricePercentPromotion::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.product_class_price_percent_promotion'))
                    ->setClass(ProductClassPricePercentPromotion::class)
                    ->setDescription('');

                return $this->container[ProductClassPricePercentPromotion::TYPE];

            case ProductClassPriceAmountPromotion::TYPE:
                $this->container[ProductClassPriceAmountPromotion::TYPE] = (new Discriminator())
                    ->setType(ProductClassPriceAmountPromotion::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.product_class_price_amount_promotion'))
                    ->setClass(ProductClassPriceAmountPromotion::class)
                    ->setDescription('');

                return $this->container[ProductClassPriceAmountPromotion::TYPE];

            case CartTotalAmountPromotion::TYPE:
                $this->container[CartTotalAmountPromotion::TYPE] = (new Discriminator())
                    ->setType(CartTotalAmountPromotion::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.cart_total_amount_promotion'))
                    ->setClass(CartTotalAmountPromotion::class)
                    ->setDescription('');

                return $this->container[CartTotalAmountPromotion::TYPE];

            case CartTotalPercentPromotion::TYPE:
                $this->container[CartTotalPercentPromotion::TYPE] = (new Discriminator())
                    ->setType(CartTotalPercentPromotion::TYPE)
                    ->setName(trans('flash_sale.admin.form.rule.cart_total_percent_promotion'))
                    ->setClass(CartTotalPercentPromotion::class)
                    ->setDescription('');

                return $this->container[CartTotalPercentPromotion::TYPE];

            default:
        }

        throw new \InvalidArgumentException('Unsupported '.$discriminatorType.' type');
    }

    /**
     * Get a discriminator
     *
     * @param $discriminatorType
     *
     * @return DiscriminatorInterface
     */
    public function get($discriminatorType)
    {
        return isset($this->container[$discriminatorType])
            ? $this->container[$discriminatorType]
            : $this->create($discriminatorType);
    }
}

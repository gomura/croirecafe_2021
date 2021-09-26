<?php

/*
 * RepeatCube for EC-CUBE4
 * Copyright(c) 2019 IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * This program is not free software.
 * It applies to terms of service.
 *
 */
namespace Plugin\IplPeriodicPurchase\Form\Type\Admin;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount;

class ProductPeriodicDiscountType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // オプションを指定
        $resolver->setDefaults(
            [
                'label' => '定期回数別商品金額割引',
                'class' => PeriodicDiscount::class,
                'placeholder' => 'common.select',
                'required' => false,
                'choice_value' => 'id',
                'choice_label' => function (PeriodicDiscount $periodicDiscount) {
                    $label = 'ID:'.$periodicDiscount->getId();
                    $label .= ' 初回' . ($periodicDiscount->getDiscountRate1() ?? '--') . '%割引';
                    $label .= ' 通常' . ($periodicDiscount->getDiscountRate2() ?? '--') . '%割引';
                    $label .= ' ' . ($periodicDiscount->getDiscountFromCount3() ?? '--') . '回毎' . ($periodicDiscount->getDiscountRate3() ?? '--') . '%割引';
                            return $label;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}

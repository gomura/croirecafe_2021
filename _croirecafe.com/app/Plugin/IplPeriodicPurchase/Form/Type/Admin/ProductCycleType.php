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

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\IplPeriodicPurchase\Entity\Cycle;

class ProductCycleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // オプションを指定
        $resolver->setDefaults(
            [
                'label' => '定期サイクル',
                'placeholder' => 'common.select',
                'choice_label' => 'display_name',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'class' => Cycle::class,
                'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('c')->orderBy('c.sort_no', 'DESC');
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

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

use Eccube\Common\EccubeConfig;
use Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PerodicDiscountType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('discount_from_count_2',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_DISCOUNT_TIMES_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('discount_from_count_3',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_DISCOUNT_TIMES_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            // ->add('discount_rate_1',ChoiceType::class, [
            //     'choices' => $this->getListChoiceArray(0,100, 5),
            //     'multiple' => false,
            //     'expanded' => false,
            //     'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            // ])
            // ->add('discount_rate_2',ChoiceType::class, [
            //     'choices' => $this->getListChoiceArray(0,100, 5),
            //     'multiple' => false,
            //     'expanded' => false,
            //     'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            // ])
            // ->add('discount_rate_3',ChoiceType::class, [
            //     'choices' => $this->getListChoiceArray(0,100, 5),
            //     'multiple' => false,
            //     'expanded' => false,
            //     'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            // ])
            ->add('discount_rate_1', TextType::class, [
              'attr' => ['style' => 'width:60px'],
              'constraints' =>[
                  new Assert\NotBlank(),
                  new Assert\Length(['max' => 100]),
              ]
            ])
            ->add('discount_rate_2', TextType::class, [
              'attr' => ['style' => 'width:60px'],
              'constraints' =>[
                  new Assert\NotBlank(),
                  new Assert\Length(['max' => 100]),
                  'placeholder' => 0
              ]
            ])
            ->add('discount_rate_3', TextType::class, [
              'attr' => ['style' => 'width:60px'],
              'constraints' =>[
                  new Assert\NotBlank(),
                  new Assert\Length(['max' => 100]),
                  'placeholder' => 0
              ]
            ])
            ;
    }

    private function getListChoiceArray($start, $end, $step = 1){
        return array_combine(range($start, $end, $step),range($start, $end, $step));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PeriodicDiscount::class,
        ]);
    }
}

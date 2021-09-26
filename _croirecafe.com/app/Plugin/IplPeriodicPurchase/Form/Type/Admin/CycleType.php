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
use Plugin\IplPeriodicPurchase\Entity\Cycle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CycleType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * CycleType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ValidatorInterface $validator
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // セレクトボックス用に日付の配列を作成
        $choice_unit = $this->getListChoiceArray(1,99);

        $builder
            ->add('cycle_type',ChoiceType::class, [
                'choices' => [
                    '日付指定'=>$this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTHLY'],
                    '月ごと'=>$this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTH'],
                    '週ごと'=>$this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_WEEK'],
                    '日ごと'=>$this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAY'],
                    '曜日指定'=>$this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']
                ],
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('cycle_unit',ChoiceType::class, [
                'choices' => $choice_unit,
                'choice_attr' => function($choice, $key, $value) {
                    $month = false;
                    $week = false;
                    $day = false;
                    $monthly = false;

                    if ($choice <= $this->eccubeConfig['CYCLE_CONFIG_DAY_UPPER']) {
                        $day = true;
                    }
                    if ($choice <= $this->eccubeConfig['CYCLE_CONFIG_MONTHLY_UPPER']) {
                        $monthly = true;
                    }
                    if ($choice <= $this->eccubeConfig['CYCLE_CONFIG_MONTH_UPPER']) {
                        $month = true;
                    }
                    if ($choice <= $this->eccubeConfig['CYCLE_CONFIG_WEEK_UPPER']) {
                        $week = true;
                    }

                    return [
                        'data-month' => $month,
                        'data-week' => $week,
                        'data-day' => $day,
                        'data-monthly' => $monthly
                    ];
                },
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {

            $form = $event->getForm();
            $Cycle = $event->getData();
            $data = $form->getData();

            if ($form->get('cycle_type')->getData() !== $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']) {
                // 曜日指定以外は表示名を設定
                $Cycle->setDisplayName(trans('ipl_periodic_purchase.admin.cycle.unit.'.$form->get('cycle_type')->getData(),['%unit%' => $form->get('cycle_unit')->getData()]));

                // 曜日指定以外は最大数を追加
                if ($form->get('cycle_type')->getData() == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTHLY']) {
                    $unit_upper = $this->eccubeConfig['CYCLE_CONFIG_MONTHLY_UPPER'];
                } elseif ($form->get('cycle_type')->getData() == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTH']) {
                    $unit_upper = $this->eccubeConfig['CYCLE_CONFIG_MONTH_UPPER'];
                } elseif ($form->get('cycle_type')->getData() == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_WEEK']) {
                    $unit_upper = $this->eccubeConfig['CYCLE_CONFIG_WEEK_UPPER'];
                } elseif ($form->get('cycle_type')->getData() == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAY']) {
                    $unit_upper = $this->eccubeConfig['CYCLE_CONFIG_DAY_UPPER'];
                }

                $errors = $this->validator->validate($data['cycle_unit'], [
                    new Assert\Range([
                        'max' => $unit_upper,
                    ]),
                ]);
                $this->addErrors('cycle_unit',$form,$errors);
            } else {
                // 曜日指定はタイプ名そのまま追加
                $Cycle->setDisplayName(trans('ipl_periodic_purchase.admin.cycle.type.'.$form->get('cycle_type')->getData()));

                // 周期にnullを設定
                $Cycle->setCycleUnit(null);
            }

        });
    }

    private function getListChoiceArray($start,$end){
        return array_combine(range($start, $end),range($start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cycle::class,
        ]);
    }

    protected function addErrors($key, FormInterface $form, ConstraintViolationListInterface $errors)
    {
        foreach ($errors as $error) {
            $form[$key]->addError(new FormError($error->getMessage()));
        }
    }
}

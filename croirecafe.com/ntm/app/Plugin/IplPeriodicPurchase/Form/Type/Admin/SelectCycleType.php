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
use Eccube\Entity\DeliveryTime;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

class SelectCycleType extends AbstractType
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
     * NextShippingType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ValidatorInterface $validator,
        PeriodicHelper $periodicHelper
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->validator = $validator;
        $this->periodicHelper = $periodicHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $Items = [];
        if (!empty($options['data']['PeriodicPurchaseItems'])) {
            $Items = $options['data']['PeriodicPurchaseItems'];
        } elseif (!empty($options['data']['OrderItems'])) {
            $Items = $options['data']['OrderItems'];
        }

        // すべての購入商品に設定されたサイクルから、重複するサイクルを取得
        $Cycles = $this->periodicHelper->getDuplicateCycle($Items);

        // 重複のないサイクルタイプを取得
        $arrCycleTypes = array_unique(array_column($Cycles, 'cycle_type'));

        $arrCycleTypeChoices = [];
        foreach ($arrCycleTypes as $cycleType) {
            switch ($cycleType) {
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTHLY']:
                    $arrCycleTypeChoices['日付指定'] = $cycleType;
                    break;
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTH']:
                    $arrCycleTypeChoices['月ごと'] = $cycleType;
                    break;
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_WEEK']:
                    $arrCycleTypeChoices['週ごと'] = $cycleType;
                    break;
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAY']:
                    $arrCycleTypeChoices['日ごと'] = $cycleType;
                    break;
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']:
                    $arrCycleTypeChoices['曜日指定'] = $cycleType;
                    break;
            }
        }

        $builder
            ->add('cycle_type',ChoiceType::class, [
                'choices' => $arrCycleTypeChoices,
                'multiple' => false,
                'expanded' => true,
                'mapped' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default',
                'data' => $options['data']['Cycle']['cycle_type']
            ]);

        $arrCycles = [];
        foreach ($Cycles as $Cycle) {
            $arrCycles[$Cycle['cycle_type']][] = $Cycle;
        }

        foreach ($arrCycles as $cycle_type => $arrCycle) {
            $this->buildCycle($builder, $cycle_type, $arrCycle, $options);
        }
    }

    public function buildCycle($builder, $cycle_type, $arrCycle, $options)
    {
        if ($cycle_type != $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']) {
            $required = false;
            $placeholder = 'common.select';
        } else {
            // display noneでレンダリングするが、選択状態とさせるため
            $required = true;
            $placeholder = null;

            $this->buildCycleDayOfWeek($builder, $options);
        }

        $builder
            ->add(
                "cycle_$cycle_type",
                EntityType::class,
                [
                    'required' => $required,
                    'placeholder' => $placeholder,
                    'label' => 'shipping.label.delivery_hour',
                    'class' => 'Plugin\IplPeriodicPurchase\Entity\Cycle',
                    'choice_label' => 'display_name',
                    'choices' => $arrCycle,
                    'expanded' => false,
                    'mapped' => false,
                    'data' => $options['data']['Cycle'] // entity
                ]
            );
    }

    public function buildCycleDayOfWeek($builder, $options)
    {
        list($arrWeek, $arrDayOfWeek) = $this->periodicHelper->getPeriodicPurchaseCycleListForDayOfWeek();

        $builder
            ->add(
                "cycle_week",
                ChoiceType::class,
                [
                    'choices' => array_flip($arrWeek),
                    'expanded' => false,
                    'multiple' => false,
                    'mapped' => false,
                    'placeholder' => '----',
                    'data' => $options['data']['cycle_week'] // cycle_week
                ]
            );
        $builder
            ->add(
                "cycle_dayofweek",
                ChoiceType::class,
                [
                    'choices' => array_flip($arrDayOfWeek),
                    'expanded' => false,
                    'multiple' => false,
                    'mapped' => false,
                    'placeholder' => '----',
                    'data' => $options['data']['cycle_day']
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // 'data_class' => PeriodicPurchase::class,
        ]);
    }

    protected function addErrors($key, FormInterface $form, ConstraintViolationListInterface $errors)
    {
        foreach ($errors as $error) {
            $form[$key]->addError(new FormError($error->getMessage()));
        }
    }
}

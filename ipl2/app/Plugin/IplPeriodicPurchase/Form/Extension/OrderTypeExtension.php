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
namespace Plugin\IplPeriodicPurchase\Form\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\SelectCycleType;
use Plugin\IplPeriodicPurchase\Repository\CycleRepository;

class OrderTypeExtension extends AbstractTypeExtension
{
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig,
        CycleRepository $cycleRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->cycleRepository = $cycleRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            // data = リクエストパラメータ
            $data = $event->getData();
            $form = $event->getForm();
            $Order = $event->getForm()->getData();

            // 購入完了時(=リクエストパラメータが存在しない)時はスキップ
            if (empty($data['cycle']['cycle_type'])) {
                return;
            }

            $cycle_type = $data['cycle']['cycle_type'];

            $Cycle = $this->cycleRepository->find($data['cycle']["cycle_$cycle_type"]);
            $Order->setCycle($Cycle);

            if ($cycle_type == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']) {
                $cycle_week = $data['cycle']['cycle_week'];
                $cycle_day = $data['cycle']['cycle_dayofweek'];
            } else {
                $cycle_week = null;
                $cycle_day = null;
            }
            $Order->setCycleWeek($cycle_week);
            $Order->setCycleDay($cycle_day);

            $form->setData($Order);

        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            // data = Order
            $data = $event->getData();
            $form = $event->getForm();

            if($data->getSaleTypes()[0]->getId() === $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
                $form
                    ->add('cycle', SelectCycleType::class, [
                        'data' => $data,
                        'required' => false,
                        'mapped' => false,
                    ]);
            }
        });
    }

    public function getExtendedType()
    {
        return OrderType::class;
    }
}

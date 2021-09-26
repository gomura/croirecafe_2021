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
use Eccube\Form\Type\Admin\ProductClassType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductClassTypeExtension extends AbstractTypeExtension
{
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if ($data['sale_type'] != $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
                // 販売種別が定期で無い場合定期関連フォームの項目は空とする
                $data['Cycles'] = null;
                $data['PeriodicDiscount'] = null;
                $event->setData($data);
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $Visible = $data->isVisible();
            $SaleTypeId = $data->getSaleType()->getId();

            // 定期で定期関連項目に値が無い場合エラーとする
            if ($Visible && ($SaleTypeId == $this->eccubeConfig['SALE_TYPE_ID_PERIODIC'])) {
                $Cycles = $data->getCycles();
                $PeriodicDiscount = $data->getPeriodicDiscount();

                if ($Cycles->isEmpty()) {
                    $form->get('Cycles')->addError(new FormError('定期サイクルが選択されていません。'));
                }

                if ($PeriodicDiscount === null) {
                    $form->get('PeriodicDiscount')->addError(new FormError('定期回数別商品金額割引が選択されていません。'));
                }
            }

        });
    }

    public function getExtendedType()
    {
        return ProductClassType::class;
    }
}
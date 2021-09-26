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
use Eccube\Form\Type\Admin\DeliveryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DeliveryTypeExtension extends AbstractTypeExtension
{
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($form->get('sale_type')->getData()->getId() === $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
                $arrUnauthorizedPaymentMethod = [];

                $PaymentsData = $form->get('payments')->getData();
                foreach ($PaymentsData as $PaymentData) {
                    $payment_method = $PaymentData->getMethodClass();
                    if (empty($this->eccubeConfig['AUTHORIZED_PAYMENT_METHOD_PERIODIC'][$payment_method])) {
                        $arrUnauthorizedPaymentMethod[] = $PaymentData->getMethod();
                    }
                }

                if (!empty($arrUnauthorizedPaymentMethod)) {
                    $form->get('payments')->addError(new FormError(implode(",", $arrUnauthorizedPaymentMethod) . 'はリピートキューブ未対応の支払方法です。'));
                }
            }
        });
    }

    public function getExtendedType()
    {
        return DeliveryType::class;
    }
}

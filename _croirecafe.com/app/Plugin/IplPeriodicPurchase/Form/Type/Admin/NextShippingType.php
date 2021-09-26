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

class NextShippingType extends AbstractType
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

        $standard_next_shipping_date = $options['data']['standard_next_shipping_date'];
        $next_shipping_date = $options['data']['next_shipping_date'];

        $arrDelivDate = $this->periodicHelper->getNextShippingDateList($standard_next_shipping_date);

        $builder
            ->add('next_shipping_date',ChoiceType::class, [
                'choices' => array_flip($arrDelivDate),
                'required' => true,
                'mapped' => false,
                'data' => $next_shipping_date->format('Y/m/d'),
            ]);

        $PeriodicPurchaseShipping = $options['data']['PeriodicPurchaseShipping'];
        $ShippingDeliveryTime = null;
        $DeliveryTimes = [];
        $Delivery = $PeriodicPurchaseShipping->getDelivery();
        if ($Delivery) {
            $DeliveryTimes = $Delivery->getDeliveryTimes();
            $DeliveryTimes = $DeliveryTimes->filter(function (DeliveryTime $DeliveryTime) {
                return $DeliveryTime->isVisible();
            });

            foreach ($DeliveryTimes as $deliveryTime) {
                if ($deliveryTime->getId() == $PeriodicPurchaseShipping->getTimeId()) {
                    $ShippingDeliveryTime = $deliveryTime;
                    break;
                }
            }
        }

        $builder->add(
            'DeliveryTime',
            EntityType::class,
            [
                'label' => 'front.shopping.delivery_time',
                'class' => 'Eccube\Entity\DeliveryTime',
                'choice_label' => 'deliveryTime',
                'choices' => $DeliveryTimes,
                'required' => false,
                'placeholder' => 'common.select__unspecified',
                'mapped' => false,
                'data' => $ShippingDeliveryTime,
            ]
        );

        // POSTされないデータをエンティティにセットする.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var PeriodicPurchase $PeriodicPurchase */
            $PeriodicPurchase = $event->getData();
            $PeriodicPurchaseShipping = $PeriodicPurchase->getPeriodicPurchaseShipping();
            $form = $event->getForm();
            $DeliveryDate = $form['next_shipping_date']->getData();
            if ($DeliveryDate) {
                $PeriodicPurchase->setNextShippingDate(new \DateTime($DeliveryDate));
            } else {
                $PeriodicPurchase->setNextShippingDate(null);
            }

            $DeliveryTime = $form['DeliveryTime']->getData();
            if ($DeliveryTime) {
                $PeriodicPurchase->setNextShippingDeliveryTime($DeliveryTime->getDeliveryTime());
                $PeriodicPurchase->setNextShippingTimeId($DeliveryTime->getId());
                $PeriodicPurchaseShipping->setShippingDeliveryTime($DeliveryTime->getDeliveryTime());
                $PeriodicPurchaseShipping->setTimeId($DeliveryTime->getId());
            } else {
                $PeriodicPurchase->setNextShippingDeliveryTime(null);
                $PeriodicPurchase->setNextShippingTimeId(null);
                $PeriodicPurchaseShipping->setShippingDeliveryTime(null);
                $PeriodicPurchaseShipping->setTimeId(null);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PeriodicPurchase::class,
        ]);
    }

    protected function addErrors($key, FormInterface $form, ConstraintViolationListInterface $errors)
    {
        foreach ($errors as $error) {
            $form[$key]->addError(new FormError($error->getMessage()));
        }
    }
}

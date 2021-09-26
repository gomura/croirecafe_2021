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
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem;
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

class ProductsType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ProductsType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = range(1, 10);

        $builder
            ->add('quantity', ChoiceType::class, [
                'choices' => array_combine($choices, $choices),
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_int_len'],
                    ]),
                    new Assert\GreaterThanOrEqual(1),
                ],
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $PeriodicPurchaseItem = $event->getForm()->getData();

            $PeriodicPurchase = $PeriodicPurchaseItem->getPeriodicPurchase();

            $sum = function ($sum, $item) {
                    $sum += $item->getPriceIncTax() * $item->getQuantity();

                    return $sum;
                };

            // 小計
            $subTotal = $PeriodicPurchase->getItems()
                ->getProductClasses()
                ->reduce($sum, 0);
            $PeriodicPurchase->setSubTotal($subTotal);

            // 合計
            $total = $PeriodicPurchase->getItems()
                ->reduce($sum, 0);
            $PeriodicPurchase->setTotal($total);
            $PeriodicPurchase->setPaymentTotal($total);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PeriodicPurchaseItem::class,
        ]);
    }

    protected function addErrors($key, FormInterface $form, ConstraintViolationListInterface $errors)
    {
        foreach ($errors as $error) {
            $form[$key]->addError(new FormError($error->getMessage()));
        }
    }
}

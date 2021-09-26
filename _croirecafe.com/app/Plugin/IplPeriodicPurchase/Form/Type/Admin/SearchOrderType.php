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
use Eccube\Form\Type\Master\PaymentType;
use Eccube\Form\Type\MasterType;
use Eccube\Form\Type\PriceType;
use Eccube\Repository\Master\OrderStatusRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\PeriodicStatusType;

class SearchOrderType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct
    (
        EccubeConfig $eccubeConfig,
        OrderStatusRepository $orderStatusRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 定期ID・注文者名・注文者（フリガナ）・注文者会社名・メールアドレス
            ->add('multi', TextType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('periodic_status', PeriodicStatusType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.periodic_status',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'admin.order.orderer_name',
                'required' => false,
            ])
            ->add($builder
                ->create('kana', TextType::class, [
                    'label' => 'admin.order.orderer_kana',
                    'required' => false,
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u',
                            'message' => 'form_error.kana_only',
                        ]),
                    ],
                ])
                ->addEventSubscriber(new \Eccube\Form\EventListener\ConvertKanaListener('CV')
            ))
            ->add('company_name', TextType::class, [
                'label' => 'admin.order.orderer_company_name',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'admin.common.mail_address',
                'required' => false,
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'admin.common.phone_number',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^[\d-]+$/u",
                        'message' => 'form_error.graph_and_hyphen_only',
                    ]),
                ],
            ])
            ->add('payment', PaymentType::class, [
                'label' => 'admin.common.payment_method',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('last_order_status', MasterType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.last_order_status',
                'class' => 'Eccube\Entity\Master\OrderStatus',
                'placeholder' => 'common.select__unspecified',
            ])
            ->add('first_order_date_start', DateType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.first_order_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_first_order_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('first_order_date_end', DateType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.first_order_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_first_order_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('next_shipping_date_start', DateType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.next_shipping_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_next_shipping_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('next_shipping_date_end', DateType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.next_shipping_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_next_shipping_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('periodic_count_start', IntegerType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.periodic_count__start',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
            ->add('periodic_count_end', IntegerType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.periodic_count__end',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
            ->add('periodic_id', TextType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.periodic_id',
                'required' => false,
            ])
            ->add('product_id', TextType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.product_id',
                'required' => false,
            ])
            ->add('order_no', TextType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.order_no',
                'required' => false,
            ])
            ->add('payment_total_start', PriceType::class, [
                'label' => 'admin.order.purchase_price__start',
                'required' => false,
            ])
            ->add('payment_total_end', PriceType::class, [
                'label' => 'admin.order.purchase_price__end',
                'required' => false,
            ])
            ->add('product_name', TextType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.product_name',
                'required' => false,
            ])
            ->add('card_change_mail_status', ChoiceType::class, [
                'label' => 'ipl_periodic_purchase.admin.order.card_change_mail_status',
                'choices' => [
                    'ipl_periodic_purchase.admin.order.card_change_mail_status__unsent' => PeriodicPurchase::CARD_CHANGE_MAIL_STATUS_UNSENT,
                    'ipl_periodic_purchase.admin.order.card_change_mail_status__sent' => PeriodicPurchase::CARD_CHANGE_MAIL_STATUS_SENT,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('periodic_count_item_start', IntegerType::class, [
                'label' => '商品定期回数(開始)',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
            ->add('periodic_count_item_end', IntegerType::class, [
                'label' => '商品定期回数(終了)',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'periodic_admin_order';
    }
}

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
use Plugin\IplPeriodicPurchase\Entity\Config;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\PerodicDiscountType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigType extends AbstractType
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

        // 配列→ハッシュ値変換のTransformer作成
        $transformer = new CallbackTransformer(
            function ($string) {
                $decode = unserialize($string);

                // 配列以外が入った場合は空配列をセット
                if (!is_array($decode)) {
                    $decode = array();
                }
                return $decode;
            },
            function ($array) {
                $encode = serialize($array);
                if (is_bool($encode)) {
                    $encode = '';
                }
                return $encode;
            }
        );

        $builder
            ->add('reception_address', EmailType::class)
            ->add('mypage_process',ChoiceType::class, [
                'choices' => [
                    '定期サイクル変更' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_CYCLE_CHANGE'],
                    '次回発送予定日変更' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SHIPPING_DATE_CHANGE'],
                    '商品数変更' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_ITEM_QUANTITY_CHANGE'],
                    '解約' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_CANCEL'],
                    '休止・再開' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SUSPEND'],
                    '1回スキップ' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SKIP'],
                ],
                'multiple' => true,
                'expanded' => true
                ])
            ->add('can_cancel_count',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_CANCEL_OK_COUNT_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('can_suspend_count',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_CANCEL_OK_COUNT_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('shipping_date_change_range',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_SHIPPING_DATE_CHANGE_RANGE_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('point_rate',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(0,$this->eccubeConfig['PERIODIC_CONFIG_POINT_RATE_UPPER']),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('first_shipping_date',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_FIRST_SHIPPING_DATE_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('resume_next_shipping_date',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_RESUME_NEXT_SHIPPING_DATE_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default',
                'constraints' => new Assert\Callback(function (
                    $resume_next_shipping_date = null,
                    ExecutionContextInterface $context
                ) {
                    $cutoff_date = $context->getRoot()->get('cutoff_date')->getData();
                    
                    if ($cutoff_date >= $resume_next_shipping_date) {
                        $context->buildViolation('※ 定期受注再開時次回配送予定日 > 締め日となるように選択してください。')
                            ->atPath('resume_next_shipping_date')
                            ->addViolation();
                    }
                }),
            ])
            ->add('resettlement_next_shipping_date',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_RESETTLEMENT_NEXT_SHIPPING_DATE_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('cutoff_date',ChoiceType::class, [
                'choices' => $this->getListChoiceArray(1,$this->eccubeConfig['PERIODIC_CONFIG_CUTOFF_DATE_UPPER']),
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'ipl_periodic_purchase.admin.config.select.default'
            ])
            ->add('can_resume_date', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ]
            ])
            ->add('pre_information_date', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ]
            ])
            ->add('notification_periodic_time', CollectionType::class, [
                'entry_type' => TextType::class,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'mapped' => true,
                'entry_options' => [
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => "/^\d+$/u",
                            'message' => 'form_error.numeric_only',
                        ]),
                        new Assert\Range([
                            'min' => 2,
                            'minMessage' => '2回目以上を設定してください。',
                        ]),
                    ]
                ]
            ])
            ->add('PeriodicDiscount',CollectionType::class,[
                'entry_type' => PerodicDiscountType::class,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'mapped' => true,
            ]);

        $builder->get('mypage_process')
            ->addModelTransformer($transformer);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $Config = $event->getData();
            $PeriodicDiscounts = $Config->getPeriodicDiscount();

            foreach ($PeriodicDiscounts as $PeriodicDiscount) {
                // ID存在時（更新）の場合スキップ
                if ($PeriodicDiscount->getId()) {
                    continue;
                }

                $PeriodicDiscount->setConfig($Config);
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
            'data_class' => Config::class,
        ]);
    }
}

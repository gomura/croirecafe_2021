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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\DeliveryTime;
use Eccube\Form\DataTransformer;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Eccube\Form\Validator\Email;
use Eccube\Repository\DeliveryTimeRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\PeriodicPurchaseShippingType;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\PeriodicPurchaseItemType;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PeriodicPurchaseType extends AbstractType
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
    public function __construct
    (
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        PeriodicHelper $periodicHelper,
        DeliveryTimeRepository $deliveryTimeRepository,
        PeriodicPurchaseRepository $periodicPurchaseRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->periodicHelper = $periodicHelper;
        $this->deliveryTimeRepository = $deliveryTimeRepository;
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', NameType::class, [
                'required' => false,
                'options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ],
            ])
            ->add('kana', KanaType::class, [
                'required' => false,
                'options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ],
            ])
            ->add('company_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('postal_code', PostalType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'options' => [
                    'attr' => ['class' => 'p-postal-code'],
                ],
            ])
            ->add('address', AddressType::class, [
                'required' => false,
                'pref_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'attr' => ['class' => 'p-region-id'],
                ],
                'addr01_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length([
                            'max' => $this->eccubeConfig['eccube_mtext_len'],
                        ]),
                    ],
                    'attr' => ['class' => 'p-locality p-street-address'],
                ],
                'addr02_options' => [
                    'required' => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length([
                            'max' => $this->eccubeConfig['eccube_mtext_len'],
                        ]),
                    ],
                    'attr' => ['class' => 'p-extended-address'],
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('company_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('return_link', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])
            ->add('PeriodicPurchaseShipping', PeriodicPurchaseShippingType::class ,[
                'mapped' => false,
                'data' => $options['data']->getPeriodicPurchaseShipping(),
            ])
            ->add('PeriodicPurchaseItems', CollectionType::class, [
                'entry_type' => PeriodicPurchaseItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->add('PeriodicPurchaseItemsErrors', TextType::class, [
                'mapped' => false,
            ]);

        $builder
            ->add($builder->create('Customer', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    '\Eccube\Entity\Customer'
            )));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'addNextShippingDate']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'sortPeriodicPurchaseItems']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'checkNextShippingDateOverCutoffDate']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validatePeriodicPurhcaseItemsIncludeProduct']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'associatePeriodicPurchaseAndShipping']);
    }

    /**
     * .次回お届け予定日の追加を行う
     *
     * @param FormEvent $event
     */
    public function addNextShippingDate(FormEvent $event)
    {
        $PeriodicPurchase = $event->getData();

        // 現在のステータスで変更可かチェック
        $arrAllowList = $this->periodicHelper->getChangeAllow($PeriodicPurchase, true);
        if ($arrAllowList['next_shipping']) {
            $form = $event->getForm();

            //$standard_next_shipping_date = $PeriodicPurchase->getStandardNextShippingDate();
            $next_shipping_date = $PeriodicPurchase->getNextShippingDate();

            $date = new \DateTime();

            if (isset($next_shipping_date) && $date->format('Y') > $next_shipping_date->format('Y')) {
                // 項目出力用として設定年を要素に追加
                $years[] = (int)$next_shipping_date->format('Y');
                $years = array_merge($years, range(date('Y'), date('Y') + 1));

            } else {
                $years = range(date('Y'), date('Y') + 1);
            }

            if (isset($next_shipping_date) && $date->modify('+1 years')->format('Y') < $next_shipping_date->format('Y')) {
                // 項目出力用として設定年を要素に追加
                $years[] = (int)$next_shipping_date->format('Y');
            }

            $form->add('next_shipping_date', DateType::class, [
                'placeholder' => '',
                'years' => $years,
                'format' => 'yyyy-MM-dd',
                'required' => false,
                'mapped' => false,
                'data' => $PeriodicPurchase->getNextShippingDate()
            ]);

            // 次回お届け予定日の設定
            $form['next_shipping_date']->setData($PeriodicPurchase->getNextShippingDate());

            $PeriodicPurchaseShipping = $PeriodicPurchase->getPeriodicPurchaseShipping();

            $Delivery = $PeriodicPurchaseShipping->getDelivery();
            $timeId = $PeriodicPurchaseShipping->getTimeId();
            $DeliveryTime = null;
            if ($timeId) {
                $DeliveryTime = $this->deliveryTimeRepository->find($timeId);
            }

            // お届け時間を配送業者で絞り込み
            $form->add('DeliveryTime', EntityType::class, [
                'class' => 'Eccube\Entity\DeliveryTime',
                'choice_label' => function (DeliveryTime $DeliveryTime) {
                    return $DeliveryTime->isVisible()
                        ? $DeliveryTime->getDeliveryTime()
                        : $DeliveryTime->getDeliveryTime().trans('admin.common.hidden_label');
                },
                'placeholder' => 'common.select__unspecified',
                'required' => false,
                'data' => $DeliveryTime,
                'query_builder' => function (EntityRepository $er) use ($Delivery) {
                    $qb = $er->createQueryBuilder('dt');
                    $qb
                        ->orderBy('dt.visible', 'DESC') // 非表示は下に配置
                        ->addOrderBy('dt.sort_no', 'ASC')
                        ->where('dt.Delivery = :Delivery')
                        ->setParameter('Delivery', $Delivery);


                    return $qb;
                },
                'mapped' => false,
            ]);
        }
    }

    /**
     * 定期受注明細をソートする.
     *
     * @param FormEvent $event
     */
    public function sortPeriodicPurchaseItems(FormEvent $event)
    {
        /** @var PeriodicPurchase $PeriodicPurchase */
        $PeriodicPurchase = $event->getData();
        if (null === $PeriodicPurchase) {
            return;
        }
        $PeriodicPurchaseItems = $PeriodicPurchase->getItems();

        $form = $event->getForm();
        $form['PeriodicPurchaseItems']->setData($PeriodicPurchaseItems);
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

    /**
     * 次回お届け予定日が設定可能か確認を行う.
     *
     * @param FormEvent $event
     */
    public function checkNextShippingDateOverCutoffDate(FormEvent $event)
    {
        $form = $event->getForm();
        $PeriodicPurchase = $event->getData();

        // 現在のステータスで変更可かチェック
        $arrAllowList = $this->periodicHelper->getChangeAllow($PeriodicPurchase, true);
        if ($arrAllowList['next_shipping']) {

            $next_shipping_date = $form['next_shipping_date']->getData();

            $original_next_shipping_date = $PeriodicPurchase->getNextShippingDate();

            // 値が異なる場合のみエラーチェック
            if ($next_shipping_date != $original_next_shipping_date) {

                $Config = $this->configRepository->get();

                $cutoff_date = $Config->getCutoffDate();
                $deadline = new \DateTime();
                $deadline->modify('+' . $cutoff_date . ' days');

                // 締め日より前の場合エラー
                if ($next_shipping_date <= $deadline) {
                    $form['next_shipping_date']->addError(new FormError(trans('ipl_periodic_purchase.admin.order.next_shipping_date_deadline_over', ['%deadline%' => $deadline->format('Y年m月d日')])));
                } elseif ($form->isValid()) {
                    // エラー無しの場合次回お届け日の設定
                    $PeriodicPurchase->setNextShippingDate($form['next_shipping_date']->getData());
                }
            }

            // お届け時間の取得
            $DeliveryTime = $form['DeliveryTime']->getData();

            $PeriodicPurchaseShipping = $PeriodicPurchase->getPeriodicPurchaseShipping();

            // 未選択ならnullを設定
            if (is_null($DeliveryTime)) {
                $PeriodicPurchaseShipping->setTimeId(null);
            } else {
                $PeriodicPurchaseShipping->setTimeId($DeliveryTime->getId());
            }
        }
    }

    /**
     * 受注明細のバリデーションを行う.
     * 商品明細が1件も登録されていない場合はエラーとする.
     *
     * @param FormEvent $event
     */
    public function validatePeriodicPurhcaseItemsIncludeProduct(FormEvent $event)
    {
        /** @var PeriodicPurchase $PeriodicPurchase */
        $PeriodicPurchase = $event->getData();
        $PeriodicPurchaseItems = $PeriodicPurchase->getPeriodicPurchaseItems();

        $count = 0;
        foreach ($PeriodicPurchaseItems as $PeriodicPurchaseItem) {
            if ($PeriodicPurchaseItem->isProduct()) {
                $count++;
            }
        }
        // 商品明細が1件もない場合はエラーとする.
        if ($count < 1) {
            // 画面下部にエラーメッセージを表示させる
            $form = $event->getForm();
            $form['PeriodicPurchaseItemsErrors']->addError(new FormError(trans('admin.order.product_item_not_found')));
        }
    }

    /**
     * 受注明細と, PeriodicPurchase/PeriodicPurchaseShippingの紐付けを行う.
     *
     * @param FormEvent $event
     */
    public function associatePeriodicPurchaseAndShipping(FormEvent $event)
    {
        /** @var PeriodicPurchase $PeriodicPurchase */
        $PeriodicPurchase = $event->getData();
        $PeriodicPurchaseItems = $PeriodicPurchase->getPeriodicPurchaseItems();

        // 明細とPeriodicPurchaseOrder, PeriodicPurchaseShippingを紐付ける.
        // 新規の明細のみが対象, 更新時はスキップする.
        foreach ($PeriodicPurchaseItems as $PeriodicPurchaseItem) {
            // 更新時はスキップ
            if ($PeriodicPurchaseItem->getId()) {
                continue;
            }

            $PeriodicPurchaseItem->setPeriodicPurchase($PeriodicPurchase);

            // 送料明細の紐付けを行う.
            if ($PeriodicPurchaseItem->isDeliveryFee()) {
                $PeriodicPurchaseItem->setPeriodicPurchaseShipping($PeriodicPurchase->getPeriodicPurchaseShipping());
            }

            // 商品明細の紐付けを行う.
            // 複数配送時は, 明細の追加は行われないためスキップする.
            if ($PeriodicPurchaseItem->isProduct()) {
                $PeriodicPurchaseItem->setPeriodicPurchaseShipping($PeriodicPurchase->getPeriodicPurchaseShipping());
            }
        }
    }

}
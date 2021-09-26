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
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\OrderItemType as OrderItemTypeMaster;
use Eccube\Entity\ProductClass;
use Eccube\Form\DataTransformer;
use Eccube\Form\Type\PriceType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\TaxRuleRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PeriodicPurchaseItemType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var OrderItemTypeRepository
     */
    protected $orderItemTypeRepository;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * OrderItemType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     * @param ProductClassRepository $productClassRepository
     * @param OrderItemRepository $orderItemRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        BaseInfoRepository $baseInfoRepository,
        ProductClassRepository $productClassRepository,
        OrderItemRepository $orderItemRepository,
        OrderItemTypeRepository $orderItemTypeRepository,
        TaxRuleRepository $taxRuleRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->productClassRepository = $productClassRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product_name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_mtext_len'],
                    ]),
                ],
            ])
            ->add('price', PriceType::class, [
                'accept_minus' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_int_len'],
                    ]),
                ],
            ])
            ->add('periodic_purchase_count_by_item', IntegerType::class, [
                'empty_data' => 0
            ])
            ;

        $builder
            ->add($builder->create('order_item_type', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    OrderItemTypeMaster::class
                )))
            ->add($builder->create('ProductClass', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    ProductClass::class
                )));

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var PeriodicPurchaseItem $PeriodicPurchaseItem */
            $PeriodicPurchaseItem = $event->getData();

            $OrderItemType = $PeriodicPurchaseItem->getOrderItemType();
            switch ($OrderItemType->getId()) {
                case OrderItemTypeMaster::PRODUCT:
                    $ProductClass = $PeriodicPurchaseItem->getProductClass();
                    $Product = $ProductClass->getProduct();
                    $PeriodicPurchaseItem->setProduct($Product);
                    if (null === $PeriodicPurchaseItem->getPrice()) {
                        $PeriodicPurchaseItem->setPrice($ProductClass->getPrice02());
                    }
                    if (null === $PeriodicPurchaseItem->getProductCode()) {
                        $PeriodicPurchaseItem->setProductCode($ProductClass->getCode());
                    }
                    if (null === $PeriodicPurchaseItem->getClassName1() && $ProductClass->hasClassCategory1()) {
                        $ClassCategory1 = $ProductClass->getClassCategory1();
                        $PeriodicPurchaseItem->setClassName1($ClassCategory1->getClassName()->getName());
                        $PeriodicPurchaseItem->setClassCategoryName1($ClassCategory1->getName());
                    }
                    if (null === $PeriodicPurchaseItem->getClassName2() && $ProductClass->hasClassCategory2()) {
                        if ($ClassCategory2 = $ProductClass->getClassCategory2()) {
                            $PeriodicPurchaseItem->setClassName2($ClassCategory2->getClassName()->getName());
                            $PeriodicPurchaseItem->setClassCategoryName2($ClassCategory2->getName());
                        }
                    }
                    break;

                default:
                    break;
            }
        });

        // price, quantityのバリデーション
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var PeriodicPurchaseItem $PeriodicPurchaseItem */
            $PeriodicPurchaseItem = $event->getData();

            $OrderItemType = $PeriodicPurchaseItem->getOrderItemType();
            switch ($OrderItemType->getId()) {
                // 商品明細: 金額 -> 正, 個数 -> 正負
                case OrderItemTypeMaster::PRODUCT:
                    $errors = $this->validator->validate($PeriodicPurchaseItem->getPrice(), [new Assert\GreaterThanOrEqual(0)]);
                    $this->addErrorsIfExists($form['price'], $errors);
                    break;

                // 値引き明細: 金額 -> 負, 個数 -> 正
                case OrderItemTypeMaster::DISCOUNT:
                    $errors = $this->validator->validate($PeriodicPurchaseItem->getPrice(), [new Assert\LessThanOrEqual(0)]);
                    $this->addErrorsIfExists($form['price'], $errors);
                    $errors = $this->validator->validate($PeriodicPurchaseItem->getQuantity(), [new Assert\GreaterThanOrEqual(0)]);
                    $this->addErrorsIfExists($form['quantity'], $errors);

                    break;

                // 送料, 手数料: 金額 -> 正, 個数 -> 正
                case OrderItemTypeMaster::DELIVERY_FEE:
                case OrderItemTypeMaster::CHARGE:
                    $errors = $this->validator->validate($PeriodicPurchaseItem->getPrice(), [new Assert\GreaterThanOrEqual(0)]);
                    $this->addErrorsIfExists($form['price'], $errors);
                    $errors = $this->validator->validate($PeriodicPurchaseItem->getQuantity(), [new Assert\GreaterThanOrEqual(0)]);
                    $this->addErrorsIfExists($form['quantity'], $errors);

                    break;

                default:
                    break;
            }
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'periodic_purchase_item';
    }

    /**
     * @param FormInterface $form
     * @param ConstraintViolationListInterface $errors
     */
    protected function addErrorsIfExists(FormInterface $form, ConstraintViolationListInterface $errors)
    {
        if (empty($errors)) {
            return;
        }

        foreach ($errors as $error) {
            $form->addError(new FormError(
                $error->getMessage(),
                $error->getMessageTemplate(),
                $error->getParameters(),
                $error->getPlural()));
        }
    }
}

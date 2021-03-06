<?php

/*
 * This file is part of PostCarrier for EC-CUBE
 *
 * Copyright(c) IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\PostCarrier4\Form\Type;

use Eccube\Common\EccubeConfig;
use Plugin\PostCarrier4\Entity\PostCarrierGroup;
use Plugin\PostCarrier4\Service\PostCarrierService;
use Plugin\PostCarrier4\Util\PostCarrierUtil;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostCarrierGroupType extends AbstractType
{
    /**
     * @var PostCarrierService
     */
    protected $postCarrierService;

    /**
     * PostCarrierGroupType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param PostCarrierService $postCarrierService
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        PostCarrierService $postCarrierService
    ) {
        //parent::__construct($customerStatusRepository, $eccubeConfig);
        $this->postCarrierService = $postCarrierService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $templateList = [];
        $apiData = $this->postCarrierService->getTemplateList($isError, $total, 50);
        if (!$isError) {
            foreach ($apiData['templates'] as $t) {
                $kind = PostCarrierUtil::decodeTemplateKind($t);
                if ($kind == 2 || $kind == 3) {
                    $templateLabel = "???" . $t['type'] . "???" . $t['subject'];
                    $templateList[$templateLabel] = $t['template_id'];
                }
            }
        }

        $isValidateStep = !($options['search_form'] || $options['test_mail']);

        $checkInsertItem = function ($data, ExecutionContextInterface $context) {
            // choices?????????????????????
            //dump($context->getRoot()->get('d__varList'));
            $items = ['email','memo01','memo02','memo03','memo04','memo05','memo06','memo07','memo08','memo09','memo10'];
            $errors = PostCarrierUtil::checkInsertItem($data, $items);
            foreach ($errors as $msg) {
                $context->buildViolation($msg)
                    ->addViolation();
            }
        };

        $builder
            ->add('d__template', ChoiceType::class, [
                'label' => 'postcarrier.select.label_template',
                'required' => false,
                'placeholder' => 'postcarrier.select.select_template',
                'choices' => $templateList,
            ])
            // ???????????????????????????????????????????????????????????????????????????
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($options, $isValidateStep) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    $choices = [
                        '????????????' => 'immediate',
                        '????????????????????????' => 'schedule',
                        '?????????????????????' => 'event',
                        //'????????????' => 'periodic',
                    ];

                    // ???????????????????????????????????????????????????
                    if (isset($data['d__id']) && $data['d__id'] !== null) {
                        switch($data['d__trigger']) {
                        case 'schedule':
                            $choices = ['????????????????????????' => 'schedule']; break;
                        case 'event':
                            $choices = ['?????????????????????' => 'event']; break;
                        case 'periodic':
                            $choices = ['????????????' => 'periodic']; break;
                        default:
                            assert(false); break;
                        }
                    }

                    $form->add('d__trigger', ChoiceType::class, [
                        'label' => 'postcarrier.select.label_trigger',
                        'required' => false,
                        'placeholder' => false, // ?????????????????????????????????
                        'choices' => $choices,
                        'constraints' => $isValidateStep ? [
                            new Assert\NotBlank(),
                        ] : [],
                    ]);

                    // FIXMIE: ???????????????????????????????????????????????????????????????????????????????????????????????????$data['b__event'] ?????????????????????.
                    if (!isset($data['b__event']) || $data['b__event'] == 'birthday') {
                        $choices = [
                            '???' => 'front',
                            '???' => 'back',
                        ];
                    } else {
                        $choices = [
                            '???' => 'back',
                        ];
                    }

                    $form->add('b__eventDaySelect', ChoiceType::class, [
                        'choices' => $choices,
                        'required' => false,
                        'placeholder' => false, // ?????????????????????????????????
                        'constraints' => $isValidateStep ? [
                            new Assert\NotBlank(),
                        ] : [],
                    ]);
                }
            )
            ->add('d__sch_date', DateTimeType::class, [
                'label' => '??????????????????????????????',
                'required' => false,
                'input' => 'datetime',
                // select??????
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y') + 1),
                'minutes' => range(0, 55, 5),
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--', 'hour' => '--', 'minute'  => '--'],
                // datetimepicker??????
                // 'widget' => 'single_text',
                // 'format' => 'yyyy-MM-dd HH:mm',
                // 'html5' => false,
                // 'attr' => [
                //     'class' => 'datetimepicker-input',
                //     'data-target' => '#'.$this->getBlockPrefix().'_sch_date',
                //     'data-toggle' => 'datetimepicker',
                // ],
                'constraints' => $isValidateStep ? [
                    new Assert\Callback(function (
                        $data, ExecutionContextInterface $context
                    ) {
                        $trigger = $context->getRoot()->get('d__trigger')->getData();
                        if ($trigger == 'schedule') {
                            $context
                                ->getValidator()
                                ->inContext($context)
                                ->validate($data, [
                                    new Assert\NotBlank(),
                                    new Assert\DateTime(),
                                    new Assert\GreaterThan('now'),
                                ]);
                        }
                    }),
                ] : [],
            ])
            // ???????????????????????????
            ->add('b__event', ChoiceType::class, [
                'choices' => [
                    '???????????????' => 'memberRegistrationDate',
                    // '?????????' => 'birthday',
                    // '?????????' => 'paymentDate',
                    // '?????????' => 'orderDate',
                    // '???????????????' => 'latestOrderDate',
                    // '?????????' => 'commitDate',
                    // '???????????????' => 'latestCommitDate',
                ],
                'constraints' => $isValidateStep ? [
                    new Assert\Callback(function (
                        $data, ExecutionContextInterface $context
                    ) {
                        $trigger = $context->getRoot()->get('d__trigger')->getData();
                        if ($trigger == 'event') {
                            $context
                                ->getValidator()
                                ->inContext($context)
                                ->validate($data, [
                                    new Assert\NotBlank(),
                                ]);
                        }
                    }),
                ] : [],
            ])
            ->add('b__eventDay', IntegerType::class, [
                'required' => false,
                'constraints' => $isValidateStep ? [
                    new Assert\Callback(function (
                        $data, ExecutionContextInterface $context
                    ) {
                        $trigger = $context->getRoot()->get('d__trigger')->getData();
                        if ($trigger == 'event') {
                            $event = $context->getRoot()->get('b__event')->getData();
                            $min = $event == 'birthday' ? 0 : 1; // ????????????????????????

                            $context
                                ->getValidator()
                                ->inContext($context)
                                ->validate($data, [
                                    new Assert\NotBlank(),
                                    new Assert\Range(['min' => $min, 'max' => 365]),
                                ]);
                        }
                    }),
                ] : [],
            ])
            ->add('d__stepmail_time', TimeType::class, [
                'label' => '????????????',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'choice',
                'constraints' => $isValidateStep ? [
                    new Assert\Callback(function (
                        $data, ExecutionContextInterface $context
                    ) {
                        $trigger = $context->getRoot()->get('d__trigger')->getData();
                        if ($trigger == 'event') {
                            $context
                                ->getValidator()
                                ->inContext($context)
                                ->validate($data, [
                                    new Assert\NotBlank(),
                                ]);
                        }
                    }),
                ] : [],
            ])
            // ???????????????
            ->add('d__mail_method', ChoiceType::class, [
                'label' => '???????????????',
                // ???????????????????????????'required' = false ??????????????????????????????????????????????????????
                // An invalid form control with name='post_carrier[mail_method]' is not focusable.
                'required' => !$options['search_form'],
                'expanded' => true,
                'choices' => ['HTML' => 1, '????????????'=> 2],
            ])
            ->add('d__fromAddr', EmailType::class, [
                'label' => '?????????????????????',
                'required' => false,
                'constraints' => $options['search_form'] ? [] : [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Regex([
                        'pattern' => '/^[[:graph:][:space:]]+$/i',
                        'message' => 'form.type.graph.invalid',
                    ]),
                    new Assert\Length(['max' => 128]),
                ],
            ])
            ->add('d__fromDisp', TextType::class, [
                'label' => '??????????????????',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('d__subject', TextType::class, [
                'label' => 'postcarrier.select.label_subject',
                'required' => false,
                'constraints' => $options['search_form'] ? [] : [
                    new Assert\NotBlank(),
                    new Assert\Callback($checkInsertItem),
                ],
            ])
            ->add('d__body', TextareaType::class, [
                'label' => 'postcarrier.select.label_body',
                'required' => false,
                'constraints' => $options['search_form'] ? [] : [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100000]),
                    new Assert\Callback($checkInsertItem),
                ],
            ])
            ->add('d__htmlBody', TextareaType::class, [
                'label' => 'postcarrier.select.label_body_html',
                'required' => false,
                'constraints' => $options['search_form'] ? [] : [
                    new Assert\Callback(function (
                        $data, ExecutionContextInterface $context
                    ) use ($checkInsertItem) {
                        $mail_method = $context->getRoot()->get('d__mail_method')->getData();
                        if ($mail_method == 1) {
                            $context
                                ->getValidator()
                                ->inContext($context)
                                ->validate($data, [
                                    new Assert\NotBlank(),
                                    new Assert\Length(['max' => 100000]),
                                ]);

                            $checkInsertItem($data, $context);
                        }
                    }),
                ]
            ])
            ->add('discriminator_type', HiddenType::class, [
                'data' => 'mail_customer'
            ])
            ->add('d__id', HiddenType::class) // ??????ID ??????????????????
            ->add('d__count', HiddenType::class) // ??????????????????
            ->add('d__varList',  ChoiceType::class, [
                'label' => '??????????????????',
                'required' => false,
                'placeholder' => '-- ?????????????????? --',
                'choices' => ['?????????????????????' => '{email}', '????????????1' => '{memo01}', '????????????2' => '{memo02}', '????????????3' => '{memo03}', '????????????4' => '{memo04}', '????????????5' => '{memo05}', '????????????6' => '{memo06}', '????????????7' => '{memo07}', '????????????8' => '{memo08}', '????????????9' => '{memo09}', '????????????10' => '{memo10}'],
                'data' => null,
            ])
            ->add('d__varList2',  ChoiceType::class, [
                'label' => '??????????????????',
                'required' => false,
                'placeholder' => '-- ?????????????????? --',
                'choices' => ['?????????????????????' => '{email}', '????????????1' => '{memo01}', '????????????2' => '{memo02}', '????????????3' => '{memo03}', '????????????4' => '{memo04}', '????????????5' => '{memo05}', '????????????6' => '{memo06}', '????????????7' => '{memo07}', '????????????8' => '{memo08}', '????????????9' => '{memo09}', '????????????10' => '{memo10}'],
                'data' => null,
            ])
            ->add('d__testEmail', EmailType::class, [
                'label' => '???????????????????????????',
                'required' => false,
                'constraints' => $options['test_mail'] ? [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ] : [],
            ])
            // 
            ->add('Group', EntityType::class, [
                'label' => 'postcarrier.common.group',
                'class' => PostCarrierGroup::class,
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'postcarrier.common.email',
                'required' => false,
                'constraints' => [
                    // TODO
                    // new Assert\Regex([
                    //     'pattern' => "/^[\d-]+$/u",
                    //     'message' => 'form_error.graph_and_hyphen_only',
                    // ]),
                ],
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'admin.common.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.common.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('ignore_permissions', CheckboxType::class, [
                'label' => 'postcarrier.mail_customer.label_ignore_permissions',
                'required' => false,
            ])
            ->add('d__kind', HiddenType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'search_form' => false,
            'test_mail' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'post_carrier_group';
    }
}

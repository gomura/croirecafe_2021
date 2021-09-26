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

use Eccube\Form\Type\MasterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;

class PeriodicStatusType extends AbstractType
{
    /**
     * @var PeriodicPurchaseRepository
     */
    protected $periodicPurchaseRepository;

    /**
     * PeriodicStatusType constructor.
     *
     * @param PeriodicPurchaseRepository $periodicPurchaseRepository
     */
    public function __construct(PeriodicPurchaseRepository $periodicPurchaseRepository)
    {
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var PeriodicStatus[] $PeriodicStatuses */
        $PeriodicStatuses = $options['choice_loader']->loadChoiceList()->getChoices();
        foreach ($PeriodicStatuses as $PeriodicStatus) {
            $id = $PeriodicStatus->getId();
            if ($PeriodicStatus->isDisplayOrderCount()) {
                $count = $this->periodicPurchaseRepository->countByPeriodicStatus($id);
                $view->vars['order_count'][$id]['display'] = true;
                $view->vars['order_count'][$id]['count'] = $count;
            } else {
                $view->vars['order_count'][$id]['display'] = false;
                $view->vars['order_count'][$id]['count'] = null;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => PeriodicStatus::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'periodic_order_status';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return MasterType::class;
    }
}

<?php
/**
 * This file is part of FirstTimeOnly4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 *  https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FirstTimeOnly4\Form\Admin;


use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\FirstTimeOnly4\Entity\EnabledOrderStatus;
use Plugin\FirstTimeOnly4\Repository\EnabledOrderStatusRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EnabledOrderStatusType extends AbstractType
{
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var EnabledOrderStatusRepository
     */
    private $enabledOrderStatusRepository;

    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        EnabledOrderStatusRepository $enabledOrderStatusRepository
    )
    {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->enabledOrderStatusRepository = $enabledOrderStatusRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('order_statuses', CollectionType::class, [
                'required' => true,
                'entry_type' => OrderStatusType::class
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();

                $data = new ArrayCollection();
                $OrderStatuses =  $this->orderStatusRepository->findBy([], ['sort_no' => 'ASC']);
                foreach($OrderStatuses as $OrderStatus) {
                    $EnabledOrderStatus = $this->enabledOrderStatusRepository->findOneBy([
                        'OrderStatus' => $OrderStatus
                    ]);
                    if(null === $EnabledOrderStatus) {
                        $EnabledOrderStatus = new EnabledOrderStatus();
                        $EnabledOrderStatus
                            ->setEnabled(false)
                            ->setOrderStatus($OrderStatus);
                    }
                    $data->add($EnabledOrderStatus);
                }
                $form->get('order_statuses')->setData($data);
            });
    }
}

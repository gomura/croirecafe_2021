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

namespace Plugin\FirstTimeOnly4;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\FirstTimeOnly4\Entity\EnabledOrderStatus;
use Plugin\FirstTimeOnly4\Reppsitory\EnabledOrderStatusRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->setInitialData($container);
    }

    public function update(array $meta, ContainerInterface $container)
    {
        $this->setInitialData($container);
    }

    private function setInitialData(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        /** @var EnabledOrderStatusRepository $enabledOrderStatusRepository */
        $enabledOrderStatusRepository = $entityManager->getRepository(EnabledOrderStatus::class);

        $orderStatuses = $entityManager->getRepository(OrderStatus::class)->findAll();
        /** @var OrderStatus $orderStatus */
        foreach ($orderStatuses as $orderStatus) {
            $enabledOrderStatus = $enabledOrderStatusRepository->findOneBy([
                'OrderStatus' => $orderStatus
            ]);
            if (!$enabledOrderStatus) {
                $enabledOrderStatus = new EnabledOrderStatus();
                $enabledOrderStatus
                    ->setEnabled(true)
                    ->setOrderStatus($orderStatus);
                if (
                    $orderStatus->getId() === OrderStatus::PROCESSING ||
                    $orderStatus->getId() === OrderStatus::PENDING
                ) {
                    $enabledOrderStatus->setEnabled(false);
                }
                $entityManager->persist($enabledOrderStatus);
            }
        }

        $entityManager->flush();
    }
}

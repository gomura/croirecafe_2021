<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Service\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Plugin\FlashSale\Entity\FlashSale;

class CartRuleDoctrineEventSubscriber implements EventSubscriber
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * CartRuleEventSubscriber constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public function getSubscribedEvents()
    {
        return [
            DoctrineEvents::postLoad,
            DoctrineEvents::preRemove,
        ];
    }

    /**
     * Calc flash sale discount & add to entity
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (!$entity instanceof Cart && !$entity instanceof Order) {
            return;
        }

        $repository = $this->entityManager->getRepository(FlashSale::class);
        $FlashSale = $repository->getAvailableFlashSale();
        if (!$FlashSale instanceof FlashSale) {
            return;
        }

        $discount = $FlashSale->getDiscount($entity);
        if (!$discount->getValue()) {
            return;
        }

        $entity->cleanFlashSaleDiscount()->addFlashSaleDiscount($discount->getRuleId(), $discount->getValue());
    }

    /**
     * Calc flash sale discount & add to entity
     *
     * @param LifecycleEventArgs $eventArgs
     * @codeCoverageIgnore
     */
    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        // TODO: since core does not config cascade remove for item, we need do manually
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Cart) {
            foreach ($entity->getCartItems() as $cartItem) {
                $this->entityManager->remove($cartItem);
            }
        }

        if ($entity instanceof Order) {
            foreach ($entity->getOrderItems() as $orderItem) {
                $this->entityManager->remove($orderItem);
            }
        }
    }
}

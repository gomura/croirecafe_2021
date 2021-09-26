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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Plugin\FlashSale\Service\Promotion\PromotionInterface;
use Plugin\FlashSale\Service\Rule\RuleInterface;
use Plugin\FlashSale\Service\Condition\ConditionInterface;
use Plugin\FlashSale\Service\Operator\OperatorInterface;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Service\Metadata\DiscriminatorManager;

class EntityDiEventSubscriber implements EventSubscriber
{
    /**
     * @var array
     */
    protected $container;

    /**
     * DiEventSubscriber constructor.
     *
     * @param OperatorFactory $operatorFactory
     * @param EntityManagerInterface $entityManager
     * @param DiscriminatorManager $discriminatorManager
     */
    public function __construct(
        OperatorFactory $operatorFactory,
        EntityManagerInterface $entityManager,
        DiscriminatorManager $discriminatorManager
    ) {
        $this->defineDI($operatorFactory);
        $this->defineDI($discriminatorManager);
        $this->container[EntityManagerInterface::class] = $entityManager;
    }

    /**
     * Add dependencies into container
     *
     * @param $obj
     */
    protected function defineDI($obj)
    {
        $this->container[get_class($obj)] = $obj;
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
        ];
    }

    /**
     * Inject dependencies into entity
     *
     * @param LifecycleEventArgs $eventArgs
     *
     * @throws \ReflectionException
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (!$entity instanceof RuleInterface
            && !$entity instanceof ConditionInterface
            && !$entity instanceof OperatorInterface
            && !$entity instanceof PromotionInterface
        ) {
            return;
        }

        $refClass = new \ReflectionClass($entity);
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $refMethod) {
            if (false !== stripos($refMethod->getDocComment(), '@required') && preg_match('#(?:^/\*\*|\n\s*+\*)\s*+@required(?:\s|\*/$)#i', $refMethod->getDocComment())) {
                foreach ($refMethod->getParameters() as $refParam) {
                    if (isset($this->container[$refParam->getType()->getName()])) {
                        $entity->{$refMethod->getName()}($this->container[$refParam->getType()->getName()]);
                    }
                }
            }
        }
    }
}

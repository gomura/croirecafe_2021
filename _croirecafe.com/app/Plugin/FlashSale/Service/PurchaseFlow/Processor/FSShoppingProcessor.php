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

namespace Plugin\FlashSale\Service\PurchaseFlow\Processor;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;

class FSShoppingProcessor implements DiscountProcessor
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * FSShoppingProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // TODO: Due to core load config of all plugins, we need check by our self
        $enabledPlugins = $this->container->getParameter('eccube.plugins.enabled');
        if (!in_array('FlashSale', $enabledPlugins)) {
            return;
        }

        /** @var OrderItem $item */
        foreach ($itemHolder->getItems() as $item) {
            if ($item->isDiscount() && $item->getProcessorName() == static::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @return ProcessResult|null
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // TODO: Due to core load config of all plugins, we need check by our self
        $enabledPlugins = $this->container->getParameter('eccube.plugins.enabled');
        if (!in_array('FlashSale', $enabledPlugins)) {
            return;
        }

        $discountValue = $itemHolder->getFlashSaleTotalDiscount();
        if ($discountValue) {
            $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
            $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            $Taxation = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

            $OrderItem = new OrderItem();
            $OrderItem->setProductName($DiscountType->getName())
                ->setPrice(-1 * $discountValue)
                ->setQuantity(1)
                ->setTax(0)
                ->setTaxRate(0)
                ->setTaxRuleId(null)
                ->setRoundingType(null)
                ->setOrderItemType($DiscountType)
                ->setTaxDisplayType($TaxInclude)
                ->setTaxType($Taxation)
                ->setProcessorName(static::class)
                ->setOrder($itemHolder);
            $itemHolder->addItem($OrderItem);
        }

        return null;
    }
}

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

namespace Plugin\IplPeriodicPurchase\Service\PurchaseFlow;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping;
use Plugin\IplPeriodicPurchase\Repository\CycleRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;

/**
 * 定期割引率処理.
 */
class PeriodicDiscountProcessor implements DiscountProcessor
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * DeliveryFeePreprocessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        CycleRepository $cycleRepository,
        PeriodicStatusRepository $periodicStatusRepository,
        PeriodicHelper $periodicHelper
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->cycleRepository = $cycleRepository;
        $this->periodicStatusRepository = $periodicStatusRepository;
        $this->periodicHelper = $periodicHelper;
    }

    /*
     * DiscountProcessors
     */

    /**
     * {@inheritdoc}
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }

        foreach ($itemHolder->getItems() as $item) {
            if ($item->getProcessorName() == PeriodicDiscountProcessor::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }

        $periodic_discount_amount = $this->periodicHelper->getPeriodicDiscountAmount($itemHolder);

        if ($periodic_discount_amount > 0) {
            $periodic_discount_amount *= -1;

            $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
            $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            $Taxation = $this->entityManager->find(TaxType::class, TaxType::TAXATION);

            $OrderItem = new OrderItem();
            $OrderItem->setProductName('定期割引')
                ->setPrice($periodic_discount_amount)
                ->setQuantity(1)
                ->setOrderItemType($DiscountType)
                ->setTaxDisplayType($TaxInclude)
                ->setTaxType($Taxation)
                ->setOrder($itemHolder)
                ->setProcessorName(PeriodicDiscountProcessor::class);
            $itemHolder->addItem($OrderItem);
        }
    }

    /*
     * Helper methods
     */

    /**
     * Processorが実行出来るかどうかを返す.
     *
     * 以下を満たす場合に実行できる.
     *
     * - ポイント設定が有効であること.
     * - $itemHolderがOrderエンティティであること.
     * - 会員のOrderであること.
     *
     * @param ItemHolderInterface $itemHolder
     *
     * @return bool
     */
    private function supports(ItemHolderInterface $itemHolder)
    {
        if (!$itemHolder instanceof Order) {
            return false;
        }

        if ($itemHolder->getSaleTypes()[0]->getId() !== $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
            return false;
        }

        if (!$itemHolder->getCustomer()) {
            return false;
        }

        return true;
    }

}

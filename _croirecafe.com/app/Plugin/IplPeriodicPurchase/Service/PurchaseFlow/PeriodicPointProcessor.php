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

use Eccube\Annotation\ShoppingFlow;
use Eccube\Annotation\OrderFlow;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPostValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;

/**
 * @ShoppingFlow
 * @OrderFlow
 * 定期ポイント倍率処理.
 */
class PeriodicPointProcessor extends ItemHolderPostValidator
{
    /**
     * PeriodicPointProcessor constructor.
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        BaseInfoRepository $baseInfoRepository,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->Config = $configRepository->get();
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }

        $periodic_point_rate = $this->Config->getPointRate();

        // 付与ポイントを計算
        $itemHolder->setAddPoint($itemHolder->getAddPoint() * $periodic_point_rate);
    }

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

        if (!$this->BaseInfo->isOptionPoint()) {
            return false;
        }

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

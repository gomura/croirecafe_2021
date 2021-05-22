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
use Eccube\Common\Constant;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\Processor\AbstractPurchaseProcessor;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseItem;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchaseShipping;
use Plugin\IplPeriodicPurchase\Repository\CycleRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;

/**
 * @ShoppingFlow
 * 定期マスタ生成.
 */
class CreatePeriodicPurchaseProcessor extends AbstractPurchaseProcessor
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

    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        if (!$target instanceof Order) {
            return;
        }

        // バッチから呼び出された際はスキップ
        if ($target->getPeriodicPurchase()) {
            return;
        }

        // 商品タイプは一意である前提
        $salyTypes = $target->getSaleTypes();
        if ($salyTypes[0]->getId() !== $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
            return;
        }

        // 非対応の決済方法は登録できないようにしているが、安全弁としてチェック
        $payment_method = $target->getPayment()->getMethodClass();
        if (empty($this->eccubeConfig['AUTHORIZED_PAYMENT_METHOD_PERIODIC'][$payment_method])) {
            return;
        }

        $Config = $this->configRepository->get();
        // 定期では配送先が1件のみ
        $Shippings = $target->getShippings();
        $Shipping = $Shippings[0];

        // お届け日
        if (empty($shipping_date = $Shipping->getShippingDeliveryDate())) {
            $shipping_date = new \DateTime('today');
            $shipping_date->modify('+'. $Config->getFirstShippingDate() . 'days');
            $Shipping->setShippingDeliveryDate($shipping_date);
        }

        // サイクル
        $Cycle = $target->getCycle();
        $cycle_week = $target->getCycleWeek();
        $cycle_day  = $target->getCycleDay();

        // DateTimeの計算は参照されるためcloneする
        $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust(clone $shipping_date, $Cycle, $cycle_week, $cycle_day);
        $current_date = new \DateTime();

        // 定期ステータス継続
        $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE);

        // 計算し直す
        $usePoint = $target->getUsePoint();
        $periodic_discount = 0;
        foreach ($target->getItems() as $item) {
            if ($item->isPeriodicDiscount()) {
                $periodic_discount = $item->getPrice();
            }
        }

        $payment_total = $target->getPaymentTotal() - $periodic_discount + $usePoint;
        $total = $target->getTotal() - $periodic_discount + $usePoint;

        $discount = $target->getDiscount() + $periodic_discount - $usePoint;
        if ($discount < 0) {
            $discount = 0;
        }

        // 受注情報をコピー
        $PeriodicPurchase = new PeriodicPurchase();
        $PeriodicPurchase
            ->setCustomer($target->getCustomer())
            ->setPayment($target->getPayment())

            ->setCycle($Cycle)
            ->setCycleWeek($cycle_week)
            ->setCycleDay($cycle_day)

            ->setStandardNextShippingDate($next_shipping_date)
            ->setNextShippingDate($next_shipping_date)

            ->setNextShippingTimeId($Shipping->getTimeId())
            ->setNextShippingDeliveryTime($Shipping->getShippingDeliveryTime())
            ->setShippingDate($shipping_date)
            ->setShippingTimeId($Shipping->getTimeId())
            ->setShippingDeliveryTime($Shipping->getShippingDeliveryTime())
            ->setSkipFlg(Constant::DISABLED)
            ->setPeriodicPointRate($Config->getPointRate())

            ->setPeriodicDiscount($this->periodicHelper->getPeriodicDiscount($target))

            ->setPeriodicPurchaseCount(1)
            ->setFirstOrderId($target->getId())
            ->setLastOrderId($target->getId())
            ->setMessage($target->getMessage())
            ->setName01($target->getName01())
            ->setName02($target->getName02())
            ->setKana01($target->getKana01())
            ->setKana02($target->getKana02())
            ->setCompanyName($target->getCompanyName())
            ->setEmail($target->getEmail())
            ->setPhoneNumber($target->getPhoneNumber())
            ->setPostalCode($target->getPostalCode())
            ->setPref($target->getPref())
            ->setSex($target->getSex())
            ->setAddr01($target->getAddr01())
            ->setAddr02($target->getAddr02())
            ->setBirth($target->getBirth())

            ->setSubtotal($target->getSubtotal())
            ->setDiscount($discount)
            ->setDeliveryFeeTotal($target->getDeliveryFeeTotal())
            ->setCharge($target->getCharge())
            ->setTax($target->getTax())
            ->setTotal($total)
            ->setPaymentTotal($payment_total)
            ->setPaymentMethod($target->getPaymentMethod())
            ->setNote($target->getNote())

            ->setCreateDate($current_date)
            ->setUpdateDate($current_date)
            ->setPaymentDate($current_date)

            ->setAddPoint($target->getAddPoint())
            ->setUsePoint(0)

            ->setPeriodicStatus($PeriodicStatus);

        // 配送先を作成
        $PeriodicPurchaseShipping = $this->createPeiodicPurchaseShippingFromOrdererShipping($Shipping);
        $PeriodicPurchaseShipping->setPeriodicPurchase($PeriodicPurchase);
        $PeriodicPurchase->setPeriodicPurchaseShipping($PeriodicPurchaseShipping);

        // 商品詳細を作成(送料/手数料等も含めてコピー)
        $this->createPeriodicPurchaseItemFromOrderItem($target, $PeriodicPurchase, $PeriodicPurchaseShipping);

        // target(Order)自体への定期とのリレーション
        $PeriodicPurchase->addOrder($target);
        $target->setPeriodicPurchase($PeriodicPurchase);

        $this->setOrderCompleteMessages($target, $periodic_discount);

        $this->entityManager->persist($PeriodicPurchase);
    }

    private function createPeiodicPurchaseShippingFromOrdererShipping($Shipping)
    {
        $PeriodicPurchaseShipping = new PeriodicPurchaseShipping();
        $PeriodicPurchaseShipping
            ->setName01($Shipping->getName01())
            ->setName02($Shipping->getName02())
            ->setKana01($Shipping->getKana01())
            ->setKana02($Shipping->getKana02())
            ->setCompanyName($Shipping->getCompanyName())
            ->setPhoneNumber($Shipping->getPhoneNumber())
            ->setPostalCode($Shipping->getPostalCode())
            ->setPref($Shipping->getPref())
            ->setAddr01($Shipping->getAddr01())
            ->setAddr02($Shipping->getAddr02())
            ->setShippingDeliveryName($Shipping->getShippingDeliveryName())
            ->setTimeId($Shipping->getTimeId())
            ->setShippingDeliveryTime($Shipping->getShippingDeliveryTime())
            ->setDelivery($Shipping->getDelivery());

        return $PeriodicPurchaseShipping;
    }

    private function createPeriodicPurchaseItemFromOrderItem($Order, $PeriodicPurchase, $PeriodicPurchaseShipping)
    {
        // 商品明細(PeriodicItems)とのリレーション
        foreach ($Order->getOrderItems() as $OrderItem) {
            // 定期割引、ポイント、値引きは含めない
            if ($OrderItem->isPeriodicDiscount() || $OrderItem->isPoint() || $OrderItem->isDiscount()) {
                continue;
            }

            // コピー
            $PeriodicItem = new PeriodicPurchaseItem();
            $PeriodicItem
                ->setRoundingType($OrderItem->getRoundingType())
                ->setTaxType($OrderItem->getTaxType())
                ->setTaxDisplayType($OrderItem->getTaxDisplayType())
                ->setTax($OrderItem->getTax())
                ->setTaxRate($OrderItem->getTaxRate())
                ->setTaxRuleId($OrderItem->getTaxRuleId())
                ->setOrderItemType($OrderItem->getOrderItemType())
                ->setProductName($OrderItem->getProductName())
                ->setPrice($OrderItem->getPrice())
                ->setQuantity($OrderItem->getQuantity())
                ->setProcessorName($OrderItem->getProcessorName())
                ->setPeriodicPurchaseCountByItem(1);

            if ($OrderItem->isProduct()) {
                $ProductClass = $OrderItem->getProductClass();
                $Product = $ProductClass->getProduct();

                $PeriodicItem
                    ->setProduct($Product)
                    ->setProductClass($ProductClass)
                    ->setProductCode($ProductClass->getCode());

                $ClassCategory1 = $ProductClass->getClassCategory1();
                if (!is_null($ClassCategory1)) {
                    $PeriodicItem->setClasscategoryName1($ClassCategory1->getName());
                    $PeriodicItem->setClassName1($ClassCategory1->getClassName()->getName());
                }
                $ClassCategory2 = $ProductClass->getClassCategory2();
                if (!is_null($ClassCategory2)) {
                    $PeriodicItem->setClasscategoryName2($ClassCategory2->getName());
                    $PeriodicItem->setClassName2($ClassCategory2->getClassName()->getName());
                }
            }

            $PeriodicPurchase->addPeriodicPurchaseItem($PeriodicItem);
            $PeriodicPurchaseShipping->addPeriodicPurchaseItem($PeriodicItem);
            $PeriodicItem->setPeriodicPurchase($PeriodicPurchase);
            $PeriodicItem->setPeriodicPurchaseShipping($PeriodicPurchaseShipping);
        }
    }

    private function setOrderCompleteMessages($target, $periodic_discount)
    {
        $periodic_discount = number_format(abs($periodic_discount));

        $complete_mail_message = <<<MESS
        ************************************************
        　定期購入情報
        ************************************************
        お届け頻度：{$target->getCycle()->getDisplayName()}
        定期初回割引：-￥{$periodic_discount}\n\n
MESS;

        $complete_message = <<<MESS
        <div class="ec-rectHeading">
            <h2>■定期購入情報</h2>
        </div>
        <p style="text-align:left; word-wrap: break-word; white-space: normal;">
            お届け頻度：{$target->getCycle()->getDisplayName()}<br>
            定期初回割引：-￥{$periodic_discount}<br><br>
        </p>
MESS;

        $target->appendCompleteMailMessage($complete_mail_message);
        $target->appendCompleteMessage($complete_message);
    }
}

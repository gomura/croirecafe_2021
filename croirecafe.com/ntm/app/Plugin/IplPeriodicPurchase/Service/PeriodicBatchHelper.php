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

namespace Plugin\IplPeriodicPurchase\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\CartService;
use Eccube\Service\Payment\Method\Cash;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\OrderRepository;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Plugin\YamatoPayment4\Entity\YamatoPaymentStatus;
use Plugin\YamatoPayment4\Service\Method\Credit as YamatoCredit;
use Plugin\YamatoPayment4\Service\Client\CreditClientService;
use Plugin\YamatoPayment4\Repository\ConfigRepository as YamatoConfigRepository;
use Plugin\YamatoPayment4\Repository\YamatoOrderRepository;
use Plugin\YamatoPayment4\Repository\YamatoPaymentMethodRepository;
use Plugin\YamatoPayment4\Repository\YamatoPaymentStatusRepository;
use Plugin\YamatoPayment4\Util\SecurityUtil as YamatoSecurityUtil;

class PeriodicBatchHelper
{
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        EccubeConfig $eccubeConfig,
        PurchaseFlow $shoppingPurchaseFlow,
        PluginRepository $pluginRepository,
        ConfigRepository $configRepository,
        OrderStatusRepository $orderStatusRepository,
        CartService $cartService,
        Session $session
    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->eccubeConfig = $eccubeConfig;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->pluginRepository = $pluginRepository;

        $this->Config = $configRepository->get();
        $this->orderStatusRepository = $orderStatusRepository;
        $this->cartService = $cartService;
        $this->session = $session;
    }

    /**
     * 定期Entityを元にOrder,Shipping,OrderItemを生成する
     * @param PeriodicPurchase $PeriodicPurchase
     * @param \DateTime $run_date
     * @return \Order $Order
     * @return \Shipping $Shipping
     */
    public function createOrderBasedOnPeriodicPurchase($PeriodicPurchase, $run_date)
    {
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $Order = new Order($OrderStatus);
        $Order->copyProperties(
            $PeriodicPurchase,
            [
                'id',
                'create_date',
                'update_date',
                'payment_date',
                'del_flg',
                'message',
            ]
        );
        $Order->setPeriodicPurchase($PeriodicPurchase);

        $PeriodicPurchaseShipping = $PeriodicPurchase->getPeriodicPurchaseShipping();
        $Shipping = new Shipping();
        $Shipping->copyProperties(
            $PeriodicPurchaseShipping,
            [
                'id',
                'create_date',
                'update_date',
            ]
        );
        $Shipping->setOrder($Order);
        $Shipping->setTimeId($PeriodicPurchase->getNextShippingTimeId());
        $Shipping->setShippingDeliveryTime($PeriodicPurchase->getNextShippingDeliveryTime());

        // 再決済待ち状態の定期が対象ならばお届け予定日はコンフィグを元に算出する
        if ($PeriodicPurchase->getPeriodicStatus()->getId() == PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT) {
            $shipping_date = $run_date->modify('+'. $this->Config->getResettlementNextShippingDate() . 'days');
        } else {
            $shipping_date = $PeriodicPurchase->getNextShippingDate();
        }
        $Shipping->setShippingDeliveryDate($shipping_date);
        $Order->addShipping($Shipping);

        // PeriodicItemからProductItemのみ取得して最新の情報に更新する
        $Items = $PeriodicPurchase->getProductPeriodicItems();
        foreach ($Items as $Item) {
            /* @var $ProductClass \Eccube\Entity\ProductClass */
            $ProductClass = $Item->getProductClass();
            /* @var $Product \Eccube\Entity\Product */
            $Product = $ProductClass->getProduct();

            // 商品公開状態のチェック
            if (!$Product->isEnable()) {
                throw new Exception("【商品情報取得エラー】商品が公開状態ではありません。商品ID：{$Product->getId()}", $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
            }

            // 在庫のチェック
            if (!($ProductClass->isStockUnlimited() || $ProductClass->getStock() > $Item->getQuantity())) {
                throw new Exception("【商品情報取得エラー】対象商品の在庫が不足しています。商品ID：{$Product->getId()}", $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
            }

            $OrderItem = new OrderItem();
            $OrderItem
                ->setProduct($Product)
                ->setProductClass($ProductClass)
                ->setProductName($Product->getName())
                ->setProductCode($ProductClass->getCode())
                ->setPrice($ProductClass->getPrice02())
                ->setQuantity($Item->getQuantity())
                ->setOrderItemType($Item->getOrderItemType());

            $ClassCategory1 = $ProductClass->getClassCategory1();
            if (!is_null($ClassCategory1)) {
                $OrderItem->setClasscategoryName1($ClassCategory1->getName());
                $OrderItem->setClassName1($ClassCategory1->getClassName()->getName());
            }
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if (!is_null($ClassCategory2)) {
                $OrderItem->setClasscategoryName2($ClassCategory2->getName());
                $OrderItem->setClassName2($ClassCategory2->getClassName()->getName());
            }

            $Order->addOrderItem($OrderItem);
            $Shipping->addOrderItem($OrderItem);
            $OrderItem->setOrder($Order);
            $OrderItem->setShipping($Shipping);
        }

        // purchaseFlowで設定されないため割引明細を受注に設定する
        $PeriodicPurchaseDiscountItems = $PeriodicPurchase->getPeriodicPurchaseItems()->filter(function ($ppi) {
            return $ppi->getOrderItemType()->getId() === OrderItemType::DISCOUNT;
        });

        foreach ($PeriodicPurchaseDiscountItems as $PeriodicPurchaseDiscountItem) {
            $OrderItem = new OrderItem();
            $OrderItem
                ->setProductName($PeriodicPurchaseDiscountItem->getProductName())
                ->setPrice($PeriodicPurchaseDiscountItem->getPriceIncTax())
                ->setQuantity($PeriodicPurchaseDiscountItem->getQuantity())
                ->setOrderItemType($PeriodicPurchaseDiscountItem->getOrderItemType())
                ->setShipping($Shipping)
                ->setOrder($Order)
                ->setTaxDisplayType($PeriodicPurchaseDiscountItem->getTaxDisplayType())
                ->setTaxType($PeriodicPurchaseDiscountItem->getTaxType());

            $Order->addOrderItem($OrderItem);
            $Shipping->addOrderItem($OrderItem);
            $OrderItem->setOrder($Order);
            $OrderItem->setShipping($Shipping);
        }

        return [$Order, $Shipping];
    }

    public function doPayment($Order)
    {
        // 共通処理(送料、手数料の計算など)
        $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));

        if ($flowResult->hasError()) {
            $mess = "【受注計算エラー】";
            foreach ($flowResult->getErrors() as $error) {
                $mess .= $error->getMessage();
            }
            $this->entityManager->remove($Order);
            throw new Exception($mess, $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
        }
        if ($flowResult->hasWarning()) {
            $mess = "【受注計算エラー】";
            foreach ($flowResult->getWarning() as $warning) {
                $mess .= $warning->getMessage();
            }
            $this->entityManager->remove($Order);
            throw new Exception($mess, $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
        }

        switch ($Order->getPayment()->getMethodClass()) {
            case Cash::class:
                // 購入処理を進める
                $this->purchaseFlow->prepare($Order, new PurchaseContext());
                // 購入処理を完了
                $this->purchaseFlow->commit($Order, new PurchaseContext());

                break;
            case YamatoCredit::class:
                $this->doYamatoPayment($Order);
                break;
        }
    }

    private function doYamatoPayment($Order)
    {
        // apply
        $this->purchaseFlow->prepare($Order, new PurchaseContext());

        $productRepository = $this->container->get(ProductRepository::class);
        $orderRepository = $this->container->get(OrderRepository::class);
        $yamatoConfigRepository = $this->container->get(YamatoConfigRepository::class);
        $yamatoPaymentStatusRepository = $this->container->get(YamatoPaymentStatusRepository::class);
        $yamatoPaymentMethodRepository = $this->container->get(YamatoPaymentMethodRepository::class);
        $yamatoOrderRepository = $this->container->get(YamatoOrderRepository::class);

        $YamatoPlugin = $this->pluginRepository->findByCode("YamatoPayment4");

        // ver1.2.0以降は機能追加によりコンストラクタに改修が入っているため分岐
        // リポジトリと違いサービスメソッドはprivateなためnewするしかない
        $is_ver_120_or_higher = version_compare($YamatoPlugin->getVersion(), '1.2.0', '>=');

        if ($is_ver_120_or_higher) {
            $client = new CreditClientService(
                $this->eccubeConfig,
                $productRepository,
                $orderRepository,
                $yamatoConfigRepository,
                $yamatoPaymentMethodRepository,
                $yamatoOrderRepository,
                $this->router
            );
        } else {
            $client = new CreditClientService(
                $this->eccubeConfig,
                $yamatoConfigRepository,
                $yamatoPaymentMethodRepository,
                $yamatoOrderRepository,
                $this->router
            );
        }

        // checkout
        $PluginResult = $client->doPaymentRequestForPeriodicBatch($Order, []);

        if ($PluginResult) {
            if ($is_ver_120_or_higher) {
                $securityUtil = new YamatoSecurityUtil(
                    $this->session,
                    $this->eccubeConfig
                );

                $YamatoCredit = new YamatoCredit(
                    $this->eccubeConfig,
                    $productRepository,
                    $this->purchaseFlow,
                    $orderRepository,
                    $this->orderStatusRepository,
                    $this->entityManager,
                    $this->router,
                    $yamatoConfigRepository,
                    $yamatoPaymentStatusRepository,
                    $yamatoPaymentMethodRepository,
                    $yamatoOrderRepository,
                    $securityUtil,
                    $this->cartService
                );
            } else {
                $YamatoCredit = new YamatoCredit(
                    $this->eccubeConfig,
                    $this->purchaseFlow,
                    $this->orderStatusRepository,
                    $this->entityManager,
                    $this->router,
                    $yamatoConfigRepository,
                    $yamatoPaymentMethodRepository,
                    $yamatoPaymentMethodRepository,
                    $yamatoOrderRepository
                );
            }
            $YamatoCredit->setOrder($Order);

            $data['status'] = YamatoPaymentStatus::YAMATO_ACTION_STATUS_COMP_AUTH;
            $data['settle_price'] = $Order->getPaymentTotal();

            $YamatoCredit->updatePluginPurchaseLog($data);

            // 購入処理を完了
            $this->purchaseFlow->commit($Order, new PurchaseContext());

        } else {
            $this->entityManager->remove($Order);
            throw new Exception($client->getError()[0], $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_PAYMENT_ERROR']);
        }
    }

    public function logging($msg, $PeriodicPurchase = null)
    {
        $context = [];
        if ($PeriodicPurchase) {
            $context['定期ID'] = $PeriodicPurchase->getId();
        }

        logs('IplPeriodicPurchaseBatch')->info($msg, $context);
    }

    public function isExistsBatchLogFile()
    {
        // 現在のログ出力場所を取得
        $logDir = $this->container->getParameter('kernel.logs_dir').DIRECTORY_SEPARATOR.$this->container->getParameter('kernel.environment');

        // Finder作成
        $finder = Finder::create();
        $finder->in($logDir);

        // 定期バッチのファイル全体
        $pattern = '/^IplPeriodicPurchaseBatch.*\.log$/';

        if (!$finder->files()->name($pattern)->hasResults()) {
            return '定期受注バッチの実行結果ログが存在しません。cronによる定期実行を設定して下さい。';
        }

        // Finder再作成
        $finder = Finder::create();
        $finder->in($logDir);

        $today = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');

        // 当日と前日のどちらかのログファイル
        $pattern = "/^(IplPeriodicPurchaseBatch-{$today}\.log|IplPeriodicPurchaseBatch-{$yesterday}\.log)$/";

        if (!$finder->name($pattern)->hasResults()) {
            return '直近1日における定期受注バッチの実行結果ログが存在しません。cronによる定期実行が行われているか確認して下さい。';
        }

        return;
    }
}

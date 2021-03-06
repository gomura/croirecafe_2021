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
     * ??????Entity?????????Order,Shipping,OrderItem???????????????
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

        // ?????????????????????????????????????????????????????????????????????????????????????????????????????????
        if ($PeriodicPurchase->getPeriodicStatus()->getId() == PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT) {
            $shipping_date = $run_date->modify('+'. $this->Config->getResettlementNextShippingDate() . 'days');
        } else {
            $shipping_date = $PeriodicPurchase->getNextShippingDate();
        }
        $Shipping->setShippingDeliveryDate($shipping_date);
        $Order->addShipping($Shipping);

        // PeriodicItem??????ProductItem????????????????????????????????????????????????
        $Items = $PeriodicPurchase->getProductPeriodicItems();
        foreach ($Items as $Item) {
            /* @var $ProductClass \Eccube\Entity\ProductClass */
            $ProductClass = $Item->getProductClass();
            /* @var $Product \Eccube\Entity\Product */
            $Product = $ProductClass->getProduct();

            // ?????????????????????????????????
            if (!$Product->isEnable()) {
                throw new Exception("????????????????????????????????????????????????????????????????????????????????????ID???{$Product->getId()}", $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
            }

            // ?????????????????????
            if (!($ProductClass->isStockUnlimited() || $ProductClass->getStock() > $Item->getQuantity())) {
                throw new Exception("???????????????????????????????????????????????????????????????????????????????????????ID???{$Product->getId()}", $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
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

        // purchaseFlow???????????????????????????????????????????????????????????????
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
        // ????????????(?????????????????????????????????)
        $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));

        if ($flowResult->hasError()) {
            $mess = "???????????????????????????";
            foreach ($flowResult->getErrors() as $error) {
                $mess .= $error->getMessage();
            }
            $this->entityManager->remove($Order);
            throw new Exception($mess, $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
        }
        if ($flowResult->hasWarning()) {
            $mess = "???????????????????????????";
            foreach ($flowResult->getWarning() as $warning) {
                $mess .= $warning->getMessage();
            }
            $this->entityManager->remove($Order);
            throw new Exception($mess, $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']);
        }

        switch ($Order->getPayment()->getMethodClass()) {
            case Cash::class:
                // ????????????????????????
                $this->purchaseFlow->prepare($Order, new PurchaseContext());
                // ?????????????????????
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

        // ver1.2.0??????????????????????????????????????????????????????????????????????????????????????????
        // ???????????????????????????????????????????????????private?????????new??????????????????
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

            // ?????????????????????
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
            $context['??????ID'] = $PeriodicPurchase->getId();
        }

        logs('IplPeriodicPurchaseBatch')->info($msg, $context);
    }

    public function isExistsBatchLogFile()
    {
        // ????????????????????????????????????
        $logDir = $this->container->getParameter('kernel.logs_dir').DIRECTORY_SEPARATOR.$this->container->getParameter('kernel.environment');

        // Finder??????
        $finder = Finder::create();
        $finder->in($logDir);

        // ????????????????????????????????????
        $pattern = '/^IplPeriodicPurchaseBatch.*\.log$/';

        if (!$finder->files()->name($pattern)->hasResults()) {
            return '??????????????????????????????????????????????????????????????????cron????????????????????????????????????????????????';
        }

        // Finder?????????
        $finder = Finder::create();
        $finder->in($logDir);

        $today = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');

        // ???????????????????????????????????????????????????
        $pattern = "/^(IplPeriodicPurchaseBatch-{$today}\.log|IplPeriodicPurchaseBatch-{$yesterday}\.log)$/";

        if (!$finder->name($pattern)->hasResults()) {
            return '??????1?????????????????????????????????????????????????????????????????????????????????cron?????????????????????????????????????????????????????????????????????';
        }

        return;
    }
}

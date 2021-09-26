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
namespace Plugin\IplPeriodicPurchase\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\TaxRuleService;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\PeriodicPurchaseType;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\SelectCycleType;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class PeriodicEditController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var PeriodicPurchaseRepository
     */
    protected $periodicPurchaseRepository;

    /**
     * @var PeriodicStatusRepository
     */
    protected $periodicStatusRepository;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        OrderRepository $orderRepository,
        TaxRuleRepository $taxRuleRepository,
        TaxRuleService $taxRuleService,
        PeriodicPurchaseRepository $periodicPurchaseRepository,
        PeriodicStatusRepository $periodicStatusRepository,
        PurchaseFlow $orderPurchaseFlow,
        PeriodicHelper $periodicHelper
    )
    {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->orderRepository = $orderRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleService = $taxRuleService;
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
        $this->periodicStatusRepository = $periodicStatusRepository;
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->periodicHelper = $periodicHelper;

    }

    /**
     * 定期編集画面.
     *
     * @Route("/%eccube_admin_route%/periodic/order/{id}/edit", requirements={"id" = "\d+"}, name="periodic_admin_order_edit")
     * @Template("@IplPeriodicPurchase/admin/periodic_edit.twig")
     */
    public function index(Request $request, $id = null, RouterInterface $router)
    {
        $TargetPeriodicPurchase = $this->periodicPurchaseRepository->find($id);
        if (null === $TargetPeriodicPurchase) {
            throw new NotFoundHttpException();
        }

        $arrAllowList = $this->periodicHelper->getChangeAllow($TargetPeriodicPurchase, true);

        // 編集前の定期受注情報を保持
        $OriginPeriodicPurchase = clone $TargetPeriodicPurchase;
        $OriginItems = new ArrayCollection();
        foreach ($TargetPeriodicPurchase->getPeriodicPurchaseItems() as $Item) {
            $OriginItems->add($Item);
        }

        // 作成順で定期履歴を取得
        $PeriodicPurchaseOrders = $this->orderRepository->findBy(['PeriodicPurchase' => $TargetPeriodicPurchase],['create_date' => 'DESC']);

        $builder = $this->formFactory->createBuilder(PeriodicPurchaseType::class, $TargetPeriodicPurchase);

        $form = $builder->getForm();

        // サイクル変更時は入力項目を元に戻す
        if ($request->get('mode') !== 'regist_cycle' && $request->get('mode') !== 'change_status'){
            $form->handleRequest($request);
        }

        $cycleBuilder = $this->formFactory->createBuilder(SelectCycleType::class, $TargetPeriodicPurchase);

        $cycleForm = $cycleBuilder->getForm();

        $cycleForm->handleRequest($request);

        $Cycle = $TargetPeriodicPurchase->getCycle();
        $cycle_week = $TargetPeriodicPurchase->getCycleWeek();
        $cycle_day = $TargetPeriodicPurchase->getCycleDay();

        // 編集不可時の定期サイクルの表示名を取得
        $cycle_disp_name = $this->periodicHelper->getFormatedCycleDispName($Cycle, $cycle_week, $cycle_day);

        $purchaseContext = new PurchaseContext($OriginPeriodicPurchase, $OriginPeriodicPurchase->getCustomer());

        // validateと処理出来ない税、小計、支払合計、ポイントの計算を行う。
        $this->setTax($TargetPeriodicPurchase, $purchaseContext);
        $flowResult = $this->purchaseFlow->validate($TargetPeriodicPurchase, $purchaseContext);
        $this->calculateSubTotal($TargetPeriodicPurchase);
        $TargetPeriodicPurchase->setPaymentTotal($TargetPeriodicPurchase->getTotal());

        // ポイント機能が有効の場合のみポイント計算
        if ($this->BaseInfo->isOptionPoint()){
            // 付与ポイントを計算
            $addPoint = $this->calculateAddPoint($TargetPeriodicPurchase);
            $TargetPeriodicPurchase->setAddPoint($addPoint);
        }

        if ($flowResult->hasWarning()) {
            foreach ($flowResult->getWarning() as $warning) {
                $this->addWarning($warning->getMessage(), 'admin');
            }
        }

        if ($flowResult->hasError()) {
            foreach ($flowResult->getErrors() as $error) {
                $this->addError($error->getMessage(), 'admin');
            }
        }

        // 登録ボタン押下
        switch ($request->get('mode')) {
            case 'register':
                $this->periodicHelper->logging('[管理画面] 定期受注編集開始', $TargetPeriodicPurchase);
                if ($form->isSubmitted() && $form->isValid()) {
                    try {
                        $this->purchaseFlow->prepare($TargetPeriodicPurchase, $purchaseContext);
                        $this->purchaseFlow->commit($TargetPeriodicPurchase, $purchaseContext);
                    } catch (PurchaseException $e) {
                        $this->addError($e->getMessage(), 'admin');
                        break;
                    }

                    $this->entityManager->persist($TargetPeriodicPurchase);
                    $this->entityManager->flush();

                    foreach ($OriginItems as $Item) {
                        if ($TargetPeriodicPurchase->getPeriodicPurchaseItems()->contains($Item) === false) {
                            $this->entityManager->remove($Item);
                        }
                    }
                    $this->entityManager->flush();

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    $this->periodicHelper->logging('[管理画面] 定期受注編集完了', $TargetPeriodicPurchase);

                    return $this->redirectToRoute('periodic_admin_order_edit', ['id' => $TargetPeriodicPurchase->getId()]);
                }
                break;
            case 'regist_cycle':

                if ($cycleForm->isSubmitted() && $cycleForm->isValid()) {

                    $shipping_date = $TargetPeriodicPurchase->getShippingDate();

                    $cycle_type = $cycleForm['cycle_type']->getData();

                    $Cycle = $cycleForm["cycle_$cycle_type"]->getData();
                    $TargetPeriodicPurchase->setCycle($Cycle);

                    if ($cycle_type == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']) {
                        $cycle_week = $cycleForm['cycle_week']->getData();
                        $cycle_day = $cycleForm['cycle_dayofweek']->getData();
                    } else {
                        $cycle_week = null;
                        $cycle_day = null;
                    }
                    $TargetPeriodicPurchase->setCycleWeek($cycle_week);
                    $TargetPeriodicPurchase->setCycleDay($cycle_day);

                    // 変更後のサイクルに基づき次回お届け予定日を変更する
                    $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day);

                    $TargetPeriodicPurchase->setNextShippingDate($next_shipping_date);
                    $TargetPeriodicPurchase->setStandardNextShippingDate($next_shipping_date);

                    $this->entityManager->persist($TargetPeriodicPurchase);
                    $this->entityManager->flush();

                    $this->addSuccess('ipl_periodic_purchase.admin.order.cycle_complete', 'admin');

                    $this->periodicHelper->logging('[管理画面] 定期受注 定期サイクル編集完了', $TargetPeriodicPurchase);

                    return $this->redirectToRoute('periodic_admin_order_edit', ['id' => $TargetPeriodicPurchase->getId()]);
                }

                break;
            case 'change_status':

                $change_status_type = $request->get('change_status_type');

                // POSTされたステータスが操作可能な状態であるかチェック
                if (!(isset($arrAllowList[$change_status_type]) && $arrAllowList[$change_status_type] === true)) {
                    // 異常であればエラー
                    $this->addError('ipl_periodic_purchase.admin.order.change_status_error', 'admin');
                    return $this->redirectToRoute('periodic_admin_order_edit', ['id' => $TargetPeriodicPurchase->getId()]);
                }

                $change_name = '';
                // 決済操作で分岐
                switch ($change_status_type) {
                    case 'suspend':
                        $change_name = trans('ipl_periodic_purchase.admin.order.change_status.suspend');
                        $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND);
                        $TargetPeriodicPurchase->setPeriodicStatus($PeriodicStatus);
                        $TargetPeriodicPurchase->setNextShippingDate(null);

                        break;
                    case 'resume':
                        $change_name = trans('ipl_periodic_purchase.admin.order.change_status.resume');
                        $shipping_date = $TargetPeriodicPurchase->getShippingDate();

                        $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day);

                        $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE);
                        $TargetPeriodicPurchase->setPeriodicStatus($PeriodicStatus);
                        $TargetPeriodicPurchase->setNextShippingDate($next_shipping_date);

                        break;
                    case 'cancel':
                        $change_name = trans('ipl_periodic_purchase.admin.order.change_status.cancel');
                        $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL);
                        $TargetPeriodicPurchase->setPeriodicStatus($PeriodicStatus);
                        $TargetPeriodicPurchase->setNextShippingDate(null);
                        $TargetPeriodicPurchase->setNextShippingTimeId(null);

                        break;
                    case 'resettlement':
                        $change_name = trans('ipl_periodic_purchase.admin.order.change_status.resettlement');
                        $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT);
                        $TargetPeriodicPurchase->setPeriodicStatus($PeriodicStatus);

                        break;
                    case 'cycle':
                    case 'next_shipping':
                    default:
                        // 該当項目無しならエラー
                        $this->addError('ipl_periodic_purchase.admin.order.change_status_error', 'admin');
                        return $this->redirectToRoute('periodic_admin_order_edit', ['id' => $TargetPeriodicPurchase->getId()]);

                        break;
                }

                // 変更した情報を保存
                $this->entityManager->persist($TargetPeriodicPurchase);
                $this->entityManager->flush();

                $this->addSuccess(trans('ipl_periodic_purchase.admin.order.change_status_complete', ['%status%' => $change_name]), 'admin');

                $this->periodicHelper->logging('[管理画面] 定期受注 '.$change_name.'決済操作完了', $TargetPeriodicPurchase);

                return $this->redirectToRoute('periodic_admin_order_edit', ['id' => $TargetPeriodicPurchase->getId()]);

                break;
            default:
                break;
        }

        // 商品検索フォーム
        $builder = $this->formFactory->createBuilder(SearchProductType::class);
        $searchProductModalForm = $builder->getForm();

        return [
            'form' => $form->createView(),
            'cycleForm' => $cycleForm->createView(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'PeriodicPurchase' => $TargetPeriodicPurchase,
            'PeriodicPurchaseOrders' => $PeriodicPurchaseOrders,
            'cycle_type_dayofweek' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK'],
            'id' => $id,
            'arrAllowList' => $arrAllowList,
            'cycle_disp_name' => $cycle_disp_name
        ];
    }

    /**
     * 税率の設定を行う
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    public function setTax(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        foreach ($itemHolder->getPeriodicPurchaseItems() as $item) {
            // 明細種別に応じて税区分, 税表示区分を設定する,
            $OrderItemType = $item->getOrderItemType();

            if (!$item->getTaxType()) {
                $item->setTaxType($this->getTaxType($OrderItemType));
            }
            if (!$item->getTaxDisplayType()) {
                $item->setTaxDisplayType($this->getTaxDisplayType($OrderItemType));
            }

            // 税区分: 非課税, 不課税
            if ($item->getTaxType()->getId() != TaxType::TAXATION) {
                $item->setTax(0);
                $item->setTaxRate(0);
                $item->setRoundingType(null);
                $item->setTaxRuleId(null);

                continue;
            }

            if ($item->getTaxRuleId()) {
                $TaxRule = $this->taxRuleRepository->find($item->getTaxRuleId());
            } else {
                $TaxRule = $this->taxRuleRepository->getByRule($item->getProduct(), $item->getProductClass());
            }

            // $TaxRuleを取得出来ない場合は基本税率設定を使用.
            if (null === $TaxRule) {
                $TaxRule = $this->taxRuleRepository->getByRule();
            }

            // 税込表示の場合は, priceが税込金額のため割り戻す.
            if ($item->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                $tax = $this->taxRuleService->calcTaxIncluded(
                    $item->getPrice(), $TaxRule->getTaxRate(), $TaxRule->getRoundingType()->getId(),
                    $TaxRule->getTaxAdjust());
            } else {
                $tax = $this->taxRuleService->calcTax(
                    $item->getPrice(), $TaxRule->getTaxRate(), $TaxRule->getRoundingType()->getId(),
                    $TaxRule->getTaxAdjust());
            }

            $item->setTax($tax);
            $item->setTaxRate($TaxRule->getTaxRate());
            $item->setRoundingType($TaxRule->getRoundingType());
            $item->setTaxRuleId($TaxRule->getId());
        }
    }

    /**
     * 税区分を取得する.
     *
     * - 商品: 課税
     * - 送料: 課税
     * - 値引き: 課税
     * - 手数料: 課税
     * - ポイント値引き: 不課税
     *
     * @param $OrderItemType
     *
     * @return TaxType
     */
    protected function getTaxType($OrderItemType)
    {
        if ($OrderItemType instanceof OrderItemType) {
            $OrderItemType = $OrderItemType->getId();
        }

        $TaxType = $OrderItemType == OrderItemType::POINT
            ? TaxType::NON_TAXABLE
            : TaxType::TAXATION;

        return $this->entityManager->find(TaxType::class, $TaxType);
    }

    /**
     * 税表示区分を取得する.
     *
     * - 商品: 税抜
     * - 送料: 税込
     * - 値引き: 税抜
     * - 手数料: 税込
     * - ポイント値引き: 税込
     *
     * @param $OrderItemType
     *
     * @return TaxType
     */
    protected function getTaxDisplayType($OrderItemType)
    {
        if ($OrderItemType instanceof OrderItemType) {
            $OrderItemType = $OrderItemType->getId();
        }

        switch ($OrderItemType) {
            case OrderItemType::PRODUCT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
            case OrderItemType::DELIVERY_FEE:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            case OrderItemType::DISCOUNT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
            case OrderItemType::CHARGE:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            case OrderItemType::POINT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            default:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
        }
    }

    /**
     * 付与ポイントを計算.
     *
     * @param ItemHolderInterface $itemHolder
     *
     * @return int
     */
    private function calculateAddPoint(ItemHolderInterface $itemHolder)
    {
        $basicPointRate = $this->BaseInfo->getBasicPointRate();

        // 明細ごとのポイントを集計
        $totalPoint = array_reduce($itemHolder->getItems()->toArray(),
            function ($carry, ItemInterface $item) use ($basicPointRate) {
                $pointRate = $item->isProduct() ? $item->getProductClass()->getPointRate() : null;
                if ($pointRate === null) {
                    $pointRate = $basicPointRate;
                }

                // TODO: ポイントは税抜き分しか割引されない、ポイント明細は税抜きのままでいいのか？
                $point = 0;
                if ($item->isPoint()) {
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                    // Only calc point on product
                } elseif ($item->isProduct()) {
                    // ポイント = 単価 * ポイント付与率 * 数量
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                } elseif ($item->isDiscount()) {
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                }

                return $carry + $point;
            }, 0);

        return $totalPoint < 0 ? 0 : $totalPoint;
    }

    /**
     * 小計を計算.
     *
     * @param ItemHolderInterface $itemHolder
     *
     * @return int
     */
    protected function calculateSubTotal(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->getProductClasses()
            ->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);

        $itemHolder->setSubTotal($total);
    }
}
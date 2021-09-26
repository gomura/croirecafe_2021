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
namespace Plugin\IplPeriodicPurchase\Controller\Mypage;

use Eccube\Common\EccubeConfig;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\NextShippingType;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\ShippingType;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\SelectCycleType;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\ProductsType;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseShippingRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class MypageController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PeriodicPurchaseRepository
     */
    protected $periodicRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        PeriodicPurchaseRepository $periodicRepository,
        PeriodicPurchaseShippingRepository $periodicPurchaseShippingRepository,
        PeriodicStatusRepository $periodicStatusRepository,
        PeriodicHelper $periodicHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->periodicRepository = $periodicRepository;
        $this->periodicPurchaseShippingRepository = $periodicPurchaseShippingRepository;
        $this->periodicStatusRepository = $periodicStatusRepository;
        $this->periodicHelper = $periodicHelper;
    }

    public function init($periodic_purchase_id)
    {
        $this->PeriodicPurchase = $this->periodicRepository->findOneBy(
            [
                'id' => $periodic_purchase_id,
                'Customer' => $this->getUser(),
            ]
        );

        $this->arrChangeAllow = $this->periodicHelper->getChangeAllow($this->PeriodicPurchase);
    }

    /**
     * @Route("/mypage/periodic", name="ipl_periodic_purchase_index")
     * @Template("@IplPeriodicPurchase/mypage/index.twig")
     */
    public function index(Request $request, Paginator $paginator)
    {
        $Customer = $this->getUser();

        $qb = $this->periodicRepository->getQueryBuilderByCustomer($Customer);

        $pagination = $paginator->paginate(
            $qb,
            $request->get('pageno', 1),
            $this->eccubeConfig['eccube_search_pmax']
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * 定期受注詳細を表示する.
     *
     * @Route("/mypage/periodic/history/{periodic_purchase_id}", name="ipl_periodic_purchase_history")
     * @Template("@IplPeriodicPurchase/mypage/history.twig")
     */
    public function history(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $Cycle = $this->PeriodicPurchase->getCycle();
        $cycle_week = $this->PeriodicPurchase->getCycleWeek();
        $cycle_day = $this->PeriodicPurchase->getCycleDay();
        $cycle_disp_name = $this->periodicHelper->getFormatedCycleDispName($Cycle, $cycle_week, $cycle_day);

        $PeriodicStatusSuspend = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND);

        return [
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
            'cycle_disp_name' => $cycle_disp_name,
            'PeriodicStatusSuspend' => $PeriodicStatusSuspend
        ];
    }

    /**
     * お届け頻度変更
     * 
     * @Route("/mypage/periodic/cycle/{periodic_purchase_id}", name="ipl_periodic_purchase_cycle")
     * @Template("@IplPeriodicPurchase/mypage/cycle.twig")
     */
    public function cycle(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $shipping_date = $this->PeriodicPurchase->getShippingDate();

        $builder = $this->formFactory->createBuilder(SelectCycleType::class, $this->PeriodicPurchase);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pre_cycle_id = $this->PeriodicPurchase->getCycle()->getId();
            $this->periodicHelper->logging("[マイページ] お届け頻度変更 変更前サイクルID:$pre_cycle_id", $this->PeriodicPurchase);

            $cycle_type = $form['cycle_type']->getData();

            $Cycle = $form["cycle_$cycle_type"]->getData();
            $this->PeriodicPurchase->setCycle($Cycle);

            if ($cycle_type == $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']) {
                $cycle_week = $form['cycle_week']->getData();
                $cycle_day = $form['cycle_dayofweek']->getData();
            } else {
                $cycle_week = null;
                $cycle_day = null;
            }
            $this->PeriodicPurchase->setCycleWeek($cycle_week);
            $this->PeriodicPurchase->setCycleDay($cycle_day);

            // 変更後のサイクルに基づき次回お届け予定日を変更する
            $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day);

            $this->PeriodicPurchase->setNextShippingDate($next_shipping_date);
            $this->PeriodicPurchase->setStandardNextShippingDate($next_shipping_date);

            $this->periodicHelper->logging("[マイページ] お届け頻度変更 変更後サイクルID:{$Cycle->getId()}", $this->PeriodicPurchase);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'shipping_date' => $shipping_date->format('Ymd'),
            'cycle_type_dayofweek' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK'],
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }

    /**
     * 次回お届け予定日変更
     * 
     * @Route("/mypage/periodic/next_shipping/{periodic_purchase_id}", name="ipl_periodic_purchase_next_shipping")
     * @Template("@IplPeriodicPurchase/mypage/next_shipping.twig")
     */
    public function next_shipping(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        // handleRequestを叩くと変更後の値しか取れないので予め取っておく
        $PreNextShippingDate = $this->PeriodicPurchase->getNextShippingDate()->format('Y-m-d');

        $builder = $this->formFactory->createBuilder(NextShippingType::class, $this->PeriodicPurchase);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] 次回お届け予定日変更 変更前次回お届け予定日:{$PreNextShippingDate}", $this->PeriodicPurchase);
            $this->periodicHelper->logging("[マイページ] 次回お届け予定日変更 変更後次回お届け予定日:{$this->PeriodicPurchase->getNextShippingDate()->format('Y-m-d')}", $this->PeriodicPurchase);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }

    /**
     * お届け先変更
     * 
     * @Route("/mypage/periodic/shipping/{periodic_purchase_id}", name="ipl_periodic_purchase_shipping")
     * @Template("@IplPeriodicPurchase/mypage/shipping.twig")
     */
    public function shipping(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $PeriodicPurchaseShipping = $this->PeriodicPurchase->getPeriodicPurchaseShipping();

        $builder = $this->formFactory->createBuilder(ShippingType::class, $PeriodicPurchaseShipping);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] お届け先変更", $this->PeriodicPurchase);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];

    }

    /**
     * お届け商品数変更
     * 
     * @Route("/mypage/periodic/products/{periodic_purchase_id}", name="ipl_periodic_purchase_products")
     * @Template("@IplPeriodicPurchase/mypage/products.twig")
     */
    public function products(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $PeriodicPurchaseItems = $this->PeriodicPurchase->getProductPeriodicItems();

        $builder = $this->formFactory->createBuilder();
        $builder
            ->add('products', CollectionType::class, [
                'entry_type' => ProductsType::class,
                'data' => $PeriodicPurchaseItems,
            ]);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] お届け商品数変更", $this->PeriodicPurchase);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }

    /**
     * スキップ
     * 
     * @Route("/mypage/periodic/skip/{periodic_purchase_id}", name="ipl_periodic_purchase_skip")
     * @Template("@IplPeriodicPurchase/mypage/skip.twig")
     */
    public function skip(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $pre_next_shipping_date = $this->PeriodicPurchase->getNextShippingDate();
        $Cycle = $this->PeriodicPurchase->getCycle();
        $cycle_week = $this->PeriodicPurchase->getCycleWeek();
        $cycle_day = $this->PeriodicPurchase->getCycleDay();

        $next_shipping_date = $this->periodicHelper->getNextShippingDate(clone $pre_next_shipping_date, $Cycle, $cycle_week, $cycle_day);

        $form = $this->formFactory->createBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] スキップ", $this->PeriodicPurchase);

            $this->PeriodicPurchase->setSkipFlg(Constant::ENABLED);
            $this->PeriodicPurchase->setNextShippingDate($next_shipping_date);
            $this->PeriodicPurchase->setStandardNextShippingDate($next_shipping_date);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
            'next_shipping_date' => $next_shipping_date
        ];
    }

    /**
     * 休止
     * 
     * @Route("/mypage/periodic/suspend/{periodic_purchase_id}", name="ipl_periodic_purchase_suspend")
     * @Template("@IplPeriodicPurchase/mypage/suspend.twig")
     */
    public function suspend(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $form = $this->formFactory->createBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] 休止", $this->PeriodicPurchase);

            $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND);
            $this->PeriodicPurchase->setPeriodicStatus($PeriodicStatus);
            $this->PeriodicPurchase->setNextShippingDate(null);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }

    /**
     * 再開
     * 
     * @Route("/mypage/periodic/resume/{periodic_purchase_id}", name="ipl_periodic_purchase_resume")
     * @Template("@IplPeriodicPurchase/mypage/resume.twig")
     */
    public function resume(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        $shipping_date = $this->PeriodicPurchase->getShippingDate();
        $Cycle = $this->PeriodicPurchase->getCycle();
        $cycle_week = $this->PeriodicPurchase->getCycleWeek();
        $cycle_day = $this->PeriodicPurchase->getCycleDay();

        $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day);

        $form = $this->formFactory->createBuilder()->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] 再開", $this->PeriodicPurchase);

            $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE);
            $this->PeriodicPurchase->setPeriodicStatus($PeriodicStatus);

            $this->PeriodicPurchase->setNextShippingDate($next_shipping_date);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
            'next_shipping_date' => $next_shipping_date
        ];
    }

    /**
     * 解約
     * 
     * @Route("/mypage/periodic/cancel/{periodic_purchase_id}", name="ipl_periodic_purchase_cancel")
     * @Template("@IplPeriodicPurchase/mypage/cancel.twig")
     */
    public function cancel(Request $request, $periodic_purchase_id)
    {
        // TODO : AmazonPayの場合、ステータスの反映に時間を要する場合があることを明記する

        $this->init($periodic_purchase_id);

        $form = $this->formFactory->createBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[マイページ] 解約", $this->PeriodicPurchase);

            $PeriodicStatus = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL);
            $this->PeriodicPurchase->setPeriodicStatus($PeriodicStatus);
            $this->PeriodicPurchase->setNextShippingDate(null);
            $this->PeriodicPurchase->setNextShippingTimeId(null);

            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('ipl_periodic_purchase_complete', ['periodic_purchase_id' => $periodic_purchase_id]));
        }

        return [
            'form' => $form->createView(),
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }

    /**
     * 完了
     * 
     * @Route("/mypage/periodic/complete/{periodic_purchase_id}", name="ipl_periodic_purchase_complete")
     * @Template("@IplPeriodicPurchase/mypage/complete.twig")
     */
    public function complete(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        return [
            'PeriodicPurchase' => $this->PeriodicPurchase,
            'arrChangeAllow' => $this->arrChangeAllow,
        ];
    }
}

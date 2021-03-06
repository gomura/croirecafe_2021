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
     * ?????????????????????????????????.
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
     * ?????????????????????
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
            $this->periodicHelper->logging("[???????????????] ????????????????????? ?????????????????????ID:$pre_cycle_id", $this->PeriodicPurchase);

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

            // ???????????????????????????????????????????????????????????????????????????
            $next_shipping_date = $this->periodicHelper->getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day);

            $this->PeriodicPurchase->setNextShippingDate($next_shipping_date);
            $this->PeriodicPurchase->setStandardNextShippingDate($next_shipping_date);

            $this->periodicHelper->logging("[???????????????] ????????????????????? ?????????????????????ID:{$Cycle->getId()}", $this->PeriodicPurchase);

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
     * ??????????????????????????????
     * 
     * @Route("/mypage/periodic/next_shipping/{periodic_purchase_id}", name="ipl_periodic_purchase_next_shipping")
     * @Template("@IplPeriodicPurchase/mypage/next_shipping.twig")
     */
    public function next_shipping(Request $request, $periodic_purchase_id)
    {
        $this->init($periodic_purchase_id);

        // handleRequest????????????????????????????????????????????????????????????????????????
        $PreNextShippingDate = $this->PeriodicPurchase->getNextShippingDate()->format('Y-m-d');

        $builder = $this->formFactory->createBuilder(NextShippingType::class, $this->PeriodicPurchase);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[???????????????] ?????????????????????????????? ?????????????????????????????????:{$PreNextShippingDate}", $this->PeriodicPurchase);
            $this->periodicHelper->logging("[???????????????] ?????????????????????????????? ?????????????????????????????????:{$this->PeriodicPurchase->getNextShippingDate()->format('Y-m-d')}", $this->PeriodicPurchase);

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
     * ??????????????????
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
            $this->periodicHelper->logging("[???????????????] ??????????????????", $this->PeriodicPurchase);

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
     * ????????????????????????
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
            $this->periodicHelper->logging("[???????????????] ????????????????????????", $this->PeriodicPurchase);

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
     * ????????????
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
            $this->periodicHelper->logging("[???????????????] ????????????", $this->PeriodicPurchase);

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
     * ??????
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
            $this->periodicHelper->logging("[???????????????] ??????", $this->PeriodicPurchase);

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
     * ??????
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
            $this->periodicHelper->logging("[???????????????] ??????", $this->PeriodicPurchase);

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
     * ??????
     * 
     * @Route("/mypage/periodic/cancel/{periodic_purchase_id}", name="ipl_periodic_purchase_cancel")
     * @Template("@IplPeriodicPurchase/mypage/cancel.twig")
     */
    public function cancel(Request $request, $periodic_purchase_id)
    {
        // TODO : AmazonPay?????????????????????????????????????????????????????????????????????????????????????????????

        $this->init($periodic_purchase_id);

        $form = $this->formFactory->createBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->periodicHelper->logging("[???????????????] ??????", $this->PeriodicPurchase);

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
     * ??????
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

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
namespace Plugin\IplPeriodicPurchase\Controller\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Plugin\IplPeriodicPurchase\Entity\Cycle;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\CycleType;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\CycleRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicDiscountRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ShippingDateController extends AbstractController
{

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        CycleRepository $cycleRepository,
        PeriodicDiscountRepository $periodicDiscountRepository,
        PeriodicHelper $periodicHelper
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->cycleRepository = $cycleRepository;
        $this->periodicDiscountRepository = $periodicDiscountRepository;
        $this->periodicHelper = $periodicHelper;
    }

    /**
     * @Route("/shopping/periodic/shipping_date", name="periodic_shopping_shipping_date")
     * @param Request $request
     */
    public function index(Request $request){
        $Config = $this->configRepository->get();

        $shipping_date = $request->get('shipping_date');
        if ($shipping_date) {
            $shipping_date = new \DateTime($shipping_date);
        } else {
            $shipping_date = new \DateTime('today');
            $shipping_date->modify('+'. $Config->getFirstShippingDate() . 'days');
        }

        $cycle_id = $request->get('cycle_id');
        if ($cycle_id) {
            $cycle_week = null;
            $cycle_day = null;

            $Cycle = $this->cycleRepository->find($cycle_id);
        } else {
            $cycle_week = $request->get('cycle_week');
            $cycle_day = $request->get('cycle_day');

            $Cycle = $this->cycleRepository->findOneBy(
                [
                    'cycle_type' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']
                ]
            );
        }

        if ($request->get('is_mypage_action')) {
            $shipping_date = $this->periodicHelper->getNextShippingDateToAdjust(clone $shipping_date, $Cycle, $cycle_week, $cycle_day);
        }

        $shippingDateList = [];
        // 初期表示時（＝必ず未選択）は呼ばない
        if ($cycle_id || $cycle_week || $cycle_day) {
            $shippingDateList = $this->periodicHelper->getNextShippingDateListForThreeMonths($shipping_date, $Cycle, $cycle_week, $cycle_day);
        }

        $arrCalendar = $this->periodicHelper->getDelivCalendar($shippingDateList);

        $return = ['arrCalendar' => $arrCalendar];

        if ($shippingDateList) {
            // 曜日設定用
            $dateFormatter = \IntlDateFormatter::create(
                'ja_JP@calendar=japanese',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'Asia/Tokyo',
                \IntlDateFormatter::TRADITIONAL,
                'E'
            );

            $shipping_date = $shippingDateList[0];
            $next_shipping_date = $shippingDateList[1];
            $return += [
                'shipping_date' => $shipping_date->format('Y/m/d').'('.$dateFormatter->format($shipping_date).')',
                'next_shipping_date' => $next_shipping_date->format('Y/m/d').'('.$dateFormatter->format($next_shipping_date).')',
            ];
        }

        return $this->json($return);
    }

}

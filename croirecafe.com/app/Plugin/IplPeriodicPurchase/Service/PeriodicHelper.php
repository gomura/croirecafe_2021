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
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Cart;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Entity\Cycle;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Yasumi\Yasumi;

class PeriodicHelper
{
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository

    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;

        $this->Config = $configRepository->get();
    }

    /**
     * サイクルIDをもとに次回お届け予定日を取得
     * @param \DateTime $shipping_date
     * @param Entity\Cycle $Cycle
     * @param int $cycle_week
     * @param int $cycle_day
     * @return \DateTime $shipping_date
     */
    public function getNextShippingDate($shipping_date, $Cycle, $cycle_week, $cycle_day)
    {
        // DB内とECCUBE上で扱うタイムゾーンの違いにより計算に差異が生まれるため、一度ECCUBE側の設定値に合わせる
        $timezone = new \DateTimeZone($this->container->getParameter('timezone'));
        $shipping_date->setTimezone($timezone);

        switch ($Cycle->getCycleType()) {
            // 毎月n(cycle_unit)日
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTHLY']:
                $pre_day = $shipping_date->format('d');

                $shipping_date->modify('+1 months');
                $next_day = $shipping_date->format('d');

                // 2/30などの場合に該当
                if ($next_day != $pre_day) {
                    $shipping_date->modify('-' . $next_day . ' days');
                }
                $next_year = $shipping_date->format('Y');
                $next_month = $shipping_date->format('m');
                $shipping_date->setDate($next_year, $next_month, $Cycle->getCycleUnit());
                $next_month2 = $shipping_date->format('m');

                // 2/30などの場合に該当
                if ($next_month != $next_month2) {
                    $shipping_date->modify('-' . $shipping_date->format('d') . ' days');
                }
                break;
            // nヶ月ごと
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTH']:
                $pre_day = $shipping_date->format('d');
                $shipping_date->modify('+' . $Cycle->getCycleUnit() . ' months');
                $next_day = $shipping_date->format('d');

                if ($pre_day != $next_day) {
                    $shipping_date->modify('-' . $next_day . ' days');
                }
                break;
            // n週間ごと
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_WEEK']:
                $shipping_date->modify('+' . $Cycle->getCycleUnit() . ' weeks');
                break;
            // n日ごと
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAY']:
                $shipping_date->modify('+' . $Cycle->getCycleUnit() . ' days');
                break;
            // 第n週m曜日ごと
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']:
                $pre_day = $shipping_date->format('d');
                $shipping_date->modify('+1 months');
                $next_day = $shipping_date->format('d');

                if ($pre_day != $next_day) {
                    $shipping_date->modify('-' . $next_day . ' days');
                }
                $next_year = $shipping_date->format('Y');
                $next_month = $shipping_date->format('m');
                
                // 翌月1日の曜日を取得
                $shipping_date->setDate($next_year, $next_month, 1);
                $day_of_week = $shipping_date->format('w');

                $correct = ($day_of_week <= $cycle_day) ? -$day_of_week : (7 - $day_of_week);
                $next_day = ($cycle_day + 1) + (($cycle_week - 1) * 7) + $correct;

                $shipping_date->setDate($next_year, $next_month, $next_day);
                break;
        }

        return $shipping_date;
    }

    /**
     * サイクル変更時/再開時に過去日を考慮した次回お届け予定日を取得する
     * @param \DateTime $shipping_date
     * @param Entity\Cycle $Cycle
     * @param int $cycle_week
     * @param int $cycle_day
     * @return \DateTime $compare_date
     */
    public function getNextShippingDateToAdjust($shipping_date, $Cycle, $cycle_week, $cycle_day)
    {
        $compare_date = $this->getNextShippingDate($shipping_date, $Cycle, $cycle_week, $cycle_day);
        $now_date = new \DateTime('today');
        $now_date->modify('+' . $this->Config->getResumeNextShippingDate() . 'days');

        if ($now_date > $compare_date) {
            switch ($Cycle->getCycleType()) {
                // `毎月n日`,`第n週m曜日ごと`はさらに来月
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_MONTHLY']:
                case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']:
                    while($now_date > $compare_date) {
                        $compare_date = $this->getNextShippingDate($compare_date, $Cycle, $cycle_week, $cycle_day);
                    }
                    break;

                default:
                    $compare_date = $now_date;
            }
        }

        return $compare_date;
    }

    /**
     * 実行可能な定期マイページ処理を取得する
     * @param /PeriodicPurchase $PeriodicPurchase
     * @param bool $is_admin
     * @param array $arrResults
     */
    public function getChangeAllow($PeriodicPurchase, $is_admin = false)
    {
        $arrResults = [];

        $arrProcessChecks = $this->getProcessChecks($is_admin);

        foreach ($arrProcessChecks as $process => $checks) {
            foreach ($checks as $check) {
                $arg = $check === 'isCheckProcess' ? $process : $PeriodicPurchase;

                if (($result = $this->$check($arg)) === false) {
                    break;
                }
            }
            $arrResults[$process] = $result;
        }

        return $arrResults;
    }

    public function getProcessChecks($is_admin)
    {
        if ($is_admin) {
            return [
                'cycle'         => ['isContinue'],
                'next_shipping' => ['isContinue'],
                'suspend'       => ['isNotCancel', 'isNotSuspend'],
                'resume'        => ['isSuspend'],
                'cancel'        => ['isNotCancel'],
                'resettlement'  => ['isErrorStatus'],
            ];
        } else {
            return [
                'cycle'         => ['isContinue', 'isNotPaymentError', 'isNotSystemError', 'isCheckProcess', 'isMultipleCycle'],
                'next_shipping' => ['isContinue', 'isNotPaymentError', 'isNotSystemError', 'isCheckProcess'],
                'products'      => ['isNotCancel', 'isNotPaymentError', 'isNotSystemError', 'isCheckProcess'],
                'shipping'      => ['isNotCancel', 'isNotPaymentError', 'isNotSystemError'],
                'skip'          => ['isNotCancel', 'isNotPaymentError', 'isNotSystemError', 'isNotSuspend', 'isCheckProcess', 'isNotSkip'],
                'suspend'       => ['isNotCancel', 'isNotPaymentError', 'isNotSystemError', 'isNotSuspend', 'isCheckProcess', 'canSuspend'],
                'resume'        => ['isSuspend', 'isNotPaymentError', 'isNotSystemError', 'isCheckProcess'],
                'cancel'        => ['isNotCancel', 'isNotSystemError', 'isCheckProcess', 'canCancel'],
            ];
        }
    }

    public function isContinue($PeriodicPurchase)
    {
        return $PeriodicPurchase->getPeriodicStatus()->getId() === PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE;
    }

    public function isNotCancel($PeriodicPurchase)
    {
        $status = $PeriodicPurchase->getPeriodicStatus()->getId();
        return ($status !== PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL &&
                $status !== PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL_OVER_RESUME_PERIOD);
    }

    public function isNotSuspend($PeriodicPurchase)
    {
        return $PeriodicPurchase->getPeriodicStatus()->getId() !== PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND;
    }

    public function isSuspend($PeriodicPurchase)
    {
        return $PeriodicPurchase->getPeriodicStatus()->getId() === PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND;
    }

    public function isNotPaymentError($PeriodicPurchase)
    {
        return $PeriodicPurchase->getPeriodicStatus()->getId() !== PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR;
    }

    public function isNotSystemError($PeriodicPurchase)
    {
        return $PeriodicPurchase->getPeriodicStatus()->getId() !== PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SYSTEM_ERROR;
    }

    public function isErrorStatus($PeriodicPurchase)
    {
        $status = $PeriodicPurchase->getPeriodicStatus()->getId();
        return ($status === PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR ||
                $status === PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SYSTEM_ERROR);
    }

    public function isCheckProcess($process) {
        $arrCheckMypageProcess = unserialize($this->Config->getMypageProcess());

        $arrMypageProcess = [
            'cycle'         => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_CYCLE_CHANGE'],
            'next_shipping' => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SHIPPING_DATE_CHANGE'],
            'products'      => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_ITEM_QUANTITY_CHANGE'],
            'suspend'       => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SUSPEND'],
            'resume'        => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SUSPEND'],
            'skip'          => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_SKIP'],
            'cancel'        => $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_MYPAGE_CANCEL'],
        ];

        return in_array($arrMypageProcess[$process], $arrCheckMypageProcess);
    }

    public function isMultipleCycle($PeriodicPurchase)
    {
        $arrDuplicateCycle = $this->getDuplicateCycle($PeriodicPurchase->getPeriodicPurchaseItems());

        return count($arrDuplicateCycle) > 0;
    }

    /**
     * 引数のサイクルから、重複したIDを持つサイクルを返す
     * 
     * @return Cycle[]
     */
    public function getDuplicateCycle($PeriodicPurchaseItems)
    {
        $Cycles = [];
        foreach ($PeriodicPurchaseItems as $PeriodicPurchaseItem) {
            if ($PeriodicPurchaseItem->isProduct()) {
                $CompareCycles = $PeriodicPurchaseItem->getProductClass()->getCycles()->toArray();
                // array_intersectでオブジェクトが比較できないため、idをキーにして比較
                $CompareCycles = array_column($CompareCycles, null, 'id');
                if (empty($Cycles)) {
                    $Cycles = $CompareCycles;
                } else {
                    $Cycles = array_intersect_key($Cycles, $CompareCycles);
                }
            }
        }

        return $Cycles;
    }

    public function isNotSkip($PeriodicPurchase)
    {
        return $PeriodicPurchase->getSkipFlg() !== 1;
    }

    public function canCancel($PeriodicPurchase)
    {
        $count = $PeriodicPurchase->getPeriodicPurchaseCount();
        return $this->Config->getCanCancelCount() <= $count;
    }

    public function canSuspend($PeriodicPurchase)
    {
        $count = $PeriodicPurchase->getPeriodicPurchaseCount();
        return $this->Config->getCanSuspendCount() <= $count;
    }

    /**
     * 画面表示用のサイクル名を取得する
     */
    public function getFormatedCycleDispName(Cycle $Cycle, $cycle_week, $cycle_day)
    {
        switch ($Cycle->getCycleType()) {
            // 第n週m曜日ごと
            case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']:
                list($arrWeek, $arrDayOfWeek) = $this->getPeriodicPurchaseCycleListForDayOfWeek();

                $cycle_disp_name = "毎月$arrWeek[$cycle_week]$arrDayOfWeek[$cycle_day]";
                break;
            default:
                $cycle_disp_name = $Cycle->getDisplayName();
                break;
        }

        return $cycle_disp_name;
    }

    public function getPeriodicPurchaseCycleListForDayOfWeek()
    {
        $arrWeek = [
            1 => '第一',
            2 => '第二',
            3 => '第三',
            4 => '第四',
        ];
        $arrDayOfWeek = [
            0 => '日曜日',
            1 => '月曜日',
            2 => '火曜日',
            3 => '水曜日',
            4 => '木曜日',
            5 => '金曜日',
            6 => '土曜日',
        ];

        return array($arrWeek, $arrDayOfWeek);
    }

    public function getNextShippingDateList($next_shipping_date)
    {
        $current_date = new \DateTime('today');
        // modifyするのでcloneしておく
        $next_shipping_date = clone $next_shipping_date;

        $diff = $current_date->diff($next_shipping_date)->format('%a');
        $from = $diff - $this->Config->getShippingDateChangeRange();
        $min_from = $this->Config->getCutoffDate() + 1;
        // バッチで処理できない日付(締め日より前の日付)への変更を抑制
        if ($from < $min_from) {
            $from = $min_from;
        }
        $from = new \DateTime("$from days");

        $to = $next_shipping_date->modify("+ {$this->Config->getShippingDateChangeRange()} days");
        // 終点をリストに含める
        $to->add(new \DateInterval('P1D'));

        $period = new \DatePeriod(
            $from,
            new \DateInterval('P1D'),
            $to
        );

        // 曜日設定用
        $dateFormatter = \IntlDateFormatter::create(
            'ja_JP@calendar=japanese',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Asia/Tokyo',
            \IntlDateFormatter::TRADITIONAL,
            'E'
        );

        foreach ($period as $day) {
            $arrDelivDate[$day->format('Y/m/d')] = $day->format('Y/m/d').'('.$dateFormatter->format($day).')';
        }

        return $arrDelivDate;
    }

    /**
     * 定期割引金額を取得する
     * @param ItemHolderInterface $itemHolder
     * @param PeriodicDiscount $PeriodicDiscount
     * @return int periodic_discount_amount
     */
    public function getPeriodicDiscountAmount(ItemHolderInterface $itemHolder, PeriodicDiscount $PeriodicDiscount = null)
    {
        if (!$PeriodicDiscount) {
            $PeriodicDiscount = $this->getPeriodicDiscount($itemHolder);
        }

        if ($itemHolder instanceof Cart) {
            $total = $itemHolder->getTotal();
            $periodic_discount_rate = $this->getPeriodicDiscountRate($itemHolder, $PeriodicDiscount);
			//return round($total * ($periodic_discount_rate / 100));
            //return ceil($total * ($periodic_discount_rate / 100));
            return ceil($total * ($periodic_discount_rate / 100));
        } else {
            // 初回購入のOrderで通る
            if (empty($PeriodicPurchase = $itemHolder->getPeriodicPurchase())) {
                $total = $itemHolder->getSubtotal();
                $periodic_discount_rate = $this->getPeriodicDiscountRate($itemHolder, $PeriodicDiscount);
				//return round($total * ($periodic_discount_rate / 100));
                //return floor($total * ($periodic_discount_rate / 100));
                return ceil($total * ($periodic_discount_rate / 100));
            }

            // getPeriodicDiscountRateの中で分岐させると複雑になるのでべた書き
            $discount_amount = 0;
            foreach ($PeriodicPurchase->getProductPeriodicItems() as $PeriodicItem) {
                if (!$PeriodicItem->isProduct()) {
                    continue;
                }

                $PeriodicDiscount = $PeriodicItem->getProductClass()->getPeriodicDiscount();

                $periodic_count = $PeriodicItem->getPeriodicPurchaseCountByItem() + 1;

                // 初回
                if ($periodic_count === 1) {
                    $periodic_discount_rate = $PeriodicDiscount->getDiscountRate1();
                // n回目毎
                } elseif ($periodic_count % $PeriodicDiscount->getDiscountFromCount3() === 0) {
                    $periodic_discount_rate = $PeriodicDiscount->getDiscountRate3();
                } else {
                    $periodic_discount_rate = $PeriodicDiscount->getDiscountRate2();
                }
				

                //$discount_amount += floor($PeriodicItem->getPrice() * ($periodic_discount_rate / 100));
                //$discount_amount += ceil($PeriodicItem->getPrice() * $PeriodicItem->getQuantity() * ($periodic_discount_rate / 100));
                //$discount_amount += ceil($PeriodicItem->getPrice() * $PeriodicItem->getQuantity() * ($periodic_discount_rate / 100));
                //$discount_amount += round($PeriodicItem->getPrice() * $PeriodicItem->getQuantity() * ($periodic_discount_rate / 100));
                $discount_amount += round( $PeriodicItem->getPrice() * ($periodic_discount_rate / 100) ) * $PeriodicItem->getQuantity() ;
            }

            return $discount_amount;
        }
    }

    /**
     * 定期割引Entityを取得する
     * @param ItemHolderInterface $itemHolder
     * @return PeriodicDiscount
     */
    public function getPeriodicDiscount(ItemHolderInterface $itemHolder)
    {
        // 異なる定期割引率が設定された商品は同時に購入できない前提
        foreach ($itemHolder->getItems() as $item) {
            return $item->getProductClass()->getPeriodicDiscount();
        }
    }

    /**
     * 定期割引Entityから適用する割引率を取得する
     * @param ItemHolderInterface $itemHolder
     * @param PeriodicDiscount $PeriodicDiscount
     * @return int discount_rate
     */
    public function getPeriodicDiscountRate(ItemHolderInterface $itemHolder, PeriodicDiscount $PeriodicDiscount)
    {
        $discount_rate = $PeriodicDiscount->getDiscountRate1();

        if ($itemHolder instanceof Cart) {
            return $discount_rate;
        }

        if (!empty($PeriodicPurchase = $itemHolder->getPeriodicPurchase())) {
            $periodic_count = $PeriodicPurchase->getPeriodicPurchaseCount() + 1;

            if ($periodic_count >= $PeriodicDiscount->getDiscountFromCount2()) {
                $discount_rate = $PeriodicDiscount->getDiscountRate2();
            }
            if ($periodic_count >= $PeriodicDiscount->getDiscountFromCount3()) {
                $discount_rate = $PeriodicDiscount->getDiscountRate3();
            }
        }

        return $discount_rate;
    }

    /**
     * itemHolder(カート)内の定期商品が持つ定期割引が重複していないか判定する
     * @param ItemHolderInterface $itemHolder
     * @return bool
     */
    public function isNotDuplicatedPeriodicDiscount(ItemHolderInterface $itemHolder)
    {
        $nowPeriodicDiscount = null;
        foreach ($itemHolder->getItems() as $item) {
            $ProductClass = $item->getProductClass();
            if (empty($nowPeriodicDiscount)) {
                $nowPeriodicDiscount = $ProductClass->getPeriodicDiscount();
            } else {
                if ($nowPeriodicDiscount->getId() !== $ProductClass->getPeriodicDiscount()->getId()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getNextShippingDateListForThreeMonths($shipping_date, $Cycle, $cycle_week, $cycle_day)
    {
        $cnt = 0;
        $shippingDateList[] = $shipping_date;

        while (true) {
            $next_shipping_date = $this->getNextShippingDateToAdjust(clone $shipping_date, $Cycle, $cycle_week, $cycle_day);

            $diff = $shipping_date->diff($next_shipping_date);
            // 月が変わった
            if ($shipping_date->format('m') != $next_shipping_date->format('m')) {
                if (++$cnt === 3) {
                    break;
                }
            }

            $shipping_date = $next_shipping_date;
            $shippingDateList[] = $next_shipping_date;
        }

        return $shippingDateList;
    }

    public function getDelivCalendar($shippingDateList = [], $disp_month = 3)
    {
        $date = new \DateTime('today');
        $arrCalendar = [];
        $first_deliv_day = false;
        $today = false;

        // 祝日判定用のライブラリ
        // @See https://azuyalabs.github.io/yasumi/cookbook/
        // @See https://qiita.com/sola-msr/items/7901835b3bfe145de033
        $holidays = Yasumi::create('Japan', $date->format('Y'), 'ja_JP');

        for ($i = 0; $i < $disp_month; $i++) {
            $j = 0;
            // 開始日 月初を含む週の日曜日
            $from = clone $date;
            $from->modify('first day of')->modify('+1 day')->modify('last Sunday');

            // 終了日 月末を含む週の土曜の次の日 DatePeriodは最終日が含まれないため+1する
            $to = clone $date;
            $to->modify('last day of')->modify('-1 day')->modify('next Saturday')->modify('+1 day');

            $calendar = new \DatePeriod(
                $from,
                new \DateInterval('P1D'),
                $to
            );

            foreach ($calendar as $day) {
                $arrCalendar[$i][$j]['in_month'] = $day->format('m') === $date->format('m');
                $arrCalendar[$i][$j]['first']    = $day->format('w') === '0';
                $arrCalendar[$i][$j]['last']     = $day->format('w') === '6';
                $arrCalendar[$i][$j]['year']     = $date->format('Y');
                $arrCalendar[$i][$j]['month']    = $date->format('n');
                $arrCalendar[$i][$j]['day']      = $day->format('j');
                $arrCalendar[$i][$j]['holiday']  = $holidays->isHoliday($day) || false !== array_search($day->format('w'), [0, 6]);
                if (!$today) {
                    $arrCalendar[$i][$j]['today'] = $today = $day == $date;
                }

                // お届け日のチェック
                if (in_array($day, $shippingDateList)) {
                    if (!$first_deliv_day) {
                        $arrCalendar[$i][$j]['first_deliv_day'] = true;
                        $first_deliv_day = true;
                    } else {
                        $arrCalendar[$i][$j]['deliv_day'] = true;
                    }
                }
                $j++;
            }

            $date = $this->getNextMonth($date);
        }

        return $arrCalendar;

    }

    private function getNextMonth($baseDate)
    {
        $date = clone $baseDate;
        $date->modify('first day of next month');
        $day = min((int)$baseDate->format('d'), (int)$date->format('t'));
        $date->modify("+${day} days -1 day");

        return $date;
    }

    public function logging($msg, $PeriodicPurchase = null)
    {
        $context = [];
        if ($PeriodicPurchase) {
            $context['定期ID'] = $PeriodicPurchase->getId();
        }

        logs('IplPeriodicPurchase')->info($msg, $context);
    }
}

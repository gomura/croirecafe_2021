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
namespace Plugin\IplPeriodicPurchase\Repository;

use Doctrine\ORM\QueryBuilder;
use Eccube\Repository\AbstractRepository;
use Eccube\Repository\QueryKey;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\StringUtil;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PeriodicPurchaseRepository extends AbstractRepository
{
    public function __construct(
        RegistryInterface $registry,
        ConfigRepository $configRepository,
        PaymentRepository $paymentRepository,
        PeriodicStatusRepository $periodicStatusRepository,
        PeriodicHelper $periodicHelper
    )
    {
        parent::__construct($registry, PeriodicPurchase::class);
        $this->Config = $configRepository->get();
        $this->paymentRepository = $paymentRepository;
        $this->periodicStatusRepository = $periodicStatusRepository;
        $this->periodicHelper = $periodicHelper;
    }

    public function getQueryBuilderByCustomer(\Eccube\Entity\Customer $Customer)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.Customer = :Customer')
            ->setParameter('Customer', $Customer);

        // Order By
        $qb->addOrderBy('o.id', 'DESC');

        return $qb;
    }

    public function getContinuedPeriodicPurchase(\Eccube\Entity\Customer $Customer)
    {
        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('o.PeriodicStatus != :Status1')
            ->andWhere('o.PeriodicStatus != :Status2')
            ->setParameter(':Customer', $Customer)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL)
            ->setParameter(':Status2', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL_OVER_RESUME_PERIOD)
            ->getQuery()
            ->getResult();

        return $PeriodicPurchases;
    }

    public function getPaymentErroredPeriodicPurchase(\Eccube\Entity\Customer $Customer)
    {
        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('o.PeriodicStatus = :Status1')
            ->setParameter(':Customer', $Customer)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR)
            ->getQuery()
            ->getResult();

        return $PeriodicPurchases;
    }

    public function getPaymentErroredPeriodicPurchaseWithYamatoCredit(\Eccube\Entity\Customer $Customer)
    {
        $Payment = $this->paymentRepository->findOneBy(['method_class' => \Plugin\YamatoPayment4\Service\Method\Credit::class]);

        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('o.PeriodicStatus = :Status1')
            ->andWhere('o.Payment = :Payment')
            ->setParameter(':Customer', $Customer)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR)
            ->setParameter(':Payment', $Payment)
            ->getQuery()
            ->getResult();

        return $PeriodicPurchases;
    }

    public function updatePaymentErroredPeriodicPurchasesToWaitingResettlement(\Eccube\Entity\Customer $Customer)
    {
        $this
            ->createQueryBuilder('o')
            ->update()
            ->set('o.PeriodicStatus', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT)
            ->where('o.Customer = :Customer')
            ->andWhere('o.PeriodicStatus = :Status1')
            ->setParameter(':Customer', $Customer)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR)
            ->getQuery()
            ->execute();
    }

    public function updatePeriodicPurchaseOverResumePeriod($run_date)
    {
        $limit_date = $run_date->modify('-'. $this->Config->getCanResumeDate() . 'days');

        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->where('o.PeriodicStatus = :Status1')
            ->andWhere('EXTRACT(YEAR FROM o.update_date) = :year')
            ->andWhere('EXTRACT(MONTH FROM o.update_date) = :month')
            ->andWhere('EXTRACT(DAY FROM o.update_date) = :day')
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND)
            ->setParameter(':year', $limit_date->format('Y'))
            ->setParameter(':month', $limit_date->format('m'))
            ->setParameter(':day', $limit_date->format('j'))
            ->getQuery()
            ->getResult();

        $this
            ->createQueryBuilder('o')
            ->update()
            ->set('o.PeriodicStatus', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CANCEL_OVER_RESUME_PERIOD)
            ->where('o.PeriodicStatus = :Status1')
            ->andWhere('EXTRACT(YEAR FROM o.update_date) = :year')
            ->andWhere('EXTRACT(MONTH FROM o.update_date) = :month')
            ->andWhere('EXTRACT(DAY FROM o.update_date) = :day')
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SUSPEND)
            ->setParameter(':year', $limit_date->format('Y'))
            ->setParameter(':month', $limit_date->format('m'))
            ->setParameter(':day', $limit_date->format('j'))
            ->getQuery()
            ->execute();

        return $PeriodicPurchases;
    }

    public function getPeriodicPurchasesMatchedToPreInformationDate($run_date)
    {
        $pre_information_date = $run_date->modify('+'. $this->Config->getPreInformationDate() . 'days');

        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->where('o.next_shipping_date = :pre_information_date')
            ->andWhere('o.PeriodicStatus = :Status1')
            ->setParameter(':pre_information_date', $pre_information_date)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE)
            ->getQuery()
            ->getResult();

        return $PeriodicPurchases;
    }

    public function getPeriodicPurchasesMatchedToTargetDate($target_date)
    {
        $PeriodicPurchases = $this->createQueryBuilder('o')
            ->where('o.next_shipping_date = :next_shipping_date AND o.PeriodicStatus = :Status1')
            ->orWhere('o.PeriodicStatus = :Status2')
            ->setParameter(':next_shipping_date', $target_date)
            ->setParameter(':Status1', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE)
            ->setParameter(':Status2', PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT)
            ->getQuery()
            ->getResult();

        return $PeriodicPurchases;
    }

    public function updatePeriodicPurchaseWhenBatchProcessNormality($PeriodicPurchase, $run_date, $Order)
    {
        $Cycle = $PeriodicPurchase->getCycle();
        $cycle_week = $PeriodicPurchase->getCycleWeek();
        $cycle_day = $PeriodicPurchase->getCycleDay();
        if ($PeriodicPurchase->getPeriodicStatus()->getId() == PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_WATING_RESETTLEMENT) {
            $shipping_date = $run_date->modify('+'. $this->Config->getResettlementNextShippingDate() . 'days');
        } else {
            $shipping_date = $PeriodicPurchase->getNextShippingDate();
        }

        $next_shipping_date = $this->periodicHelper->getNextShippingDate(clone $shipping_date, $Cycle, $cycle_week, $cycle_day);
        
        $PeriodicPurchase->setPeriodicStatus($this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE));
        $PeriodicPurchase->setPeriodicPurchaseCount($PeriodicPurchase->getPeriodicPurchaseCount() + 1);
        $PeriodicPurchase->setSkipFlg(0);
        $PeriodicPurchase->setShippingDate($shipping_date);
        $PeriodicPurchase->setNextShippingDate($next_shipping_date);
        $PeriodicPurchase->setStandardNextShippingDate($next_shipping_date);
        $PeriodicPurchase->setShippingTimeId($PeriodicPurchase->getNextShippingTimeId());
        $PeriodicPurchase->setNextShippingTimeId($PeriodicPurchase->getNextShippingTimeId());
        $PeriodicPurchase->setCardChangeDate(null);
        $PeriodicPurchase->setLastOrderId($Order->getId());

        $em = $this->getEntityManager();

        foreach ($PeriodicPurchase->getPeriodicPurchaseItems() as $PeriodicPurchaseItem) {
            if ($PeriodicPurchaseItem->isProduct()) {
                $PeriodicPurchaseItem->setPeriodicPurchaseCountByItem($PeriodicPurchaseItem->getPeriodicPurchaseCountByItem() + 1);
                $em->persist($PeriodicPurchaseItem);
                $em->flush($PeriodicPurchaseItem);
            }
        }

        $em->persist($PeriodicPurchase);
        $em->flush($PeriodicPurchase);
    }

    public function updatePeriodicPurchaseWhenBatchProcessException($PeriodicPurchase, $newStatus)
    {
        $PeriodicPurchase->setPeriodicStatus($newStatus);
        $PeriodicPurchase->setCardChangeDate(null);

        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $em->persist($PeriodicPurchase);
        $em->flush($PeriodicPurchase);
    }

    /**
     * @param  array        $searchData
     *
     * @return QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('pp')
            ->leftJoin('pp.Orders', 'o')
            ->leftJoin('pp.PeriodicPurchaseItems', 'ppi');

        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;
            $qb
                ->andWhere('pp.id = :multi OR pp.name01 LIKE :likemulti OR pp.name02 LIKE :likemulti OR '.
                    'pp.kana01 LIKE :likemulti OR pp.kana02 LIKE :likemulti OR pp.company_name LIKE :likemulti OR '.
                    'pp.email LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%'.$searchData['multi'].'%');
        }

        // company_name
        if (isset($searchData['company_name']) && StringUtil::isNotBlank($searchData['company_name'])) {
            $qb
                ->andWhere('pp.company_name LIKE :company_name')
                ->setParameter('company_name', '%'.$searchData['company_name'].'%');
        }

        // name
        if (isset($searchData['name']) && StringUtil::isNotBlank($searchData['name'])) {
            $qb
                ->andWhere('CONCAT(pp.name01, pp.name02) LIKE :name')
                ->setParameter('name', '%'.$searchData['name'].'%');
        }

        // kana
        if (isset($searchData['kana']) && StringUtil::isNotBlank($searchData['kana'])) {
            $qb
                ->andWhere('CONCAT(pp.kana01, pp.kana02) LIKE :kana')
                ->setParameter('kana', '%'.$searchData['kana'].'%');
        }

        // email
        if (isset($searchData['email']) && StringUtil::isNotBlank($searchData['email'])) {
            $qb
                ->andWhere('pp.email like :email')
                ->setParameter('email', '%'.$searchData['email'].'%');
        }

        // tel
        if (isset($searchData['phone_number']) && StringUtil::isNotBlank($searchData['phone_number'])) {
            $tel = preg_replace('/[^0-9]/ ', '', $searchData['phone_number']);
            $qb
                ->andWhere('pp.phone_number LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }

        // periodic_status
        if (!empty($searchData['periodic_status']) && count($searchData['periodic_status'])) {
            $qb
                ->andWhere($qb->expr()->in('pp.PeriodicStatus', ':periodic_status'))
                ->setParameter('periodic_status', $searchData['periodic_status']);
        }

        // payment
        if (!empty($searchData['payment']) && count($searchData['payment'])) {
            $payments = [];
            foreach ($searchData['payment'] as $payment) {
                $payments[] = $payment->getId();
            }
            $qb
                ->leftJoin('pp.Payment', 'p')
                ->andWhere($qb->expr()->in('p.id', ':payments'))
                ->setParameter('payments', $payments);
        }

        // periodic_id
        if (isset($searchData['periodic_id']) && StringUtil::isNotBlank($searchData['periodic_id'])) {
            $qb
                ->andWhere('pp.id = :periodic_id')
                ->setParameter('periodic_id', $searchData['periodic_id']);
        }

        // order_no
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
                ->andWhere('o.order_no = :order_no')
                ->setParameter('order_no', $searchData['order_no']);
        }

        // last_order_status
        if (isset($searchData['last_order_status']) && StringUtil::isNotBlank($searchData['last_order_status'])) {
            $qb
                ->andWhere('o.id = pp.last_order_id AND o.OrderStatus = :last_order_status')
                ->setParameter('last_order_status', $searchData['last_order_status']);
        }

        // first_order_date
        if (!empty($searchData['first_order_date_start']) && $searchData['first_order_date_start']) {
            $date = $searchData['first_order_date_start'];
            $qb
                ->andWhere('o.id = pp.first_order_id AND o.order_date >= :first_order_date_start')
                ->setParameter('first_order_date_start', $date);
        }
        if (!empty($searchData['first_order_date_end']) && $searchData['first_order_date_end']) {
            $date = clone $searchData['first_order_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.id = pp.first_order_id AND o.order_date < :first_order_date_end')
                ->setParameter('first_order_date_end', $date);
        }

        // next_shipping_date
        if (!empty($searchData['next_shipping_date_start']) && $searchData['next_shipping_date_start']) {
            $date = $searchData['next_shipping_date_start'];
            $qb
                ->andWhere('pp.next_shipping_date >= :next_shipping_date_start')
                ->setParameter('next_shipping_date_start', $date);
        }
        if (!empty($searchData['next_shipping_date_end']) && $searchData['next_shipping_date_end']) {
            $date = clone $searchData['next_shipping_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('pp.next_shipping_date < :next_shipping_date_end')
                ->setParameter('next_shipping_date_end', $date);
        }

        // periodic_count
        if (!empty($searchData['periodic_count_start']) && $searchData['periodic_count_start']) {
            $date = $searchData['periodic_count_start'];
            $qb
                ->andWhere('pp.periodic_purchase_count >= :periodic_count_start')
                ->setParameter('periodic_count_start', $date);
        }
        if (!empty($searchData['periodic_count_end']) && $searchData['periodic_count_end']) {
            $date = $searchData['periodic_count_end'];
            $qb
                ->andWhere('pp.periodic_purchase_count <= :periodic_count_end')
                ->setParameter('periodic_count_end', $date);
        }

        // payment_total
        if (!empty($searchData['payment_total_start']) && $searchData['payment_total_start']) {
            $date = $searchData['payment_total_start'];
            $qb
                ->andWhere('pp.payment_total >= :payment_total_start')
                ->setParameter('payment_total_start', $date);
        }
        if (!empty($searchData['payment_total_end']) && $searchData['payment_total_end']) {
            $date = $searchData['payment_total_end'];
            $qb
                ->andWhere('pp.payment_total <= :payment_total_end')
                ->setParameter('payment_total_end', $date);
        }

        // product_name
        if (isset($searchData['product_id']) && StringUtil::isNotBlank($searchData['product_id'])) {
            $qb
                ->leftJoin('ppi.Product', 'ppip')
                ->andWhere($qb->expr()->in('ppip.id', ':product_id'))
                ->setParameter('product_id', $searchData['product_id']);
        }

        // product_name
        if (isset($searchData['product_name']) && StringUtil::isNotBlank($searchData['product_name'])) {
            $qb
                ->andWhere('ppi.product_name LIKE :product_name')
                ->setParameter('product_name', '%'.$searchData['product_name'].'%');
        }

        // stock status
        if (isset($searchData['card_change_mail_status']) && !empty($searchData['card_change_mail_status'])) {
            switch ($searchData['card_change_mail_status']) {
                case [PeriodicPurchase::CARD_CHANGE_MAIL_STATUS_UNSENT]:
                    $qb->andWhere('pp.card_change_date IS NULL');
                    break;
                case [PeriodicPurchase::CARD_CHANGE_MAIL_STATUS_SENT]:
                    $qb->andWhere('pp.card_change_date IS NOT NULL');
                    break;
                default:
                    // 共に選択された場合は全件該当するので検索条件に含めない
            }
        }

        // periodic_item_count
        if (!empty($searchData['periodic_count_item_start']) && $searchData['periodic_count_item_start']) {
            $date = $searchData['periodic_count_item_start'];
            $qb
                ->andWhere('ppi.periodic_purchase_count_by_item >= :periodic_count_item_start')
                ->setParameter('periodic_count_item_start', $date);
        }
        if (!empty($searchData['periodic_count_item_end']) && $searchData['periodic_count_item_end']) {
            $date = $searchData['periodic_count_item_end'];
            $qb
                ->andWhere('ppi.periodic_purchase_count_by_item <= :periodic_count_item_end')
                ->setParameter('periodic_count_item_end', $date);
        }

        // Order By
        $qb->orderBy('pp.update_date', 'DESC');
        $qb->addOrderBy('pp.id', 'DESC');

        return $qb;
    }

    /**
     * ステータスごとの定期受注件数を取得する.
     *
     * @param integer $PeriodicStatusOrId
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByPeriodicStatus($PeriodicStatusOrId)
    {
        return (int) $this->createQueryBuilder('pp')
            ->select('COALESCE(COUNT(pp.id), 0)')
            ->where('pp.PeriodicStatus = :PeriodicStatus')
            ->setParameter('PeriodicStatus', $PeriodicStatusOrId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

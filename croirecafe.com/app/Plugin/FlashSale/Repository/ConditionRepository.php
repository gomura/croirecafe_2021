<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Repository;

use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\AbstractRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Service\Operator\OperatorFactory;

class ConditionRepository extends AbstractRepository
{
    /**
     * @var OperatorFactory
     */
    private $operatorFactory;

    /**
     * @var FlashSaleRepository
     */
    private $fsRepository;

    /**
     * ConditionRepository constructor.
     *
     * @param ManagerRegistry $registry
     * @param OperatorFactory $operatorFactory
     * @param FlashSaleRepository $flashSaleRepository
     */
    public function __construct(ManagerRegistry $registry, OperatorFactory $operatorFactory, FlashSaleRepository $flashSaleRepository)
    {
        parent::__construct($registry, Condition::class);
        $this->operatorFactory = $operatorFactory;
        $this->fsRepository = $flashSaleRepository;
    }

    /**
     * @param int $max
     *
     * @return array
     */
    public function getProductList(int $max = 8)
    {
        $fs = $this->fsRepository->getAvailableFlashSale();
        if (!$fs) {
            return [];
        }
        /** @var Rule[] $Rules */
        $Rules = $fs->getRules();
        $products = [];

        $prodRepository = $this->getEntityManager()->getRepository('Eccube\Entity\Product');
        foreach ($Rules as $Rule) {
            if (!$Rule instanceof Rule\ProductClassRule) {
                continue;
            }
            $qbItem = $prodRepository->createQueryBuilder('p');
            $ruleOperatorName = $Rule->getOperator();
            $operatorRule = $this->operatorFactory->createByType($ruleOperatorName);
            try {
                $qbItem = $Rule->createQueryBuilder($qbItem, $operatorRule);
            } catch (\Exception $exception) {
                log_alert($exception->getMessage());
                continue;
            }

            /** @var Product $Product */
            foreach ($qbItem->getQuery()->getResult() as $Product) {
                $tmp = [];
                /** @var ProductClass $ProductClass */
                foreach ($Product->getProductClasses() as $ProductClass) {
                    $tmp[$ProductClass->getId()] = $ProductClass->getFlashSaleDiscountPercent();
                }
                if (count($tmp) == 0) {
                    continue;
                }
                $products[$Product->getId()]['promotion'] = max($tmp);
                $products[$Product->getId()]['product'] = $Product;
                if (count($products) >= $max) {
                    break 2;
                }
            }
        }

        return $products;
    }
}

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

namespace Plugin\FlashSale\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Entity\Condition\CartTotalCondition;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Repository\FlashSaleRepository;
use Plugin\FlashSale\Service\Operator\GreaterThanOperator;
use Plugin\FlashSale\Service\Operator\InOperator;
use Plugin\FlashSale\Service\Operator\OrOperator;

/**
 * Class AbstractRepositoryTestCase
 */
abstract class AbstractRepositoryTestCase extends EccubeTestCase
{
    /**
     * @var FlashSaleRepository
     */
    protected $flashSaleRepository;

    protected $tables = [
        'plg_flash_sale_promotion',
        'plg_flash_sale_condition',
        'plg_flash_sale_rule',
        'plg_flash_sale_flash_sale',
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->flashSaleRepository = $this->container->get(FlashSaleRepository::class);
        $this->deleteAllRows($this->tables);
    }

    protected function createFS($evenName = 'Test', $rule = ProductClassRule::TYPE, $operator = OrOperator::TYPE, $conditionOperator = InOperator::TYPE)
    {
        $rules['rules'] = $this->createRule($rule, $operator, $conditionOperator);

        $FlashSale = new FlashSale();
        $FlashSale->setName($evenName);
        $FlashSale->setFromTime((new \DateTime())->modify('-1 day'));
        $FlashSale->setToTime((new \DateTime())->modify('+1 day'));
        $FlashSale->setStatus(FlashSale::STATUS_ACTIVATED);
        $FlashSale->setCreatedAt(new \DateTime());
        $FlashSale->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($FlashSale);
        $this->entityManager->flush($FlashSale);

        $FlashSale->updateFromArray($rules);
        foreach ($FlashSale->getRules() as $Rule) {
            $Promotion = $Rule->getPromotion();
            if ($Promotion instanceof Promotion) {
                if (isset($Rule->modified)) {
                    $this->entityManager->persist($Promotion);
                } else {
                    $this->entityManager->remove($Promotion);
                }
            }
            foreach ($Rule->getConditions() as $Condition) {
                if (isset($Rule->modified)) {
                    $this->entityManager->persist($Condition);
                } else {
                    $this->entityManager->remove($Condition);
                }
            }

            if (isset($Rule->modified)) {
                $this->entityManager->persist($Rule);
            } else {
                $this->entityManager->remove($Rule);
            }
        }

        $this->entityManager->flush();

        return $FlashSale;
    }

    protected function createRule($rule = ProductClassRule::TYPE, $operator = OrOperator::TYPE, $conditionOperator = InOperator::TYPE)
    {
        // promotion + condition
        if ($rule == ProductClassRule::TYPE) {
            $promotion = [
                'id' => '',
                'type' => ProductClassPricePercentPromotion::TYPE,
                'value' => 30,
            ];
            $condition = [
                [
                    'id' => '',
                    'type' => ProductClassIdCondition::TYPE,
                    'operator' => $conditionOperator,
                    'value' => '1,2,3,4,5,6,7,8,9,10',
                ],
            ];
        } elseif ($rule == CartRule::TYPE) {
            $promotion = [
                'id' => '',
                'type' => Promotion\CartTotalPercentPromotion::TYPE,
                'value' => 30,
            ];

            $condition = [
                [
                    'id' => '',
                    'type' => CartTotalCondition::TYPE,
                    'operator' => GreaterThanOperator::TYPE,
                    'value' => 2000,
                ],
            ];
        }

        $rules[] = [
            'id' => '',
            'type' => $rule,
            'operator' => $operator,
            'promotion' => $promotion,
            'conditions' => $condition,
        ];

        return $rules;
    }
}

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

use Plugin\FlashSale\Entity\Rule\CartRule;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Repository\ConditionRepository;
use Plugin\FlashSale\Service\Operator\GreaterThanOperator;
use Plugin\FlashSale\Service\Operator\OrOperator;

/**
 * Class RuleRepositoryTest
 */
class ConditionRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @var ConditionRepository
     */
    protected $conditionRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->conditionRepository = $this->container->get(ConditionRepository::class);
    }

    public function testProductList()
    {
        $this->createFS('Test');
        $this->entityManager->clear();

        $data = $this->conditionRepository->getProductList();
        $product = current($data);

        $this->expected = true;
        $this->actual = isset($product['product']);
        $this->verify();
    }

    public function testProductListEmpty()
    {
        $this->deleteAllRows($this->tables);
        $this->entityManager->clear();

        $data = $this->conditionRepository->getProductList();
        $this->expected = [];
        $this->actual = $data;
        $this->verify();
    }

    public function testProductListIsNotRule()
    {
        $this->createFS('Test', CartRule::TYPE, GreaterThanOperator::TYPE);
        $this->entityManager->clear();

        $data = $this->conditionRepository->getProductList();
        $this->expected = [];
        $this->actual = $data;
        $this->verify();
    }

    public function testProductListRuleOperatorNotMatch()
    {
//        $this->expectException(RuleException::class);

        $this->createFS('Test', ProductClassRule::TYPE, GreaterThanOperator::TYPE);
        $this->entityManager->clear();

        $data = $this->conditionRepository->getProductList();
        $this->expected = [];
        $this->actual = $data;
        $this->verify();
    }

    public function testProductListConditionOperatorNotMatch()
    {
//        $this->expectException(ConditionException::class);
        $this->createFS('Test', ProductClassRule::TYPE, OrOperator::TYPE, GreaterThanOperator::TYPE);
        $this->entityManager->clear();

        $data = $this->conditionRepository->getProductList();
        $this->expected = [];
        $this->actual = $data;
        $this->verify();
    }
}

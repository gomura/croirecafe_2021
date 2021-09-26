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

namespace Plugin\FlashSale\Tests\Entity\Condition;

use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductRepository;
use Plugin\FlashSale\Exception\ConditionException;
use Plugin\FlashSale\Service\Operator as Operator;
use Plugin\FlashSale\Entity\Condition\ProductCategoryIdCondition;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Tests\Entity\ConditionTest;
use Plugin\FlashSale\Tests\Service\Operator as OperatorTest;

/**
 * Class ProductClassIdConditionTest
 */
class ProductCategoryIdConditionTest extends ConditionTest
{
    /**
     * @var ProductCategoryIdCondition
     */
    protected $condition;

    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->condition = new ProductCategoryIdCondition();
        $this->condition->setOperatorFactory($this->container->get(OperatorFactory::class));
        $this->condition->setEntityManager($this->entityManager);
        $this->qb = $this->container->get(ProductRepository::class)->createQueryBuilder('p');
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [
            [['id' => 1, 'type' => 'condition_product_category_id', 'operator' => 'operator_in', 'value' => '1,2']],
            [['id' => 2, 'type' => 'condition_product_category_id', 'operator' => 'operator_not_in', 'value' => '3,4']],
        ];
    }

    public function testGetOperatorTypes()
    {
        $this->expected = [
            Operator\InOperator::TYPE,
            Operator\NotInOperator::TYPE,
        ];
        $this->actual = $this->condition->getOperatorTypes();
        $this->verify();
    }

    public function testMatch_Invalid()
    {
        $actual = $this->condition->match(new \stdClass());
        $this->assertEquals(false, $actual);
    }

    /**
     * @param $conditionData
     * @param $ProductClass
     * @param $expected
     * @dataProvider dataProvider_testMatch_Valid
     */
    public function testMatch_Valid($conditionData, $ProductClass, $expected)
    {
        list($conditionOperator, $conditionValue) = $conditionData;
        $this->condition->setId(rand());
        $this->condition->setValue($conditionValue);
        $this->condition->setOperator($conditionOperator);

        $actual = $this->condition->match($ProductClass);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testMatch_Valid($testMethod = null, $categoryId = 1)
    {
        $data = [];
        foreach (OperatorTest\InOperatorTest::dataProvider_testMatch($categoryId) as $operatorData) {
            list($conditionValue, , $expected) = $operatorData;
            if (is_array($conditionValue)) {
                continue;
            }

            $Product = new Product();
            $ProductCategory = new ProductCategory();
            $ProductCategory->setCategoryId($categoryId);
            $Product->addProductCategory($ProductCategory);
            $ProductClass = new ProductClass();
            $ProductClass->setProduct($Product);

            $data[] = [['operator_in', (string) $conditionValue], $ProductClass, $expected];
        }

        foreach (OperatorTest\NotInOperatorTest::dataProvider_testMatch($categoryId) as $operatorData) {
            list($conditionValue, , $expected) = $operatorData;
            if (is_array($conditionValue)) {
                continue;
            }

            $Product = new Product();
            $ProductCategory = new ProductCategory();
            $ProductCategory->setCategoryId($categoryId);
            $Product->addProductCategory($ProductCategory);
            $ProductClass = new ProductClass();
            $ProductClass->setProduct($Product);

            $data[] = [['operator_not_in', (string) $conditionValue], $ProductClass, $expected];
        }

        return $data;
    }

    /**
     * @param $operatorRule
     * @param $operatorCondition
     * @throws ConditionException
     * @dataProvider dataProvider_testCreateQueryBuilder_Exception
     */
    public function testCreateQueryBuilder_Exception($operatorRule, $operatorCondition)
    {
        $this->expectException(ConditionException::class);
        $this->condition->createQueryBuilder($this->qb, $operatorRule, $operatorCondition);
    }

    public static function dataProvider_testCreateQueryBuilder_Exception()
    {
        return [
            [new Operator\AllOperator(), new Operator\EqualOperator()],
            [new Operator\AllOperator(), new Operator\NotEqualOperator()],
            [new Operator\AllOperator(), new Operator\GreaterThanOperator()],
            [new Operator\AllOperator(), new Operator\LessThanOperator()],
            [new Operator\AllOperator(), new Operator\AllOperator()],
            [new Operator\AllOperator(), new Operator\OrOperator()],
        ];
    }

    /**
     * @param $conditionValue
     * @param $operatorRule
     * @param $operatorCondition
     * @param $expectedWhere
     * @param $expectedDQL
     * @throws ConditionException
     * @dataProvider dataProvider_testCreateQueryBuilder_Valid
     */
    public function testCreateQueryBuilder_Valid($conditionValue, $operatorRule, $operatorCondition, $expectedWhere, $expectedDQL)
    {
        $this->condition->setValue($conditionValue);
        $qb  = $this->condition->createQueryBuilder($this->qb, $operatorRule, $operatorCondition);
        $this->assertTrue((bool)strstr($qb->getDQL(), $expectedDQL));
        $this->assertEquals(get_class($qb->getDQLPart('where')), $expectedWhere);
    }

    public static function dataProvider_testCreateQueryBuilder_Valid()
    {
        return [
            [
                $i = rand(),
                new Operator\AllOperator(),
                new Operator\InOperator(),
                'Doctrine\ORM\Query\Expr\Andx',
                "pct.category_id IN($i)"
            ],
            [
                $i = rand(),
                new Operator\AllOperator(),
                new Operator\NotInOperator(),
                'Doctrine\ORM\Query\Expr\Andx',
                "p.id NOT IN(SELECT p2 FROM Eccube\Entity\Product p2 WHERE pct IN($i)"
            ],
            [
                $i = rand(),
                new Operator\OrOperator(),
                new Operator\InOperator(),
                'Doctrine\ORM\Query\Expr\Orx',
                "pct.category_id IN($i)"
            ],
            [
                $i = rand(),
                new Operator\OrOperator(),
                new Operator\NotInOperator(),
                'Doctrine\ORM\Query\Expr\Orx',
                "p.id NOT IN(SELECT p2 FROM Eccube\Entity\Product p2 WHERE pct IN($i)"
            ],
        ];
    }
}

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

namespace Plugin\FlashSale\Tests\Service\Operator;

use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Service\Operator as Operator;

class OperatorFactoryTest extends EccubeTestCase
{
    /**
     * @var  OperatorFactory
     */
    protected $operatorFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->operatorFactory = $this->container->get(OperatorFactory::class);
    }

    public function testCreateByType_Exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->operatorFactory->createByType('');
    }

    /**
     * @param $type
     * @param $expected
     * @dataProvider dataProvider_testCreateByType
     */
    public function testCreateByType($type, $expected)
    {
        $actual = $this->operatorFactory->createByType($type);
        $this->assertInstanceOf($expected, $actual);
    }

    public static function dataProvider_testCreateByType()
    {
        return [
            [Operator\AllOperator::TYPE, Operator\AllOperator::class],
            [Operator\OrOperator::TYPE, Operator\OrOperator::class],
            [Operator\InOperator::TYPE, Operator\InOperator::class],
            [Operator\NotInOperator::TYPE, Operator\NotInOperator::class],
            [Operator\EqualOperator::TYPE, Operator\EqualOperator::class],
            [Operator\NotEqualOperator::TYPE, Operator\NotEqualOperator::class],
            [Operator\GreaterThanOperator::TYPE, Operator\GreaterThanOperator::class],
            [Operator\LessThanOperator::TYPE, Operator\LessThanOperator::class],
        ];
    }
}

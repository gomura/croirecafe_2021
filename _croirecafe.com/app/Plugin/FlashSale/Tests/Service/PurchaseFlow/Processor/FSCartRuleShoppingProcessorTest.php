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

namespace Plugin\FlashSale\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Order;
use Eccube\Tests\EccubeTestCase;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Tests\Entity\FlashSaleTest;
use Plugin\FlashSale\Service\PurchaseFlow\Processor\FSCartRuleShoppingProcessor;
use Plugin\FlashSale\Repository\FlashSaleRepository;

class FSCartRuleShoppingProcessorTest extends EccubeTestCase
{
    /**
     * @var FSCartRuleShoppingProcessor
     */
    protected $cartRuleShoppingProcessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $flashSaleRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->flashSaleRepository = $this->getMockBuilder(FlashSaleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRuleShoppingProcessor = new FSCartRuleShoppingProcessor($this->flashSaleRepository);
    }

    /**
     * @param $FlashSale
     * @param $Order
     * @param $expected
     * @dataProvider dataProvider_testProcess
     */
    public function testProcess($FlashSale, $Order, $expected)
    {
        if ($FlashSale) {
            /** @var FlashSale $FlashSale */
            foreach ($FlashSale->getRules() as $Rule) {
                $Rule->setOperatorFactory($this->container->get(OperatorFactory::class));
                foreach ($Rule->getConditions() as $Condition) {
                    $Condition->setOperatorFactory($this->container->get(OperatorFactory::class));
                }
            }
        }

        /** @var PurchaseContext $purchaseContext */
        $purchaseContext = $this->getMockBuilder(PurchaseContext::class)->getMock();
        $this->flashSaleRepository->method('getAvailableFlashsale')->willReturn($FlashSale);
        $this->cartRuleShoppingProcessor->process($Order, $purchaseContext);
        $this->assertEquals($expected, $Order->getFlashSaleTotalDiscount());
    }

    public static function dataProvider_testProcess($testMethod = null, $orderSubtotal = 12345)
    {
        $data = [];

        $data[] = [null, new Order(), 0];

        $tmp = FlashSaleTest::dataProvider_testGetDiscount_Valid_CartRule(null, $orderSubtotal);
        foreach ($tmp as $tmpData) {
            list($Rules, $tmpOrder, $expected) = $tmpData;

            $FlashSale = new FlashSale();
            $FlashSale->setId(rand());

            /** @var Rule $Rule */
            foreach ($Rules as $Rule) {
                $FlashSale->addRule($Rule);
            }
            $Order = new Order();
            $Order->setPropertiesFromArray(['id' => $tmpOrder->getId()]);
            $Order->setSubtotal($tmpOrder->getSubtotal());
            $Order->setTotal($tmpOrder->getSubtotal());

            $data[] = [$FlashSale, $Order, $expected];
        }

        return $data;
    }
}

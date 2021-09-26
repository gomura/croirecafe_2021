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

namespace Plugin\FlashSale\Tests\Service;

use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\FlashSale\Service\PurchaseFlow\Processor\FSShoppingProcessor;

class FSShoppingProcessorTest extends AbstractServiceTestCase
{
    /**
     * @var FSShoppingProcessor
     */
    protected $flashSaleShoppingProcessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $purchaseContext;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->purchaseContext = $this->getMockBuilder(PurchaseContext::class)->getMock();
        $this->flashSaleShoppingProcessor = new FSShoppingProcessor($this->entityManager, $this->container);
    }

    public function testRemoveDiscountItem()
    {
        $Order = new Order();
        $DiscountItem = new OrderItem();
        $DiscountItem->setProcessorName(FSShoppingProcessor::class);
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        $DiscountItem->setOrderItemType($DiscountType);
        $Order->addItem($DiscountItem);
        $this->flashSaleShoppingProcessor->removeDiscountItem($Order, $this->purchaseContext);
        $this->assertEquals(0, count($Order->getItems()));
    }

    public function testAddDiscountItem_Scenario0()
    {
        $Order = new Order();
        $this->flashSaleShoppingProcessor->addDiscountItem($Order, $this->purchaseContext);
        $this->assertEquals(0, count($Order->getItems()));
    }

    public function testAddDiscountItem_Scenario1()
    {
        $Order = new Order();
        $Order->setTotal(1000);
        $Order->addFlashSaleDiscount(rand(), 1);
        $this->flashSaleShoppingProcessor->addDiscountItem($Order, $this->purchaseContext);
        $this->assertEquals(1, count($Order->getItems()->getDiscounts()));
        $DiscountItem = $Order->getItems()->getDiscounts()->current();
        $this->assertEquals(-1 * $Order->getFlashSaleTotalDiscount(), $DiscountItem->getPrice());
        $this->assertEquals(FSShoppingProcessor::class, $DiscountItem->getProcessorName());
    }
}

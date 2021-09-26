<?php
namespace Plugin\FlashSale\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Cart;
use Eccube\Tests\EccubeTestCase;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\FlashSale\Service\PurchaseFlow\Processor\FSOrderItemProcessor;


class FSOrderItemProcessorTest extends EccubeTestCase
{
    /**
     * @var FSOrderItemProcessor
     */
    protected $orderItemProcessor;

    protected $purchaseContext;

    public function setUp()
    {
        parent::setUp();
        $this->orderItemProcessor = new FSOrderItemProcessor();
        $this->purchaseContext = $this->getMockBuilder(PurchaseContext::class)->getMock();
    }

    public function testCommit()
    {
        $Order = new Order();
        $OrderItem = new OrderItem();
        $ProductType = $this->entityManager->find(OrderItemType::class, OrderItemType::PRODUCT);
        $OrderItem->setOrderItemType($ProductType);
        $OrderItem->setQuantity(rand(1, 5));
        $ProductClass = new ProductClass();
        $ProductClass->setPrice02IncTax(time());
        $ProductClass->addFlashSaleDiscount(rand(), floor($ProductClass->getPrice02IncTax()/$OrderItem->getQuantity()));
        $OrderItem->setProductClass($ProductClass);

        $Order->getOrderItems()->add($OrderItem);

        $this->orderItemProcessor->commit(new Cart(), $this->purchaseContext); // just make sure nothing happen
        $this->orderItemProcessor->commit($Order, $this->purchaseContext);

        $this->assertEquals($OrderItem->getFlashSaleTotalDiscountPrice(), $OrderItem->getFsPrice());
    }
}

<?php
namespace Plugin\FlashSale\Tests\Entity;

use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\ProductClass;
use Eccube\Tests\EccubeTestCase;

class FSOrderTraitTest extends EccubeTestCase
{
    public function testGetFsMergedProductOrderItems()
    {
        $ProductClass = new ProductClass();
        $OrderItem = new OrderItem();
        $OrderItem->setPropertiesFromArray(['id' => rand()]);
        $OrderItem->setOrderItemType($this->entityManager->find(OrderItemType::class, OrderItemType::PRODUCT));
        $OrderItem->setProductClass($ProductClass);
        $OrderItem->setFsPrice(floatval(rand()));
        $Order = new Order();
        $Order->getOrderItems()->add($OrderItem);

        $this->assertNotEquals($OrderItem->getId(), $Order->getFSMergedProductOrderItems()[0]->getId());
        $this->assertEquals($OrderItem->getFsPrice(), $Order->getFSMergedProductOrderItems()[0]->getFsPrice());
    }
}

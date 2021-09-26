<?php
/**
 * This file is part of FirstTimeOnly4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 *  https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FirstTimeOnly4\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Tests\EccubeTestCase;
use Plugin\FirstTimeOnly4\Entity\EnabledOrderStatus;
use Plugin\FirstTimeOnly4\Service\PurchaseFlow\Validator\FirstTimeOnlyValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Entity\CartItem;

/**
 * Description of PurchaseLimitValidatorTest
 *
 * @author Akira Kurozumi <info@a-zumi.net>
 */
class FirstTimeOnlyValidatorTest extends EccubeTestCase
{
    private $orderStatusRepository;

    private $validator;


    public function setUp()
    {
        parent::setUp();

        $this->orderStatusRepository = $this->entityManager->getRepository(OrderStatus::class);

        $orderRepository = $this->entityManager->getRepository(Order::class);
        $enabledOrderStatusRepository = $this->entityManager->getRepository(EnabledOrderStatus::class);
        $this->validator = new FirstTimeOnlyValidator(
            $orderRepository,
            $enabledOrderStatusRepository
        );
    }

    public function testInstance()
    {
        self::assertInstanceOf(FirstTimeOnlyValidator::class, $this->validator);
    }

    public function test初回購入限定商品を初めて購入するテスト()
    {
        $Customer = $this->createCustomer();
        $Product = $this->createProduct();
        $Product->getProductClasses()->first()->setFirstTimeOnly(true);

        $cartItem = new CartItem();
        $cartItem->setProductClass($Product->getProductClasses()->first());

        $result = $this->validator->execute($cartItem, new PurchaseContext(null, $Customer));
        self::assertTrue($result->isSuccess());
    }

    public function test初回購入限定商品を2回購入するテスト()
    {
        $Customer = $this->createCustomer();
        $Product = $this->createProduct();
        $Product->getProductClasses()->first()->setFirstTimeOnly(true);

        $Order = $this->createOrderWithProductClasses($Customer, [$Product->getProductClasses()->first()]);

        // 受注を新規受付に変更する
        if ($Order instanceof Order) {
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
            $Order->setOrderStatus($OrderStatus);
            $this->entityManager->persist($Order);
            $this->entityManager->flush();
        }

        $cartItem = new CartItem();
        $cartItem->setProductClass($Product->getProductClasses()->first());

        $result = $this->validator->execute($cartItem, new PurchaseContext(null, $Customer));
        self::assertTrue($result->isWarning());
    }

    public function test初回購入限定商品が購入処理中の受注しかない場合は購入可能()
    {
        $Customer = $this->createCustomer();
        $Product = $this->createProduct();
        $Product->getProductClasses()->first()->setFirstTimeOnly(true);

        $Order = $this->createOrderWithProductClasses($Customer, [$Product->getProductClasses()->first()]);

        $cartItem = new CartItem();
        $cartItem->setProductClass($Product->getProductClasses()->first());

        $result = $this->validator->execute($cartItem, new PurchaseContext(null, $Customer));
        self::assertTrue($result->isSuccess());
    }

}

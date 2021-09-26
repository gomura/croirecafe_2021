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

namespace Plugin\FirstTimeOnly4\Service\PurchaseFlow\Validator;

use Eccube\Annotation\CartFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Repository\OrderRepository;
use Eccube\Entity\Master\OrderStatus;
use Plugin\FirstTimeOnly4\Entity\EnabledOrderStatus;
use Plugin\FirstTimeOnly4\Repository\EnabledOrderStatusRepository;

/**
 * 商品を一度購入したら購入できないようにする
 *
 * @CartFlow
 * @ShoppingFlow()
 */
class FirstTimeOnlyValidator extends ItemValidator
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var EnabledOrderStatusRepository
     */
    private $enabledOrderStatusRepository;

    public function __construct(
        OrderRepository $orderRepository,
        EnabledOrderStatusRepository $enabledOrderStatusRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->enabledOrderStatusRepository = $enabledOrderStatusRepository;
    }

    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     *
     * @throws InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        if ($item->getProductClass()->isFirstTimeOnly()) {
            $enabledOrderStatuses = $this->enabledOrderStatusRepository->findBy([
                'enabled' => true
            ]);

            $qb = $this->orderRepository->createQueryBuilder("o");
            $qb
                ->select('COUNT(oi.Product)')
                ->leftJoin('o.OrderItems', 'oi')
                ->where("o.Customer = :Customer")
                ->andWhere("oi.ProductClass = :ProductClass")
                ->andWhere('o.OrderStatus IN (:OrderStatuses)');

            $PurchaseTimes = $qb
                ->getQuery()
                ->setParameters([
                    'Customer' => $context->getUser(),
                    'ProductClass' => $item->getProductClass(),
                    'OrderStatuses' => array_map(function (EnabledOrderStatus $enabledOrderStatus) {
                        return $enabledOrderStatus->getOrderStatus()->getId();
                    }, $enabledOrderStatuses)
                ])
                ->getSingleScalarResult();

            if ($PurchaseTimes > 0) {
                $this->throwInvalidItemException('plugin.first_time_only.front.product.add_cart_error', $item->getProductClass());
            }
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $item->setQuantity(0);
    }

}

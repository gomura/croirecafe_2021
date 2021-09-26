<?php


namespace Plugin\ProductOnlyCustomerB\Service\PurchaseFlow\Processor;

use Eccube\Annotation\CartFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\ItemValidator;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * 会員限定商品を購入可能かどうか
 **
 * @CartFlow
 * @ShoppingFlow
 */
class ProductOnlyCustomerBValidator extends ItemValidator
{


    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;


    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
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

        if ($item->isProduct()) {
            $Product = $item->getProductClass()->getProduct();
            if ($Product->getOnlyCustomer() && $this->authorizationChecker->isGranted('ROLE_USER') == false ) {
                $this->throwInvalidItemException('product_only_customer_b.front.shopping.not_purchase');
            }
        }

    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $item->setQuantity(0);
    }
}

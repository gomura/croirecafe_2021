<?php
namespace Plugin\FlashSale\Tests\Service\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Eccube\Entity\Order;
use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Repository\FlashSaleRepository;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Service\Operator\OperatorFactory;
use Plugin\FlashSale\Service\Event\CartRuleDoctrineEventSubscriber;
use Plugin\FlashSale\Tests\Entity\FlashSaleTest;

class CartRuleDoctrineEventSubscriberTest extends EccubeTestCase
{
    /**
     * @var CartRuleDoctrineEventSubscriber
     */
    protected $cartRuleDoctrineEventSubscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->cartRuleDoctrineEventSubscriber = new CartRuleDoctrineEventSubscriber($this->entityManager);
    }

    public function testGetSubscribedEvents()
    {
        $this->expected = [
            DoctrineEvents::postLoad,
            DoctrineEvents::preRemove,
        ];
        $this->actual = $this->cartRuleDoctrineEventSubscriber->getSubscribedEvents();
        $this->verify();
    }

    /**
     * @param $FlashSale
     * @param $Order
     * @param $expected
     * @dataProvider dataProvider_testPostLoad
     */
    public function testPostLoad($FlashSale, $Order, $expected)
    {
        $eventArgs = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgs->method('getEntity')->willReturn($Order);

        $flashSaleRepository = $this->getMockBuilder(FlashSaleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $flashSaleRepository->method('getAvailableFlashSale')->willReturn($FlashSale);
        
        $entityManager= $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->method('getRepository')->willReturn($flashSaleRepository);
        $this->cartRuleDoctrineEventSubscriber = new CartRuleDoctrineEventSubscriber($entityManager);

        if ($FlashSale) {
            /** @var FlashSale $FlashSale*/
            foreach ($FlashSale->getRules() as $Rule) {
                $Rule->setOperatorFactory($this->container->get(OperatorFactory::class));
                foreach ($Rule->getConditions() as $Condition) {
                    $Condition->setOperatorFactory($this->container->get(OperatorFactory::class));
                }
            }
        }

        $this->cartRuleDoctrineEventSubscriber->postLoad($eventArgs);
        $this->assertEquals($expected, $Order->getFlashSaleTotalDiscount());
    }

    public static function dataProvider_testPostLoad($testMethod = null, $orderSubtotal = 12345)
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

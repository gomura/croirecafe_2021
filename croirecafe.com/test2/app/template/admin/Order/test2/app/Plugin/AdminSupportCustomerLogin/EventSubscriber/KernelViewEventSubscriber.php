<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/25
 */

namespace Plugin\AdminSupportCustomerLogin\EventSubscriber;


use Eccube\Common\EccubeConfig;
use Plugin\AdminSupportCustomerLogin\Listener\AllTempListener;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelViewEventSubscriber implements EventSubscriberInterface
{

    /** @var EccubeConfig */
    protected $eccubeService;

    /** @var EventDispatcherInterface  */
    protected $eventDispatcher;

    public function __construct(EccubeConfig $eccubeConfig, EventDispatcherInterface $eventDispatcher)
    {
        $this->eccubeService = $eccubeConfig;

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {

        return [
            // KernelView
            // Sensio\Bundle\FrameworkExtraBundle\EventListener より先に動作
            KernelEvents::VIEW => ['onKernelView', 10],
        ];
    }

    /**
     * フロント側への追加
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {

        if(!$event->isMasterRequest())
        {
            return;
        }

        $request = $event->getRequest();

        $template = $request->attributes->get('_template');

        if(!$template instanceof Template) {
            return;
        }

        $path = $request->getPathInfo();
        $adminRoot = $this->eccubeService->get('eccube_admin_route');

        if (strpos($path, '/' . trim($adminRoot, '/')) === 0) {
            return;
        }

        $listener = new AllTempListener($request);
        $this->eventDispatcher->addListener($template->getTemplate(), [$listener, 'viewEvent']);
    }
}

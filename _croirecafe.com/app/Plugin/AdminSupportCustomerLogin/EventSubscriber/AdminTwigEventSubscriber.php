<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/23
 */

namespace Plugin\AdminSupportCustomerLogin\EventSubscriber;


use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Plugin\AdminSupportCustomerLogin\Listener\AllTempListener;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminTwigEventSubscriber implements EventSubscriberInterface
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {

        return [
            // 会員一覧
            '@admin/Customer/index.twig' => ['onTemplateAdminCustomerIndex', 10],
            // 会員編集
            '@admin/Customer/edit.twig' => ['onTemplateAdminCustomerEdit', 10],
        ];
    }

    /**
     * 会員一覧　テンプレート追加
     *
     * @param TemplateEvent $event
     */
    public function onTemplateAdminCustomerIndex(TemplateEvent $event)
    {
        // 代理ログインボタン追加
        $event->addSnippet('@AdminSupportCustomerLogin/admin/Customer/index_ex.twig');
    }

    public function onTemplateAdminCustomerEdit(TemplateEvent $event)
    {
        // 代理ログインボタン追加
        $event->addSnippet('@AdminSupportCustomerLogin/admin/Customer/edit_ex.twig');
    }

}

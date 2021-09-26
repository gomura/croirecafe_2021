<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/24
 */

namespace Plugin\AdminSupportCustomerLogin\Listener;


use Eccube\Event\TemplateEvent;
use Plugin\AdminSupportCustomerLogin\Controller\Customer\CustomerLoginController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class AllTempListener
{

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function viewEvent(TemplateEvent $event)
    {

        /** @var Session $session */
        $session = $this->request->getSession();

        if($session->has(CustomerLoginController::ADMIN_LOGIN_FLG_KEY)) {
            $event->addSnippet('@AdminSupportCustomerLogin/default/all_add.twig');
        }
    }
}

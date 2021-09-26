<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/22
 */
namespace Plugin\AdminSupportCustomerLogin\DependencyInjection\Factory;

use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Plugin\AdminSupportCustomerLogin\Controller\Customer\CustomerLoginController;
use Plugin\AdminSupportCustomerLogin\Security\Http\logout\CustomSessionLogoutHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;

class SecurityLogoutHandlerSessionFactory
{

    private $plugins;

    protected $cartService;

    protected $orderHelper;

    protected $requestStack;

    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        OrderHelper $orderHelper,
        RequestStack $requestStack
    ) {

        $this->plugins = $container->getParameter('eccube.plugins.disabled');
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->requestStack = $requestStack;
    }

    public function createSecurityLogoutHandlerSession()
    {

        $request = $this->requestStack->getCurrentRequest();

        $route = $request->attributes->get('_route');

        // 代理ログイン中でない場合はデフォルトのHandler使用
        if ($request->hasSession()) {

            if(!$request->getSession()->has(CustomerLoginController::ADMIN_LOGIN_FLG_KEY)
                || 'admin_logout' == $route) {
                return new SessionLogoutHandler();
            }
        }

        if (empty($this->plugins)) {
            // 無効なし
            return new CustomSessionLogoutHandler($this->cartService, $this->orderHelper);
        }

        $search_my_plugin = "Plugin\AdminSupportCustomerLogin";

        foreach ($this->plugins as $plugin) {

            $namespace = 'Plugin\\' . $plugin;

            if (false !== \strpos($search_my_plugin, $namespace)) {
                return new SessionLogoutHandler();
            }
        }

        return new CustomSessionLogoutHandler($this->cartService, $this->orderHelper);
    }
}

<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/17
 */

namespace Plugin\AdminSupportCustomerLogin\Security\Http\logout;

use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Plugin\AdminSupportCustomerLogin\Controller\Customer\CustomerLoginController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class CustomSessionLogoutHandler implements LogoutHandlerInterface
{

    /** @var CartService */
    protected $cartService;

    protected $orderHelper;

    public function __construct(
        CartService $cartService,
        OrderHelper $orderHelper
    ) {
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
    }

    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {

        /** @var Session $session */
        $session = $request->getSession();
        $is_admin = $session->has('_security_admin');

        if (!$is_admin) {
            // 管理者でログインしていない場合、完全クリア
            $session->invalidate();
        }

        if($session->has(CustomerLoginController::ADMIN_LOGIN_FLG_KEY)) {
            // 代理ログイン中
            $session = $request->getSession();
            // カートクリア
            $session->remove('cart_key');
            $session->remove('cart_keys');

            // 受注関連クリア
            $this->orderHelper->removeSession();

            // 管理画面顧客検索の復帰用に _csrf 復元
            $csrfValues = $session->get('csrf_back_values');

            foreach ($csrfValues as $key => $value) {
                $session->set($key, $value);
            }

            $session->remove('csrf_back_values');
        }

        // 代理ログインフラグ削除
        $session->remove(CustomerLoginController::ADMIN_LOGIN_FLG_KEY);
    }
}

<?php
/**
 * Copyright(c) 2018 SYSTEM_KD
 * Date: 2018/09/23
 */

namespace Plugin\AdminSupportCustomerLogin\Controller\Customer;


use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CustomerLoginController extends AbstractController
{

    public const ADMIN_LOGIN_FLG_KEY = 'admin_login_flg';

    public const ADMIN_LOGIN_ON = 1;

    protected $customerRepository;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(
        CustomerRepository $customerRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerRepository = $customerRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * 代理ログイン処理
     *
     * @Route("/customer/{id}/login", name="plg_admin_support_customer_login", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function login(Request $request, $id)
    {

        $session = $this->session;

        // 代理ログインフラグクリア
        $session->remove(self::ADMIN_LOGIN_FLG_KEY);

        // 管理ログインチェック
        $is_admin = $this->session->has('_security_admin');

        if (!$is_admin) {
            // 管理者でログインしていない場合、homepageへ
            return $this->redirectToRoute('homepage');
        }

        // 顧客情報取得
        /** @var Customer $Customer */
        $Customer = $this->customerRepository->find($id);

        // 対象顧客なし or 本会員以外(仮・退会)
        if(is_null($Customer)
            || $Customer->getStatus()->getId() != CustomerStatus::REGULAR) {

            // エラーメッセージ出力
            $message = trans('admin_support_customer_login.login_error');
            $this->addError($message, 'admin');

            // ログアウト状態にする
            $this->tokenStorage->setToken(null);

            // 現在のページへ
            $page_no = $session->get('eccube.admin.customer.search.page_no', 1);
            $url = $this->generateUrl('admin_customer_page', ['page_no' => $page_no]);

            return $this->redirect($url . "?resume=1");
        }

        // 対象顧客でログイン
        $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);
        $this->session->migrate(true);

        // セッションにフラグセット
        $this->session->set(self::ADMIN_LOGIN_FLG_KEY, self::ADMIN_LOGIN_ON);

        $backValues = [];

        foreach (array_keys($this->session->all()) as $key) {

            if(0 === strpos($key, '_csrf/')) {
                $backValues[$key] = $this->session->get($key);
            }
        }

        $this->session->set('csrf_back_values', $backValues);

        return $this->redirectToRoute('homepage');
    }
}

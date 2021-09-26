<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegration;
use Plugin\ECCUBE4LineLoginIntegration\Controller\Admin\LineLoginIntegrationAdminController;
use Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationSettingRepository;
use Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationRepository;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Routing\Annotation\Route;

class LineLoginIntegrationController extends AbstractController
{

    private $lineChannelId;
    private $lineChannelSecret;
    private $lineIntegrationSettingRepository;
    private $lineIntegrationRepository;
    private $customerRepository;
    private $tokenStorage;

    const PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID = 'plugin.line_login_integration.sso.userid';
    const PLUGIN_LINE_LOGIN_INTEGRATION_SSO_STATE = 'plugin.line_login_integration.sso.state';

    public function __construct(
        LineLoginIntegrationSettingRepository $lineIntegrationSettingRepository,
        LineLoginIntegrationRepository $lineIntegrationRepository,
        CustomerRepository $customerRepository,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $lineIntegrationSetting = $this->getLineLoginIntegrationSetting();
        $this->lineChannelId = $lineIntegrationSetting->getLineChannelId();
        $this->lineChannelSecret = $lineIntegrationSetting->getLineChannelSecret();
        $this->customerRepository = $customerRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * ログイン画面の表示
     *
     * @Route("/plugin_line_login", name="plugin_line_login")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function login(Request $request)
    {
        $url = $this->generateUrl('plugin_line_login_callback',array(),0);
        $state = uniqid();
        $session = $request->getSession();
        $session->set(self::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_STATE, $state);

        $previousUrl = parse_url(
            $request->headers->get('referer'),PHP_URL_PATH);
        $session->set('$previousUrl' ,$previousUrl);

        // TODO bot_prompt
        // bot_prompt=normal or aggressive
        // https://developers.line.me/ja/docs/line-login/web/link-a-bot/
        $lineAuthUrl = 'https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=' . $this->lineChannelId . '&redirect_uri=' . rawurlencode($url) . '&state=' . $state . '&scope=profile&bot_prompt=aggressive';

        return $this->redirect($lineAuthUrl);
    }

    /**
     * ログインのコールバック処理
     *
     * @Route("/plugin_line_login_callback", name="plugin_line_login_callback")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $session = $request->getSession();

        $originalState = $session->get(self::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_STATE);
        $session->remove(self::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_STATE);

        // APIアクセスの為のパラメータ検証
        $this->validateParameter($code, $state, $originalState);

        // アクセストークン発行
        $tokenJson = $this->publishAccessToken($code);

        // LineId取得
        $lineUserId = $this->getLineId($tokenJson['access_token']);
        $session->set(self::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID, $lineUserId);
        $this->setSession($session);

        // LINE連携レコードを取得
        $lineIntegration = $this->lineIntegrationRepository->
            findOneBy(['line_user_id' => $lineUserId]);

        // LINE連携レコードの顧客IDを取得
        $lineIntegration['customer_id'] ?
            $customerId = $lineIntegration['customer_id'] :
            $customerId = null;

        // 顧客レコードから顧客取得
        $this->customerRepository->findOneBy(['id' => $customerId]) ?
            $customer =
                $this->customerRepository->findOneBy(['id' => $customerId]) :
            $customer = null;

        // LINE連携レコードがあり、LINE連携レコードに紐づく顧客レコードが見つからない場合、LINE連携レコード削除
        if (!is_null($lineIntegration)) {
            // DB上にLINE IDの登録はあるが、Customerオブジェクトが未発見の場合、LINE IDの削除
            if (is_null($customer)) {
                log_info('削除されたユーザ(customer_id:' . $customerId . ')とのLINE IDのレコードを削除します');
                $this->lineIntegrationRepository->deleteLineAssociation($lineIntegration);

                // DB上にLINE IDの登録はあるが、Customerが退会済み扱いのときも、LINE IDを削除する
            } else if ($customer->getStatus()['id'] == CustomerStatus::WITHDRAWING) {
                log_info('退会しているユーザ(customer_id:' . $customerId . ')とのLINE IDのレコードを削除します');
                $this->lineIntegrationRepository->deleteLineAssociation($lineIntegration);
                $customer = null; // 会員を存在しなかった扱いにすることで、新規登録フローに流す
            }
            // 削除後はそのままスルーし、普通のフローに
        }

        // EC-CUBEにログインしているとき（会員情報編集からの遷移）、LINE連携レコードと紐付け
        if ($this->isGranted('ROLE_USER')) {
            log_info('LINEコールバック:ログイン済み。');

            //  LINE連携レコードに紐づく、顧客が存在しない場合
            if (is_null($customer)) {
                $this->associationCustomerAndLineid($lineUserId);

            } else {
                // 既にDBにLINE IDと紐づけられている顧客ID
                $registeredCustomerId = $customer->getId();
                // 新たにLINE IDと紐付けようと申請する顧客ID
                $nowLoggedInCustomerId = $this->getUser()->getId();

                if($nowLoggedInCustomerId != $registeredCustomerId) {
                    log_info('すでに連携済みのLINE IDを別のアカウントの連携に使おうとしました。 $lineUserId:'.$lineUserId);
                    return $this->render('error.twig', [
                        'error_title'   => '重複したLINE IDです',
                        'error_message' => "既に別のアカウントで、同じLINE IDが登録されています。",
                    ]);
                }
            }
            return $this->redirectToRoute('mypage_change');
        }
        // EC-CUBEに未ログインであるとき
        else {
            log_info('LINEコールバック: 未ログイン');

            // LINE連携レコードがなかったら、会員登録へ
            if (is_null($lineIntegration)) {
                log_info('LINE連携レコードなし');

                return $this->redirectToRoute('entry');
            }

            // LINE連携レコードがあっても、顧客レコードがない場合は会員登録へ
            if (is_null($customer)) {
                log_info('顧客レコードが取得できなかった為、会員登録へ');

                return $this->redirectToRoute('entry');
            }

            // 仮会員の場合ログインへ
            if ($customer->getStatus()->getId() == 1) {
                log_info('仮会員のため、ログインへ customer_id:'.$customerId);

                if ($session->get('$previousUrl') == '/shopping/login') {
                    return $this->redirectToRoute('shopping_login');
                }

                return $this->redirectToRoute('mypage_login');
            }

            // 本会員かつ、LINE連携レコード・顧客レコードが存在するのでログイン処理
            if ($customer->getStatus()->getId() == 2) {
                $token = new UsernamePasswordToken($customer, null, 'customer',
                    array('ROLE_USER'));
                $this->tokenStorage->setToken($token);
                log_info('ログイン済に変更。dtb_customer.id:'.$this->getUser()->getId());

                // カートのマージなどの処理
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->eventDispatcher->dispatch(
                    SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);

                // 遷移元がカート経由のログインだった場合、購入画面。そうでない場合マイページに遷移
                if ($session->get('$previousUrl') == '/shopping/login') {
                    return $this->redirectToRoute('shopping');
                }

                return $this->redirectToRoute('mypage');
            }

            // 例外としてログインページに戻す
            return $this->redirectToRoute('login');
        }
    }

    /**
     * 設定レコードを取得します
     * @return string
     */
    private function getLineLoginIntegrationSetting()
    {
        $lineIntegrationSetting = $this->lineIntegrationSettingRepository
            ->find(LineLoginIntegrationAdminController::LINE_LOGIN_INTEGRATION_SETTING_TABLE_ID);

        return $lineIntegrationSetting;
    }


    /**
     * LINE APIからアクセストークンを取得する為の、パラメータを検証します
     * @param $code
     * @param $state
     * @param $originalState
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function validateParameter($code, $state, $originalState){
        if (empty($code)) {
            log_error('LINE API エラー(0)');
            return $this->redirectToRoute('ECCUBE4LineLoginIntegration/Resource/template/admin/error.twig');
        }
        if (empty($state)) {
            log_error('LINE API エラー(1)');
            return $this->redirectToRoute('ECCUBE4LineLoginIntegration/Resource/template/admin/error.twig');
        }
        if (empty($originalState)) {
            log_error('LINE API エラー(2)');
            return $this->redirectToRoute('ECCUBE4LineLoginIntegration/Resource/template/admin/error.twig');
        }
        if ($state != $originalState) {
            log_error('LINE API エラー(3)');
            return $this->redirectToRoute('ECCUBE4LineLoginIntegration/Resource/template/admin/error.twig');
        }
    }

    /**
     * LINE APIでアクセストークンを発行します
     * @param $code
     *
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function publishAccessToken($code){
        $url = $this->generateUrl('plugin_line_login_callback',array(),0);
        $accessTokenUrl = "https://api.line.me/oauth2/v2.1/token";
        $accessTokenData = array(
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $url,
            "client_id" => $this->lineChannelId,
            "client_secret" => $this->lineChannelSecret,
        );
        $accessTokenData = http_build_query($accessTokenData, "", "&");
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($accessTokenData)
        );
        $context = array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $accessTokenData
            )
        );

        $response = file_get_contents($accessTokenUrl, false, stream_context_create($context));
        $tokenJson = json_decode($response, true);

        if (isset($token['error'])) {
            log_error('LINE API エラー(4)' . $tokenJson['error'] . ' ' . $tokenJson['error_description']);
            return $this->redirectToRoute('ECCUBE4LineLoginIntegration/Resource/template/admin/error.twig');
        }

        if (!array_key_exists("access_token", $tokenJson)) {
            log_error('LINE API エラー(5)');
        }

        return $tokenJson;
    }

    /**
     * LINE APIからLINE IDを取得します
     * @param $accessToken
     *
     * @return mixed
     */
    private function getLineId($accessToken){
        $lineProfileUrl = "https://api.line.me/v2/profile";
        $context = array(
            "http" => array(
                "method" => "GET",
                "header" => "Authorization: Bearer " . $accessToken
            )
        );

        $response = file_get_contents($lineProfileUrl, false, stream_context_create($context));
        $profileJson = json_decode($response, true);

        if (!array_key_exists("userId", $profileJson)) {
            log_error('LINE API エラー(6)');
        }

        if (empty($profileJson['userId'])) {
            log_error('LINE API エラー(7)');
        }

        return $profileJson['userId'];
    }

    /**
     * 顧客とLINE連携レコードの紐付けを行います
     * @param $customer
     * @param $lineUserId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function associationCustomerAndLineid($lineUserId){
        log_info('plg_line_login_integrationレコードなし');
        $lineIntegration = new LineLoginIntegration();
        $lineIntegration->setLineUserId($lineUserId);
        $lineIntegration->setCustomerId($this->getUser()->getId());
        $this->entityManager->persist($lineIntegration);
        $this->entityManager->flush();
        log_info('LINE IDとユーザーの関連付け終了');
    }
}

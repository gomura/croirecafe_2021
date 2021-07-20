<?php

namespace Plugin\ECCUBE4LineLoginIntegration;

use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EccubeEvents;
use Eccube\Entity\Master\CustomerStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\ECCUBE4LineLoginIntegration\Controller\LineLoginIntegrationController;
use Plugin\ECCUBE4LineLoginIntegration\Controller\Admin\LineLoginIntegrationAdminController;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegration;
use Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationRepository;
use Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationSettingRepository;
use Twig_Environment;

class LineLoginIntegrationEvent implements EventSubscriberInterface
{
    private $lineLoginIntegrationRepository;
    private $lineLoginIntegrationSettingRepository;
    private $lineLoginIntegration;
    private $container;
    private $router;
    private $session;
    private $entityManager;
    private $formFactory;
    private $twig;

    public function __construct(
        LineLoginIntegrationRepository $lineLoginIntegrationRepository,
        LineLoginIntegrationSettingRepository $lineLoginIntegrationSettingRepository,
        LineLoginIntegration $lineLoginIntegration,
        ContainerInterface $container,
        Twig_Environment $twig
    ) {
        $this->lineLoginIntegrationRepository = $lineLoginIntegrationRepository;
        $this->lineLoginIntegrationSettingRepository = $lineLoginIntegrationSettingRepository;
        $this->lineLoginIntegration = $lineLoginIntegration;
        $this->container = $container;
        $this->router = $this->container->get('router');
        $this->session = $this->container->get('session');
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $this->formFactory = $this->container->get('form.factory');
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Entry/index.twig' => [
                ['onRenderEntryIndex', 10],
                ['onRenderLineEntryButton', -10]
            ],
            'Entry/confirm.twig' => 'onRenderEntryConfirm',
            EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE => 'onCompleteEntry',
            'Mypage/login.twig' => 'onRenderLineLoginButton',
            'Mypage/change.twig' => 'onRenderMypageChange',
            'Shopping/login.twig' => 'onRenderShoppingLineLoginButton',
            EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE => 'onCompleteMypageChange',
            EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE => 'onCompleteMypageWithdraw',
            EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE => 'onCompleteCustomerEdit',
        ];
    }


    /**
     * 新規会員登録画面の表示
     * @param TemplateEvent $event
     */
    public function onRenderEntryIndex(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }
    }

    /**
     * 新規会員登録画面にLINEボタンを出力します
     *
     * @param TemplateEvent $event
     */
    public function onRenderLineEntryButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $lineUserId = $this->session->get(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID);

        $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage", array(),
                UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_login_integration/assets/img/btn_register_base.png';

        $snipet = '';
        // LINEボタンを表示
        if (empty($lineUserId)) {
            $snipet .= '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEで登録"></a></div>' . PHP_EOL;
            $snipet .= PHP_EOL;
        }
        // LINEにログイン済みなので登録を促す
        else {
            $snipet .= '<div class="col" style="margin-top:-10px; padding:10px;">LINEログイン済みです。この会員登録が完了すると、LINEでログインできるようになります。</div>';
            $snipet .= PHP_EOL;
        }

        $search = '<div class="ec-off1Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員登録処理完了時のLINE連携処理
     * @param EventArgs $event
     */
    public function onCompleteEntry(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        // 顧客とLINEユーザーIDをひも付け（line_login_integrationテーブルのレコードを作成）
        log_info('LINEユーザーとの関連付け開始');

        $lineUserId = $this->session->get(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID);
        if (!empty($lineUserId)) {
            log_info('LINEログインしているため、ユーザーとの関連付けを実行');

            $this->lineLoginIntegration = $this->lineLoginIntegrationRepository->findOneBy(['line_user_id' => $lineUserId]);

            if (empty($this->lineLoginIntegration)) {
                $customer = $event['Customer'];
                log_info('LINE IDとユーザーの関連付けを開始', [$customer['id']]);
                $lineLoginIntegration = new LineLoginIntegration();
                $lineLoginIntegration->setLineUserId($lineUserId);
                $lineLoginIntegration->setCustomer($customer);
                $lineLoginIntegration->setCustomerId($customer['id']);
                $this->entityManager->persist($lineLoginIntegration);
                $this->entityManager->flush($lineLoginIntegration);
                log_info('LINEユーザーとの関連付け終了');
            }

            log_info('LINEユーザーとの関連付け終了');
        } else {
            log_info('LINE未ログインのため関連付け未実施');
        }
    }

    /**
     * ログイン画面にLINEボタンを出力します
     * @param TemplateEvent $event
     */
    public function onRenderLineLoginButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage", array(),
                UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_login_integration/assets/img/btn_login_base.png';
        $snipet = '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEログイン"></a></div><br>' . PHP_EOL;
        $search = '<div class="ec-off2Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * カート経由のログイン画面にLINEボタンを出力します
     * @param TemplateEvent $event
     */
    public function onRenderShoppingLineLoginButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage", array(),
                UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_login_integration/assets/img/btn_login_base.png';
        $snipet = '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEログイン"></a></div><br>' . PHP_EOL;
        $search = '<div class="ec-grid3__cell2">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員情報変更画面の表示
     * @param TemplateEvent $event
     */
    public function onRenderMypageChange(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $form = $event->getParameter('form');
        $customerId = $form->vars['value']['id'];
        if (empty($customerId)) {
            error_log("会員IDを取得できませんでした", [$form]);
            return;
        }

        $lineLoginIntegration = $this->lineLoginIntegrationRepository
            ->findOneBy(['customer_id' => $customerId]);
        $lineIdBySession = $this->session
            ->get(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID);

        // LINEとの紐づけがないとき
        if (empty($lineLoginIntegration)) {
            // LINEのログインボタン表示
            $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
            $imgUrl = $this->router->generate("homepage", array(),
                    UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_login_integration/assets/img/btn_register_base.png';
            $snipet = '<div class="btn"><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEで登録"></a></div>' . PHP_EOL;
            $snipet .= PHP_EOL;
            $snipet .= '<div class="col" style="padding-bottom:10px;">「LINEで登録」ボタンを押してLINEにログインすると、LINEアカウントでログインできるようになります。</div>';
            $snipet .= PHP_EOL;
        }
        // LINEとの紐づけがあっても、現在LINEにログインしていないっぽいとき
        else if (empty($lineIdBySession)) {
            // LINEのログインボタン表示
            $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
            $imgUrl = $this->router->generate("homepage", array(),
                    UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_login_integration/assets/img/btn_login_base.png';
            $snipet = '<div class="btn"><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEで登録"></a></div>' . PHP_EOL;
            $snipet .= PHP_EOL;
            $snipet .= '<div class="col" style="padding-bottom:10px;">LINEアカウントと連携済みですが、現在LINEでログインしていません。</div>';
            $snipet .= PHP_EOL;
        }
        // LINEとの紐づけがあって、かつLINEにログイン中のとき
        else {
            // 連携解除項目を追加
            $this->replaceMypageChangeForm($event);
            $snipet = '<div class="col" style="padding-bottom:10px;">LINEアカウント連携済です。解除したいときは「LINE連携 解除」をチェックして「登録する」ボタンを押してください。</div>';
            $snipet .= PHP_EOL;
        }

        $search = '<div class="ec-off1Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員情報編集完了時のイベント処理を行います
     *
     * @param EventArgs $event
     */
    public function onCompleteMypageChange(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $customerId = $event['Customer']->getId();
        $lineLoginIntegration = $this->lineLoginIntegrationRepository->findOneBy(['customer_id' => $customerId]);

        // LINEの紐づけがすでにあるとき
        if (!empty($lineLoginIntegration)) {
            $form = $event['form'];
            // LINE情報を削除する
            if ($form->has('is_line_delete')) {
                $is_line_delete = $form->get('is_line_delete')->getData();
            }
            if ($is_line_delete == 1) {
                // 連携解除
                $this->lineIdUnassociate($customerId, true);
            }
        }
        // LINEの紐づけがないとき
        else {
            // 何もしない
            // LINEとの紐づけ処理はログインのコールバック関数(LineLoginIntegrationController.php)内で行われるのでここでは行わない
        }
    }

    /**
     * 会員がマイページから退会手続きを行ったとき
     *
     * 退会した会員のLINE連携を解除する
     *
     * @param EventArgs $event
     */
    public function onCompleteMypageWithdraw(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        log_info('マイページから退会');
        $customerId = $event['Customer']['id'];
        $this->lineIdUnassociate($customerId, true);
    }

    /**
     * 管理画面から顧客情報を更新したとき
     *
     * 会員を退会にした場合にはLINE連携を解除する
     *
     * @param EventArgs $event
     */
    public function onCompleteCustomerEdit(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $customerId = $event['Customer']->getId();
        $customerStatus = $event['Customer']->getStatus();
        // 退会扱いのとき
        if ($customerStatus['id'] == CustomerStatus::WITHDRAWING) {
            log_info('仮画面の会員情報編集ページから退会扱い');
            $this->lineIdUnassociate($customerId);
        }
    }


    /**
     * LINE設定が初期化済みかチェックする
     */
    private function isLineSettingCompleted()
    {
        $lineLoginIntegrationSetting = $this->lineLoginIntegrationSettingRepository
            ->find(LineLoginIntegrationAdminController::LINE_LOGIN_INTEGRATION_SETTING_TABLE_ID);

        if (empty($lineLoginIntegrationSetting)) {
            log_error("Line Lineの情報が未設定です");
            return false;
        }

        $lineChannelId = $lineLoginIntegrationSetting->getLineChannelId();
        if (empty($lineChannelId)) {
            log_error("Line Channel Idが未設定です");
            return false;
        }

        $lineChannelSecret = $lineLoginIntegrationSetting->getLineChannelSecret();
        if (empty($lineChannelSecret)) {
            log_error("Line Channel Secretが未設定です");
            return false;
        }

        return true;
    }


    /**
     * LINEアカウントとの連携を解除する処理
     *
     * 会員IDから連携DBを検索し、該当するレコードを削除する処理。管理画面でなくフロントからのフローでは、
     * セッションを削除するのでフラグをtrueにしておく
     *
     * @param int $customerId       LINEとの連携を解除したい会員ID
     * @param bool $isDeleteSession セッションまで削除する。デフォでfalse
     * @return bool                 会員がLINEと紐づけされていて、紐づけを解除したときにtrueを返す
     */
    private function lineIdUnassociate(int $customerId, ?bool $isDeleteSession = null) {
        $lineLoginIntegration = $this->lineLoginIntegrationRepository->findOneBy(['customer_id' => $customerId]);
        // LINE情報を削除する
        if (!empty($lineLoginIntegration)) {
            log_info('customer_id:' . $customerId . 'のLINE連携を解除');
            $this->lineLoginIntegrationRepository->deleteLineAssociation($lineLoginIntegration);
            log_info('LINEの連携を解除しました');

            if ($isDeleteSession) {
                $this->session->remove(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_STATE);
                $this->session->remove(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID);
                $this->session->remove($this->session->is_line_delete);
            }
            return true;
        }
        return false;
    }


    private function replaceMypageChangeForm(TemplateEvent $event)
    {
        log_info('LINE連携削除を追加');
        $snipet = $this->twig->getLoader()->getSourceContext('ECCUBE4LineLoginIntegration/Resource/template/mypage_change_add_is_line_delete.twig')->getCode();
        $search = '{# エンティティ拡張の自動出力 #}';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }
}

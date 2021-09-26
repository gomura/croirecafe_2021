<?php

/*
 * RepeatCube for EC-CUBE4
 * Copyright(c) 2019 IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * This program is not free software.
 * It applies to terms of service.
 *
 */
namespace Plugin\IplPeriodicPurchase;

use Eccube\Common\EccubeConfig;
use Eccube\Common\Constant;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\SaleType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;
use Plugin\IplPeriodicPurchase\Entity\Config;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatusColor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {

    }

    public function install(array $config, ContainerInterface $container)
    {
        // TODO : GmailのSMTPサーバを使う場合送信がうまくいかないので、後ほど動作確認する
        // メール送信
        $this->sendAutoMail($container, 'インストール');
    }

    public function enable(array $config, ContainerInterface $container)
    {
        $eccubeConfig = $container->get(EccubeConfig::class);
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // マスターデータ追加
        $this->addMasterOfPeriodicSaleType($eccubeConfig, $entityManager);
        $this->addMasterOfPeriodicStatus($entityManager);
        $this->addMasterOfPeriodicStatusColor($entityManager);

        // ページ追加
        $this->addPage($container, $entityManager);

        // メールテンプレート追加
        $this->addPreInformationMailTemplate($container, $entityManager);
    }

    public function uninstall(array $config, ContainerInterface $container)
    {
        // メール送信
        $this->sendAutoMail($container, '削除');
    }

    /**
     * マスターデータ登録
     */
    private function addMasterOfPeriodicSaleType($eccubeConfig, $entityManager)
    {
        $periodic_sale_type = $eccubeConfig['SALE_TYPE_ID_PERIODIC'];

        $SaleType = $entityManager->find(SaleType::class, $periodic_sale_type);
        if ($SaleType) {
            return;
        }

        $SaleType = new SaleType();

        $SaleType->setId($periodic_sale_type);
        $SaleType->setName('定期商品');
        // TODO : sort_noのMAXどう取る
        $SaleType->setSortNo(3);

        $entityManager->persist($SaleType);
        $entityManager->flush($SaleType);
    }

    /**
     * マスターデータ登録
     */
    private function addMasterOfPeriodicStatus($entityManager)
    {
        $periodicStatuses = [
            1 => '継続',
            2 => '休止',
            3 => '解約',
            4 => '解約（再開期限切れ）',
            5 => '決済エラー',
            6 => 'システムエラー',
            7 => '再決済待ち',
        ];

        $i = 0;
        foreach($periodicStatuses as $id => $name) {
            $PeriodicStatus = $entityManager->find(PeriodicStatus::class, $id);
            if ($PeriodicStatus) {
                continue;
            }

            $PeriodicStatus = new PeriodicStatus();

            $PeriodicStatus->setId($id);
            $PeriodicStatus->setName($name);
            $PeriodicStatus->setSortNo($i++);
            $PeriodicStatus->setDisplayOrderCount(true);

            $entityManager->persist($PeriodicStatus);
            $entityManager->flush($PeriodicStatus);
        }
    }

    /**
     * マスターデータ登録
     */
    private function addMasterOfPeriodicStatusColor($entityManager)
    {
        $periodicStatusColors = [
            1 => '#437EC4',
            2 => '#A3A3A3',
            3 => '#C9C9C9',
            4 => '#C9C9C9',
            5 => '#C04949',
            6 => '#C04949',
            7 => '#EEB128',
        ];

        $i = 0;
        foreach ($periodicStatusColors as $id => $name) {
            $PeriodicStatusColor = $entityManager->find(PeriodicStatusColor::class, $id);
            if ($PeriodicStatusColor) {
                continue;
            }

            $PeriodicStatusColor = new PeriodicStatusColor();

            $PeriodicStatusColor->setId($id);
            $PeriodicStatusColor->setName($name);
            $PeriodicStatusColor->setSortNo($i++);

            $entityManager->persist($PeriodicStatusColor);
            $entityManager->flush($PeriodicStatusColor);
        }
    }

    // TODO : 動作確認
    private function addPage(ContainerInterface $container, $entityManager)
    {
        // ページ追加
        $pageRepository = $container->get(PageRepository::class);

        $layoutRepository = $container->get(LayoutRepository::class);
        // IDを直接指定(2:下層ページ用レイアウト)
        $Layout = $layoutRepository->find(2);

        $pageLayoutRepository = $container->get(PageLayoutRepository::class);
        $LastPageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $LastPageLayout->getSortNo();

        $arrPage = [
                [
                    'page_name' => '定期MYページ/定期一覧',
                    'url' => 'ipl_periodic_purchase_index',
                    'file_name' => '@IplPeriodicPurchase/mypage/index'
                ],
                [
                    'page_name' => '定期MYページ/定期詳細',
                    'url' => 'ipl_periodic_purchase_history',
                    'file_name' => '@IplPeriodicPurchase/mypage/history'
                ],
                [
                    'page_name' => '定期MYページ/お届け頻度変更',
                    'url' => 'ipl_periodic_purchase_cycle',
                    'file_name' => '@IplPeriodicPurchase/mypage/cycle'
                ],
                [
                    'page_name' => '定期MYページ/次回お届け予定日変更',
                    'url' => 'ipl_periodic_purchase_next_shipping',
                    'file_name' => '@IplPeriodicPurchase/mypage/next_shipping'
                ],
                [
                    'page_name' => '定期MYページ/お届け先変更',
                    'url' => 'ipl_periodic_purchase_shipping',
                    'file_name' => '@IplPeriodicPurchase/mypage/shipping'
                ],
                [
                    'page_name' => '定期MYページ/お届け商品数変更',
                    'url' => 'ipl_periodic_purchase_products',
                    'file_name' => '@IplPeriodicPurchase/mypage/products'
                ],
                [
                    'page_name' => '定期MYページ/スキップ',
                    'url' => 'ipl_periodic_purchase_skip',
                    'file_name' => '@IplPeriodicPurchase/mypage/skip'
                ],
                [
                    'page_name' => '定期MYページ/休止',
                    'url' => 'ipl_periodic_purchase_suspend',
                    'file_name' => '@IplPeriodicPurchase/mypage/suspend'
                ],
                [
                    'page_name' => '定期MYページ/再開',
                    'url' => 'ipl_periodic_purchase_resume',
                    'file_name' => '@IplPeriodicPurchase/mypage/resume'
                ],
                [
                    'page_name' => '定期MYページ/解約',
                    'url' => 'ipl_periodic_purchase_cancel',
                    'file_name' => '@IplPeriodicPurchase/mypage/cancel'
                ],
                [
                    'page_name' => '定期MYページ/完了ページ',
                    'url' => 'ipl_periodic_purchase_complete',
                    'file_name' => '@IplPeriodicPurchase/mypage/complete'
                ],
                // 定期商品の非会員購入エラー用
                [
                    'page_name' => '定期商品購入/購入エラー',
                    'url' => 'cart_buystep',
                    'file_name' => 'Shopping/shopping_error'
                ],
        ];

        foreach ($arrPage as $p) {
            $Page = $pageRepository->findOneBy(['url' => $p['url']]);
            if ($Page) {
                continue;
            }

            $Page = new Page();
            $Page->setName($p['page_name']);
            $Page->setUrl($p['url']);
            $Page->setFileName($p['file_name']);
            $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
            $Page->setCreateDate(new \DateTime());
            $Page->setUpdateDate(new \DateTime());
            $Page->setMetaRobots('noindex');

            $entityManager->persist($Page);
            $entityManager->flush($Page);

            $PageLayout = new PageLayout();
            $PageLayout->setPage($Page);
            $PageLayout->setPageId($Page->getId());
            $PageLayout->setLayout($Layout);
            $PageLayout->setLayoutId($Layout->getId());
            $PageLayout->setSortNo($sortNo++);

            $entityManager->persist($PageLayout);
            $entityManager->flush($PageLayout);
        }
    }

    private function addPreInformationMailTemplate($container, $entityManager)
    {
        $mailTemplateRepository = $container->get(MailTemplateRepository::class);
        $Mail = $mailTemplateRepository->findOneBy(['name' => '定期購入事前お知らせメール']);

        if ($Mail) {
            return;
        }

        $Mail = new MailTemplate();
        $Mail->setName('定期購入事前お知らせメール');
        $Mail->setFileName('@IplPeriodicPurchase/mail/pre_information.twig');
        $Mail->setMailSubject('定期購入事前お知らせメール');

        $entityManager->persist($Mail);
        $entityManager->flush($Mail);
    }

    /**
     * 自動メール送信 ※インストール、アンイストール、アップデート時
     *
     * @param string $process
     */
    public function sendAutoMail($container, $process)
    {
        $baseInfoRepository = $container->get(BaseInfoRepository::class);
        $BaseInfo = $baseInfoRepository->get();

        $mailService = $container->get(MailService::class);

        $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
        $url = substr($url, 0, strrpos($url, 'store') - 1);
        $url = substr($url, 0, strrpos($url, '/') + 1);

        $datetime = date('Y-m-d H:i:s');

        // EC-CUBEのバージョン取得
        $version = Constant::VERSION;

        $body = <<<__EOS__
リピートキューブ プラグインサポート各位

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
■　プラグイン{$process}のお知らせ　■
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

以下のECサイトでリピートキューブ プラグインが{$process}されました。

【店名】{$BaseInfo->getShopName()}
【EC-CUBE】{$version}
【URL】{$url}
【メールアドレス】{$BaseInfo->getEmail01()}
【処理日時】{$datetime}


※本メールは、配信専用です。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
リピートキューブ　プラグインサポート
URL：https://www.iplogic.co.jp/lp/periodicpurchase.html
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
__EOS__;

        $message = new \Swift_Message();
        $message
            ->setSubject('[' . $BaseInfo->getShopName() . '] ' . 'プラグイン' . $process . '処理のお知らせ【リピートキューブ】')
            ->setFrom(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setTo(array('periodic@iplogic.co.jp' => 'periodic'))
            ->setBody($body);

        $transport = $container->get('swiftmailer.mailer.default.transport.real');
        $mailer = new \Swift_Mailer($transport);

        $mailer->send($message);
    }
}

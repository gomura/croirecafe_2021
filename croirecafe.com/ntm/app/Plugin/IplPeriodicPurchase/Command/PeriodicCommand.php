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

namespace Plugin\IplPeriodicPurchase\Command;

use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\MailTemplateRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Plugin\IplPeriodicPurchase\Service\PeriodicBatchHelper;

class PeriodicCommand extends Command
{
    protected static $defaultName = 'eccube:periodic:batch';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    // 実行日
    protected $run_date;

    // 実行日＋締め日
    protected $target_date;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        \Twig_Environment $twig,
        ConfigRepository $configRepository,
        BaseInfoRepository $baseInfoRepository,
        OrderRepository $orderRepository,
        MailTemplateRepository $mailTemplateRepository,
        MailService $mailService,
        PurchaseFlow $shoppingPurchaseFlow,
        OrderNoProcessor $orderNoProcessor,
        PeriodicPurchaseRepository $periodicPurchaseRepository,
        PeriodicHelper $periodicHelper,
        PeriodicBatchHelper $periodicBatchHelper,
        PeriodicStatusRepository $periodicStatusRepository
    ) {
        parent::__construct();

        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->twig = $twig;
        $this->Config = $configRepository->get();
        $this->BaseInfo = $baseInfoRepository->get();
        $this->orderRepository = $orderRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->orderNoProcessor = $orderNoProcessor;
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
        $this->periodicHelper = $periodicHelper;
        $this->periodicBatchHelper = $periodicBatchHelper;
        $this->periodicStatusRepository = $periodicStatusRepository;
    }

    protected function configure()
    {
        $this->addArgument('run_date', InputArgument::OPTIONAL, 'in running date');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // 実行日を設定
        if ($inputed_run_date = $input->getArgument('run_date')) {
            $d = \DateTime::createFromFormat('!Ymd', $inputed_run_date);

            if (!($d && $d->format('Ymd') == $inputed_run_date)) {
                $output->writeln("<error>日付形式(YYYYmmdd)が誤っています。</error>");
                // 終了コード 1
                return 1;
            }

            $this->run_date = $d;
        } else {
            $this->run_date = new \DateTime('today');
        }

        $this->target_date = clone $this->run_date;
        $this->target_date->modify('+'. $this->Config->getCutoffDate() . 'days');

        // 処理結果保存用
        $this->Result = new \stdClass();

        $this->Result->arrPeriodicIdsOverResumePeriod = [];

        $this->Result->arrPeriodicIdsSentThePreInfoMail = [];
        $this->Result->arrPreInfoError = [];

        $this->Result->arrPaymentErr = [];
        $this->Result->arrSystemErr = [];
        $this->Result->targetPeriodicPurchases = [];
        $this->Result->successCnt = 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->periodicBatchHelper->logging('[バッチ] 定期バッチ開始');

        // 再開期限切れの受注を処理
        $this->cancelPeriodicPurchaseOverResumePeriod();

        // 事前お知らせメールチェック
        $this->sendPreInformationMail($output);

        // 受注を作成
        $this->createNextOrder($output);

        // n回目の定期購入情報を管理者宛にメールで通知
        $this->notificationPeriodicTimes();

        // メール送信
        $this->sendResultMail();

        $this->periodicBatchHelper->logging('[バッチ] 定期バッチ終了');
    }

    private function cancelPeriodicPurchaseOverResumePeriod()
    {
        // 再開可能期間が設定されていなければスキップ
        if (!$this->Config->getCanResumeDate()) {
            return;
        }

        $this->periodicBatchHelper->logging('[バッチ] 再開期限切れ定期ステータス変更開始');

        $PeriodicPurchases = $this->periodicPurchaseRepository->updatePeriodicPurchaseOverResumePeriod(clone $this->run_date);

        // 該当の定期が存在しなければスキップ
        if (!$PeriodicPurchases) {
            $this->periodicBatchHelper->logging('[バッチ] 再開期限切れ定期が存在しないため終了');
            return;
        }

        foreach ($PeriodicPurchases as $PeriodicPurchase) {
            $this->periodicBatchHelper->logging('[バッチ] 再開期限切れ定期', $PeriodicPurchase);
            $this->Result->arrPeriodicIdsOverResumePeriod[] = $PeriodicPurchase->getId();
        }

        $this->periodicBatchHelper->logging('[バッチ] 再開期限切れ定期ステータス変更終了');
    }

    private function sendPreInformationMail($output)
    {
        // 事前お知らせメール配信日が設定されてないければスキップ
        if (!$this->Config->getPreInformationDate()) {
            return;
        }

        $this->periodicBatchHelper->logging('[バッチ] 事前お知らせメール配信開始');

        // リポジトリから該当の定期を取得
        $PeriodicPurchases = $this->periodicPurchaseRepository->getPeriodicPurchasesMatchedToPreInformationDate(clone $this->run_date);

        // 該当の定期が存在しなければスキップ
        if (!$PeriodicPurchases) {
            $this->periodicBatchHelper->logging('[バッチ] 事前お知らせメール配信対象定期が存在しないため終了');
            return;
        }

        $MailTemplate = $this->mailTemplateRepository->findOneBy(['name' => '定期購入事前お知らせメール']);

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());

        $subject = "[{$this->BaseInfo->getShopName()}] {$MailTemplate->getMailSubject()}";
        $from = [$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()];
        $bcc = $this->BaseInfo->getEmail01();
        $replyTo = $this->BaseInfo->getEmail03();
        $returnPath = $this->BaseInfo->getEmail04();

        $transport = $this->container->get('swiftmailer.mailer.default.transport.real');
        $mailer = new \Swift_Mailer($transport);

        foreach ($PeriodicPurchases as $PeriodicPurchase) {
            try {
                list($Order, $Shipping) = $this->periodicBatchHelper->createOrderBasedOnPeriodicPurchase($PeriodicPurchase, clone $this->run_date);

                $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));

                if ($flowResult->hasError()) {
                    $mess = "【受注計算エラー】";
                    foreach ($flowResult->getErrors() as $error) {
                        $mess .= $error->getMessage();
                    }
                    throw new Exception($mess);
                }
                if ($flowResult->hasWarning()) {
                    $mess = "【受注計算エラー】";
                    foreach ($flowResult->getWarning() as $warning) {
                        $mess .= $warning->getMessage();
                    }
                    throw new Exception($mess);
                }

                // メール送信
                $message = (new \Swift_Message())
                    ->setSubject($subject)
                    ->setFrom($from)
                    ->setTo($Order->getEmail())
                    ->setBcc($bcc)
                    ->setReplyTo($replyTo)
                    ->setReturnPath($returnPath);

                $body = $this->twig->render($MailTemplate->getFileName(), [
                    'Order' => $Order,
                    'BaseInfo' => $this->BaseInfo
                ]);

                if (!is_null($htmlFileName)) {
                    $htmlBody = $this->twig->render($htmlFileName, [
                        'Order' => $Order,
                        'BaseInfo' => $this->BaseInfo
                    ]);
                    $message
                        ->setContentType('text/plain; charset=UTF-8')
                        ->setBody($body, 'text/plain')
                        ->addPart($htmlBody, 'text/html');
                } else {
                    $message->setBody($body);
                }

                $mailer->send($message);

                $this->Result->arrPeriodicIdsSentThePreInfoMail[] = $PeriodicPurchase->getId();

                $this->periodicBatchHelper->logging('[バッチ] 事前お知らせメール配信完了', $PeriodicPurchase);
            } catch (\Exception $e) {
                $error_info = [
                    'PeriodicPurchase' => $PeriodicPurchase,
                    'name'             => $PeriodicPurchase->getName01().$PeriodicPurchase->getName02(),
                    'error_detail'     => $e->getMessage()
                ];

                $this->Result->arrPreInfoError[] = $error_info;

                $this->periodicBatchHelper->logging('[バッチ] 事前お知らせメール配信失敗 error_detail:'.$e->getMessage(), $PeriodicPurchase);
            }
        }

        $this->periodicBatchHelper->logging('[バッチ] 事前お知らせメール配信終了');
    }

    private function createNextOrder($output)
    {
        $this->periodicBatchHelper->logging('[バッチ] 受注作成開始');

        // 対象定期受注を取得
        $PeriodicPurchases = $this->periodicPurchaseRepository->getPeriodicPurchasesMatchedToTargetDate($this->target_date);

        // 存在しなければスキップ
        if (!$PeriodicPurchases) {
            $this->periodicBatchHelper->logging('[バッチ] 受注作成対象の定期が存在しないため終了');
            return;
        }
        $this->Result->targetPeriodicPurchases = $PeriodicPurchases;

        /** @var Connection $Connection */
        $Connection = $this->entityManager->getConnection();
        $Connection->setAutoCommit(false);
        $Connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
        if (!$Connection->isConnected()) {
            $Connection->connect();
        }
        // AutoCommit = falseでは、commit/rollback時に新規transactionが発行されるためこの位置で最初のtransactionを発行する
        // フロントからのバッチ実行時にはTransactionListenerでトランザクションが貼られているため、二重に貼らないようにする
        if (!$Connection->isTransactionActive()) {
            $Connection->beginTransaction();
        }

        $arrErrInfo = [];

        foreach ($PeriodicPurchases as $idx => $PeriodicPurchase) {
            try {
                // 1. 受注を作成
                list($Order, $Shipping) = $this->periodicBatchHelper->createOrderBasedOnPeriodicPurchase($PeriodicPurchase, clone $this->run_date);

                $this->entityManager->persist($Order);
                $this->entityManager->persist($Shipping);
                $this->entityManager->flush();

                // 2. 決済
                $this->periodicBatchHelper->doPayment($Order);

                $this->entityManager->persist($Order);
                $this->entityManager->persist($Shipping);
                $this->entityManager->flush();

                // 3. 定期マスタを更新
                $this->periodicPurchaseRepository->updatePeriodicPurchaseWhenBatchProcessNormality($PeriodicPurchase, clone $this->run_date, $Order);

                $this->periodicBatchHelper->logging("[バッチ] 受注作成完了 受注ID:{$Order->getId()}", $PeriodicPurchase);

                $this->entityManager->commit();
                $this->Result->successCnt++;
            } catch (\Exception $e) {
                $error_info = [
                    'PeriodicPurchase' => $PeriodicPurchase,
                    'name'             => $PeriodicPurchase->getName01().$PeriodicPurchase->getName02(),
                    'error_detail'     => $e->getMessage()
                ];

                switch ($e->getCode()) {
                    case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_PAYMENT_ERROR']:
                        $error_info['newStatus'] = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR);
                        $this->Result->arrPaymentErr[] = $error_info;
                        break;

                    case $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_SYSTEM_ERROR']:
                    default:
                        $error_info['newStatus'] = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_SYSTEM_ERROR);
                        $this->Result->arrSystemErr[] = $error_info;
                        break;
                }
                $arrErrInfo[] = $error_info;

                $this->periodicBatchHelper->logging('[バッチ] 受注作成失敗 error_detail:'.$e->getMessage(), $PeriodicPurchase);

                $this->entityManager->rollback();
            }
        }

        // 失敗した定期マスタを更新
        if (!empty($arrErrInfo)) {
            foreach ($arrErrInfo as $err_info) {
                $PeriodicPurchase = $err_info['PeriodicPurchase'];
                $newStatus = $err_info['newStatus'];
                $this->periodicPurchaseRepository->updatePeriodicPurchaseWhenBatchProcessException($PeriodicPurchase, $newStatus);
            }
            $this->entityManager->commit();
        }

        // 既存の受注処理と順序が異なりOrderNoがSetされていないので明示的にSetする
        $this->setOrderNoForSuccessfulOrders();

        $this->periodicBatchHelper->logging('[バッチ] 受注作成終了');
    }

    private function setOrderNoForSuccessfulOrders()
    {
        $Orders = $this->orderRepository->findBy(['order_no' => null]);
        $PurchaseContext = new PurchaseContext();

        foreach ($Orders as $Order) {
            // 注文入力画面から先に進んでいないOrderを除く(その段階ではorder_noが振られていないため)
            if ($PeriodicPurchase = $Order->getPeriodicPurchase()) {
                $this->orderNoProcessor->process($Order, $PurchaseContext);
                $PeriodicPurchase->setLastOrderId($Order->getOrderNo());

                $this->entityManager->persist($Order);
                $this->entityManager->persist($PeriodicPurchase);
            }
        }

        $this->entityManager->commit();
    }

    private function notificationPeriodicTimes()
    {
        $this->periodicBatchHelper->logging('[バッチ] 指定定期回数の通知開始');

        $arrNotificationPeriodicTime = $this->Config->getNotificationPeriodicTime();

        foreach ($this->Result->targetPeriodicPurchases as $PeriodicPurchase) {
            // 決済/システムエラーが発生した定期は対象としない
            if ($PeriodicPurchase->getPeriodicStatus()->getId() == PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_CONTINUE) {
                $cnt = $PeriodicPurchase->getPeriodicPurchaseCount();
                if (in_array($cnt, $arrNotificationPeriodicTime)) {
                    $arrNotificationPeriodicIds[$cnt][] = $PeriodicPurchase->getId();
                }
            }
        }

        // 通知メール送信
        if (!empty($arrNotificationPeriodicIds)) {
            $this->sendNotificationMail($arrNotificationPeriodicIds);
        }

        $this->periodicBatchHelper->logging('[バッチ] 指定定期回数の通知終了');
    }

    private function sendNotificationMail($arrNotificationPeriodicIds)
    {
        $transport = $this->container->get('swiftmailer.mailer.default.transport.real');
        $mailer = new \Swift_Mailer($transport);

        foreach ($arrNotificationPeriodicIds as $periodicTime => $periodicIds) {
            $body = $this->twig->render('@IplPeriodicPurchase/mail/notification_periodic_time.twig', [
                'run_date' => $this->run_date,
                'periodicTime' => $periodicTime,
                'periodicIds' => $periodicIds
            ]);

            $subject = <<<SUB
            ({$this->run_date->format('Y-m-d H:i')}) 定期回数{$periodicTime}回目を迎えたお客様がいらっしゃいます
SUB;
            $message = new \Swift_Message();
            $message
                ->setSubject($subject)
                ->setFrom([$this->Config->getReceptionAddress()])
                ->setTo([$this->Config->getReceptionAddress()])
                ->setBody($body);

            $mailer->send($message);
        }
    }

    private function sendResultMail()
    {
        $this->periodicBatchHelper->logging('[バッチ] 結果メール送信開始');

        $body = $this->twig->render('@IplPeriodicPurchase/mail/batch_result.twig', [
            'run_date' => $this->run_date,
            'Result' => $this->Result
        ]);

        $process_cnt = count($this->Result->targetPeriodicPurchases);
        $pre_info_cnt = count($this->Result->arrPeriodicIdsSentThePreInfoMail);
        $subject = <<<SUB
        ({$this->run_date->format('Y-m-d H:i')}) 定期バッチ実行結果 購入処理:{$process_cnt}件 事前お知らせメール配信:{$pre_info_cnt}件
SUB;
        $message = new \Swift_Message();
        $message
            ->setSubject($subject)
            ->setFrom([$this->Config->getReceptionAddress()])
            ->setTo([$this->Config->getReceptionAddress()])
            ->setBody($body);

        $transport = $this->container->get('swiftmailer.mailer.default.transport.real');
        $mailer = new \Swift_Mailer($transport);
        $mailer->send($message);

        $this->periodicBatchHelper->logging('[バッチ] 結果メール送信終了');
    }
}

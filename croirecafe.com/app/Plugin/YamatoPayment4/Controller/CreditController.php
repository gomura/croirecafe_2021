<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\YamatoPayment4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Service\MailService;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Plugin\YamatoPayment4\Form\Type\CreditChangeType;
use Plugin\YamatoPayment4\Service\Client\CreditClientService;
use Plugin\YamatoPayment4\Service\Method\Credit;

class CreditController extends AbstractController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    public function __construct(
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        TokenStorageInterface $tokenStorage,
        CreditClientService $creditClientService
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->creditClientService = $creditClientService;
    }

    /**
     * 変更画面.
     *
     * @Route("/mypage/kuronekocredit", name="mypage_kuronekocredit")
     * @Template("@YamatoPayment4/mypage/credit.twig")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(CreditChangeType::class);

        $Customer = $this->getUser();
        // クレジットカード決済の受注をひとつ取得する
        $Payment  = $this->paymentRepository->findOneBy(['method_class' => Credit::class]);
        $Order = $this->orderRepository->findOneBy(['Payment' => $Payment, 'Customer' => $Customer]);

        // 決済エラー状態の定期があればメッセージを出す
        $has_paymenterrored_periodicpurchase = $this->hasPaymentErroredPeriodicPurchase($Customer);

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'Order' => $Order,
            'has_paymenterrored_periodicpurchase' => $has_paymenterrored_periodicpurchase,
        ];
    }

    /**
     * @Route("/mypage/kuronekocredit/delete", name="mypage_kuronekocredit_delete")
     * @Template("@YamatoPayment4/mypage/credit.twig")
     */
    public function delete(Request $request)
    {
        log_info('カード削除処理開始');

        $form = $this->createForm(CreditChangeType::class);

        $Customer = $this->getUser();
        // クレジットカード決済の受注をひとつ取得する
        $Payment  = $this->paymentRepository->findOneBy(['method_class' => Credit::class]);
        $Order = $this->orderRepository->findOneBy(['Payment' => $Payment, 'Customer' => $Customer]);

        $form->handleRequest($request);
        $errMessage = '';

        $this->creditClientService->setSetting($Order);
        // 必要な入力値をセット
        $arrParam['card_key'] = $request->get('card_key');
        $arrParam['lastCreditDate'] = $request->get('last_credit_date');

        $result = $this->creditClientService->doDeleteCard($Customer->getId(), $arrParam);
        if($result == false) {
            $errMessage = implode("\n", $this->creditClientService->getError());
        }

        if($errMessage) {
            $this->addError($errMessage);
        } else {
            $this->addSuccess('yamato_payment.mypage.delete.success');

            $this->eventDispatcher->dispatch($this->eccubeConfig['YAMATO_MYPAGE_CARD_DELETE'], null);
        }

        // 決済エラー状態の定期があればメッセージを出す
        $has_paymenterrored_periodicpurchase = $this->hasPaymentErroredPeriodicPurchase($Customer);

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'Order' => $Order,
            'has_paymenterrored_periodicpurchase' => $has_paymenterrored_periodicpurchase,
        ];
    }

    /**
     * @Route("/mypage/kuronekocredit/register", name="mypage_kuronekocredit_register")
     * @Template("@YamatoPayment4/mypage/credit.twig")
     */
    public function register(Request $request)
    {
        log_info('カード登録処理開始');

        $form = $this->createForm(CreditChangeType::class);

        $Customer = $this->getUser();
        // クレジットカード決済の受注をひとつ取得する
        $Payment  = $this->paymentRepository->findOneBy(['method_class' => Credit::class]);
        $Order = $this->orderRepository->findOneBy(['Payment' => $Payment, 'Customer' => $Customer]);

        $form->handleRequest($request);
        $errMessage = '';

        $this->creditClientService->setSetting($Order);

        // 必要な入力値をセット
        $arrParam['token'] = $request->get('webcollectToken');

        // カード登録のためにダミー受注を作成
        $dummyOrder = $this->creditClientService->getDummyOrder($Customer);

        // IDを吐かせるためにflushまで
        $this->entityManager->persist($dummyOrder);
        $this->entityManager->flush();

        $result = $this->creditClientService->doRegistCard($Customer, $dummyOrder, $arrParam);
        if ($result === false) {
            $errMessage = implode("\n", $this->creditClientService->getError());
        } else {
            $this->addSuccess('yamato_payment.mypage.register.success');

            // 登録のために行った1円決済をキャンセル
            $result = $this->creditClientService->doCancelDummyOrder($dummyOrder);
            if ($result === false) {
                $errMessage = implode("\n", $this->creditClientService->getError());
            } else {
                // 定期プラグインでこのイベントをsubscribeしている
                $this->eventDispatcher->dispatch($this->eccubeConfig['YAMATO_MYPAGE_CARD_REGISTER'], null);
            }
        }

        if ($errMessage) {
            $this->addError($errMessage);
        }

        // 決済エラー状態の定期があればメッセージを出す
        $has_paymenterrored_periodicpurchase = $this->hasPaymentErroredPeriodicPurchase($Customer);

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'Order' => $Order,
            'has_paymenterrored_periodicpurchase' => $has_paymenterrored_periodicpurchase,
        ];
    }

    private function hasPaymentErroredPeriodicPurchase($Customer)
    {
        if (!empty($this->eccubeConfig['SALE_TYPE_ID_PERIODIC'])) {
            $periodicPurchaseRepository =$this->entityManager->getRepository(\Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase::class);
            if ($periodicPurchaseRepository->getPaymentErroredPeriodicPurchase($Customer)) {
                return true;
            }
        }

        return false;
    }
}

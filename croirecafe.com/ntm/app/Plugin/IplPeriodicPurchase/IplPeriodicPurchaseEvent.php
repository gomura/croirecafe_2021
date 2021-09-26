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
use Eccube\Event\TemplateEvent;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\EventDispatcher\Event;

use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;

class IplPeriodicPurchaseEvent implements EventSubscriberInterface
{
    protected $eccubeConfig;
    protected $paymentRepository;
    protected $customerRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        TokenStorageInterface $tokenStorage,
        PeriodicPurchaseRepository $periodicPurchaseRepository,
        PeriodicHelper $periodicHelper,
        \Twig_Environment $twig,
        SessionInterface $session
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->tokenStorage = $tokenStorage;
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
        $this->periodicHelper = $periodicHelper;
        $this->twig = $twig;
        $this->session = $session;
    }


    /**
     * リッスンしたいサブスクライバのイベント名の配列を返します。
     * 配列のキーはイベント名、値は以下のどれかをしてします。
     * - 呼び出すメソッド名
     * - 呼び出すメソッド名と優先度の配列
     * - 呼び出すメソッド名と優先度の配列の配列
     * 優先度を省略した場合は0
     *
     * 例：
     * - array('eventName' => 'methodName')
     * - array('eventName' => array('methodName', $priority))
     * - array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_CART_BUYSTEP_COMPLETE => 'onShoppingIndex',
            EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_INITIALIZE => 'onWithDrawInitialize',

            'Cart/index.twig' => 'onCartIndexTwig',
            'Shopping/index.twig' => 'onShoppingIndexTwig',
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            'Mypage/index.twig' => 'onMypageNaviTwig',
            'Mypage/favorite.twig' => 'onMypageNaviTwig',
            'Mypage/change.twig' => 'onMypageNaviTwig',
            'Mypage/delivery.twig' => 'onMypageNaviTwig',
            'Mypage/delivery_edit.twig' => 'onMypageNaviTwig',
            'Mypage/withdraw.twig' => [['onMypageNaviTwig'], ['onWithDrawInitializeTwig']],

            '@IplPeriodicPurchase/mypage/index.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/history.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/cycle.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/next_shipping.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/shipping.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/products.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/skip.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/suspend.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/resume.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/cancel.twig' => 'onMypageNaviTwig',
            '@IplPeriodicPurchase/mypage/complete.twig' => 'onMypageNaviTwig',
            '@YamatoPayment4/mypage/credit.twig' => 'onMypageNaviTwig',
            '@admin/Product/product.twig' => 'onAdminProductTwig',
            '@admin/Product/product_class.twig' => 'onAdminProductClassTwig',

            // static内でthisを参照できないため仕方なく固定値で見る
            'yamato.mypage.card.register' => 'onYamatoCardRegister',
        ];
    }

    public function onCartIndexTwig(TemplateEvent $event)
    {
        $Carts = $event->getParameter('Carts');

        $periodic_sale_type = preg_quote($this->eccubeConfig['SALE_TYPE_ID_PERIODIC']);
        // cartKey = "会員ID_商品種別ID"
        foreach ($Carts as $Cart) {
            if (preg_match("/_{$periodic_sale_type}/", $Cart->getCartKey())) {
                $PeriodicCart = $Cart;
                $Cart->is_periodic_cart = true;
            } else {
                $Cart->is_periodic_cart = false;
            }
        }

        // 定期商品がカートに入っていなければ処理は行わない
        if (!$PeriodicCart) {
            return;
        }

        $cart_key = $PeriodicCart->getCartKey();

        // 共通の定期サイクルが設定されていない定期商品がカートに入っていないか
        if (!$this->periodicHelper->getDuplicateCycle($PeriodicCart->getCartItems())) {
            $mess = '定期サイクルが異なる定期商品を同時に購入することはできません。恐れ入りますが、別々にご購入頂きますようお願い致します。' ;
            $this->session->getFlashBag()->add("eccube.front.cart.$cart_key.request.error", $mess);

            $event->setParameter('is_not_duplicate_cycle', true);
        }

        // 異なる定期割引率が登録された定期商品が同時にカートに入っていないか
        if (!$this->periodicHelper->isNotDuplicatedPeriodicDiscount($PeriodicCart)) {
            $mess = '異なる定期割引率の定期商品を同時に購入することはできません。恐れ入りますが、別々にご購入頂きますようお願い致します。' ;
            $this->session->getFlashBag()->add("eccube.front.cart.$cart_key.request.error", $mess);

            $event->setParameter('is_not_duplicate_periodic_discount', true);
        }

        $periodic_discount = $this->periodicHelper->getPeriodicDiscountAmount($PeriodicCart);
        $PeriodicCart->setTotal($PeriodicCart->getTotal() - $periodic_discount);

        // カートの置き換え
        foreach ($Carts as $Cart) {
            if ($Cart->getCartKey() === $cart_key) {
                $Cart = $PeriodicCart;
            }
        }

        $event->setParameter('periodic_discount', $periodic_discount);
        $event->setParameter('periodic_cart_key', $PeriodicCart->getCartKey());
        $event->addSnippet('@IplPeriodicPurchase/default/cart/add_periodic_discount.twig');
    }

    public function onShoppingIndex(EventArgs $event)
    {
        $Request = $event->getRequest();

        if (strpos($Request->get('cart_key'), (string)$this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) !== false) {
            if (!$this->getUser()) {
                $this->session->getFlashBag()->add('eccube.front.error', '定期購入商品を含むお買い物は、会員登録が必要です。お手数ですが、会員登録をお願いします。すでにご登録いただいている場合はログインをお願いします。');
                $content = $this->twig->render('Shopping/shopping_error.twig');
                $event->setResponse(Response::create($content, 200));
            } else {
                $this->session->remove('eccube.front.error');
            }
        } else {
            $this->session->remove('eccube.front.error');
        }
    }

    public function onShoppingIndexTwig(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');

        if ($Order->getSaleTypes()[0]->getId() === $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
            $event->addSnippet('@IplPeriodicPurchase/default/shopping/add_cycle_index.twig');
            $event->setParameter('cycle_type_dayofweek', $this->eccubeConfig['PLG_IPLPERIODICPURCHASE_CYCLE_TYPE_DAYOFWEEK']);
        }
    }

    public function onShoppingConfirmTwig(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');

        if ($Order->getSaleTypes()[0]->getId() === $this->eccubeConfig['SALE_TYPE_ID_PERIODIC']) {
            $Cycle = $Order->getCycle();
            $cycle_week = $Order->getCycleWeek();
            $cycle_day = $Order->getCycleDay();
            $cycle_disp_name = $this->periodicHelper->getFormatedCycleDispName($Cycle, $cycle_week, $cycle_day);

            $event->addSnippet('@IplPeriodicPurchase/default/shopping/add_cycle_confirm.twig');
            $event->setParameter('cycle_disp_name', $cycle_disp_name);
        }
    }

    public function onWithDrawInitialize(EventArgs $event)
    {
        // ボタンを非表示とするが、手動で突破可能なので安全弁を設ける
        $Customer = $this->getUser();

        $PeriodicPurchases = $this->periodicPurchaseRepository->getContinuedPeriodicPurchase($Customer);
        if ($PeriodicPurchases) {
            $Request = $event->getRequest();
            $mode = $Request->get('mode');
            if ($mode === 'confirm' || $mode === 'complete') {
                $Request->attributes->set('mode', '');
                $event->setRequest($Request);
            }
        }
    }

    public function onWithDrawInitializeTwig(TemplateEvent $event)
    {
        // 契約中の定期が存在したら、エラーメッセージを出してConfirmボタンを消す
        $Customer = $this->getUser();

        $PeriodicPurchases = $this->periodicPurchaseRepository->getContinuedPeriodicPurchase($Customer);
        if ($PeriodicPurchases) {
            $event->addSnippet('@IplPeriodicPurchase/mypage/hide_withdraw_confirm_button.twig');
        }
    }

    public function onMypageNaviTwig(TemplateEvent $event)
    {
        $event->addSnippet('@IplPeriodicPurchase/mypage/add_navi.twig');
    }

    public function onAdminProductTwig(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $parameters['periodic_sale_type_id'] = $this->eccubeConfig['SALE_TYPE_ID_PERIODIC'];
        $event->setParameters($parameters);

        $event->addSnippet('@IplPeriodicPurchase/admin/product.twig');
    }

    public function onAdminProductClassTwig(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $parameters['periodic_sale_type_id'] = $this->eccubeConfig['SALE_TYPE_ID_PERIODIC'];
        $event->setParameters($parameters);

        $event->addSnippet('@IplPeriodicPurchase/admin/product_class.twig');
    }

    public function onYamatoCardRegister(Event $event)
    {
        $Customer = $this->getUser();

        $PeriodicPurchases = $this->periodicPurchaseRepository->getPaymentErroredPeriodicPurchaseWithYamatoCredit($Customer);

        if ($PeriodicPurchases) {
            $this->periodicPurchaseRepository->updatePaymentErroredPeriodicPurchasesToWaitingResettlement($Customer);
        }
    }

    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }
}

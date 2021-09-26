<?php

namespace Plugin\YamatoPayment4\Util;

use Eccube\Common\EccubeConfig;
use Symfony\Component\HttpFoundation\Session\Session;

class SecurityUtil
{
    /**
     * 試行回数を保持する用のセッションキー
     *
     * @var string
     */
    const SESSION_MULTI_ATTACK_COUNT = 'yamato_payment.yfc_multi_atack_count';

    /** @var Session */
    private $session;

    /** @var EccubeConfig */
    private $eccubeConfig;

    /**
     * SecurityUtil constructor.
     *
     * @param Session $session
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        Session $session,
        EccubeConfig $eccubeConfig
    )
    {
        $this->session = $session;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * 不正なアタックを検知し一定回数を超えたかをチェック
     * 超えている場合はtrue、超えていない場合はfalseを返す
     *
     * @return boolean
     */
    public function checkMultiAtack()
    {
        $multi_attack_count = 0;
        // 現在の失敗数を取得
        if ($this->session->has(self::SESSION_MULTI_ATTACK_COUNT)) {
            $multi_attack_count = $this->session->get(self::SESSION_MULTI_ATTACK_COUNT);
        }

        // 今回を加算
        $multi_attack_count++;

        // 既定の回数を超えているか確認
        if ($multi_attack_count > $this->eccubeConfig['YAMATO_MULTI_ATACK_PERMIT_COUNT']) {
            return true;
        }

        // 超えていない場合はウェイトをかける
        $this->session->set(self::SESSION_MULTI_ATTACK_COUNT, $multi_attack_count);
        sleep($this->eccubeConfig['YAMATO_MULTI_ATACK_WAIT']);
        return false;
    }

    /**
     * 不正なアタック用のセッションをリセットする
     *
     * @return void
     */
    public function removeMultiAtack()
    {
        $this->session->set(self::SESSION_MULTI_ATTACK_COUNT, 0);
    }

    public function setErrorMessage($message)
    {
        $this->session->getFlashBag()->add('eccube.front.error', $message);
    }
}

<?php
/*
 * This file is part of the PostCarrier for EC-CUBE
 *
 * Copyright(c) IPLOGIC CO.,LTD. All Rights Reserved.
 * http://www.iplogic.co.jp
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\PostCarrier4\Controller;

use Eccube\Controller\AbstractController;
use Plugin\PostCarrier4\Repository\PostCarrierConfigRepository;
use Plugin\PostCarrier4\Service\PostCarrierService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReceiveController extends AbstractController
{
    /**
     * @var PostCarrierService
     */
    protected $postCarrierService;

    /**
     * @var PostCarrierConfigRepository
     */
    protected $configRepository;

    /**
     * PostCarrierReceiveController constructor.
     *
     * @param PostCarrierService $postCarrierService
     * @param PostCarrierConfigRepository $configRepository
     */
    public function __construct(
        PostCarrierService $postCarrierService,
        PostCarrierConfigRepository $configRepository
    ) {
        $this->postCarrierService = $postCarrierService;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/postcarrier", name="post_carrier_receive", methods={"GET","POST"})
     *
     * @param Request $request
     * @param int $deliveryId
     *
     * @return RedirectResponse|Response
     */
    public function receive(Request $request)
    {
        $service = $this->postCarrierService;

        $Config = $this->configRepository->get();

        if ($request->getMethod() === 'GET') {
            if ($request->get('cmd') === 'check') {
                return new Response(
                    "I'm fine.",
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            if ($Config === null) {
                return $this->redirectToRoute('homepage');
            }

            if ($request->get('cmd') === 'count') {
                $count = $this->postCarrierService->getEffectiveAddressCount($request->get('key'));

                return new Response(
                    'count='.$count,
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            $p = $request->get('p');
            if (!$p) {
                return $this->redirectToRoute('homepage');
            }

            $t = $p[0];
            if ($t === 'c' || $t === 'p') {
                $token = substr($p, 1);
                $click_param = "$t/$token";
            } else if ($t === 'o') {
                $token = substr($p, 1);
                $click_param = "$t/$token.jpeg";
            } else if ($t === 'v') {
                $order_id = $request->get('o');
                $total = $request->get('t');
                $click_param = "$t/t.jpeg";
                if ($order_id && $total) {
                    $click_param = $click_param . "?o=${order_id}&t=${total}";
                }
            } else {
                return $this->redirectToRoute('homepage');
            }

            $curl_opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_USERAGENT => 'User-Agent: ' . $request->headers->get('User-Agent'),
            ];

            $session = $request->getSession();
            if ($session->has('postcarrier.conversion_cookie')) {
                $curl_opts[CURLOPT_COOKIE] = "JSESSIONID=" . $session->get('postcarrier.conversion_cookie');
            }

            $clickUrl = $Config->getClickUrl() . $click_param;
            $ch = curl_init($clickUrl);
            curl_setopt_array($ch, $curl_opts);
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            switch ($info['http_code']) {
            case 200:
            case 301: // Moved Permanently
            case 302: // Moved Temporarily, Found
                break;
            default:
                return $this->redirectToRoute('homepage');
            }

            $redirect_url = $info['redirect_url'];
            if (!$redirect_url) {
                return $this->redirectToRoute('homepage');
            }

            $http_response_header = preg_split('/\r\n/', $ret);
            unset($http_response_header[0]); // ステータス行を削除 HTTP/1.1 302 Moved Temporarily
            $cookies = [];
            $resHeader = [];
            foreach ($http_response_header as $hdr) {
                if ($hdr == '') break; // 空行はヘッダの終り
                list($name,$value) = explode(':', $hdr, 2);
                $hdrname = strtolower($name);
                $hdrvalue = ltrim($value);
                if ('set-cookie' === $hdrname) {
                    if (preg_match_all('/([^\s]+?)=([^;]+)/', $hdrvalue, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $m) {
                            switch (strtolower($m[1])) {
                            case 'path':
                            case 'domain':
                            case 'secure':
                            case 'expires':
                                break;
                            default:
                                $cookies[] = array('name' => $m[1], 'value' => $m[2]);
                                break;
                            }
                        }
                    }
                } else {
                    $resHeader[$hdrname] = $hdrvalue;
                }
            }

            foreach ($cookies as $cookie) {
                if ($cookie['name'] == 'JSESSIONID') {
                    $session->set('postcarrier.conversion_cookie', $cookie['value']);
                    break;
                }
            }

            // コンバージョン・開封通知の画像を出力する。
            if ($t === 'v' || $t === 'o') {
                return new Response(
                    file_get_contents(__DIR__."/../Resource/box/cv.gif"),
                    Response::HTTP_OK,
                    ['Content-Type' => 'image/gif']
                );
            }

            return $this->redirect($redirect_url);
        } else if ($request->getMethod() === 'POST') {
            $deliveryId = $request->get('deliveryId');
            if (is_numeric($deliveryId)) {
                // $h = $request->get('h');
                // $raw_msg = file_get_contents("php://input");
                // $msg = substr($raw_msg, 0, strpos($raw_msg, "&h="));
                // $key = $Config->getShopName()." ".$Config->getShopPass();
                // $hash = hash_hmac('sha256', $msg, $key);
                // if (!hash_equals($hash, $h)) {
                //     log('postcarrier')->error('hash mismatch.', [$deliveryId, $raw_mag, $hash]);
                //     return new Response(
                //         json_encode([
                //             'status' => 1,
                //             'deliveryId' => $deliveryId,
                //         ]),
                //         Response::HTTP_OK,
                //         ['Content-Type' => 'text/plain; charset=utf-8']
                //     );
                // }

                if ($deliveryId == 0) {
                    logs('postcarrier')->info('check ok.', [$deliveryId]);
                    return new Response(
                        json_encode([
                            'status' => 0,
                            'deliveryId' => "0",
                        ]),
                        Response::HTTP_OK,
                        ['Content-Type' => 'text/plain; charset=utf-8']
                    );
                }

                $n = $service->upload($deliveryId);

                return new Response(
                    json_encode([
                        'status' => ($n !== null) ? 0 : 1, // 0:成功 1:失敗
                        'deliveryId' => $deliveryId,
                    ]),
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }
        }
    }
}

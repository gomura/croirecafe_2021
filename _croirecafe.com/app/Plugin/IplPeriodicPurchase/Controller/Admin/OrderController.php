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
namespace Plugin\IplPeriodicPurchase\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\IplPeriodicPurchase\Entity\PeriodicPurchase;
use Plugin\IplPeriodicPurchase\Entity\PeriodicStatus;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\SearchOrderType;
use Plugin\IplPeriodicPurchase\Repository\PeriodicPurchaseRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicStatusRepository;
use Plugin\IplPeriodicPurchase\Service\PeriodicBatchHelper;
use Plugin\IplPeriodicPurchase\Service\PeriodicHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var PeriodicPurchaseRepository
     */
    protected $periodicPurchaseRepository;

    /**
     * @var PeriodicStatusRepository
     */
    protected $periodicStatusRepository;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    public function __construct(
        \Swift_Mailer $mailer,
        BaseInfoRepository $baseInfoRepository,
        PageMaxRepository $pageMaxRepository,
        PeriodicPurchaseRepository $periodicPurchaseRepository,
        PeriodicStatusRepository $periodicStatusRepository,
        PeriodicBatchHelper $periodicBatchHelper,
        PeriodicHelper $periodicHelper,
        \Twig_Environment $twig
    )
    {
        $this->mailer = $mailer;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->pageMaxRepository = $pageMaxRepository;
        $this->periodicPurchaseRepository = $periodicPurchaseRepository;
        $this->periodicStatusRepository = $periodicStatusRepository;
        $this->periodicBatchHelper = $periodicBatchHelper;
        $this->periodicHelper = $periodicHelper;
        $this->twig = $twig;
    }

    /**
     * @Route("/%eccube_admin_route%/periodic/order", name="periodic_admin_order")
     * @Route("/%eccube_admin_route%/periodic/order/page/{page_no}", requirements={"page_no" = "\d+"}, name="periodic_admin_order_page")
     * @param Request $request
     * @Template("@IplPeriodicPurchase/admin/order.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $builder = $this->formFactory->createBuilder(SearchOrderType::class);

        $searchForm = $builder->getForm();

        $err_msg = $this->periodicBatchHelper->isExistsBatchLogFile();

        if (!is_null($err_msg)) {
            $this->addError($err_msg, 'admin');
        }

        /**
         * ???????????????????????????, ??????????????????????????????.
         * - ??????????????????????????????
         * - ???????????????
         * - ??????????????????
         * ??????, ???????????????????????????????????? mtb_page_max????????????, ????????????????????????????????????.
         **/
        $page_count = $this->session->get('eccube.admin.order.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.periodic.admin.order.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * ?????????????????????????????????, ?????????????????????????????????????????????.
                 * ????????????????????????????????????????????????????????????.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // ????????????, ??????????????????????????????????????????.
                $this->session->set('eccube.periodic.admin.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.periodic.admin.order.search.page_no', $page_no);
            } else {
                // ????????????????????????, ????????????????????????????????????????????????.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ???????????????????????????????????????????????????????????????????????????, ????????????????????????????????????????????????.
                 */
                if ($page_no) {
                    // ????????????????????????????????????.
                    $this->session->set('eccube.periodic.admin.order.search.page_no', (int) $page_no);
                } else {
                    // ?????????????????????????????????.
                    $page_no = $this->session->get('eccube.periodic.admin.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.periodic.admin.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * ?????????????????????.
                 */
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('periodic_status_id')) {
                    $viewData = ['periodic_status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // ?????????????????????????????????, ???????????????????????????.
                $this->session->set('eccube.periodic.admin.order.search', $viewData);
                $this->session->set('eccube.periodic.admin.order.search.page_no', $page_no);
            }
        }

        $qb = $this->periodicPurchaseRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
        ];

    }

    /**
     * @Route("/%eccube_admin_route%/periodic/order/preview_card_change_mail/{id}", requirements={"id" = "\d+"}, name="periodic_admin_order_preview_card_change_mail")
     *
     * @param PeriodicPurchase $PeriodicPurchase
     *
     * @return Response
     *
     * @throws \Twig_Error
     */
    public function previewChangeCardMail(PeriodicPurchase $PeriodicPurchase)
    {
        // ????????????????????????
        $fileName = '@IplPeriodicPurchase/mail/card_change_request.twig';

        // ?????????????????????????????????
        return new Response($this->twig->render($fileName, [
            'BaseInfo' => $this->BaseInfo,
            'PeriodicPurchase' => $PeriodicPurchase,
        ]));
    }




    /**
     * @Route("/%eccube_admin_route%/periodic/order/card_change_mail/{id}", requirements={"id" = "\d+"}, name="periodic_admin_order_card_change_mail", methods={"PUT"})
     *
     * @param PeriodicPurchase $PeriodicPurchase
     *
     * @return JsonResponse
     */
    public function changeCardMail(PeriodicPurchase $PeriodicPurchase)
    {
        $this->isTokenValid();

        $PeriodicStatus = $PeriodicPurchase->getPeriodicStatus();

        // ?????????????????????????????????????????????????????????
        $PaymentError = $this->periodicStatusRepository->find(PeriodicStatus::PLG_IPLPERIODICPURCHASE_STATUS_PAYMENT_ERROR);
        if ($PeriodicStatus !== $PaymentError) {
            return $this->json([], 400);
        }

        // ????????????????????????
        $fileName = '@IplPeriodicPurchase/mail/card_change_request.twig';

        // subject
        $subject = '['.$this->BaseInfo->getShopName().'] '.'????????????????????????????????????????????????';

        // body
        $body = $this->twig->render($fileName, [
            'BaseInfo' => $this->BaseInfo,
            'PeriodicPurchase' => $PeriodicPurchase,
        ]);

        // ???????????????????????????
        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom([$this->BaseInfo->getEmail02() => $this->BaseInfo->getShopName()])
            ->setTo($PeriodicPurchase->getEmail())
            ->setBcc($this->BaseInfo->getEmail02())
            ->setReplyTo($this->BaseInfo->getEmail02())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        // ???????????????
        $this->mailer->send($message);

        $this->periodicHelper->logging('[????????????] ??????????????????????????????????????????', $PeriodicPurchase);

        // ?????????????????????????????????????????????
        $date =  new \DateTime();
        $PeriodicPurchase->setCardChangeDate($date);

        $this->periodicPurchaseRepository->save($PeriodicPurchase);
        $this->entityManager->flush();

        return $this->json([
            'mail' => true,
            'shipped' => false,
        ]);
    }

    /**
     * @Route("/%eccube_admin_route%/periodic/order/action_periodic_batch", name="periodic_admin_order_exec_periodic_batch", methods={"POST"})
     *
     * @param KernelInterface $kernel
     *
     * @return JsonResponse
     */
    public function actionPeriodicBatch(Request $request, KernelInterface $kernel)
    {
        $this->isTokenValid();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $run_date = $request->get('run_date');

        $input = new ArrayInput([
            'command' => 'eccube:periodic:batch',
            'run_date' => $run_date,
        ]);

        // ???????????????
        $output = new NullOutput();

        // ???????????????
        $application->run($input, $output);

        return $this->json(['result' => 'ok'], 200);
    }
}
<?php
/**
 * This file is part of FirstTimeOnly4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 *  https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FirstTimeOnly4\Controller\Admin;


use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Util\CacheUtil;
use Plugin\FirstTimeOnly4\Form\Admin\EnabledOrderStatusType;
use Plugin\FirstTimeOnly4\Repository\EnabledOrderStatusRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EnabledOrderStatusController extends AbstractController
{
    /**
     * @var EnabledOrderStatusRepository
     */
    private $enabledOrderStatusRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    public function __construct(
        EnabledOrderStatusRepository $enabledOrderStatusRepository,
        OrderStatusRepository $orderStatusRepository
    )
    {
        $this->enabledOrderStatusRepository = $enabledOrderStatusRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * @param Request $request
     * @param CacheUtil $cacheUtil
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/%eccube_admin_route%/first_time_only/enabled_order_status", name="first_time_only_admin_enabled_order_status")
     * @Template("@FirstTimeOnly4/admin/enabled_order_status.twig")
     */
    public function index(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->createForm(EnabledOrderStatusType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('order_statuses')->getData();
            foreach ($data as $OrderStatus) {
                $this->entityManager->persist($OrderStatus);
            }
            $this->entityManager->flush();

            $cacheUtil->clearCache();

            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('first_time_only_admin_enabled_order_status');
        }

        return [
            'form' => $form->createView()
        ];
    }
}

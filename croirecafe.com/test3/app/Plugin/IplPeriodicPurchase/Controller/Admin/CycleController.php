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

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Controller\AbstractController;
use Plugin\IplPeriodicPurchase\Entity\Cycle;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\CycleType;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\CycleRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicDiscountRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CycleController extends AbstractController
{

    /**
     * CycleController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        CycleRepository $cycleRepository,
        PeriodicDiscountRepository $periodicDiscountRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->cycleRepository = $cycleRepository;
        $this->periodicDiscountRepository = $periodicDiscountRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/periodic/cycle", name="periodic_admin_cycle")
     * @param Request $request
     * @Template("@IplPeriodicPurchase/admin/cycle.twig")
     */
    public function index(Request $request){
        $newCycle = new Cycle();

        $Cycles = $this->cycleRepository
            ->findBy(
                [],
                ['sort_no' => 'DESC']
            );

        $builder = $this->formFactory->createBuilder(CycleType::class,$newCycle);
        $form = $builder->getForm();

        $mode = $request->get('mode');

        if ($mode != 'edit_inline') {
            // 編集の場合はPOST値をフォームに反映しない
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // ソート順を設定
                $CycleSortNo = $this->cycleRepository->findOneBy([], ['sort_no' => 'DESC']);
                $sortNo = 1;
                if ($CycleSortNo) {
                    $sortNo = $CycleSortNo->getSortNo() + 1;
                }

                $newCycle->setSortNo($sortNo);
                $this->entityManager->persist($newCycle);
                $this->entityManager->flush();

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('periodic_admin_cycle');
            }
        }

        // 個別編集用フォーム
        $forms = [];
        $errors = [];

        foreach ($Cycles as $key => $Cycle) {
            $error = 0;
            $builder = $this->formFactory->createBuilder(CycleType::Class,$Cycle);

            $editCycleForm = $builder->getForm();
            if ($mode == 'edit_inline'
                && $request->getMethod() === 'POST'
                && (string) $Cycle->getId() === $request->get('cycle_id')
            ) {
                $orignalCycle = clone $Cycle;
                $editCycleForm->handleRequest($request);
                if ($editCycleForm->isValid()) {
                    $CycleData = $editCycleForm->getData();

                    $this->entityManager->persist($CycleData);
                    $this->entityManager->flush();

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('periodic_admin_cycle');
                }
                $error = count($editCycleForm->getErrors(true));

                // エラーの場合Entityの値に元の値をセット
                if ($error > 0) {
                    $Cycles[$key] = $orignalCycle;
                }
            }

            $forms[$Cycle->getId()] = $editCycleForm->createView();
            $errors[$Cycle->getId()] = $error;
        }

        return [
            'newCycle' => $newCycle,
            'Cycles' => $Cycles,
            'form' => $form->createView(),
            'forms' => $forms,
            'errors' => $errors,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/periodic/cycle/sort_no/move", name="periodic_admin_cycle_sort_no_move", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function moveSortNo(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $cycleId => $sortNo) {
                /** @var Cycle Cycle */
                $Cycle = $this->cycleRepository
                    ->find($cycleId);
                $Cycle->setSortNo($sortNo);
                $this->entityManager->persist($Cycle);
            }
            $this->entityManager->flush();

            return new Response();
        }
    }

    /**
     * @Route("/%eccube_admin_route%/periodic/cycle/{id}/delete", requirements={"id" = "\d+"}, name="periodic_admin_cycle_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cycle $Cycle)
    {
        $this->isTokenValid();

        $sortNo = 1;
        $sortCycles = $this->cycleRepository->findBy([], ['sort_no' => 'ASC']);
        foreach ($sortCycles as $sortCycle) {
            $sortCycle->setSortNo($sortNo++);
        }

        try {
            $this->cycleRepository->delete($Cycle);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.delete_complete', 'admin');
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->entityManager->rollback();

            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => 'ID:'.$Cycle->getId()]);
            $this->addError($message, 'admin');
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            $message = trans('admin.common.delete_error');
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('periodic_admin_cycle');
    }

}
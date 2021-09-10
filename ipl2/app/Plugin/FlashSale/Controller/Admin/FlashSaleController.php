<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Form\Type\Admin\FlashSaleType;
use Plugin\FlashSale\Repository\FlashSaleRepository;
use Plugin\FlashSale\Entity\Promotion;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\FlashSale\Service\FlashSaleService;

class FlashSaleController extends AbstractController
{
    /**
     * @var FlashSaleRepository
     */
    protected $flashSaleRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var FlashSaleService
     */
    protected $flashSaleService;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /**
     * FlashSaleController constructor.
     *
     * @param FlashSaleRepository $flashSaleRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param FlashSaleService $flashSaleService
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        FlashSaleRepository $flashSaleRepository,
        PageMaxRepository $pageMaxRepository,
        FlashSaleService $flashSaleService,
        CategoryRepository $categoryRepository
    ) {
        $this->flashSaleRepository = $flashSaleRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->flashSaleService = $flashSaleService;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/flash_sale/list", name="flash_sale_admin_list")
     * @Route("/%eccube_admin_route%/flash_sale/list/page/{page_no}", requirements={"page_no" = "\d+"}, name="flash_sale_admin_list_page")
     *
     * @Template("@FlashSale/admin/list.twig")
     */
    public function index(Request $request, $page_no = 1, Paginator $paginator)
    {
        $qb = $this->flashSaleRepository->getQueryBuilderAll();

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * 新着情報を登録・編集する。
     *
     * @Route("/%eccube_admin_route%/flash_sale/new", name="flash_sale_admin_new", methods={"POST", "GET"})
     * @Route("/%eccube_admin_route%/flash_sale/{id}/edit", requirements={"id" = "\d+"}, name="flash_sale_admin_edit")
     * @Template("@FlashSale/admin/edit.twig")
     *
     * @param Request $request
     * @param null $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id = null, CacheUtil $cacheUtil)
    {
        $conditionData = [];
        $productClassIds = [];
        $categoryIds = [];
        if ($id) {
            /** @var FlashSale $FlashSale */
            $FlashSale = $this->flashSaleRepository->find($id);
            if (!$FlashSale) {
                throw new NotFoundHttpException();
            }
            $FlashSale->setUpdatedAt(new \DateTime());
        } else {
            $FlashSale = new FlashSale();
            $FlashSale->setCreatedAt(new \DateTime());
            $FlashSale->setUpdatedAt(new \DateTime());
            $FlashSale->setCreatedBy($this->getUser());
        }

        $builder = $this->formFactory
            ->createBuilder(FlashSaleType::class, $FlashSale);

        $form = $builder->getForm();
        $form->handleRequest($request);

        $newConditionForm = json_decode($form->get('rules')->getData(), true);
        foreach ($newConditionForm as $rule) {
            if ($rule['type'] == Rule\ProductClassRule::TYPE && !empty($rule['conditions'])) {
                foreach ($rule['conditions'] as $condition) {
                    if ($condition['type'] == Condition\ProductClassIdCondition::TYPE && !empty($condition['value'])) {
                        $productClassIds = array_merge($productClassIds, explode(',', $condition['value']));
                    }
                    if ($condition['type'] == Condition\ProductCategoryIdCondition::TYPE && !empty($condition['value'])) {
                        $categoryIds = array_merge($categoryIds, explode(',', $condition['value']));
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->beginTransaction();
                $this->flashSaleRepository->save($FlashSale);
                $data = $FlashSale->rawData($form->get('rules')->getData());
                $FlashSale->updateFromArray($data);
                foreach ($FlashSale->getRules() as $Rule) {
                    $Promotion = $Rule->getPromotion();
                    if ($Promotion instanceof Promotion) {
                        if (isset($Promotion->modified)) {
                            $this->entityManager->persist($Promotion);
                        } else {
                            $this->entityManager->remove($Promotion);
                        }
                    }
                    foreach ($Rule->getConditions() as $Condition) {
                        if (isset($Condition->modified)) {
                            $this->entityManager->persist($Condition);
                        } else {
                            $Rule->getConditions()->remove($Condition->getId());
                            $this->entityManager->remove($Condition);
                        }
                    }

                    if (isset($Rule->modified)) {
                        $this->entityManager->persist($Rule);
                    } else {
                        $FlashSale->getRules()->remove($Rule->getId());
                        $this->entityManager->remove($Rule);
                    }
                }
                if (isset($FlashSale->removed)) {
                    foreach ($FlashSale->removed as $removedEntity) {
                        $this->entityManager->remove($removedEntity);
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->commit();
                $this->addSuccess('admin.common.save_complete', 'admin');

                // キャッシュの削除
                $cacheUtil->clearDoctrineCache();

                return $this->redirectToRoute('flash_sale_admin_edit', ['id' => $FlashSale->getId()]);
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->addError('admin.common.save_error', 'admin');
            }
        }

        $builder = $this->formFactory
            ->createBuilder(SearchProductType::class);
        $searchProductModalForm = $builder->getForm();

        $conditionProductClassData = $this->flashSaleService->getProductClassName($productClassIds);
        $conditionData['condition_product_class_id'] = [];
        foreach ($conditionProductClassData as $row) {
            $class_name2 = $row['class_name2'] ? ' - '.$row['class_name2'] : '';
            $row['name'] = $row['name'].(($row['class_name1'] || $row['class_name2']) ? ('('.$row['class_name1'].$class_name2).')' : '');
            $conditionData['condition_product_class_id'][] = $row;
        }
        $conditionData['condition_product_category_id'] = $CategoryName = $this->flashSaleService->getCategoryName($categoryIds);

        return [
            'form' => $form->createView(),
            'FlashSale' => $FlashSale,
            'conditionData' => json_encode($conditionData),
            'metadata' => $this->flashSaleService->getMetadata(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flash_sale/get/category", name="flash_sale_admin_get_category", methods={"GET"})
     * @Template("@FlashSale/admin/template_category.twig")
     */
    public function getCategory(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            $Categories = $this->categoryRepository->getList();

            return [
                'Categories' => $Categories,
            ];
        }
    }

    /**
     * @Route("/%eccube_admin_route%/flash_sale/{id}/delete", requirements={"id" = "\d+"}, name="flash_sale_admin_delete", methods={"DELETE"})
     *
     * @param Request $request
     * @param FlashSale $FlashSale
     * @param CacheUtil $cacheUtil
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, FlashSale $FlashSale, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();

        log_info('新着情報削除開始', [$FlashSale->getId()]);

        try {
            $this->flashSaleRepository->delete($FlashSale);

            $this->addSuccess('admin.common.delete_complete', 'admin');

            log_info('新着情報削除完了', [$FlashSale->getId()]);

            // キャッシュの削除
            $cacheUtil->clearDoctrineCache();
        } catch (\Exception $e) {
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $FlashSale->getName()]);
            $this->addError($message, 'admin');

            log_error('新着情報削除エラー', [$FlashSale->getId(), $e]);
        }

        return $this->redirectToRoute('flash_sale_admin_list');
    }
}

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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Controller\AbstractController;
use Plugin\IplPeriodicPurchase\Entity\PeriodicDiscount;
use Plugin\IplPeriodicPurchase\Form\Type\Admin\ConfigType;
use Plugin\IplPeriodicPurchase\Repository\ConfigRepository;
use Plugin\IplPeriodicPurchase\Repository\PeriodicDiscountRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    protected $periodicDiscountRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        PeriodicDiscountRepository $periodicDiscountRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->periodicDiscountRepository = $periodicDiscountRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/ipl_periodic_purchase/config", name="ipl_periodic_purchase_admin_config")
     * @Template("@IplPeriodicPurchase/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();

        $OriginPeriodicDiscounts = new ArrayCollection();
        if (!empty($Config)) {
            foreach ($Config->getPeriodicDiscount() as $PeriodicDiscount) {
                $OriginPeriodicDiscounts->add($PeriodicDiscount);
            }
        }

        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $PeriodicDiscounts = $Config->getPeriodicDiscount();
            $this->entityManager->persist($Config);
            foreach ($PeriodicDiscounts as $PeriodicDiscount) {
                $this->entityManager->persist($PeriodicDiscount);
            }
            $this->entityManager->flush();

            // 入力欄から消されたらPeriodicDiscountテーブルからも消去
            foreach ($OriginPeriodicDiscounts as $PeriodicDiscount) {
                if ($Config->getPeriodicDiscount()->contains($PeriodicDiscount) === false) {
                    $this->entityManager->remove($PeriodicDiscount);
                }
            }
            $this->entityManager->flush();

            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('ipl_periodic_purchase_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/ipl_periodic_purchase/config/{id}/periodic_discount_delete", requirements={"id" = "\d+"}, name="ipl_periodic_purchase_admin_config_periodic_discount_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PeriodicDiscount $PeriodicDiscount)
    {
        $this->isTokenValid();

        try {

            $this->periodicDiscountRepository->delete($PeriodicDiscount);
            $this->addSuccess('admin.common.delete_complete', 'admin');

        } catch (ForeignKeyConstraintViolationException $e) {

            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => '定期回数別商品金額割引']);
            $this->addError($message, 'admin');
        } catch (\Exception $e) {

            $message = trans('admin.common.delete_error');
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('ipl_periodic_purchase_admin_config');
    }
}

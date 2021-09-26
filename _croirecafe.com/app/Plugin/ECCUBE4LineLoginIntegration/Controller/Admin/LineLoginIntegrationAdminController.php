<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Controller\AbstractController;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegrationSetting;
use Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationSettingRepository;
use Plugin\ECCUBE4LineLoginIntegration\Form\Type\LineLoginSettingType;


class LineLoginIntegrationAdminController extends AbstractController
{
    private $lineIntegrationSettingRepository;
    const LINE_LOGIN_INTEGRATION_SETTING_TABLE_ID = 1;

    public function __construct(LineLoginIntegrationSettingRepository $lineIntegrationSettingRepository)
    {
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
    }

    /**
     * 設定画面の表示
     *
     * @Route("/%eccube_admin_route%/plugin_line_login_setting/", name="plugin_line_login_setting")
     * @Template("@ECCUBE4LineLoginIntegration/admin/setting.twig")
     * @param Request $request
     * @return array
     */
    public function setting(Request $request)
    {
        $lineIntegrationSetting = $this->getLineLoginIntegrationSetting();
        $form = $this->createForm(LineLoginSettingType::class, $lineIntegrationSetting);
        $form->handleRequest($request);

        return ['form' => $form->createView()];
    }

    /**
     * 設定の更新処理
     *
     * @Route("/%eccube_admin_route%/plugin_line_login_setting/commit", name="plugin_line_login_setting_commit")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function commit(Request $request)
    {
        // POST以外はエラーにする
        if ('POST' !== $request->getMethod()) {
            throw new MethodNotAllowedHttpException();
        }

        $postParameters = $request->request->get('line_login_setting');
        $lineChannelId = trim($postParameters['line_channel_id']);
        $lineChannelSecret = trim($postParameters['line_channel_secret']);

        $lineIntegrationSetting = $this->getLineLoginIntegrationSetting();
        if (empty($lineIntegrationSetting)) {
            $lineIntegrationSetting = new LineLoginIntegrationSetting();
        }
        $lineIntegrationSetting->setId(self::LINE_LOGIN_INTEGRATION_SETTING_TABLE_ID);
        $lineIntegrationSetting->setLineChannelId($lineChannelId);
        $lineIntegrationSetting->setLineChannelSecret($lineChannelSecret);
        $this->entityManager->persist($lineIntegrationSetting);
        $this->entityManager->flush();

        $this->addSuccess('admin.common.save_complete', 'admin');
        return $this->redirectToRoute('plugin_line_login_setting');
    }

    /**
     * LINEの設定を読み込む
     */
    private function getLineLoginIntegrationSetting()
    {
        $lineIntegrationSetting = $this->lineIntegrationSettingRepository
            ->find(self::LINE_LOGIN_INTEGRATION_SETTING_TABLE_ID);
        return $lineIntegrationSetting;
    }
}

<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ECCUBE4LineLoginIntegration\Form\Extension;

use Eccube\Form\Type\Front\EntryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Plugin\ECCUBE4LineLoginIntegration\Controller\LineLoginIntegrationController;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegration;

class MypageChangeLineLoginExtension extends AbstractTypeExtension
{
    private $container;
    private $session;
    private $lineIntegration;
    private $tokenStorage;

    public function __construct(
        LineLoginIntegration $lineIntegration,
        TokenStorageInterface $tokenStorage,
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->lineIntegration = $lineIntegration;
        $this->tokenStorage = $tokenStorage;
        $this->session = $this->container->get('session');

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // LINEにログインしていなくても連携を解除できるようにするにはこの条件を見直す($options['data']->getId)
        // LINEログインしている場合に表示
        $lineUserId = $this->session->get(LineLoginIntegrationController::PLUGIN_LINE_LOGIN_INTEGRATION_SSO_USERID);
        if (!empty($lineUserId)) {
            $builder
                ->add('is_line_delete', CheckboxType::class, [
                    'required' => false,
                    'label' => '解除',
                    'mapped' => false,
                    'value' => '0',
                ]);
        }
    }

    public function getExtendedType()
    {
        return EntryType::class;
    }
}

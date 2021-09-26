<?php

/*
 * This file is part of PostCarrier for EC-CUBE
 *
 * Copyright(c) IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\PostCarrier4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\PostCarrier4\Entity\PostCarrierConfig;
use Plugin\PostCarrier4\Entity\PostCarrierGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        $this->srcRoutesFile = __DIR__ . '/Resource/config/routes.yaml';
        $this->dstRoutesFile = '/app/config/eccube/routes/postcarrier_routes.yaml';
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        // routes.yaml をコピー
        $fs = new Filesystem();
        $fs->copy($this->srcRoutesFile, $projectDir.$this->dstRoutesFile);

        $em = $container->get('doctrine.orm.entity_manager');

        // メルマガ会員グループを作成
        $this->createMailmagaGroup($em);
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        // routes.yaml を削除
        $fs = new Filesystem();
        $r = $fs->remove($projectDir.$this->dstRoutesFile);
    }

    protected function createMailmagaGroup(EntityManagerInterface $em)
    {
        $Group = $em->find(PostCarrierGroup::class, 1);
        if ($Group) {
            return;
        }
        $Group = new PostCarrierGroup();
        $Group->setGroupName('メールアドレスのみ会員グループ');
        $Group->setUpdateDate(new \DateTime());
        $em->persist($Group);
        $em->flush($Group);
    }
}

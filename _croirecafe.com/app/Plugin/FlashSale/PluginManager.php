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

namespace Plugin\FlashSale;

use Eccube\Common\Constant;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var string
     */
    private $originBlock;
    /**
     * @var string
     */
    private $blockName = 'Flash Sale';
    /**
     * @var string
     */
    private $blockFileName = 'flash_sale';

    /**
     * @var array
     */
    private $mailTemplate = [
        'order.twig',
        'order.html.twig',
    ];

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        $this->originBlock = __DIR__.'/Resource/template/front/block/'.$this->blockFileName.'.twig';
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->removeDataBlock($container);
    }

    /**
     * @param null|array $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta = null, ContainerInterface $container)
    {
        $this->copyMailTemplate($container);
        $this->copyBlock($container);
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);
        if (is_null($Block)) {
            $this->createDataBlock($container);
        }
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function disable(array $meta = null, ContainerInterface $container)
    {
        $this->removeMailTemplate($container);
        $this->removeDataBlock($container);
        $this->removeBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta = null, ContainerInterface $container)
    {
        $this->copyMailTemplate($container);
        $this->copyBlock($container);
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $DeviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);
        try {
            /** @var Block $Block */
            $Block = $container->get(BlockRepository::class)->newBlock($DeviceType);
            $Block->setName($this->blockName)
                ->setFileName($this->blockFileName)
                ->setUseController(Constant::DISABLED);
            $em->persist($Block);
            $em->flush($Block);
            // check exists block position
            $blockPos = $container->get(BlockPositionRepository::class)->findOneBy(['Block' => $Block]);
            if ($blockPos) {
                return;
            }
            $blockPos = $container->get(BlockPositionRepository::class)->findOneBy(
                ['section' => Layout::TARGET_ID_CONTENTS_TOP, 'layout_id' => Layout::DEFAULT_LAYOUT_TOP_PAGE],
                ['block_row' => 'DESC']
            );
            $BlockPosition = new BlockPosition();
            $BlockPosition->setBlockRow(1);
            if ($blockPos) {
                $blockRow = $blockPos->getBlockRow() + 1;
                $BlockPosition->setBlockRow($blockRow);
            }
            /** @var Layout $LayoutDefault */
            $LayoutDefault = $container->get(LayoutRepository::class)->find(Layout::DEFAULT_LAYOUT_TOP_PAGE);
            $BlockPosition->setLayout($LayoutDefault)
                ->setLayoutId($LayoutDefault->getId())
                ->setSection(Layout::TARGET_ID_CONTENTS_TOP)
                ->setBlock($Block)
                ->setBlockId($Block->getId());
            $em->persist($BlockPosition);
            $LayoutDefault->addBlockPosition($BlockPosition);
            $em->flush($BlockPosition);
            $em->flush($LayoutDefault);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        /** @var \Eccube\Entity\Block $Block */
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);
        if (!$Block) {
            return;
        }
        $em = $container->get('doctrine.orm.entity_manager');
        try {
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $em->remove($BlockPosition);
            }
            $em->remove($Block);
            $em->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->copy($this->originBlock, $templateDir.'/Block/'.$this->blockFileName.'.twig');
    }

    /**
     * Remove block template.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir.'/Block/'.$this->blockFileName.'.twig');
    }

    /**
     * @param ContainerInterface $container
     */
    private function copyMailTemplate(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $mailFolder = __DIR__.'/Resource/template/default/Mail/';

        $file = new Filesystem();
        foreach ($this->mailTemplate as $item) {
            $file->copy($mailFolder.$item, $templateDir.'/Mail/'.$item, true);
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function removeMailTemplate(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        foreach ($this->mailTemplate as $item) {
            $file->remove($templateDir.'/Mail/'.$item);
        }
    }
}

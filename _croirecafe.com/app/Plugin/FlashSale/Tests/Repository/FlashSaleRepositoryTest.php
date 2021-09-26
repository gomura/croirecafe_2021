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

namespace Plugin\FlashSale\Tests\Repository;

use Plugin\FlashSale\Entity\FlashSale;

/**
 * Class FlashSaleRepositoryTest
 */
class FlashSaleRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
    }

    public function testAvailableFlashSale()
    {
        $this->createFS('Test');
        $FlashSale = $this->flashSaleRepository->getAvailableFlashSale();
        $this->expected = 'Test';
        $this->actual = $FlashSale->getName();
        $this->verify();
    }

    public function testDelete()
    {
        // make sure created success
        $FlashSale = $this->createFS('Create to delete - test only');
        $countAll = $this->flashSaleRepository->count(['status' => FlashSale::STATUS_ACTIVATED]);

        $this->expected = 1;
        $this->actual = $countAll;
        $this->verify();

        // Test delete
        $this->flashSaleRepository->delete($FlashSale);
        $countActivated = $this->flashSaleRepository->count(['status' => FlashSale::STATUS_ACTIVATED]);
        $this->expected = 0;
        $this->actual = $countActivated;
        $this->verify();
    }

    public function testSave()
    {
        $faker = $this->getFaker();
        $name = $faker->name;
        $FlashSale = new FlashSale();
        $FlashSale->setName($name);
        $FlashSale->setFromTime(new \DateTime((date('Y-m-d')).' 00:00:00'));
        $FlashSale->setToTime(new \DateTime((date('Y-m-d')).' 23:59:59'));
        $FlashSale->setStatus(FlashSale::STATUS_ACTIVATED);
        $FlashSale->setCreatedAt(new \DateTime());
        $FlashSale->setUpdatedAt(new \DateTime());
        $this->flashSaleRepository->save($FlashSale);

        $this->expected = $name;
        $this->actual = $this->flashSaleRepository->findOneBy(['name' => $name])->getName();
        $this->verify();
    }
}

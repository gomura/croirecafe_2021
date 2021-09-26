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

namespace Plugin\FlashSale\Test\Web;

use Eccube\Entity\ProductClass;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Repository\FlashSaleRepository;
use Plugin\FlashSale\Service\Operator\InOperator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FlashSaleControllerTest.
 */
class FlashSaleControllerTest extends AbstractAdminWebTestCase
{
    /** @var FlashSaleRepository */
    protected $flashSaleRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->flashSaleRepository = $this->container->get(FlashSaleRepository::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->createFlashSaleAndRules($i);
        }
    }

    public function testRoute()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('flash_sale_admin_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains('FlashSale', $crawler->html());
    }

    public function testCreate()
    {
        $faker = $this->getFaker('en_US');

        $flash_sale_admin = [
            '_token' => 'dummy',
            'name' => $faker->name(),
            'description' => $faker->text(),
            'from_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 0,
                    'minute' => 0,
                ],
            ],
            'to_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 23,
                    'minute' => 59,
                ],
            ],
            'rules' => json_encode($this->rulesData()),
            'status' => FlashSale::STATUS_ACTIVATED,
        ];

        $this->client->request(
            'POST',
            $this->generateUrl('flash_sale_admin_new'),
            [
                'flash_sale_admin' => $flash_sale_admin,
            ]
        );

        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->expected = '保存しました';
        $this->actual = $crawler->filter('.c-contentsArea .alert-success span')->text();
        $this->verify();
    }

    public function testUpdate()
    {
        $faker = $this->getFaker('en_US');
        $newName = $faker->name().rand(111, 999);
        $rules[] = $this->rulesData();

        $flash_sale_admin = [
            '_token' => 'dummy',
            'name' => $newName,
            'description' => $faker->text(),
            'from_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 0,
                    'minute' => 0,
                ],
            ],
            'to_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 23,
                    'minute' => 59,
                ],
            ],
            'rules' => json_encode($rules),
            'status' => FlashSale::STATUS_ACTIVATED,
        ];

        /** @var FlashSale $FlashSale */
        $FlashSale = $this->flashSaleRepository->findOneBy([]);

        $this->client->request(
            'POST',
            $this->generateUrl('flash_sale_admin_edit', ['id' => $FlashSale->getId()]),
            [
                'flash_sale_admin' => $flash_sale_admin,
            ]
        );

        /** @var FlashSale $newFlashSale */
        $newFlashSale = $this->flashSaleRepository->findOneBy(['id' => $FlashSale->getId()]);
        $this->expected = $newName;
        $this->actual = $newFlashSale->getName();
        $this->verify();

        $this->expected = $FlashSale->getFromTime();
        $this->actual = $newFlashSale->getFromTime();
        $this->verify();
    }

    public function testUpdateNotFound()
    {
        $faker = $this->getFaker('en_US');
        $newName = $faker->name().rand(111, 999);
        $rules[] = $this->rulesData();

        $flash_sale_admin = [
            '_token' => 'dummy',
            'name' => $newName,
            'description' => $faker->text(),
            'from_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 0,
                    'minute' => 0,
                ],
            ],
            'to_time' => [
                'date' => [
                    'year' => 2019,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 23,
                    'minute' => 59,
                ],
            ],
            'rules' => json_encode($rules),
            'status' => FlashSale::STATUS_ACTIVATED,
        ];

        $this->client->request(
            'POST',
            $this->generateUrl('flash_sale_admin_edit', ['id' => 99999999]),
            [
                'flash_sale_admin' => $flash_sale_admin,
            ]
        );

        $this->expected = Response::HTTP_NOT_FOUND;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testDelete()
    {
        $FlashSales = $this->flashSaleRepository->findBy(['status' => FlashSale::STATUS_ACTIVATED]);
        $count = count($FlashSales);

        $this->client->request(
            'DELETE',
            $this->generateUrl('flash_sale_admin_delete', ['id' => $FlashSales[0]->getId()])
        );

        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->expected = '削除しました';
        $this->actual = $crawler->filter('.c-contentsArea .alert-success span')->text();

        $countResult = $this->flashSaleRepository->findBy(['status' => FlashSale::STATUS_ACTIVATED]);
        $this->expected = $count - 1;
        $this->actual = count($countResult);
        $this->verify();
    }

    public function testDeleteFail()
    {
        $this->client->request(
            'DELETE',
            $this->generateUrl('flash_sale_admin_delete', ['id' => 999999])
        );

        $this->expected = Response::HTTP_NOT_FOUND;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testGetCategory()
    {
        $this->client->request(
            'GET',
            $this->generateUrl('flash_sale_admin_get_category'),
            [],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->expected = Response::HTTP_OK;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function createFlashSaleAndRules($i)
    {
        $rules['rules'] = $this->rulesData();

        $FlashSale = new FlashSale();
        $FlashSale->setName('SQL-scrip-001');
        $FlashSale->setFromTime(new \DateTime((date('Y') + $i).'-09-10 00:30:00'));
        $FlashSale->setToTime(new \DateTime((date('Y') + $i).'-09-10 23:59:59'));
        $FlashSale->setStatus(FlashSale::STATUS_ACTIVATED);
        $FlashSale->setCreatedAt(new \DateTime());
        $FlashSale->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($FlashSale);
        $this->entityManager->flush($FlashSale);

        $FlashSale->updateFromArray($rules);
        foreach ($FlashSale->getRules() as $Rule) {
            $Promotion = $Rule->getPromotion();
            if ($Promotion instanceof Promotion) {
                if (isset($Rule->modified)) {
                    $this->entityManager->persist($Promotion);
                } else {
                    $this->entityManager->remove($Promotion);
                }
            }
            foreach ($Rule->getConditions() as $Condition) {
                if (isset($Rule->modified)) {
                    $this->entityManager->persist($Condition);
                } else {
                    $this->entityManager->remove($Condition);
                }
            }

            if (isset($Rule->modified)) {
                $this->entityManager->persist($Rule);
            } else {
                $this->entityManager->remove($Rule);
            }
        }
        $this->entityManager->flush();

        return $FlashSale;
    }

    public function rulesData()
    {
        $Product = $this->createProduct();
        $productClassIds = [];
        /** @var ProductClass $productClass */
        foreach ($Product->getProductClasses() as $productClass) {
            $productClassIds[] = $productClass->getId();
        }

        $rules[] = [
            'id' => '',
            'type' => ProductClassRule::TYPE,
            'operator' => InOperator::TYPE,
            'promotion' => [
                'id' => '',
                'type' => Promotion\ProductClassPricePercentPromotion::TYPE,
                'value' => 30,
            ],
            'conditions' => [
                [
                    'id' => '',
                    'type' => ProductClassIdCondition::TYPE,
                    'operator' => InOperator::TYPE,
                    'value' => implode(',', $productClassIds),
                ],
            ],
        ];

        return $rules;
    }
}

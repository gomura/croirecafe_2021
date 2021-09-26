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
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Service\Operator\InOperator;
use Plugin\FlashSale\Service\Operator\OrOperator;

/**
 * Class FlashSaleBlockTest
 */
class FlashSaleBlockTest extends AbstractWebTestCase
{
    /**
     * test up
     */
    public function setUp()
    {
        parent::setUp();

        for ($i = 1; $i < 5; $i++) {
            $this->createFlashSaleAndRules($i);
        }

        $this->entityManager->clear();
    }

    public function testList()
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('homepage')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->expected = 'product name1';
        $this->actual = $crawler->filter('#flash-sale')->text();
        self::assertContains($this->expected, $this->actual);
    }

    protected function createFlashSaleAndRules($i)
    {
        $rules['rules'] = $this->rulesData($i);

        $FlashSale = new FlashSale();
        $FlashSale->setName('Flash Sale');
        $FlashSale->setFromTime((new \DateTime())->modify("-{$i} days"));
        $FlashSale->setToTime((new \DateTime())->modify("+{$i} days"));
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

    protected function rulesData($i)
    {
        $Product = $this->createProduct('product name'.$i);
        $productClassIds = [];
        /** @var ProductClass $productClass */
        foreach ($Product->getProductClasses() as $productClass) {
            $productClassIds[] = $productClass->getId();
        }

        $rules[] = [
            'id' => '',
            'type' => ProductClassRule::TYPE,
            'operator' => OrOperator::TYPE,
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

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

namespace Plugin\FlashSale\Tests\Entity;

use Eccube\Tests\EccubeTestCase;
use Plugin\FlashSale\Entity\Condition;
use Plugin\FlashSale\Entity\Promotion;
use Plugin\FlashSale\Entity\Rule;
use Plugin\FlashSale\Service\Condition\ConditionFactory;
use Plugin\FlashSale\Service\Promotion\PromotionFactory;

abstract class RuleTest extends EccubeTestCase
{
    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid_Json($expected)
    {
        $actual = $this->rule->rawData(json_encode($expected));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $expected
     * @dataProvider dataProvider_testRawData_Valid
     */
    public function testRawData_Valid($expected)
    {
        $this->rule->setId($expected['id']);
        $this->rule->setOperator($expected['operator']);

        /** @var Promotion $promotion */
        $promotion = $this->container->get(PromotionFactory::class)->createFromArray(['type' => $expected['promotion']['type']]);
        $promotion->setId($expected['promotion']['id']);
        $promotion->setValue($expected['promotion']['value']);
        $this->rule->setPromotion($promotion);

        foreach ($expected['conditions'] as $conditionData) {
            /** @var Condition $condition */
            $condition = $this->container->get(ConditionFactory::class)->createFromArray(['type' => $conditionData['type']]);
            $condition->setId($conditionData['id']);
            $condition->setOperator($conditionData['operator']);
            $condition->setValue($conditionData['value']);
            $this->rule->addConditions($condition);
        }

        $actual = $this->rule->rawData();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProvider_testRawData_Valid()
    {
        return [];
    }
}

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

namespace Plugin\FlashSale\Tests;

class DataProvider
{
    protected $greaterThanOperatorTest = [
        'testMatch' => [
            'true#1' => [
                'data' => 10,
                'args' => [200],
                'expected' => true,
            ],
        ],
    ];

    protected $cartTotalConditionTest = [
        'testMatch' => [
            'true#1' => [
                'data' => [
                    'id' => 1,
                    'operator' => 'operator_greater_than',
                    'value' => '$greaterThanOperatorTest[testMatch][true#1][data]',
                ],
                'args' => '$greaterThanOperatorTest[testMatch][true#1][args]',
                'expected' => '$greaterThanOperatorTest[testMatch][true#1][expected]',
            ],
        ],
    ];

    protected $dataSet = [
        'CartTotalCondition' => [
            'testMatch' => [
                'true#1' => [
                    'data' => [
                        'id' => 1,
                        'operator' => 'operator_greater_than',
                        'value' => '#GreaterThanOperator[testMatch][true#1][data]',
                    ],
                    'args' => '#GreaterThanOperator[testMatch][true#1][args]',
                    'expected' => '#GreaterThanOperator[testMatch][true#1][expected]',
                ],
            ],
        ],
        'GreaterThanOperator' => [
            'testMatch' => [
                'true#1' => [
                    '$conditionData' => 10,
                    '$data' => [10, 16],
                    '$expected' => true,
                ],
                'true#2' => [
                    'data' => 10,
                    'args' => [20, 100],
                    'expected' => true,
                ],
            ],
        ],
    ];

    public function parse($id, $case)
    {
    }
}

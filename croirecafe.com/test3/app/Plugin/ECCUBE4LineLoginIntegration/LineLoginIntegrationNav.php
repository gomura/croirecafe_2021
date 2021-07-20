<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ECCUBE4LineLoginIntegration;

use Eccube\Common\EccubeNav;

class LineLoginIntegrationNav implements EccubeNav
{
    public static function getNav()
    {
        return [
            'plugin_line_login_integration' => [
                'name' => 'LINE管理',
                'icon' => 'fa-commenting',
                'has_child' => true,
                'children' => [
                    'plugin_line_login_setting' => [
                        'name' => 'ログイン設定',
                        'url' => 'plugin_line_login_setting',
                    ],
                ],
            ],
        ];
    }
}

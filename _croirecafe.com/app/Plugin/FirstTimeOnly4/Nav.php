<?php
/**
 * This file is part of FirstTimeOnly4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 *  https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FirstTimeOnly4;


use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{

    /**
     * @inheritDoc
     */
    public static function getNav()
    {
        return [
            'first_time_only' => [
                'name' => 'plugin.first_time_only.admin.title',
                'icon' => 'fa-shopping-cart',
                'children' => [
                    'config' => [
                        'name' => 'plugin.first_time_only.admin.enabled_order_statut.sub_title',
                        'url' => 'first_time_only_admin_enabled_order_status'
                    ]
                ]
            ]
        ];
    }
}

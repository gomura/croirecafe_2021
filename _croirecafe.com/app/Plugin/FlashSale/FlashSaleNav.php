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

use Eccube\Common\EccubeNav;

class FlashSaleNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'flash_sales_admin' => [
                        'url' => 'flash_sale_admin_list',
                        'name' => 'flash_sale.admin.nav.name',
                    ],
                ],
            ],
        ];
    }
}

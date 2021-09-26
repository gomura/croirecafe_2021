<?php

/*
 * RepeatCube for EC-CUBE4
 * Copyright(c) 2019 IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * This program is not free software.
 * It applies to terms of service.
 *
 */
namespace Plugin\IplPeriodicPurchase;

use Eccube\Common\EccubeNav;

class IplPeriodicPurchaseNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'periodic_purchase' => [
                'name' => 'ipl_periodic_purchase.admin.nav.periodic_purchase',
                'icon' => 'fa-calendar-alt',
                'children' => [
                    'periodic_admin_order' => [
                        'name' => 'ipl_periodic_purchase.admin.nav.order',
                        'url' => 'periodic_admin_order',
                    ],
                    'periodic_admin_cycle' => [
                        'name' => 'ipl_periodic_purchase.admin.nav.cycle',
                        'url' => 'periodic_admin_cycle',
                    ]
                ]
            ],
        ];
    }
}

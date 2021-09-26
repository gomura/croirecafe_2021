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

$loader = require __DIR__.'/../../../../vendor/autoload.php';

foreach (glob(__DIR__.'/../../../../app/proxy/entity/*.php') as $file) {
    require_once $file;
}

$envFile = __DIR__.'/../../../../.env';
if (file_exists($envFile)) {
    (new \Symfony\Component\Dotenv\Dotenv())->load($envFile);
}

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

namespace Plugin\FlashSale\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait FSOrderItemTrait
{
    use AbstractItemTrait;

    /**
     * @var float
     * @ORM\Column(name="fs_price", type="decimal", precision=12, scale=2, nullable=true, options={"default":0})
     */
    private $fsPrice;

    /**
     * @return float
     */
    public function getFsPrice()
    {
        return $this->fsPrice;
    }

    /**
     * @param float $fsPrice
     */
    public function setFsPrice(float $fsPrice): void
    {
        $this->fsPrice = $fsPrice;
    }

    /**
     * @return float
     */
    public function getFsPriceTotal()
    {
        return (is_null($this->getFsPrice()) ? $this->getPriceIncTax() : $this->getFsPrice()) * $this->getQuantity();
    }
}

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

namespace Plugin\FirstTimeOnly4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\OrderStatus;

/**
 * Class EnableOrderStatus
 * @package Plugin\FirstTimeOnly4\Entity
 *
 * @ORM\Table(name="plg_first_time_only_enabled_order_status")
 * @ORM\Entity(repositoryClass="Plugin\FirstTimeOnly4\Repository\EnabledOrderStatusRepository")
 */
class EnabledOrderStatus
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $enabled;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\OrderStatus")
     * @ORM\JoinColumn(nullable=false)
     */
    private $OrderStatus;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool|null
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return OrderStatus|null
     */
    public function getOrderStatus(): ?OrderStatus
    {
        return $this->OrderStatus;
    }

    /**
     * @param OrderStatus $orderStatus
     * @return $this
     */
    public function setOrderStatus(OrderStatus $orderStatus): self
    {
        $this->OrderStatus = $orderStatus;

        return $this;
    }
}

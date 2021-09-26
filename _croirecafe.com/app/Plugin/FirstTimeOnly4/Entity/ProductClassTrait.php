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
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait {

    /**
     * @ORM\Column(name="first_time_only", type="boolean", options={"default":false}, nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=true,
     *  type="\Eccube\Form\Type\ToggleSwitchType",
     *  options={
     *    "label": "初回購入限定"
     *  }
     * )
     */
    private $first_time_only;

    public function isFirstTimeOnly()
    {
        return $this->first_time_only;
    }

    public function setFirstTimeOnly($first_time_only)
    {
        $this->first_time_only = $first_time_only;

        return $this;
    }
}

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

namespace Plugin\FlashSale\Service\Metadata;

class Discriminator implements DiscriminatorInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $description;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set $type
     *
     * @param $type
     *
     * @return Discriminator
     */
    public function setType($type): Discriminator
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set $name
     *
     * @param $name
     *
     * @return Discriminator
     */
    public function setName($name): Discriminator
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set $description
     *
     * @param $description
     *
     * @return Discriminator
     */
    public function setDescription($description): Discriminator
    {
        $this->description = $description;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set $class
     *
     * @param $class
     *
     * @return Discriminator
     */
    public function setClass($class): Discriminator
    {
        $this->class = $class;

        return $this;
    }
}

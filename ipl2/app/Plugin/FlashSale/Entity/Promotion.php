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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\FlashSale\Repository\PromotionRepository;
use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Entity\Promotion\ProductClassPriceAmountPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalAmountPromotion;
use Plugin\FlashSale\Entity\Promotion\CartTotalPercentPromotion;
use Plugin\FlashSale\Service\Promotion\PromotionInterface;

/**
 * @ORM\Table("plg_flash_sale_promotion")
 * @ORM\Entity(repositoryClass=PromotionRepository::class)
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\DiscriminatorMap({
 *     ProductClassPricePercentPromotion::TYPE=ProductClassPricePercentPromotion::class,
 *     ProductClassPriceAmountPromotion::TYPE=ProductClassPriceAmountPromotion::class,
 *     CartTotalAmountPromotion::TYPE=CartTotalAmountPromotion::class,
 *     CartTotalPercentPromotion::TYPE=CartTotalPercentPromotion::class,
 * })
 */
abstract class Promotion extends AbstractEntity implements PromotionInterface
{
    const TYPE = 'promotion';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", nullable=false)
     */
    protected $value;

    /**
     * @var Rule
     *
     * @ORM\OneToOne(targetEntity=Rule::class, inversedBy="Promotion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rule_id", referencedColumnName="id")
     * })
     */
    protected $Rule;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return Rule
     */
    public function getRule(): Rule
    {
        return $this->Rule;
    }

    /**
     * @param Rule $Rule
     */
    public function setRule(Rule $Rule): void
    {
        $this->Rule = $Rule;
    }

    /**
     * Get data as array
     *
     * @param null $data
     *
     * @return array
     */
    public function rawData($data = null)
    {
        $result = [];
        if ($data) {
            $result = json_decode($data, true);
        } else {
            $result['id'] = $this->getId();
            $result['type'] = static::TYPE;
            $result['value'] = $this->getValue();
        }

        return $result;
    }

    /**
     * Set $entityManager
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return $this
     * @required
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}

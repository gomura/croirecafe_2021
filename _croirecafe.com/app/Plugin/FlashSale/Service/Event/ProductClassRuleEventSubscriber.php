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

namespace Plugin\FlashSale\Service\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\TemplateEvent;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Common\EccubeConfig;

class ProductClassRuleEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var \NumberFormatter
     */
    protected $formatter;

    /**
     * ProductClassRuleEventSubscriber constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->formatter = new \NumberFormatter($this->eccubeConfig['locale'], \NumberFormatter::CURRENCY);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Product/detail.twig' => 'onTemplateProductDetail',
            'Product/list.twig' => 'onTemplateProductList',
        ];
    }

    /**
     * Change price of product depend on flash sale
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductDetail(TemplateEvent $event)
    {
        if (!$event->hasParameter('Product')) {
            return;
        }

        $json = [];

        /** @var ProductClass $ProductClass */
        foreach ($event->getParameter('Product')->getProductClasses() as $ProductClass) {
            $discountValue = $ProductClass->getFlashSaleDiscount();
            if ($discountValue) {
                $discountPrice = $ProductClass->getFlashSaleDiscountPrice();
                $discountPercent = $ProductClass->getFlashSaleDiscountPercent();
                $json[$ProductClass->getId()] = [
                    'message' => '<p class="ec-color-red"><span>'.$this->formatter->formatCurrency($discountPrice, $this->eccubeConfig['currency']).'</span> (-'.$discountPercent.'%)</p>',
                ];
            }
        }

        if (empty($json)) {
            return;
        }

        $event->setParameter('ProductFlashSale', json_encode($json));
        $event->addSnippet('@FlashSale/default/detail.twig');
    }

    /**
     * Change price of product depend on flash sale
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductList(TemplateEvent $event)
    {
        if (!$event->hasParameter('pagination')) {
            return;
        }

        $json = [];

        /** @var Product $Product */
        foreach ($event->getParameter('pagination') as $Product) {
            /** @var ProductClass $ProductClass */
            foreach ($Product->getProductClasses() as $ProductClass) {
                $discountValue = $ProductClass->getFlashSaleDiscount();
                if ($discountValue) {
                    $discountPrice = $ProductClass->getFlashSaleDiscountPrice();
                    $discountPercent = $ProductClass->getFlashSaleDiscountPercent();
                    $json[$ProductClass->getId()] = [
                        'message' => '<p class="ec-color-red"><span>'.$this->formatter->formatCurrency($discountPrice, $this->eccubeConfig['currency']).'</span> (-'.$discountPercent.'%)</p>',
                    ];
                }
            }
        }

        if (empty($json)) {
            return;
        }

        $event->setParameter('ProductFlashSale', json_encode($json));
        $event->addSnippet('@FlashSale/default/list.twig');
    }
}

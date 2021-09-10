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

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Cart;
use Eccube\Entity\CustomerFavoriteProduct;
use Eccube\Entity\ProductClass;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\BaseInfoRepository;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Repository\FlashSaleRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Twig\Extension\CartServiceExtension;

/**
 * Class FlashSaleEvent
 */
class FlashSaleEvent implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var FlashSaleRepository
     */
    protected $flashSaleRepository;

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

    /**
     * FlashSaleEvent constructor.
     *
     * @param \Twig_Environment $twig
     * @param FlashSaleRepository $flashSaleRepository
     * @param BaseInfoRepository $baseInfoRepository
     *
     * @throws \Exception
     */
    public function __construct(
        \Twig_Environment $twig,
        FlashSaleRepository $flashSaleRepository,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->twig = $twig;
        $this->flashSaleRepository = $flashSaleRepository;
        $this->baseInfo = $baseInfoRepository->get();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Mypage/history.twig' => ['mypageHistory', 0],
            'Mypage/index.twig' => ['mypageIndex', 0],
            'Mypage/favorite.twig' => ['mypageFavorite', 0],
            'Block/header.twig' => ['onTemplateBlockHeader', 0],
            'Block/cart.twig' => [
                ['onTemplateBlockCart', 0],
                ['onTemplateTotalPrice', 100],
            ],
            'Cart/index.twig' => [
                ['onTemplateCartIndex', 0],
                ['onTemplateTotalPrice', 100],
            ],
            'Shopping/index.twig' => ['onTemplateShoppingIndex', 0],
            'Shopping/shipping_multiple.twig' => ['onTemplateShoppingIndex', 0],
            'Shopping/confirm.twig' => ['onTemplateShoppingIndex', 0],
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function mypageHistory(TemplateEvent $event)
    {
        $source = $event->getSource();
        $target = '{{ orderItem.price_inc_tax|price }}';
        $change = "{% if orderItem.fs_price is defined and orderItem.fs_price is not null %}<del>{{orderItem.price_inc_tax|price}}</del><span class='ec-color-red'>{{orderItem.fs_price|price}}</span>{% else %}{$target}{% endif %}";
        $source = str_replace($target, $change, $source);
        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function mypageIndex(TemplateEvent $event)
    {
        $source = $event->getSource();
        $target = '{{ OrderItem.price_inc_tax|price }}';
        $change = "{% if OrderItem.fs_price is defined and OrderItem.fs_price is not null %}<del>{{OrderItem.price_inc_tax|price}}</del><span class='ec-color-red'>{{OrderItem.fs_price|price}}</span>{% else %}{$target}{% endif %}";
        $source = str_replace($target, $change, $source);

        $target = 'Order.MergedProductOrderItems';
        $change = 'Order.FsMergedProductOrderItems';
        $source = str_replace($target, $change, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function mypageFavorite(TemplateEvent $event)
    {
        $FlashSale = $this->flashSaleRepository->getAvailableFlashSale();
        if (!$event->hasParameter('pagination') || !$FlashSale instanceof FlashSale) {
            return;
        }

        $source = $event->getSource();

        // check match price section
        preg_match('/<p class="ec-favoriteRole__itemPrice">/u', $source, $match, PREG_OFFSET_CAPTURE);
        if (empty($match)) {
            return;
        }
        $index = current($match)[1];
        $firstSection = substr($source, 0, $index);
        $endSection = substr($source, $index);

        // end of price section
        preg_match('/<\/p>/u', $endSection, $match2, PREG_OFFSET_CAPTURE);
        if (empty($match2)) {
            return;
        }

        $index2 = current($match2)[1];
        $middleSection = substr($endSection, 0, $index2);
        $endSection = substr($endSection, $index2);

        $insert = <<<EOT
        {% if fs_data is defined and fs_data[Product.id] is defined %}
<p id="discount" class="ec-color-red ec-font-size-4">
    {{ 'flash_sale.front.sale_up_to'|trans({'%percent%' : fs_data[Product.id] }) }}
</p>
{%endif%}
EOT;
        $newSource = $firstSection.$middleSection.$insert.$endSection;
        $event->setSource($newSource);

        // calculate percent
        $data = [];
        $pagination = $event->getParameter('pagination');
        /** @var CustomerFavoriteProduct $ProductFavorite */
        foreach ($pagination as $ProductFavorite) {
            $Product = $ProductFavorite->getProduct();
            $tmp = [];
            /** @var ProductClass $ProductClass */
            foreach ($Product->getProductClasses() as $ProductClass) {
                $tmp[$ProductClass->getId()] = $ProductClass->getFlashSaleDiscountPercent();
            }
            if (count($tmp)) {
                $data[$Product->getId()] = max($tmp);
            }
        }

        if (empty($data)) {
            return;
        }

        $event->setParameter('fs_data', $data);
    }

    /**
     * Make Block/cart.twig able to dispatch template event
     *
     * @param TemplateEvent $event
     */
    public function onTemplateBlockHeader(TemplateEvent $event)
    {
        $source = $event->getSource();
        $source = str_replace(
            '{{ include(\'Block/cart.twig\') }}',
            '{{ include_dispatch(\'Block/cart.twig\') }}',
            $source
        );
        $event->setSource($source);
    }

    /**
     * Display price of flashsale on block cart template
     *
     * @param TemplateEvent $event
     */
    public function onTemplateBlockCart(TemplateEvent $event)
    {
        $FlashSale = $this->flashSaleRepository->getAvailableFlashSale();
        if (!$FlashSale instanceof FlashSale) {
            return;
        }

        $source = $event->getSource();
        $source = str_replace(
            '{{ CartItem.price|price }}',
            '{% if CartItem.getFlashSaleDiscount() %} <del>{{ CartItem.price|price }}</del> <span class="ec-color-red">{{ CartItem.getFlashSaleDiscountPrice()|price }} ({{ CartItem.getFlashSaleDiscountPercent() }}%)</span> {% else %} {{ CartItem.price|price }} {% endif %}',
            $source
        );
        $source = str_replace(
            '{{ totalPrice|price }}',
            '{% if totalDiscountPrice is defined %}<del>{{ totalPrice|price }}</del> <span>{{ (totalPrice - totalDiscountPrice)|price }}</span>{% else %} {{ totalPrice|price }} {% endif %}',
            $source
        );
        $event->setSource($source);
    }

    /**
     * Assign total discount price to template
     *
     * @param TemplateEvent $event
     */
    public function onTemplateTotalPrice(TemplateEvent $event)
    {
        if ($event->hasParameter('totalDiscountPrice')) {
            return;
        }

        $totalDiscountPrice = 0;
        $Carts = $this->twig->getExtension(CartServiceExtension::class)->get_all_carts();
        foreach ($Carts as $Cart) {
            $totalDiscountPrice += $Cart->getFlashSaleTotalDiscount();
        }

        if ($totalDiscountPrice) {
            $event->setParameter('totalDiscountPrice', $totalDiscountPrice);
        }
    }

    /**
     * Display price of flashsale on cart index template
     *
     * @param TemplateEvent $event
     */
    public function onTemplateCartIndex(TemplateEvent $event)
    {
        $FlashSale = $this->flashSaleRepository->getAvailableFlashSale();
        if (!$FlashSale instanceof FlashSale) {
            return;
        }

        // clone from CartController:index
        $Carts = $event->getParameter('Carts');
        // TODO itemHolderから取得できるように
        $least = [];
        $isDeliveryFree = [];

        /** @var Cart $Cart */
        foreach ($Carts as $Cart) {
            $isDeliveryFree[$Cart->getCartKey()] = false;

            if ($this->baseInfo->getDeliveryFreeQuantity() && $this->baseInfo->getDeliveryFreeQuantity() <= $Cart->getQuantity()) {
                $isDeliveryFree[$Cart->getCartKey()] = true;
            }

            if ($this->baseInfo->getDeliveryFreeAmount()) {
                if (!$isDeliveryFree[$Cart->getCartKey()] && $this->baseInfo->getDeliveryFreeAmount() <= $Cart->getFlashSaleTotalDiscountPrice()) {
                    $isDeliveryFree[$Cart->getCartKey()] = true;
                } else {
                    $least[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeAmount() - $Cart->getFlashSaleTotalDiscountPrice();
                }
            }
        }
        $parameters = $event->getParameters();
        $parameters['least'] = $least;
        $parameters['is_delivery_free'] = $isDeliveryFree;
        $event->setParameters($parameters);

        $source = $event->getSource();
        $source = str_replace(
            '{{ CartItem.price|price }}',
            '{% if CartItem.getFlashSaleDiscount() %} <del>{{ CartItem.price|price }}</del> <span class="ec-color-red">{{ CartItem.getFlashSaleDiscountPrice()|price }} ({{ CartItem.getFlashSaleDiscountPercent() }}%)</span> {% else %} {{ CartItem.price|price }} {% endif %}',
            $source
        );
        $source = str_replace(
            '{{ CartItem.total_price|price }}',
            '{% if CartItem.getFlashSaleTotalDiscount() %} <del>{{ CartItem.total_price|price }}</del> <span class="ec-color-red">{{ CartItem.getFlashSaleTotalDiscountPrice()|price }}</span> {% else %} {{ CartItem.total_price|price }} {% endif %}',
            $source
        );
        $source = str_replace(
            '{{ Cart.totalPrice|price }}',
            '{% if Cart.getFlashSaleTotalDiscount() %} <del>{{ Cart.totalPrice|price }}</del> <span class="ec-color-red">{{ Cart.getFlashSaleTotalDiscountPrice()|price }}</span> {% else %} {{ Cart.totalPrice|price }} {% endif %}',
            $source
        );

        $replace = '
        {% if totalDiscountPrice is defined %}
            {% set totalPriceWithFlashSaleDiscount = "<del>" ~ totalPrice|price ~ "</del> <span>" ~ (totalPrice - totalDiscountPrice)|price ~ "</span>" %}
            {{ \'front.cart.total_price\'|trans({ \'%price%\': totalPriceWithFlashSaleDiscount })|raw }}
        {% else %}
            {{ \'front.cart.total_price\'|trans({ \'%price%\': totalPrice|price })|raw }}
        {% endif %}
        ';
        $source = str_replace(
            '{{ \'front.cart.total_price\'|trans({ \'%price%\': totalPrice|price })|raw }}',
            $replace,
            $source
        );
        $event->setSource($source);
    }

    /**
     * Display price of flashsale on shopping index template
     *
     * @param TemplateEvent $event
     */
    public function onTemplateShoppingIndex(TemplateEvent $event)
    {
        $FlashSale = $this->flashSaleRepository->getAvailableFlashSale();
        if (!$FlashSale instanceof FlashSale) {
            return;
        }

        $source = $event->getSource();
        $source = str_replace(
            '{{ orderItem.priceIncTax|price }}',
            '{% if orderItem.getFlashSaleDiscount() %} <del>{{ orderItem.priceIncTax|price }}</del><span class="ec-color-red">{{ orderItem.getFlashSaleDiscountPrice()|price }}({{ orderItem.getFlashSaleDiscountPercent() }}%)</span> {% else %} {{ orderItem.priceIncTax|price }} {% endif %}',
            $source
        );
        $source = str_replace(
            '{{ orderItem.totalPrice|price }}',
            '{% if orderItem.getFlashSaleTotalDiscount() %} <del>{{ orderItem.totalPrice|price }}</del><span class="ec-color-red">{{ orderItem.getFlashSaleTotalDiscountPrice()|price }}</span> {% else %} {{ orderItem.totalPrice|price }} {% endif %}',
            $source
        );
        $event->setSource($source);
    }
}

# FlashSale

[![Build Status](https://travis-ci.org/eccubevn/flash-sale.svg?branch=master)](https://travis-ci.org/eccubevn/flash-sale)
[![Coverage Status](https://coveralls.io/repos/github/eccubevn/flash-sale/badge.png?branch=master)](https://coveralls.io/github/eccubevn/flash-sale?branch=master)

## Overview 
A plugin that support the shop owner/website’s administrator to create and manage discount sales event calls FlashSale<sup>*</sup>
<small><sup>*</sup><em>FlashSale is a discount or promotion offered by an ecommerce store for a very short period of time. The quantity is limited, which often means the discounts are higher or more significant than ordinary promotions.</em></small>

## Front-end
- Display products which on flashsale campaign
- Display discount price together origin price of product
- Display discount amount on cart and shopping page. 
- Display discount price on mail and history page. 
- Order will be calculated with discount amount, delivery fee and point will base on discount price instead of original price. 

## Back-end
- Display all flashsale in store on grid view.
- Create/Edit a flashsale which specified period you want 
- Define the rule with condition and promotion for product, cart which want to be discounted.
- There are 2 rules of discount: product (class) and shopping cart
- There are 3 conditions of discount: product (class), category and cart total (order total)
- Multi rule and condition.

## License
- LGPL-2.1

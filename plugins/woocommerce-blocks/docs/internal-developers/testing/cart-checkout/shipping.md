# Shipping <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Setup](#setup)
- [What to test](#what-to-test)
    - [With shipping zones available](#with-shipping-zones-available)
    - [If you don't have any shipping zones set up and/or shipping is disabled](#if-you-dont-have-any-shipping-zones-set-up-andor-shipping-is-disabled)
    - [If you don't have any shipping zones set up, but shipping is enabled](#if-you-dont-have-any-shipping-zones-set-up-but-shipping-is-enabled)

## Setup

-   You will need to setup shipping zones for a couple of countries.
-   You will need to have a free shipping method that is enabled with a coupon or a threshold.

## What to test

### With shipping zones available

-   [ ] You should be able to see preview rates (that are not your actual rates) in the editor.
-   [ ] You should be able to see your actual rates on the frontend.
-   [ ] Selecting a shipping rate should update the totals.
-   [ ] Changing the address in Cart block should update the rates.
-   [ ] Try entering an address that does not have rates for, you should:
    -   [ ] See an error saying "No options were found".
    -   [ ] See the default shipping option if you have it setup.
-   [ ] The countries in the shipping rates form should reflect the countries you have in WooCommerce -> Settings -> General -> Shipping location(s).
-   [ ] If your cart has only digital products, the Cart and Checkout blocks should act like shipping is disabled.
-   [ ] Your free shipping method should show up when you increase the cart quantity to above that limit.
    -   [ ] Once you decrease it, the shipping rate will disappear, the next rate will be selected.
-   [ ] The rate you select in Cart should still be selected in Checkout.
-   [ ] Updating your shipping address in Checkout should give you live updates about rates in your cart.

### If you don't have any shipping zones set up and/or shipping is disabled

-   [ ] You should only see the billing form in both editor and frontend for the Checkout Block.
-   [ ] The shipping options step should not be visible.
-   [ ] The shipping cost should not be visible in the sidebar.

### If you don't have any shipping zones set up, but shipping is enabled

-   [ ] In the editor, Checkout Block will show you a placeholder promoting you to set up shipping zones.

[![Create Todo list](https://raw.githubusercontent.com/senadir/todo-my-markdown/master/public/github-button.svg?sanitize=true)](https://git-todo.netlify.app/create)

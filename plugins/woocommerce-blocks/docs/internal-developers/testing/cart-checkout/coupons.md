# Coupons <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Setup](#setup)
- [What to test](#what-to-test)
    - [With coupons disabled](#with-coupons-disabled)
    - [With coupons enabled](#with-coupons-enabled)

## Setup

You will need to setup some types of coupon in order to test this.

-   A general purpose coupon `coupon`.
-   An expired coupon `oldcoupon`.
-   An email limited coupon `a12s` that is limited to `*@automattic.com` emails.
-   A cart condition coupon that is limited to carts above x threshold (e.g \$200) `above200`.
-   An individually used coupon `alone`.
-   A free shipping coupon `freeship`.

## What to test

### With coupons disabled

-   [ ] You should not see the add coupon section in your cart and checkout and in the editor.

### With coupons enabled

-   [ ] You can apply coupons in both Cart and Checkout blocks.
-   [ ] A valid coupon `coupon` should reduce your totals.
-   [ ] An expired coupon `oldcoupon` should return an error.
-   [ ] An invalid coupon should return an error.
-   [ ] An email limited coupon should apply to your cart.
    -   [ ] If the email is correct, you should be able to checkout.
    -   [ ] If the email is incorrect, you should receive an error, and the coupon will be removed from your cart.
-   [ ] A condition coupon should not be added until you meet the condition.
    -   [ ] Adding a condition coupon then removing the condition (reduce cart total or remove related item) should remove the coupon from your cart with an error.
-   [ ] Adding a coupon then adding `alone` coupon should replace the first one.
-   [ ] Adding `alone` then trying to another coupon should result in an error.
-   [ ] Adding `freeship` should show the free shipping method you previously created.

[![Create Todo list](https://raw.githubusercontent.com/senadir/todo-my-markdown/master/public/github-button.svg?sanitize=true)](https://git-todo.netlify.app/create)

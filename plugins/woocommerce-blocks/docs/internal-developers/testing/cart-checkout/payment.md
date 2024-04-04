# Payments <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Setup](#setup)
    - [Special Cases](#special-cases)
    - [Unsupported](#unsupported)
- [What to test](#what-to-test)

## Setup

The Checkout Block supports three methods of payments:

-   Check Payment, found in WooCommerce payment section.
-   Stripe Credit Card payments, provided by [Stripe Gateway](woocommerce.com/products/stripe/).
-   Express Payment methods, provided by [Stripe Gateway](woocommerce.com/products/stripe/).

To test Stripe and Express payment methods, you will need API keys, you can get them by creating a testing account in stripe.

### Special Cases

To test Express payment methods there are some special requirements like

-   You need to be serving the website over https, try ephemeral websites.
-   You need to be from a supported country.
-   To test Apple Pay you will need to have an iOS or device.
-   To test Google Pay you will need to have Chrome installed and a payment method setup.
-   To test Microsoft Pay you will need to have Edge installed.
-   You will also need to be on a supported country, to better verify your compatibility visit [this page](https://stripe.com/docs/stripe-js/elements/payment-request-button#react-overview). You should see if you're on a supported platform or not.

### Unsupported

![image](https://i.imgur.com/EpkFrat.png).

## What to test

If no payment method is set up:

-   [ ] An error will show up in the frontend, saying that no payment method is available.
-   [ ] In the editor, you will be prompted to set up a payment method.

If you have a payment method available:

-   [ ] You should be able to perform a successful checkout with Check payments.
-   [ ] You should be able to perform a successful checkout credit card payment using this cart `4242424242424242`
-   [ ] You should be able to perform a failed checkout credit card payment using this cart `4000000000000002`

[![Create Todo list](https://raw.githubusercontent.com/senadir/todo-my-markdown/master/public/github-button.svg?sanitize=true)](https://git-todo.netlify.app/create)

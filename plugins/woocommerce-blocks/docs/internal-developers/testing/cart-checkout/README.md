# Cart and Checkout testing plan <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Introduction](#introduction)
    - [Known limitations](#known-limitations)
- [How to report issues](#how-to-report-issues)
- [What are we testing?](#what-are-we-testing)
    - [Cart Block](#cart-block)
    - [Checkout Block](#checkout-block)
    - [Data Stores](#data-stores)
- [Testing Checklist](#testing-checklist)

## Introduction

Welcome, and thank you for helping us test the Cart and Checkout blocks,
in this document, we will outline the general checklist for how to test
the blocks, any requirements, and expectations and feature parity, some
will require simple coding skills, and some are straightforward, we will
separate them.

### Known limitations

<!-- Debating on where to put this section -->

This is a list of all known limitation for Cart and Checkout blocks, it means
we're aware of them, and will probably not tackle them in this first release:

-   Cart and Checkout blocks do not support third-party plugins that integrate with
    regular Cart and Checkout shortcode, if you somehow see a third party plugin working
    well, this is pure coincidence, the only exception is Stripe payment gateway.

-   The only payment gateway supported are Check and Stripe.
-   Storefront and TwentyTwenty are expected to work fine, no guarantee is presented on other themes, but do report them if you feel like that's something we can fix on our end.

<!-- Currently this is unneeded so I'm omitting this section -->.
<!--
## Before you start <!-- omit in toc -->
<!--
Depending on how far you will test, there are certain requirements, in general
you will need the following:

Basic:
- A WordPress website running WooCommerce and the ability to install a plugin and edit pages.

Intermediate:
- A code editor and/or the ability to modify plugin PHP files.
  This could be either locally if you're hosting the code there or it could from the Plugins -> Plugin Editor
  WordPress admin page.

Advanced:
- A locally installed version of WordPress.
- [Node 12.16.1 and npm 6.14.4 installed](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/package.json#L149-L150).
- Ability to edit JS source files when needed.
-->

## How to report issues

Ideally, we would prefer it if you can submit an issue via [this link](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?template=---bug-report.md), however, you can also submit issues here in this thread.

It would be preferable to have a look at this [list of issues](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues?q=is%3Aissue+label%3A%22type%3A+bug%22+milestone%3A2.6.0+) to see if the issue you're submitting has already been submitted.

## What are we testing?

The goal is to test the new Cart and Checkout blocks, they should be replacing
the Cart and Checkout shortcodes.

### Cart Block

![image](https://i.imgur.com/mcbXgqV.png)

### Checkout Block

![image](https://i.imgur.com/9KhYK2L.png)

### Data Stores

After moving a lot of functionality from React Context to data stores, we need to rigorously test some areas of the Cart and Checkout process. [This link contains some detailed testing instructions](./data-stores.md) for this.

## Testing Checklist

-   [General Flow](general-flow.md)
-   [Editor](editor.md)
-   [Shipping](shipping.md)
-   [Payments](payment.md)
-   [Items](items.md)
-   [Taxes](taxes.md)
-   [Coupons](coupons.md)
-   [Cross Browser and platform](cross-browser.md)

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/internal-developers/testing/cart-checkout/README.md)

<!-- /FEEDBACK -->


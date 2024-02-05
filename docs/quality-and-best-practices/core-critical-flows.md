---
post_title: WooCommerce core critical flows
menu_title: Core critical flows
tags: reference
---

We have identified what we consider to be our most critical user flows within WooCommerce Core. These flows will help us focus and prioritize our testing efforts. They will also help us consider the impact of changes and priority of issues.

These flows will continually evolve as the platform evolves with flows updated, added or re-prioritised.

## Shopper critical flow areas

- [Shopper > Shop](#shopper---shop)
- [Shopper > Product](#shopper---product)
- [Shopper > Cart](#shopper---cart)
- [Shopper > Checkout](#shopper---checkout)
- [Shopper > Email](#shopper---email)
- [Shopper > My Account](#shopper---my-account)

## Merchant critical flow areas

- [Merchant > Onboarding](#merchant---onboarding)
- [Merchant > Dashboard](#merchant---dashboard)
- [Merchant > Settings](#merchant---settings)
- [Merchant > Coupons](#merchant---coupons)
- [Merchant > Marketing](#merchant---marketing)
- [Merchant > Analytics](#merchant---analytics)
- [Merchant > Products](#merchant---products)
- [Merchant > Orders](#merchant---orders)
- [Merchant > Customers](#merchant---customers)
- [Merchant > Email](#merchant---email)
- [Merchant > Plugins](#merchant---plugins)
- [Merchant > My Subscriptions](#merchant---my-subscriptions)
- [Merchant > Pages](#merchant---pages)
- [Merchant > Posts](#merchant---posts)

### Shopper - Shop

| User Type | Flow Area | Flow Name                                   | Test File                               |
| --------- | --------- | ------------------------------------------- | --------------------------------------- |
| Shopper   | Shop      | Search Store                                | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Browse by categories                        | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Can sort items                              | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Add Simple Product to Cart (from shop page) | shopper/cart.spec.js                    |
| Shopper   | Shop      | Display shop catalog                        |                                         |
| Shopper   | Shop      | Products by tag                             |                                         |
| Shopper   | Shop      | Products by attribute                       |                                         |
| Shopper   | Shop      | Use product filters                         |                                         |
| Shopper   | Shop      | Display product showcase blocks correctly   |                                         |
| Shopper   | Shop      | Navigation menu default links               |                                         |

### Shopper - Product

| User Type | Flow Area | Flow Name                                            | Test File                        |
| --------- | --------- | ---------------------------------------------------- | -------------------------------- |
| Shopper   | Product   | Add Simple Product to Cart                           | shopper/product-simple.spec.js   |
| Shopper   | Product   | Add Grouped Product to Cart                          | shopper/product-grouped.spec.js  |
| Shopper   | Product   | Variable Product info updates depending on variation | shopper/product-variable.spec.js |
| Shopper   | Product   | Add Variable Product to Cart                         | shopper/product-variable.spec.js |
| Shopper   | Product   | Display up-sell product                              |                                  |
| Shopper   | Product   | Display releated products                            |                                  |
| Shopper   | Product   | Display reviews                                      |                                  |
| Shopper   | Product   | Add review                                           |                                  |
| Shopper   | Product   | View product images                                  |                                  |
| Shopper   | Product   | View product descriptions                            |                                  |

### Shopper - Cart

| User Type | Flow Area | Flow Name                                  | Test File                          |
| --------- | --------- | ------------------------------------------ | ---------------------------------- |
| Shopper   | Cart      | Add to cart redirects to cart when enabled | shopper/cart-redirection.spec.js   |
| Shopper   | Cart      | View cart                                  | shopper/cart.spec.js               |
| Shopper   | Cart      | Update product quantity within limits      | shopper/cart.spec.js               |
| Shopper   | Cart      | Remove products from cart                  | shopper/cart.spec.js               |
| Shopper   | Cart      | Apply all coupon types                     | shopper/cart-coupons.spec.js       |
| Shopper   | Cart      | Display shipping options by address        | shopper/calculate-shipping.spec.js |
| Shopper   | Cart      | View empty cart                            |                                    |
| Shopper   | Cart      | Display correct tax                        |                                    |
| Shopper   | Cart      | Respect coupon usage contraints            |                                    |
| Shopper   | Cart      | Display cross-sell products                |                                    |
| Shopper   | Cart      | Proceed to checkout                        |                                    |

### Shopper - Checkout

| User Type | Flow Area | Flow Name                                | Test File                               |
| --------- | --------- | ---------------------------------------- | --------------------------------------- |
| Shopper   | Checkout  | Correct item in Order Review             | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Can add shipping address                 | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Guest can place order                    | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Create an account                        | shopper/checkout-create-account.spec.js |
| Shopper   | Checkout  | Login to existing account                | shopper/checkout-login.spec.js          |
| Shopper   | Checkout  | Existing customer can place order        | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Use all coupon types                     | shopper/checkout-coupons.spec.js        |
| Shopper   | Checkout  | View checkout                            |                                         |
| Shopper   | Checkout  | Receive warnings when form is incomplete |                                         |
| Shopper   | Checkout  | Add billing address                      |                                         |
| Shopper   | Checkout  | Respect coupon usage contraints          |                                         |
| Shopper   | Checkout  | Display correct tax in checkout          |                                         |
| Shopper   | Checkout  | View order confirmation page             |                                         |

### Shopper - Email

| User Type | Flow Area | Flow Name                             | Test File                             |
| --------- | --------- | ------------------------------------- | ------------------------------------- |
| Shopper   | Email     | Customer Account Emails Received      | shopper/                              |
| Shopper   | Email     | Customer Order Detail Emails Received | shopper/order-email-receiving.spec.js |

### Shopper - My Account

| User Type | Flow Area  | Flow Name                 | Test File                                 |
| --------- | ---------- | ------------------------- | ----------------------------------------- |
| Shopper   | My Account | Create an account         | shopper/my-account-create-account.spec.js |
| Shopper   | My Account | Login to existing account | shopper/my-account.spec.js                |
| Shopper   | My Account | View Account Details      | shopper/my-account.spec.js                |
| Shopper   | My Account | Update Addresses          | shopper/my-account-addresses.spec.js      |
| Shopper   | My Account | View Orders               | shopper/                                  |
| Shopper   | My Account | Pay for Order             | shopper/my-account-pay-order.spec.js      |
| Shopper   | My Account | View Downloads            | shopper/my-account-downloads.spec.js      |

### Merchant - Onboarding

| User Type | Flow Area     | Flow Name                                                      | Test File |
| --------- | ------------- | -------------------------------------------------------------- | --------- |
| Merchant  | Core Profiler | Introduction & opt-in                                          |           |
| Merchant  | Core Profiler | User profile information                                       |           |
| Merchant  | Core Profiler | Business information                                           |           |
| Merchant  | Core Profiler | Extensions page                                                |           |
| Merchant  | Core Profiler | WooPayments included in extensions for eligible criteria       |           |
| Merchant  | Core Profiler | WooPayments not included in extensions for ineligible criteria |           |
| Merchant  | Core Profiler | Install all default extensions                                 |           |
| Merchant  | Core Profiler | Complete site setup                                            |           |
| Merchant  | Core Profiler | Skip introduction and confirm business location                |           |
| Merchant  | Core Profiler | Completed profiler doesn't reappear after site upgrade         |           |

### Merchant - Dashboard

| User Type | Flow Area      | Flow Name                                              | Test File |
| --------- | -------------- | ------------------------------------------------------ | --------- |
| Merchant  | WC Home        | Completing profiler redirects to home                  |           |
| Merchant  | WC Home        | Complete all steps on task list                        |           |
| Merchant  | WC Home        | Hide the task list                                     |           |
| Merchant  | WC Home        | Store management displayed after task list finished    |           |
| Merchant  | WC Home        | Direct access to analytics reports from stats overview |           |
| Merchant  | WC Home        | Preserve task list completion status after upgrade     |           |
| Merchant  | WC Home        | Interact with extended task list                       |           |
| Merchant  | Activity Panel | Interact with activity button                          |           |
| Merchant  | Inbox          | Interact with notes and perform CTAs                   |           |
| Merchant  | Inbox          | Dismiss single note and all notes                      |           |

### Merchant - Settings

| User Type | Flow Area | Flow Name                                       | Test File                                |
| --------- | --------- | ----------------------------------------------- | ---------------------------------------- |
| Merchant  | Settings  | Update General Settings                         | merchant/settings-general.spec.js        |
| Merchant  | Settings  | Add Tax Rates                                   | merchant/settings-tax.spec.js            |
| Merchant  | Settings  | Add Shipping Zones                              | merchant/create-shipping-zones.spec.js   |
| Merchant  | Settings  | Add Shipping Classes                            | merchant/create-shipping-classes.spec.js |
| Merchant  | Settings  | Enable local pickup for checkout block          |                                          |
| Merchant  | Settings  | Enable HPOS                                     |                                          |
| Merchant  | Settings  | Update payment settings                         |                                          |
| Merchant  | Settings  | Maintain tax and shipping settings post-upgrade |                                          |

### Merchant - Coupons

| User Type | Flow Area | Flow Name             | Test File                      |
| --------- | --------- | --------------------- | ------------------------------ |
| Merchant  | Coupons   | Add all coupon types  | merchant/create-coupon.spec.js |
| Merchant  | Coupons   | Add restricted coupon |                                |

### Merchant - Marketing

| User Type | Flow Area | Flow Name                  | Test File |
| --------- | --------- | -------------------------- | --------- |
| Merchant  | Marketing | Display marketing overview |           |

### Merchant - Analytics

| User Type | Flow Area | Flow Name                                          | Test File                         |
| --------- | --------- | -------------------------------------------------- | --------------------------------- |
| Merchant  | Analytics | View revenue report                                | admin-analytics/analytics.spec.js |
| Merchant  | Analytics | View overview report                               |                                   |
| Merchant  | Analytics | Confirm correct summary numbers on overview report |                                   |
| Merchant  | Analytics | Use date filter on overview page                   |                                   |
| Merchant  | Analytics | Customize performance indicators on overview page  |                                   |
| Merchant  | Analytics | Use date filter on revenue report                  |                                   |
| Merchant  | Analytics | Download revenue report as CSV                     |                                   |
| Merchant  | Analytics | Use advanced filters on orders report              |                                   |
| Merchant  | Analytics | Analytics settings                                 |                                   |
| Merchant  | Analytics | Set custom date range on revenue report            |                                   |

### Merchant - Products

| User Type | Flow Area      | Flow Name                      | Test File                                                              |
|-----------|----------------|--------------------------------|------------------------------------------------------------------------|
| Merchant  | Products       | View all products              |                                                                        |
| Merchant  | Products       | Search products                | merchant/product-search.spec.js                                        |
| Merchant  | Products       | Add simple product             | merchant/product-create-simple.spec.js                                 |
| Merchant  | Products       | Add variable product           | merchant/products/add-variable-product/create-variable-product.spec.js |
| Merchant  | Products       | Edit product details           | merchant/product-edit.spec.js                                          |
| Merchant  | Products       | Add virtual product            | merchant/product-create-simple.spec.js                                 |
| Merchant  | Products       | Import products CSV            | merchant/product-import-csv.spec.js                                    |
| Merchant  | Products       | Add downloadable product       | merchant/product-create-simple.spec.js                                 |
| Merchant  | Products       | View product reviews list      |                                                                        |
| Merchant  | Products       | View all products reviews list |                                                                        |
| Merchant  | Products       | Edit product review            |                                                                        |
| Merchant  | Products       | Trash product review           |                                                                        |
| Merchant  | Products       | Bulk edit products             |                                                                        |
| Merchant  | Products       | Remove products                | merchant/product-delete.spec.js                                        |
| Merchant  | Products       | Manage product images          |                                                                        |
| Merchant  | Products       | Manage product inventory       |                                                                        |
| Merchant  | Products       | Manage product attributes      |                                                                        |
| Merchant  | Products       | Manage global attributes       |                                                                        |
| Merchant  | Products       | Add up-sell                    |                                                                        |
| Merchant  | Products       | Add cross-sell                 |                                                                        |
| Merchant  | Products (New) | Disable new product experience |                                                                        |
| Merchant  | Products (New) | Add simple product             |                                                                        |
| Merchant  | Products (New) | Edit simple product            |                                                                        |
| Merchant  | Products (New) | Manage product images          |                                                                        |
| Merchant  | Products (New) | Manage product inventory       |                                                                        |
| Merchant  | Products (New) | Manage product attributes      |                                                                        |

### Merchant - Orders

| User Type | Flow Area | Flow Name                                                        | Test File                              |
| --------- | --------- | ---------------------------------------------------------------- | -------------------------------------- |
| Merchant  | Orders    | View all orders                                                  | merchant/                              |
| Merchant  | Orders    | Can add new order basic                                          | merchant/order-edit.spec.js            |
| Merchant  | Orders    | View single order                                                | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update order status to completed                                 | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update order status to cancelled                                 |                                        |
| Merchant  | Orders    | Update order details                                             | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Customer payment page                                            | merchant/customer-payment-page.spec.js |
| Merchant  | Orders    | Refund order                                                     | merchant/order-refund.spec.js          |
| Merchant  | Orders    | Apply coupon                                                     | merchant/order-coupon.spec.js          |
| Merchant  | Orders    | Can add new order complex - multiple product types & tax classes | merchant/create-order.spec.js          |
| Merchant  | Orders    | Search orders                                                    | merchant/order-search.spec.js          |
| Merchant  | Orders    | Filter orders by order status                                    | merchant/order-status-filter.spec.js   |
| Merchant  | Orders    | Bulk change order status                                         |                                        |
| Merchant  | Orders    | Add order notes                                                  |                                        |

### Merchant - Customers

| User Type | Flow Area | Flow Name             | Test File |
| --------- | --------- | --------------------- | --------- |
| Merchant  | Customers | Display customer list |           |

### Merchant - Email

| User Type | Flow Area | Flow Name                                          | Test File                     |
| --------- | --------- | -------------------------------------------------- | ----------------------------- |
| Merchant  | Email     | Receive and check content of new order email       | merchant/order-emails.spec.js |
| Merchant  | Email     | Receive and check content of cancelled order email |                               |
| Merchant  | Email     | Receive and check content of failed order email    |                               |
| Merchant  | Email     | Resent new order email                             |                               |
| Merchant  | Email     | Send invoice/order details to customer via Email   |                               |

### Merchant - Plugins

| User Type | Flow Area | Flow Name                 | Test File                              |
| --------- | --------- | ------------------------- | -------------------------------------- |
| Merchant  | Plugins   | Can update WooCommerce    | smoke-tests/update-woocommerce.spec.js |
| Merchant  | Plugins   | Can uninstall WooCommerce |                                        |

### Merchant - My Subscriptions

| User Type | Flow Area        | Flow Name                               | Test File |
| --------- | ---------------- | --------------------------------------- | --------- |
| Merchant  | My Subscriptions | Can initiate Woo.com Connection |           |

### Merchant - Pages

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Pages     | Can create a new page | merchant/create-page.spec.js |

### Merchant - Posts

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Posts     | Can create a new post | merchant/create-post.spec.js |

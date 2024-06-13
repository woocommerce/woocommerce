---
post_title: WooCommerce core critical flows
menu_title: Core critical flows
tags: reference
---

We have identified what we consider to be our most critical user flows within WooCommerce Core. These flows will help us focus and prioritize our testing efforts. They will also help us consider the impact of changes and priority of issues.

These flows will continually evolve as the platform evolves with flows updated, added or re-prioritised.

## Shopper critical flow areas

-   [Shopper > Shop](#shopper---shop)
-   [Shopper > Product](#shopper---product)
-   [Shopper > Cart](#shopper---cart)
-   [Shopper > Checkout](#shopper---checkout)
-   [Shopper > Email](#shopper---email)
-   [Shopper > My Account](#shopper---my-account)

## Merchant critical flow areas

-   [Merchant > Onboarding](#merchant---onboarding)
-   [Merchant > Dashboard](#merchant---dashboard)
-   [Merchant > Settings](#merchant---settings)
-   [Merchant > Coupons](#merchant---coupons)
-   [Merchant > Marketing](#merchant---marketing)
-   [Merchant > Analytics](#merchant---analytics)
-   [Merchant > Products](#merchant---products)
-   [Merchant > Orders](#merchant---orders)
-   [Merchant > Customers](#merchant---customers)
-   [Merchant > Email](#merchant---email)
-   [Merchant > Plugins](#merchant---plugins)
-   [Merchant > My Subscriptions](#merchant---my-subscriptions)
-   [Merchant > Pages](#merchant---pages)
-   [Merchant > Posts](#merchant---posts)

### Shopper - Shop

| User Type | Flow Area | Flow Name                                   | Test File                                    |
| --------- | --------- | ------------------------------------------- | -------------------------------------------- |
| Shopper   | Shop      | Search Store                                | shopper/shop-search-browse-sort.spec.js      |
| Shopper   | Shop      | Browse by categories                        | shopper/shop-search-browse-sort.spec.js      |
| Shopper   | Shop      | Can sort items                              | shopper/shop-search-browse-sort.spec.js      |
| Shopper   | Shop      | Add Simple Product to Cart (from shop page) | shopper/cart.spec.js                         |
| Shopper   | Shop      | Display shop catalog                        | shopper/shop-search-browse-sort.spec.js      |
| Shopper   | Shop      | Products by tag                             | shopper/product-tags-attributes.spec.js      |
| Shopper   | Shop      | Products by attribute                       | shopper/product-tags-attributes.spec.js      |
| Shopper   | Shop      | Use product filters                         | shopper/shop-products-filer-by-price.spec.js |

### Shopper - Product

| User Type | Flow Area | Flow Name                                            | Test File                                |
| --------- | --------- | ---------------------------------------------------- | ---------------------------------------- |
| Shopper   | Product   | Add Simple Product to Cart                           | shopper/product-simple.spec.js           |
| Shopper   | Product   | Add Grouped Product to Cart                          | shopper/product-grouped.spec.js          |
| Shopper   | Product   | Variable Product info updates depending on variation | shopper/product-variable.spec.js         |
| Shopper   | Product   | Add Variable Product to Cart                         | shopper/product-variable.spec.js         |
| Shopper   | Product   | Display up-sell product                              | products/product-linked-products.spec.js |
| Shopper   | Product   | Display related products                             | products/product-linked-products.spec.js |
| Shopper   | Product   | Display reviews                                      | merchant/product-reviews.spec.js         |
| Shopper   | Product   | Add review                                           | merchant/product-reviews.spec.js         |
| Shopper   | Product   | View product images                                  | shopper/product-simple.spec.js           |
| Shopper   | Product   | View product descriptions                            | shopper/product-simple.spec.js           |

### Shopper - Cart

| User Type | Flow Area | Flow Name                                  | Test File                                   |
| --------- | --------- | ------------------------------------------ | ------------------------------------------- |
| Shopper   | Cart      | Add to cart redirects to cart when enabled | shopper/cart-redirection.spec.js            |
| Shopper   | Cart      | View cart                                  | shopper/cart.spec.js                        |
| Shopper   | Cart      | Update product quantity within limits      | shopper/cart.spec.js                        |
| Shopper   | Cart      | Remove products from cart                  | shopper/cart.spec.js                        |
| Shopper   | Cart      | Apply all coupon types                     | shopper/cart-coupons.spec.js                |
| Shopper   | Cart      | Display shipping options by address        | shopper/calculate-shipping.spec.js          |
| Shopper   | Cart      | View empty cart                            | shopper/cart.spec.js                        |
| Shopper   | Cart      | Display correct tax                        | shopper/cart-checkout-calculate-tax.spec.js |
| Shopper   | Cart      | Respect coupon usage constraints           | shopper/cart-checkout-coupons.spec.js       |
| Shopper   | Cart      | Display cross-sell products                | products/product-linked-products.spec.js    |
| Shopper   | Cart      | Proceed to checkout                        | shopper/checkout.spec.js                    |

### Shopper - Checkout

| User Type | Flow Area | Flow Name                                | Test File                                   |
| --------- | --------- | ---------------------------------------- | ------------------------------------------- |
| Shopper   | Checkout  | Correct item in Order Review             | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Can add shipping address                 | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Guest can place order                    | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Create an account                        | shopper/checkout-create-account.spec.js     |
| Shopper   | Checkout  | Login to existing account                | shopper/checkout-login.spec.js              |
| Shopper   | Checkout  | Existing customer can place order        | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Use all coupon types                     | shopper/checkout-coupons.spec.js            |
| Shopper   | Checkout  | View checkout                            | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Receive warnings when form is incomplete | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Add billing address                      | shopper/checkout.spec.js                    |
| Shopper   | Checkout  | Respect coupon usage constraints         | shopper/cart-checkout-coupons.spec.js       |
| Shopper   | Checkout  | Display correct tax in checkout          | shopper/cart-checkout-calculate-tax.spec.js |
| Shopper   | Checkout  | View order confirmation page             | shopper/checkout.spec.js                    |

### Shopper - Email

| User Type | Flow Area | Flow Name                             | Test File                               |
| --------- | --------- | ------------------------------------- | --------------------------------------- |
| Shopper   | Email     | Customer Account Emails Received      | shopper/account-email-receiving.spec.js |
| Shopper   | Email     | Customer Order Detail Emails Received | shopper/order-email-receiving.spec.js   |

### Shopper - My Account

| User Type | Flow Area  | Flow Name                 | Test File                                 |
| --------- | ---------- | ------------------------- | ----------------------------------------- |
| Shopper   | My Account | Create an account         | shopper/my-account-create-account.spec.js |
| Shopper   | My Account | Login to existing account | shopper/my-account.spec.js                |
| Shopper   | My Account | View Account Details      | shopper/my-account.spec.js                |
| Shopper   | My Account | Update Addresses          | shopper/my-account-addresses.spec.js      |
| Shopper   | My Account | View Orders               | shopper/my-account-pay-order.spec.js      |
| Shopper   | My Account | Pay for Order             | shopper/my-account-pay-order.spec.js      |
| Shopper   | My Account | View Downloads            | shopper/my-account-downloads.spec.js      |

### Merchant - Onboarding

| User Type | Flow Area     | Flow Name                                                      | Test File                                |
| --------- | ------------- | -------------------------------------------------------------- | ---------------------------------------- |
| Merchant  | Core Profiler | Introduction & opt-in                                          | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | User profile information                                       | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | Business information                                           | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | Extensions page                                                | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | WooPayments included in extensions for eligible criteria       | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | WooPayments not included in extensions for ineligible criteria | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | Install all default extensions                                 | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | Complete site setup                                            | activate-and-setup/core-profiler.spec.js |
| Merchant  | Core Profiler | Skip introduction and confirm business location                | activate-and-setup/core-profiler.spec.js |

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

| User Type | Flow Area | Flow Name                              | Test File                                |
| --------- | --------- | -------------------------------------- | ---------------------------------------- |
| Merchant  | Settings  | Update General Settings                | merchant/settings-general.spec.js        |
| Merchant  | Settings  | Add Tax Rates                          | merchant/settings-tax.spec.js            |
| Merchant  | Settings  | Add Shipping Zones                     | merchant/create-shipping-zones.spec.js   |
| Merchant  | Settings  | Add Shipping Classes                   | merchant/create-shipping-classes.spec.js |
| Merchant  | Settings  | Enable local pickup for checkout block | merchant/settings-shipping.spec.js       |
| Merchant  | Settings  | Update payment settings                | admin-tasks/payment.spec.js              |

### Merchant - Coupons

| User Type | Flow Area | Flow Name             | Test File                                  |
| --------- | --------- | --------------------- | ------------------------------------------ |
| Merchant  | Coupons   | Add all coupon types  | merchant/create-coupon.spec.js             |
| Merchant  | Coupons   | Add restricted coupon | merchant/create-restricted-coupons.spec.js |

### Merchant - Marketing

| User Type | Flow Area | Flow Name                  | Test File                        |
| --------- | --------- | -------------------------- | -------------------------------- |
| Merchant  | Marketing | Display marketing overview | admin-marketing/overview.spec.js |

### Merchant - Analytics

| User Type | Flow Area | Flow Name                                          | Test File                                  |
| --------- | --------- | -------------------------------------------------- | ------------------------------------------ |
| Merchant  | Analytics | View revenue report                                | admin-analytics/analytics.spec.js          |
| Merchant  | Analytics | View overview report                               | admin-analytics/analytics-overview.spec.js |
| Merchant  | Analytics | Confirm correct summary numbers on overview report | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Use date filter on overview page                   | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Customize performance indicators on overview page  | admin-analytics/analytics-overview.spec.js |
| Merchant  | Analytics | Use date filter on revenue report                  | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Download revenue report as CSV                     | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Use advanced filters on orders report              | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Analytics settings                                 | admin-analytics/analytics-data.spec.js     |
| Merchant  | Analytics | Set custom date range on revenue report            | admin-analytics/analytics-data.spec.js     |

### Merchant - Products

| User Type | Flow Area      | Flow Name                      | Test File                                                                 |
| --------- | -------------- | ------------------------------ | ------------------------------------------------------------------------- |
| Merchant  | Products       | View all products              | merchant/product-search.spec.js                                           |
| Merchant  | Products       | Search products                | merchant/product-search.spec.js                                           |
| Merchant  | Products       | Add simple product             | merchant/product-create-simple.spec.js                                    |
| Merchant  | Products       | Add variable product           | merchant/products/add-variable-product/create-variable-product.spec.js    |
| Merchant  | Products       | Edit product details           | merchant/product-edit.spec.js                                             |
| Merchant  | Products       | Add virtual product            | merchant/product-create-simple.spec.js                                    |
| Merchant  | Products       | Import products CSV            | merchant/product-import-csv.spec.js                                       |
| Merchant  | Products       | Add downloadable product       | merchant/product-create-simple.spec.js                                    |
| Merchant  | Products       | View product reviews list      | merchant/product-reviews.spec.js                                          |
| Merchant  | Products       | View all products reviews list | merchant/product-reviews.spec.js                                          |
| Merchant  | Products       | Edit product review            | merchant/product-reviews.spec.js                                          |
| Merchant  | Products       | Trash product review           | merchant/product-reviews.spec.js                                          |
| Merchant  | Products       | Bulk edit products             | merchant/product-edit.spec.js                                             |
| Merchant  | Products       | Remove products                | merchant/product-delete.spec.js                                           |
| Merchant  | Products       | Manage product images          | merchant/product-images.spec.js                                           |
| Merchant  | Products       | Manage product inventory       | merchant/product-create-simple.spec.js                                    |
| Merchant  | Products       | Manage product attributes      | merchant/product-create-simple.spec.js                                    |
| Merchant  | Products       | Manage global attributes       |                                                                           |
| Merchant  | Products       | Add up-sell                    | products/product-linked-products.spec.js                                  |
| Merchant  | Products       | Add cross-sell                 | products/product-linked-products.spec.js                                  |
| Merchant  | Products (New) | Disable new product experience | merchant/products/block-editor/disable-block-product-editor.spec.js       |
| Merchant  | Products (New) | Add simple product             | merchant/products/block-editor/create-simple-product-block-editor.spec.js |
| Merchant  | Products (New) | Edit simple product            | merchant/products/block-editor/product-edit-block-editor.spec.js          |
| Merchant  | Products (New) | Manage product images          | merchant/products/block-editor/product-images-block-editor.spec.js        |
| Merchant  | Products (New) | Manage product inventory       | merchant/products/block-editor/product-inventory-block-editor.spec.js     |
| Merchant  | Products (New) | Manage product attributes      | merchant/products/block-editor/product-attributes-block-editor.spec.js    |

### Merchant - Orders

| User Type | Flow Area | Flow Name                                                        | Test File                              |
| --------- | --------- | ---------------------------------------------------------------- | -------------------------------------- |
| Merchant  | Orders    | View all orders                                                  | merchant/order-status-filter.spec.js   |
| Merchant  | Orders    | Can add new order basic                                          | merchant/order-edit.spec.js            |
| Merchant  | Orders    | View single order                                                | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update order status to completed                                 | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update order status to cancelled                                 | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update order details                                             | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Customer payment page                                            | merchant/customer-payment-page.spec.js |
| Merchant  | Orders    | Refund order                                                     | merchant/order-refund.spec.js          |
| Merchant  | Orders    | Apply coupon                                                     | merchant/order-coupon.spec.js          |
| Merchant  | Orders    | Can add new order complex - multiple product types & tax classes | merchant/create-order.spec.js          |
| Merchant  | Orders    | Search orders                                                    | merchant/order-search.spec.js          |
| Merchant  | Orders    | Filter orders by order status                                    | merchant/order-status-filter.spec.js   |
| Merchant  | Orders    | Bulk change order status                                         | merchant/order-bulk-edit.spec.js       |
| Merchant  | Orders    | Add order notes                                                  | merchant/order-edit.spec.js            |

### Merchant - Customers

| User Type | Flow Area | Flow Name             | Test File                      |
| --------- | --------- | --------------------- | ------------------------------ |
| Merchant  | Customers | Display customer list | merchant/customer-list.spec.js |

### Merchant - Email

| User Type | Flow Area | Flow Name                                          | Test File                     |
| --------- | --------- | -------------------------------------------------- | ----------------------------- |
| Merchant  | Email     | Receive and check content of new order email       | merchant/order-emails.spec.js |
| Merchant  | Email     | Receive and check content of cancelled order email | merchant/order-emails.spec.js |
| Merchant  | Email     | Receive and check content of failed order email    | merchant/order-emails.spec.js |
| Merchant  | Email     | Resent new order email                             | merchant/order-emails.spec.js |
| Merchant  | Email     | Send invoice/order details to customer via Email   | merchant/order-emails.spec.js |

### Merchant - Plugins

| User Type | Flow Area | Flow Name              | Test File                              |
| --------- | --------- | ---------------------- | -------------------------------------- |
| Merchant  | Plugins   | Can update WooCommerce | smoke-tests/update-woocommerce.spec.js |

### Merchant - My Subscriptions

| User Type | Flow Area        | Flow Name                               | Test File                         |
| --------- | ---------------- | --------------------------------------- | --------------------------------- |
| Merchant  | My Subscriptions | Can initiate WooCommerce.com Connection | merchant/settings-woo-com.spec.js |

### Merchant - Pages

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Pages     | Can create a new page | merchant/create-page.spec.js |

### Merchant - Posts

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Posts     | Can create a new post | merchant/create-post.spec.js |

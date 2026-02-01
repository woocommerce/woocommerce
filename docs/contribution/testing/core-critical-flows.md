---
post_title: WooCommerce core critical flows
sidebar_label: Core critical flows

---

# WooCommerce core critical flows

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
| Shopper   | Shop      | Search Store                                | shopper/shop-search-browse-sort.spec.ts      |
| Shopper   | Shop      | Browse by categories                        | shopper/shop-search-browse-sort.spec.ts      |
| Shopper   | Shop      | Can sort items                              | shopper/shop-search-browse-sort.spec.ts      |
| Shopper   | Shop      | Add Simple Product to Cart (from shop page) | shopper/cart.spec.ts                         |
| Shopper   | Shop      | Display shop catalog                        | shopper/shop-search-browse-sort.spec.ts      |
| Shopper   | Shop      | Products by tag                             | shopper/product-tags-attributes.spec.ts      |
| Shopper   | Shop      | Products by attribute                       | shopper/product-tags-attributes.spec.ts      |
| Shopper   | Shop      | Use product filters                         | shopper/shop-products-filer-by-price.spec.ts |

### Shopper - Product

| User Type | Flow Area | Flow Name                                            | Test File                                |
| --------- | --------- | ---------------------------------------------------- | ---------------------------------------- |
| Shopper   | Product   | Add Simple Product to Cart                           | shopper/product-simple.spec.ts           |
| Shopper   | Product   | Add Grouped Product to Cart                          | shopper/product-grouped.spec.ts          |
| Shopper   | Product   | Variable Product info updates depending on variation | shopper/product-variable.spec.ts         |
| Shopper   | Product   | Add Variable Product to Cart                         | shopper/product-variable.spec.ts         |
| Shopper   | Product   | Display up-sell product                              | products/product-linked-products.spec.ts |
| Shopper   | Product   | Display related products                             | products/product-linked-products.spec.ts |
| Shopper   | Product   | Display reviews                                      | merchant/product-reviews.spec.ts         |
| Shopper   | Product   | Add review                                           | merchant/product-reviews.spec.ts         |
| Shopper   | Product   | View product images                                  | shopper/product-simple.spec.ts           |
| Shopper   | Product   | View product descriptions                            | shopper/product-simple.spec.ts           |

### Shopper - Cart

| User Type | Flow Area | Flow Name                                  | Test File                                   |
| --------- | --------- | ------------------------------------------ | ------------------------------------------- |
| Shopper   | Cart      | Add to cart redirects to cart when enabled | shopper/cart-redirection.spec.ts            |
| Shopper   | Cart      | View cart                                  | shopper/cart.spec.ts                        |
| Shopper   | Cart      | Update product quantity within limits      | shopper/cart.spec.ts                        |
| Shopper   | Cart      | Remove products from cart                  | shopper/cart.spec.ts                        |
| Shopper   | Cart      | Apply all coupon types                     | shopper/cart-coupons.spec.ts                |
| Shopper   | Cart      | Display shipping options by address        | shopper/calculate-shipping.spec.ts          |
| Shopper   | Cart      | View empty cart                            | shopper/cart.spec.ts                        |
| Shopper   | Cart      | Display correct tax                        | shopper/cart-checkout-calculate-tax.spec.ts |
| Shopper   | Cart      | Respect coupon usage constraints           | shopper/cart-checkout-coupons.spec.ts       |
| Shopper   | Cart      | Display cross-sell products                | products/product-linked-products.spec.ts    |
| Shopper   | Cart      | Proceed to checkout                        | shopper/checkout.spec.ts                    |

### Shopper - Checkout

| User Type | Flow Area | Flow Name                                | Test File                                   |
| --------- | --------- | ---------------------------------------- | ------------------------------------------- |
| Shopper   | Checkout  | Correct item in Order Review             | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Can add shipping address                 | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Guest can place order                    | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Create an account                        | shopper/checkout-create-account.spec.ts     |
| Shopper   | Checkout  | Login to existing account                | shopper/checkout-login.spec.ts              |
| Shopper   | Checkout  | Existing customer can place order        | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Use all coupon types                     | shopper/checkout-coupons.spec.ts            |
| Shopper   | Checkout  | View checkout                            | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Receive warnings when form is incomplete | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Add billing address                      | shopper/checkout.spec.ts                    |
| Shopper   | Checkout  | Respect coupon usage constraints         | shopper/cart-checkout-coupons.spec.ts       |
| Shopper   | Checkout  | Display correct tax in checkout          | shopper/cart-checkout-calculate-tax.spec.ts |
| Shopper   | Checkout  | View order confirmation page             | shopper/checkout.spec.ts                    |

### Shopper - Email

| User Type | Flow Area | Flow Name                             | Test File                               |
| --------- | --------- | ------------------------------------- | --------------------------------------- |
| Shopper   | Email     | Customer Account Emails Received      | shopper/account-email-receiving.spec.ts |
| Shopper   | Email     | Customer Order Detail Emails Received | shopper/order-email-receiving.spec.ts   |

### Shopper - My Account

| User Type | Flow Area  | Flow Name                 | Test File                                 |
| --------- | ---------- | ------------------------- | ----------------------------------------- |
| Shopper   | My Account | Create an account         | shopper/my-account-create-account.spec.ts |
| Shopper   | My Account | Login to existing account | shopper/my-account.spec.ts                |
| Shopper   | My Account | View Account Details      | shopper/my-account.spec.ts                |
| Shopper   | My Account | Update Addresses          | shopper/my-account-addresses.spec.ts      |
| Shopper   | My Account | View Orders               | shopper/my-account-pay-order.spec.ts      |
| Shopper   | My Account | Pay for Order             | shopper/my-account-pay-order.spec.ts      |
| Shopper   | My Account | View Downloads            | shopper/my-account-downloads.spec.ts      |

### Merchant - Onboarding

| User Type | Flow Area     | Flow Name                                                      | Test File                                |
| --------- | ------------- | -------------------------------------------------------------- | ---------------------------------------- |
| Merchant  | Core Profiler | Introduction & opt-in                                          | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | User profile information                                       | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | Business information                                           | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | Extensions page                                                | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | WooPayments included in extensions for eligible criteria       | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | WooPayments not included in extensions for ineligible criteria | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | Install all default extensions                                 | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | Complete site setup                                            | activate-and-setup/core-profiler.spec.ts |
| Merchant  | Core Profiler | Skip introduction and confirm business location                | activate-and-setup/core-profiler.spec.ts |

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
| --------- | --------- |----------------------------------------|------------------------------------------|
| Merchant  | Settings  | Update General Settings                | merchant/settings-general.spec.ts        |
| Merchant  | Settings  | Add Tax Rates                          | merchant/settings-tax.spec.ts            |
| Merchant  | Settings  | Add Shipping Zones                     | merchant/create-shipping-zones.spec.ts   |
| Merchant  | Settings  | Add Shipping Classes                   | merchant/create-shipping-classes.spec.ts |
| Merchant  | Settings  | Enable local pickup for checkout block | merchant/settings-shipping.spec.ts       |
| Merchant  | Settings  | Update payment settings                | admin-tasks/payment.spec.ts              |
| Merchant  | Settings  | Handle Product Brands                  | merchant/create-product-brand.spec.ts    |

### Merchant - Coupons

| User Type | Flow Area | Flow Name             | Test File                                  |
| --------- | --------- | --------------------- | ------------------------------------------ |
| Merchant  | Coupons   | Add all coupon types  | merchant/create-coupon.spec.ts             |
| Merchant  | Coupons   | Add restricted coupon | merchant/create-restricted-coupons.spec.ts |

### Merchant - Marketing

| User Type | Flow Area | Flow Name                  | Test File                        |
| --------- | --------- | -------------------------- | -------------------------------- |
| Merchant  | Marketing | Display marketing overview | admin-marketing/overview.spec.ts |

### Merchant - Analytics

| User Type | Flow Area | Flow Name                                          | Test File                                  |
| --------- | --------- | -------------------------------------------------- | ------------------------------------------ |
| Merchant  | Analytics | View revenue report                                | admin-analytics/analytics.spec.ts          |
| Merchant  | Analytics | View overview report                               | admin-analytics/analytics-overview.spec.ts |
| Merchant  | Analytics | Confirm correct summary numbers on overview report | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Use date filter on overview page                   | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Customize performance indicators on overview page  | admin-analytics/analytics-overview.spec.ts |
| Merchant  | Analytics | Use date filter on revenue report                  | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Download revenue report as CSV                     | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Use advanced filters on orders report              | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Analytics settings                                 | admin-analytics/analytics-data.spec.ts     |
| Merchant  | Analytics | Set custom date range on revenue report            | admin-analytics/analytics-data.spec.ts     |

### Merchant - Products

| User Type | Flow Area      | Flow Name                      | Test File                                                                 |
| --------- | -------------- | ------------------------------ | ------------------------------------------------------------------------- |
| Merchant  | Products       | View all products              | merchant/product-search.spec.ts                                           |
| Merchant  | Products       | Search products                | merchant/product-search.spec.ts                                           |
| Merchant  | Products       | Add simple product             | merchant/product-create-simple.spec.ts                                    |
| Merchant  | Products       | Add variable product           | merchant/products/add-variable-product/create-variable-product.spec.ts    |
| Merchant  | Products       | Edit product details           | merchant/product-edit.spec.ts                                             |
| Merchant  | Products       | Add virtual product            | merchant/product-create-simple.spec.ts                                    |
| Merchant  | Products       | Import products CSV            | merchant/product-import-csv.spec.ts                                       |
| Merchant  | Products       | Add downloadable product       | merchant/product-create-simple.spec.ts                                    |
| Merchant  | Products       | View product reviews list      | merchant/product-reviews.spec.ts                                          |
| Merchant  | Products       | View all products reviews list | merchant/product-reviews.spec.ts                                          |
| Merchant  | Products       | Edit product review            | merchant/product-reviews.spec.ts                                          |
| Merchant  | Products       | Trash product review           | merchant/product-reviews.spec.ts                                          |
| Merchant  | Products       | Bulk edit products             | merchant/product-edit.spec.ts                                             |
| Merchant  | Products       | Remove products                | merchant/product-delete.spec.ts                                           |
| Merchant  | Products       | Manage product images          | merchant/product-images.spec.ts                                           |
| Merchant  | Products       | Manage product inventory       | merchant/product-create-simple.spec.ts                                    |
| Merchant  | Products       | Manage product attributes      | merchant/product-create-simple.spec.ts                                    |
| Merchant  | Products       | Manage global attributes       |                                                                           |
| Merchant  | Products       | Add up-sell                    | products/product-linked-products.spec.ts                                  |
| Merchant  | Products       | Add cross-sell                 | products/product-linked-products.spec.ts                                  |
| Merchant  | Products (New) | Disable new product experience | merchant/products/block-editor/disable-block-product-editor.spec.ts       |
| Merchant  | Products (New) | Add simple product             | merchant/products/block-editor/create-simple-product-block-editor.spec.ts |
| Merchant  | Products (New) | Edit simple product            | merchant/products/block-editor/product-edit-block-editor.spec.ts          |
| Merchant  | Products (New) | Manage product images          | merchant/products/block-editor/product-images-block-editor.spec.ts        |
| Merchant  | Products (New) | Manage product inventory       | merchant/products/block-editor/product-inventory-block-editor.spec.ts     |
| Merchant  | Products (New) | Manage product attributes      | merchant/products/block-editor/product-attributes-block-editor.spec.ts    |

### Merchant - Orders

| User Type | Flow Area | Flow Name                                                        | Test File                              |
| --------- | --------- | ---------------------------------------------------------------- | -------------------------------------- |
| Merchant  | Orders    | View all orders                                                  | merchant/order-status-filter.spec.ts   |
| Merchant  | Orders    | Can add new order basic                                          | merchant/order-edit.spec.ts            |
| Merchant  | Orders    | View single order                                                | merchant/order-edit.spec.ts            |
| Merchant  | Orders    | Update order status to completed                                 | merchant/order-edit.spec.ts            |
| Merchant  | Orders    | Update order status to cancelled                                 | merchant/order-edit.spec.ts            |
| Merchant  | Orders    | Update order details                                             | merchant/order-edit.spec.ts            |
| Merchant  | Orders    | Customer payment page                                            | merchant/customer-payment-page.spec.ts |
| Merchant  | Orders    | Refund order                                                     | merchant/order-refund.spec.ts          |
| Merchant  | Orders    | Apply coupon                                                     | merchant/order-coupon.spec.ts          |
| Merchant  | Orders    | Can add new order complex - multiple product types & tax classes | merchant/create-order.spec.ts          |
| Merchant  | Orders    | Search orders                                                    | merchant/order-search.spec.ts          |
| Merchant  | Orders    | Filter orders by order status                                    | merchant/order-status-filter.spec.ts   |
| Merchant  | Orders    | Bulk change order status                                         | merchant/order-bulk-edit.spec.ts       |
| Merchant  | Orders    | Add order notes                                                  | merchant/order-edit.spec.ts            |

### Merchant - Customers

| User Type | Flow Area | Flow Name             | Test File                      |
| --------- | --------- | --------------------- | ------------------------------ |
| Merchant  | Customers | Display customer list | merchant/customer-list.spec.ts |

### Merchant - Email

| User Type | Flow Area | Flow Name                                          | Test File                     |
| --------- | --------- | -------------------------------------------------- | ----------------------------- |
| Merchant  | Email     | Receive and check content of new order email       | merchant/order-emails.spec.ts |
| Merchant  | Email     | Receive and check content of cancelled order email | merchant/order-emails.spec.ts |
| Merchant  | Email     | Receive and check content of failed order email    | merchant/order-emails.spec.ts |
| Merchant  | Email     | Resent new order email                             | merchant/order-emails.spec.ts |
| Merchant  | Email     | Send invoice/order details to customer via Email   | merchant/order-emails.spec.ts |

### Merchant - Plugins

| User Type | Flow Area | Flow Name              | Test File                              |
| --------- | --------- | ---------------------- | -------------------------------------- |
| Merchant  | Plugins   | Can update WooCommerce | smoke-tests/update-woocommerce.spec.ts |

### Merchant - My Subscriptions

| User Type | Flow Area        | Flow Name                               | Test File                         |
| --------- | ---------------- | --------------------------------------- | --------------------------------- |
| Merchant  | My Subscriptions | Can initiate WooCommerce.com Connection | merchant/settings-woo-com.spec.ts |

### Merchant - Pages

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Pages     | Can create a new page | merchant/create-page.spec.ts |

### Merchant - Posts

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Posts     | Can create a new post | merchant/create-post.spec.ts |

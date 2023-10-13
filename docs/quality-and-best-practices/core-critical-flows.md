# WooCommerce core critical flows

We have identified what we consider to be our most critical user flows within WooCommerce Core. These flows will help us focus and prioritize our testing efforts. They will also help us consider the impact of changes and priority of issues.

These flows will continually evolve as the platform evolves with flows updated, added or re-prioritised.

## Shopper critical flow areas

-   🛒 [Shopper > Shop](#shopper---shop)
-   🛒 [Shopper > Product](#shopper---product)
-   🛒 [Shopper > Cart](#shopper---cart)
-   🛒 [Shopper > Checkout](#shopper---checkout)
-   🛒 [Shopper > Email](#shopper---email)
-   🛒 [Shopper > My Account](#shopper---my-account)

## Merchant critical flow areas

-   💳 [Merchant > Onboarding](#merchant---onboarding)
-   💳 [Merchant > Dashboard](#merchant---dashboard)
-   💳 [Merchant > Settings](#merchant---settings)
-   💳 [Merchant > Coupons](#merchant---coupons)
-   💳 [Merchant > Analytics](#merchant---analytics)
-   💳 [Merchant > Products](#merchant---products)
-   💳 [Merchant > Orders](#merchant---orders)
-   💳 [Merchant > Email](#merchant---email)
-   💳 [Merchant > Plugins](#merchant---plugins)
-   💳 [Merchant > My Subscriptions](#merchant---my-subscriptions)
-   💳 [Merchant > Pages](#merchant---pages)
-   💳 [Merchant > Posts](#merchant---posts)

### Shopper - Shop

| User Type | Flow Area | Flow Name                                   | Test File                               |
| --------- | --------- | ------------------------------------------- | --------------------------------------- |
| Shopper   | Shop      | Search Store                                | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Browse by categories                        | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Can sort items                              | shopper/shop-search-browse-sort.spec.js |
| Shopper   | Shop      | Add Simple Product to Cart (from shop page) | shopper/cart.spec.js                    |

### Shopper - Product

| User Type | Flow Area | Flow Name                                            | Test File                        |
| --------- | --------- | ---------------------------------------------------- | -------------------------------- |
| Shopper   | Product   | Add Simple Product to Cart                           | shopper/product-simple.spec.js   |
| Shopper   | Product   | Add Grouped Product to Cart                          | shopper/product-grouped.spec.js  |
| Shopper   | Product   | Variable Product info updates depending on variation | shopper/product-variable.spec.js |
| Shopper   | Product   | Add Variable Product to Cart                         | shopper/product-variable.spec.js |

### Shopper - Cart

| User Type | Flow Area | Flow Name                                  | Test File                          |
| --------- | --------- | ------------------------------------------ | ---------------------------------- |
| Shopper   | Cart      | Add to Cart redirects to Cart when enabled | shopper/cart-redirection.spec.js   |
| Shopper   | Cart      | View Cart                                  | shopper/cart.spec.js               |
| Shopper   | Cart      | Update Product quantity                    | shopper/cart.spec.js               |
| Shopper   | Cart      | Remove Product                             | shopper/cart.spec.js               |
| Shopper   | Cart      | Apply Coupon                               | shopper/cart-coupons.spec.js       |
| Shopper   | Cart      | Calculate shipping address                 | shopper/calculate-shipping.spec.js |

### Shopper - Checkout

| User Type | Flow Area | Flow Name                         | Test File                               |
| --------- | --------- | --------------------------------- | --------------------------------------- |
| Shopper   | Checkout  | Correct item in Order Review      | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Can add shipping address          | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Guest can place order             | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Create an account                 | shopper/checkout-create-account.spec.js |
| Shopper   | Checkout  | Login to existing account         | shopper/checkout-login.spec.js          |
| Shopper   | Checkout  | Existing customer can place order | shopper/checkout.spec.js                |
| Shopper   | Checkout  | Apply Coupon                      | shopper/checkout-coupons.spec.js        |

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

| User Type | Flow Area    | Flow Name                   | Test File                                   |
| --------- | ------------ | --------------------------- | ------------------------------------------- |
| Merchant  | Setup Wizard | Can go through Setup Wizard | activate-and-setup/setup-onboarding.spec.js |

### Merchant - Dashboard

| User Type | Flow Area    | Flow Name                 | Test File           |
| --------- | ------------ | ------------------------- | ------------------- |
| Merchant  | WC Dashboard | Can go through Setup List | activate-and-setup/ |

### Merchant - Settings

| User Type | Flow Area | Flow Name               | Test File                                |
| --------- | --------- | ----------------------- | ---------------------------------------- |
| Merchant  | Settings  | Update General Settings | merchant/settings-general.spec.js        |
| Merchant  | Settings  | Add Tax Rates           | merchant/settings-tax.spec.js            |
| Merchant  | Settings  | Add Shipping Zones      | merchant/create-shipping-zones.spec.js   |
| Merchant  | Settings  | Add Shipping Classes    | merchant/create-shipping-classes.spec.js |

### Merchant - Coupons

| User Type | Flow Area | Flow Name  | Test File                      |
| --------- | --------- | ---------- | ------------------------------ |
| Merchant  | Coupons   | Add Coupon | merchant/create-coupon.spec.js |

### Merchant - Analytics

| User Type | Flow Area | Flow Name           | Test File                         |
| --------- | --------- | ------------------- | --------------------------------- |
| Merchant  | Analytics | View Revenue Report | admin-analytics/analytics.spec.js |

### Merchant - Products

| User Type | Flow Area | Flow Name            | Test File                                                              |
| --------- | --------- | -------------------- | ---------------------------------------------------------------------- |
| Merchant  | Products  | View all Products    |                                                                        |
| Merchant  | Products  | Search Products      | merchant/product-search.spec.js                                        |
| Merchant  | Products  | Add Simple Product   | merchant/create-simple-product.spec.js                                 |
| Merchant  | Products  | Add Variable Product | merchant/products/add-variable-product/create-variable-product.spec.js |
| Merchant  | Products  | Edit Product Details | merchant/product-edit.spec.js                                          |
| Merchant  | Products  | Add Virtual Product  | merchant/create-simple-product.spec.js                                 |
| Merchant  | Products  | Import Products CSV  | merchant/product-import-csv.spec.js                                    |

### Merchant - Orders

| User Type | Flow Area | Flow Name                                                        | Test File                              |
| --------- | --------- | ---------------------------------------------------------------- | -------------------------------------- |
| Merchant  | Orders    | View all Orders                                                  | merchant/                              |
| Merchant  | Orders    | Can add new Order basic                                          | merchant/order-edit.spec.js            |
| Merchant  | Orders    | View single Order                                                | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update Order Status                                              | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Update Order Details                                             | merchant/order-edit.spec.js            |
| Merchant  | Orders    | Customer Payment Page                                            | merchant/customer-payment-page.spec.js |
| Merchant  | Orders    | Refund Order                                                     | merchant/order-refund.spec.js          |
| Merchant  | Orders    | Apply Coupon                                                     | merchant/order-coupon.spec.js          |
| Merchant  | Orders    | Can add new Order complex - multiple product types & tax classes | merchant/create-order.spec.js          |
| Merchant  | Orders    | Search Orders                                                    | merchant/order-search.spec.js          |
| Merchant  | Orders    | Filter Orders by order status                                    | merchant/order-status-filter.spec.js   |

### Merchant - Email

| User Type | Flow Area | Flow Name                                       | Test File                     |
| --------- | --------- | ----------------------------------------------- | ----------------------------- |
| Merchant  | Email     | Merchant Order Emails (New Order, etc) Received | merchant/order-emails.spec.js |

### Merchant - Plugins

| User Type | Flow Area | Flow Name              | Test File                              |
| --------- | --------- | ---------------------- | -------------------------------------- |
| Merchant  | Plugins   | Can update WooCommerce | smoke-tests/update-woocommerce.spec.js |

### Merchant - My Subscriptions

| User Type | Flow Area        | Flow Name                               | Test File |
| --------- | ---------------- | --------------------------------------- | --------- |
| Merchant  | My Subscriptions | Can initiate WooCommerce.com Connection |           |

### Merchant - Pages

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Pages     | Can create a new page | merchant/create-page.spec.js |

### Merchant - Posts

| User Type | Flow Area | Flow Name             | Test File                    |
| --------- | --------- | --------------------- | ---------------------------- |
| Merchant  | Posts     | Can create a new post | merchant/create-post.spec.js |

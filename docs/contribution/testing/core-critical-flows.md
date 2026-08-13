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

| User Type | Flow Area | Flow Name                                   |
| --------- | --------- | ------------------------------------------- |
| Shopper   | Shop      | Search Store                                |
| Shopper   | Shop      | Browse by categories                        |
| Shopper   | Shop      | Can sort items                              |
| Shopper   | Shop      | Add Simple Product to Cart (from shop page) |
| Shopper   | Shop      | Display shop catalog                        |
| Shopper   | Shop      | Products by tag                             |
| Shopper   | Shop      | Products by attribute                       |
| Shopper   | Shop      | Use product filters                         |

### Shopper - Product

| User Type | Flow Area | Flow Name                                            |
| --------- | --------- | ---------------------------------------------------- |
| Shopper   | Product   | Add Simple Product to Cart                           |
| Shopper   | Product   | Add Grouped Product to Cart                          |
| Shopper   | Product   | Variable Product info updates depending on variation |
| Shopper   | Product   | Add Variable Product to Cart                         |
| Shopper   | Product   | Display up-sell product                              |
| Shopper   | Product   | Display related products                             |
| Shopper   | Product   | Display reviews                                      |
| Shopper   | Product   | Add review                                           |
| Shopper   | Product   | View product images                                  |
| Shopper   | Product   | View product descriptions                            |

### Shopper - Cart

| User Type | Flow Area | Flow Name                                  |
| --------- | --------- | ------------------------------------------ |
| Shopper   | Cart      | Add to cart redirects to cart when enabled |
| Shopper   | Cart      | View cart                                  |
| Shopper   | Cart      | Update product quantity within limits      |
| Shopper   | Cart      | Remove products from cart                  |
| Shopper   | Cart      | Apply all coupon types                     |
| Shopper   | Cart      | Display shipping options by address        |
| Shopper   | Cart      | View empty cart                            |
| Shopper   | Cart      | Display correct tax                        |
| Shopper   | Cart      | Respect coupon usage constraints           |
| Shopper   | Cart      | Display cross-sell products                |
| Shopper   | Cart      | Proceed to checkout                        |

### Shopper - Checkout

| User Type | Flow Area | Flow Name                                |
| --------- | --------- | ---------------------------------------- |
| Shopper   | Checkout  | Correct item in Order Review             |
| Shopper   | Checkout  | Can add shipping address                 |
| Shopper   | Checkout  | Guest can place order                    |
| Shopper   | Checkout  | Create an account                        |
| Shopper   | Checkout  | Login to existing account                |
| Shopper   | Checkout  | Existing customer can place order        |
| Shopper   | Checkout  | Use all coupon types                     |
| Shopper   | Checkout  | View checkout                            |
| Shopper   | Checkout  | Receive warnings when form is incomplete |
| Shopper   | Checkout  | Add billing address                      |
| Shopper   | Checkout  | Respect coupon usage constraints         |
| Shopper   | Checkout  | Display correct tax in checkout          |
| Shopper   | Checkout  | View order confirmation page             |

### Shopper - Email

| User Type | Flow Area | Flow Name                             |
| --------- | --------- | ------------------------------------- |
| Shopper   | Email     | Customer Account Emails Received      |
| Shopper   | Email     | Customer Order Detail Emails Received |

### Shopper - My Account

| User Type | Flow Area  | Flow Name                 |
| --------- | ---------- | ------------------------- |
| Shopper   | My Account | Create an account         |
| Shopper   | My Account | Login to existing account |
| Shopper   | My Account | View Account Details      |
| Shopper   | My Account | Update Addresses          |
| Shopper   | My Account | View Orders               |
| Shopper   | My Account | Pay for Order             |
| Shopper   | My Account | View Downloads            |

### Merchant - Onboarding

| User Type | Flow Area      | Flow Name                                                      |
| --------- | -------------- | -------------------------------------------------------------- |
| Merchant  | Core Profiler  | Introduction & opt-in                                          |
| Merchant  | Core Profiler  | User profile information                                       |
| Merchant  | Core Profiler  | Business information                                           |
| Merchant  | Core Profiler  | Extensions page                                                |
| Merchant  | Core Profiler  | WooPayments included in extensions for eligible criteria       |
| Merchant  | Core Profiler  | WooPayments not included in extensions for ineligible criteria |
| Merchant  | Core Profiler  | Install all default extensions                                 |
| Merchant  | Core Profiler  | Complete site setup                                            |
| Merchant  | Core Profiler  | Skip introduction and confirm business location                |
| Merchant  | NOX Onboarding | Open onboarding from Payments settings                         |

### Merchant - Dashboard

| User Type | Flow Area      | Flow Name                                              |
| --------- | -------------- | ------------------------------------------------------ |
| Merchant  | WC Home        | Completing profiler redirects to home                  |
| Merchant  | WC Home        | Complete all steps on task list                        |
| Merchant  | WC Home        | Hide the task list                                     |
| Merchant  | WC Home        | Store management displayed after task list finished    |
| Merchant  | WC Home        | Direct access to analytics reports from stats overview |
| Merchant  | WC Home        | Preserve task list completion status after upgrade     |
| Merchant  | WC Home        | Interact with extended task list                       |
| Merchant  | Activity Panel | Interact with activity button                          |
| Merchant  | Inbox          | Interact with notes and perform CTAs                   |
| Merchant  | Inbox          | Dismiss single note and all notes                      |

### Merchant - Settings

| User Type | Flow Area | Flow Name                              |
| --------- | --------- | -------------------------------------- |
| Merchant  | Settings  | Update General Settings                |
| Merchant  | Settings  | Add Tax Rates                          |
| Merchant  | Settings  | Add Shipping Zones                     |
| Merchant  | Settings  | Add Shipping Classes                   |
| Merchant  | Settings  | Enable local pickup for checkout block |
| Merchant  | Settings  | Update payment settings                |
| Merchant  | Settings  | Handle Product Brands                  |

### Merchant - Coupons

| User Type | Flow Area | Flow Name             |
| --------- | --------- | --------------------- |
| Merchant  | Coupons   | Add all coupon types  |
| Merchant  | Coupons   | Add restricted coupon |

### Merchant - Marketing

| User Type | Flow Area | Flow Name                  |
| --------- | --------- | -------------------------- |
| Merchant  | Marketing | Display marketing overview |

### Merchant - Analytics

| User Type | Flow Area | Flow Name                                          |
| --------- | --------- | -------------------------------------------------- |
| Merchant  | Analytics | View revenue report                                |
| Merchant  | Analytics | View overview report                               |
| Merchant  | Analytics | Confirm correct summary numbers on overview report |
| Merchant  | Analytics | Use date filter on overview page                   |
| Merchant  | Analytics | Customize performance indicators on overview page  |
| Merchant  | Analytics | Use date filter on revenue report                  |
| Merchant  | Analytics | Download revenue report as CSV                     |
| Merchant  | Analytics | Use advanced filters on orders report              |
| Merchant  | Analytics | Analytics settings                                 |
| Merchant  | Analytics | Set custom date range on revenue report            |

### Merchant - Products

| User Type | Flow Area      | Flow Name                      |
| --------- | -------------- | ------------------------------ |
| Merchant  | Products       | View all products              |
| Merchant  | Products       | Search products                |
| Merchant  | Products       | Add simple product             |
| Merchant  | Products       | Add variable product           |
| Merchant  | Products       | Edit product details           |
| Merchant  | Products       | Add virtual product            |
| Merchant  | Products       | Import products CSV            |
| Merchant  | Products       | Add downloadable product       |
| Merchant  | Products       | View product reviews list      |
| Merchant  | Products       | View all products reviews list |
| Merchant  | Products       | Edit product review            |
| Merchant  | Products       | Trash product review           |
| Merchant  | Products       | Bulk edit products             |
| Merchant  | Products       | Remove products                |
| Merchant  | Products       | Manage product images          |
| Merchant  | Products       | Manage product inventory       |
| Merchant  | Products       | Manage product attributes      |
| Merchant  | Products       | Manage global attributes       |
| Merchant  | Products       | Add up-sell                    |
| Merchant  | Products       | Add cross-sell                 |
| Merchant  | Products (New) | Disable new product experience |
| Merchant  | Products (New) | Add simple product             |
| Merchant  | Products (New) | Edit simple product            |
| Merchant  | Products (New) | Manage product images          |
| Merchant  | Products (New) | Manage product inventory       |
| Merchant  | Products (New) | Manage product attributes      |

### Merchant - Orders

| User Type | Flow Area | Flow Name                                                        |
| --------- | --------- | ---------------------------------------------------------------- |
| Merchant  | Orders    | View all orders                                                  |
| Merchant  | Orders    | Can add new order basic                                          |
| Merchant  | Orders    | View single order                                                |
| Merchant  | Orders    | Update order status to completed                                 |
| Merchant  | Orders    | Update order status to cancelled                                 |
| Merchant  | Orders    | Update order details                                             |
| Merchant  | Orders    | Customer payment page                                            |
| Merchant  | Orders    | Refund order                                                     |
| Merchant  | Orders    | Apply coupon                                                     |
| Merchant  | Orders    | Can add new order complex - multiple product types & tax classes |
| Merchant  | Orders    | Search orders                                                    |
| Merchant  | Orders    | Filter orders by order status                                    |
| Merchant  | Orders    | Bulk change order status                                         |
| Merchant  | Orders    | Add order notes                                                  |

### Merchant - Customers

| User Type | Flow Area | Flow Name             |
| --------- | --------- | --------------------- |
| Merchant  | Customers | Display customer list |

### Merchant - Email

| User Type | Flow Area | Flow Name                                          |
| --------- | --------- | -------------------------------------------------- |
| Merchant  | Email     | Receive and check content of new order email       |
| Merchant  | Email     | Receive and check content of cancelled order email |
| Merchant  | Email     | Receive and check content of failed order email    |
| Merchant  | Email     | Resend new order email                             |
| Merchant  | Email     | Send invoice/order details to customer via Email   |

### Merchant - Plugins

| User Type | Flow Area | Flow Name              |
| --------- | --------- | ---------------------- |
| Merchant  | Plugins   | Can update WooCommerce |

### Merchant - My Subscriptions

| User Type | Flow Area        | Flow Name                               |
| --------- | ---------------- | --------------------------------------- |
| Merchant  | My Subscriptions | Can initiate WooCommerce.com Connection |

### Merchant - Pages

| User Type | Flow Area | Flow Name             |
| --------- | --------- | --------------------- |
| Merchant  | Pages     | Can create a new page |

### Merchant - Posts

| User Type | Flow Area | Flow Name             |
| --------- | --------- | --------------------- |
| Merchant  | Posts     | Can create a new post |

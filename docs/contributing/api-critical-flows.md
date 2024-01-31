---
post_title: Critical flows within the WooCommerce Core API
menu_title: API critical flows
tags: reference
---

In our documentation, we've pinpointed the essential user flows within the WooCommerce Core API. These flows serve as
the compass for our testing initiatives, aiding us in concentrating our efforts where they matter most. They also
provide invaluable insights into assessing the ramifications of modifications and determining issue priorities.

It's important to note that these flows remain dynamic, evolving in lockstep with the platform. They regularly undergo
updates, additions, and re-prioritization to stay aligned with the evolving needs of our system.

## Products

| Route    | Flow name                  | Endpoint                       | Test File                                                   |
|----------|----------------------------|--------------------------------|-------------------------------------------------------------|
| Products | Can view all products      | `/wp-json/wc/v3/products`      | `tests/api-core-tests/tests/products/product-list.test.js`  |
| Products | Can search products        | `/wp-json/wc/v3/products`      | `tests/api-core-tests/tests/products/product-list.test.js`  |
| Products | Can add a simple product   | `/wp-json/wc/v3/products`      | `tests/api-core-tests/tests/products/products-crud.test.js` |
| Products | Can add a variable product | `/wp-json/wc/v3/products`      | `tests/api-core-tests/tests/products/products-crud.test.js` |
| Products | Can add a virtual product  | `/wp-json/wc/v3/products`      | `tests/api-core-tests/tests/products/products-crud.test.js` |
| Products | Can view a single product  | `/wp-json/wc/v3/products/{id}` | `tests/api-core-tests/tests/products/products-crud.test.js` |
| Products | Can update a product       | `/wp-json/wc/v3/products/{id}` | `tests/api-core-tests/tests/products/products-crud.test.js` |
| Products | Can delete a product       | `/wp-json/wc/v3/products/{id}` | `tests/api-core-tests/tests/products/products-crud.test.js` |

## Orders

| Route  | Flow name                                                        | Endpoints                    | Test File                                                 |
|--------|------------------------------------------------------------------|------------------------------|-----------------------------------------------------------|
| Orders | Can create an order                                              | `/wp-json/wc/v3/orders`      | `tests/api-core-tests/tests/orders/orders-crud.test.js`   |
| Orders | Can view a single order                                          | `/wp-json/wc/v3/orders/{id}` | `tests/api-core-tests/tests/orders/orders-crud.test.js`   |
| Orders | Can update an order                                              | `/wp-json/wc/v3/orders/{id}` | `tests/api-core-tests/tests/orders/orders-crud.test.js`   |
| Orders | Can delete an order                                              | `/wp-json/wc/v3/orders/{id}` | `tests/api-core-tests/tests/orders/orders-crud.test.js`   |
| Orders | Can view all orders                                              | `/wp-json/wc/v3/orders`      | `tests/api-core-tests/tests/orders/orders.test.js`        |
| Orders | Can search orders                                                | `/wp-json/wc/v3/orders`      | `tests/api-core-tests/tests/orders/order-search.test.js`  |
| Orders | Can add new Order complex - multiple product types & tax classes | `/wp-json/wc/v3/orders`      | `tests/api-core-tests/tests/orders/order-complex.test.js` |

## Refunds

| Route   | Flow name           | Endpoints                            | Test File                                           |
|---------|---------------------|--------------------------------------|-----------------------------------------------------|
| Refunds | Can refund an order | `/wp-json/wc/v3/orders/{id}/refunds` | `tests/api-core-tests/tests/refunds/refund.test.js` |

## Coupons

| Route   | Flow name                 | Endpoints                            | Test File                                            |
|---------|---------------------------|--------------------------------------|------------------------------------------------------|
| Coupons | Can create a coupon       | `/wp-json/wc/v3/coupons`             | `tests/api-core-tests/tests/coupons/coupons.test.js` |
| Coupons | Can update a coupon       | `/wp-json/wc/v3/coupons/{id}`        | `tests/api-core-tests/tests/coupons/coupons.test.js` |
| Coupons | Can delete a coupon       | `/wp-json/wc/v3/coupons/{id}`        | `tests/api-core-tests/tests/coupons/coupons.test.js` |
| Coupons | Can add a coupon to order | `/wp-json/wc/v3/orders/{id}/coupons` | `tests/api-core-tests/tests/coupons/coupons.test.js` |

## Shipping

| Route            | Flow name                                     | Endpoints                                    | Test File                                                    |
|------------------|-----------------------------------------------|----------------------------------------------|--------------------------------------------------------------|
| Shipping zones   | Can create shipping zones                     | `/wp-json/wc/v3/shipping/zones`              | `tests/api-core-tests/tests/shipping/shipping-zones.test.js` |
| Shipping methods | Can create shipping method to a shipping zone | `/wp-json/wc/v3/shipping/zones/{id}/methods` | n/a                                                          |
| Shipping classes | Can create a product shipping class           | `/wp-json/wc/v3/products/shipping_classes`   | `tests/api-core-tests/tests/products/products-crud.test.js`  |


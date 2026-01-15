---
sidebar_label: Store API 
category_slug: store-api
post_title: Store API
---

# WooCommerce Store API

**The Store API provides public Rest API endpoints for the development of customer-facing cart, checkout, and product functionality. It follows many of the patterns used in the [WordPress REST API](https://developer.wordpress.org/rest-api/key-concepts/).**

In contrast to the WooCommerce REST API, the Store API is unauthenticated and does not provide access to sensitive store data or other customer information.

Example of a valid API request using cURL:

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products"
```

Possible uses of the Store API include:

1. Obtaining a list of products to display that can be searched or filtered
2. Adding products to the cart and returning an updated cart object for display
3. Obtaining shipping rates for a cart
4. Converting a customer’s cart to an Order, collecting addresses, and then facilitating payment

## Requirements and limitations

* This is an unauthenticated API. It does not require API keys or authentication tokens for access.
* All API responses return JSON-formatted data.
* Data returned from the API is reflective of the current user (customer). Customer sessions in WooCommerce are cookie-based.
* Store API cannot be used to look up other customers and orders by ID; only data belonging to the current user.
* Likewise, Store API cannot be used to write store data e.g. settings. For more extensive access, use the authenticated [WC REST API.](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction)
* Endpoints that do allow writes, for example, updating the current customer address, require a [nonce-token](https://developer.wordpress.org/plugins/security/nonces/).
* Store API is render-target agnostic and should not make assumptions about where content will be displayed. For example, returning HTML would be discouraged unless the data type itself is HTML.

## Store API Namespace

Resources in the Store API are all found within the `wc/store/v1` namespace, and since this API extends the WordPress API, accessing it requires the `/wp-json/` base. Currently, the only version is `v1`. If the version is omitted, `v1` will be served.

Examples:

```http
GET /wp-json/wc/store/v1/products
GET /wp-json/wc/store/v1/cart
```

The API uses JSON to serialize data. You don’t need to specify `.json` at the end of an API URL.

## Resources and endpoints

Available resources in the Store API are listed below, with links to more detailed documentation.

| Resource                                                     | Methods                        | Endpoints                                                                                     |
| :----------------------------------------------------------- | :----------------------------- | --------------------------------------------------------------------------------------------- |
| [`Cart`](/docs/apis/store-api/resources-endpoints/cart)                                       | `GET`                          | [`/wc/store/v1/cart`](/docs/apis/store-api/resources-endpoints/cart#get-cart)                                                  |
|                                                              | `POST`                         | [`/wc/store/v1/cart/add-item`](/docs/apis/store-api/resources-endpoints/cart#add-item)                                         |
|                                                              | `POST`                         | [`/wc/store/v1/cart/remove-item`](/docs/apis/store-api/resources-endpoints/cart#remove-item)                                   |
|                                                              | `POST`                         | [`/wc/store/v1/cart/update-item`](/docs/apis/store-api/resources-endpoints/cart#update-item)                                   |
|                                                              | `POST`                         | [`/wc/store/v1/cart/apply-coupon`](/docs/apis/store-api/resources-endpoints/cart#apply-coupon)                                 |
|                                                              | `POST`                         | [`/wc/store/v1/cart/remove-coupon`](/docs/apis/store-api/resources-endpoints/cart#remove-coupon)                               |
|                                                              | `POST`                         | [`/wc/store/v1/cart/update-customer`](/docs/apis/store-api/resources-endpoints/cart#update-customer)                           |
|                                                              | `POST`                         | [`/wc/store/v1/cart/select-shipping-rate`](/docs/apis/store-api/resources-endpoints/cart#select-shipping-rate)                 |
| [`Cart Items`](/docs/apis/store-api/resources-endpoints/cart-items)                           | `GET`, `POST`, `DELETE`        | [`/wc/store/v1/cart/items`](/docs/apis/store-api/resources-endpoints/cart-items#list-cart-items)                               |
|                                                              | `GET`, `POST`, `PUT`, `DELETE` | [`/wc/store/v1/cart/items/:key`](/docs/apis/store-api/resources-endpoints/cart-items#single-cart-item)                         |
| [`Cart Coupons`](/docs/apis/store-api/resources-endpoints/cart-coupons)                       | `GET`, `POST`, `DELETE`        | [`/wc/store/v1/cart/coupons`](/docs/apis/store-api/resources-endpoints/cart-coupons#list-cart-coupons)                         |
|                                                              | `GET`, `DELETE`                | [`/wc/store/v1/cart/coupon/:code`](/docs/apis/store-api/resources-endpoints/cart-coupons#single-cart-coupon)                   |
| [`Checkout`](/docs/apis/store-api/resources-endpoints/checkout)                               | `GET`, `POST`, `PUT`           | [`/wc/store/v1/checkout`](/docs/apis/store-api/resources-endpoints/checkout)                                                   |
| [`Checkout order`](/docs/apis/store-api/resources-endpoints/checkout-order)                   | `POST`                         | [`/wc/store/v1/checkout/:id`](/docs/apis/store-api/resources-endpoints/checkout-order)                                         |
| [`Order`](/docs/apis/store-api/resources-endpoints/order)                                     | `GET`                          | [`/wc/store/v1/order/:id`](/docs/apis/store-api/resources-endpoints/order)                                                     |
| [`Products`](/docs/apis/store-api/resources-endpoints/products)                               | `GET`                          | [`/wc/store/v1/products`](/docs/apis/store-api/resources-endpoints/products#list-products)                                     |
|                                                              | `GET`                          | [`/wc/store/v1/products/:id`](/docs/apis/store-api/resources-endpoints/products#single-product-by-id)                                |
| [`Product Collection Data`](/docs/apis/store-api/resources-endpoints/product-collection-data) | `GET`                          | [`/wc/store/v1/products/collection-data`](/docs/apis/store-api/resources-endpoints/product-collection-data)                    |
| [`Product Attributes`](/docs/apis/store-api/resources-endpoints/product-attributes)           | `GET`                          | [`/wc/store/v1/products/attributes`](/docs/apis/store-api/resources-endpoints/product-attributes#list-product-attributes)      |
|                                                              | `GET`                          | [`/wc/store/v1/products/attributes/:id`](/docs/apis/store-api/resources-endpoints/product-attributes#single-product-attribute) |
| [`Product Attribute Terms`](/docs/apis/store-api/resources-endpoints/product-attribute-terms) | `GET`                          | [`/wc/store/v1/products/attributes/:id/terms`](/docs/apis/store-api/resources-endpoints/product-attribute-terms)               |
| [`Product Categories`](/docs/apis/store-api/resources-endpoints/product-categories)           | `GET`                          | [`/wc/store/v1/products/categories`](/docs/apis/store-api/resources-endpoints/product-categories)                              |
| [`Product Brands`](/docs/apis/store-api/resources-endpoints/product-brands)                   | `GET`                          | [`/wc/store/v1/products/brands`](/docs/apis/store-api/resources-endpoints/product-brands)                                      |
| [`Product Reviews`](/docs/apis/store-api/resources-endpoints/product-reviews)                 | `GET`                          | [`/wc/store/v1/products/reviews`](/docs/apis/store-api/resources-endpoints/product-reviews)                                    |
| [`Product Tags`](/docs/apis/store-api/resources-endpoints/product-tags)                       | `GET`                          | [`/wc/store/v1/products/tags`](/docs/apis/store-api/resources-endpoints/product-tags)                                          |

## Pagination

If collections contain many results, they may be paginated. When listing resources you can pass the following parameters:

| Parameter  | Description                                                                            |
| :--------- | :------------------------------------------------------------------------------------- |
| `page`     | Current page of the collection. Defaults to `1`.                                       |
| `per_page` | Maximum number of items to be returned in result set. Defaults to `10`. Maximum `100`. |

In the example below, we list 20 products per page and return page 2.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products?page=2&per_page=20"
```

Additional pagination headers are also sent back with extra information.

| Header            | Description                                                               |
| :---------------- | :------------------------------------------------------------------------ |
| `X-WP-Total`      | The total number of items in the collection.                              |
| `X-WP-TotalPages` | The total number of pages in the collection.                              |
| `Link`            | Contains links to other pages; `next`, `prev`, and `up` where applicable. |

## Status codes

The following table gives an overview of how the API functions generally behave.

| Request type | Description                                                                                                 |
| :----------- | :---------------------------------------------------------------------------------------------------------- |
| `GET`        | Access one or more resources and return `200 OK` and the result as JSON.                                    |
| `POST`       | Return `201 Created` if the resource is successfully created and return the newly created resource as JSON. |
| `PUT`        | Return `200 OK` if the resource is modified successfully. The modified result is returned as JSON.          |
| `DELETE`     | Returns `204 No Content` if the resource was deleted successfully.                                          |

The following table shows the possible return codes for API requests.

| Response code            | Description                                                                                                                                 |
| :----------------------- | :------------------------------------------------------------------------------------------------------------------------------------------ |
| `200 OK`                 | The request was successful, the resource(s) itself is returned as JSON.                                                                     |
| `204 No Content`         | The server has successfully fulfilled the request and that there is no additional content to send in the response payload body.             |
| `201 Created`            | The POST request was successful and the resource is returned as JSON.                                                                       |
| `400 Bad Request`        | A required attribute of the API request is missing.                                                                                         |
| `403 Forbidden`          | The request is not allowed.                                                                                                                 |
| `404 Not Found`          | A resource could not be accessed, for example it doesn't exist.                                                                             |
| `405 Method Not Allowed` | The request is not supported.                                                                                                               |
| `409 Conflict`           | The request could not be completed due to a conflict with the current state of the target resource. The current state may also be returned. |
| `500 Server Error`       | While handling the request something went wrong server-side.                                                                                |

## Contributing

There are 3 main parts to each route in the Store API:

1. Route - Responsible for mapping requests to endpoints. Routes in the Store API extend the `AbstractRoute` class; this class contains shared functionality for handling requests and returning JSON responses. Routes ensure a valid response is returned and handle collections, errors, and pagination.
2. Schema - Routes do not format resources. Instead we use _Schema_ classes that represent each type of resource, for example, a Product, a Cart, or a Cart Item. Schema classes in the Store API should extend the `AbstractSchema` class.
3. Utility - In more advanced cases where the Store API needs to access complex data from WooCommerce core, or where multiple routes need access to the same data, routes should use a Controller or Utility class. For example, the Store API has an Order Controller and a Cart Controller for looking up order and cart data respectfully.

Typically, routes handle the following types of requests:

* `GET` requests to read product, cart, or checkout data.
* `POST` and `PUT` requests to update cart and checkout data.
* `DELETE` requests to remove cart data.
* `OPTIONS` requests to retrieve the JSON schema for the current route.

Please review the [Store API Guiding principles](/docs/apis/store-api/guiding-principles). This covers our approach to development, and topics such as versioning, what data is safe to include, and how to build new routes.

## Extensibility

The approach to extensibility within the Store API is to expose certain routes and schema to the ExtendSchema class. [Documentation for contributors on this can be found here](/docs/apis/store-api/extending-store-api/).

If a route includes the extensibility interface, 3rd party developers can use the shared `ExtendSchema::class` instance to register additional endpoint data and additional schema.

This differs from the traditional filter hook approach in that it is more limiting, but it reduces the likelihood of a 3rd party extension breaking routes and endpoints or overwriting returned data which other apps may rely upon.

If new schema is required, and any of the following statements are true, choose to _extend_ the Store API rather than introducing new schema to existing Store API schemas:

* The data is part of an extension, not core
* The data is related to a resource, but not technically part of it
* The data is difficult to query (performance wise) or has a very narrow or niche use-case

If the data is sensitive (for example, a core setting that should be private), or not related to the current user (for example, looking up an order by order ID), [choose to use the authenticated WC REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction).

If you're looking to add _new routes and endpoints_, rather than extending the Store API _schema_, extending the Store API is not necessary. You can instead utilize core WordPress functionality to create new routes, choosing to use the same pattern of Store API if you wish. See:

* [`register_rest_route()`](https://developer.wordpress.org/reference/functions/register_rest_route/)
* [Rest API Permissions Callbacks](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#permissions-callback)

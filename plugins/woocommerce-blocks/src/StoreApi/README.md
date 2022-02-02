# WooCommerce Store API

The WooCommerce Store API is a public-facing (for internal use only currently) REST API. Unlike the main WooCommerce REST API, this API does not require authentication and is intended to be used by customer facing client side code.

This documentation assumes knowledge of REST concepts.

## Current status

This API is used internally by Blocks--it is still in flux and may be subject to revisions. There is currently no versioning system, and this API should therefore be used at your own risk. Eventually, it will be moved to the main WooCommerce REST API at which point it will be versioned and safe to use in other projects.

## Extensibility

Store API offers the possibility to extend endpoints using `ExtendRestAPI`. You can read more about extending Store API in the [Extensibility in WooCommerce Blocks](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/docs/extensibility) section.

## Basic usage

Example of a valid API request using cURL:

```http
curl "https://example-store.com/wp-json/wc/store/products"
```

The API uses JSON to serialize data. You don’t need to specify `.json` at the end of an API URL.

## Namespace

Resources in the Store API are all found within the `wc/store/` namespace, and since this API extends the WordPress API, accessing it requires the `/wp-json/` base. Examples:

```http
GET /wp-json/wc/store/products
GET /wp-json/wc/store/cart
```

## Authentication

Requests to the store API do not require authentication. Only public data is returned, and most endpoints are read-only, with the exception of the cart API which only lets you manipulate data for the current session, and requires a [nonce token](https://developer.wordpress.org/plugins/security/nonces/).

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

## Pagination

If collections contain many results, they may be paginated. When listing resources you can pass the following parameters:

| Parameter  | Description                                                                            |
| :--------- | :------------------------------------------------------------------------------------- |
| `page`     | Current page of the collection. Defaults to `1`.                                       |
| `per_page` | Maximum number of items to be returned in result set. Defaults to `10`. Maximum `100`. |

In the example below, we list 20 products per page and return page 2.

```http
curl "https://example-store.com/wp-json/wc/store/products?page=2&per_page=20"
```

### Pagination headers

Additional pagination headers are also sent back.

| Header            | Description                                                               |
| :---------------- | :------------------------------------------------------------------------ |
| `X-WP-Total`      | The total number of items in the collection.                              |
| `X-WP-TotalPages` | The total number of pages in the collection.                              |
| `Link`            | Contains links to other pages; `next`, `prev`, and `up` where applicable. |

## API resources and endpoints

Available resources in the Store API are listed below, with links to more detailed documentation.

| Resource                                                     | Methods                        | Endpoints                                                                                  |
| :----------------------------------------------------------- | :----------------------------- | ------------------------------------------------------------------------------------------ |
| [`Cart`](docs/cart.md)                                       | `GET`                          | [`/wc/store/cart`](docs/cart.md#get-cart)                                                  |
|                                                              | `POST`                         | [`/wc/store/cart/add-item`](docs/cart.md#add-item)                                         |
|                                                              | `POST`                         | [`/wc/store/cart/remove-item`](docs/cart.md#remove-item)                                   |
|                                                              | `POST`                         | [`/wc/store/cart/update-item`](docs/cart.md#update-item)                                   |
|                                                              | `POST`                         | [`/wc/store/cart/apply-coupon`](docs/cart.md#apply-coupon)                                 |
|                                                              | `POST`                         | [`/wc/store/cart/remove-coupon`](docs/cart.md#remove-coupon)                               |
|                                                              | `POST`                         | [`/wc/store/cart/update-customer`](docs/cart.md#update-customer)                           |
|                                                              | `POST`                         | [`/wc/store/cart/select-shipping-rate`](docs/cart.md#select-shipping-rate)                 |
| [`Cart Items`](docs/cart-items.md)                           | `GET`, `POST`, `DELETE`        | [`/wc/store/cart/items`](docs/cart-items.md#list-cart-items)                               |
|                                                              | `GET`, `POST`, `PUT`, `DELETE` | [`/wc/store/cart/items/:key`](docs/cart-items.md#single-cart-item)                         |
| [`Cart Coupons`](docs/cart-coupons.md)                       | `GET`, `POST`, `DELETE`        | [`/wc/store/cart/coupons`](docs/cart-coupons.md#list-cart-coupons)                         |
|                                                              | `GET`, `DELETE`                | [`/wc/store/cart/coupon/:code`](docs/cart-coupons.md#single-cart-coupon)                   |
| [`Checkout`](docs/checkout.md)                               | `GET`, `POST`                  | [`/wc/store/checkout`](docs/checkout.md)                                                   |
| [`Products`](docs/products.md)                               | `GET`                          | [`/wc/store/products`](docs/products.md#list-products)                                     |
|                                                              | `GET`                          | [`/wc/store/products/:id`](docs/products.md#single-product)                                |
| [`Product Collection Data`](docs/product-collection-data.md) | `GET`                          | [`/wc/store/products/collection-data`](docs/product-collection-data.md)                    |
| [`Product Attributes`](docs/product-attributes.md)           | `GET`                          | [`/wc/store/products/attributes`](docs/product-attributes.md#list-product-attributes)      |
|                                                              | `GET`                          | [`/wc/store/products/attributes/:id`](docs/product-attributes.md#single-product-attribute) |
| [`Product Attribute Terms`](docs/product-attribute-terms.md) | `GET`                          | [`/wc/store/products/attributes/:id/terms`](docs/product-attribute-terms.md)               |

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/README.md)
<!-- /FEEDBACK -->


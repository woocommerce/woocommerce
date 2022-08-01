# Store API Guiding principles <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Routes must include a well-defined JSON schema](#routes-must-include-a-well-defined-json-schema)
- [Routes should be designed around resources with a single type of schema](#routes-should-be-designed-around-resources-with-a-single-type-of-schema)
    - [Error Handling](#error-handling)
    - [Cart Operations](#cart-operations)
- [Exposed data must belong to the current user or be non-sensitive](#exposed-data-must-belong-to-the-current-user-or-be-non-sensitive)
- [Collections of resources should be paginated](#collections-of-resources-should-be-paginated)
- [API Responses should use standard HTTP status codes](#api-responses-should-use-standard-http-status-codes)
- [Breaking changes should be avoided where possible](#breaking-changes-should-be-avoided-where-possible)

The following principles should be considered when extending, creating, or updating endpoints in the Store API.

## Routes must include a [well-defined JSON schema](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/)

Each route/endpoint requires a particular structure of input data and should return data using a defined and predictable structure. This is defined in the JSON schema, which contains a comprehensive list of all of the properties the API can return and which input parameters it can accept.

Well-defined schema also provides a layer of security, as it enables us to validate and sanitize the requests being made to the API.

When defining schema, take note of the [WordPress REST API handbook](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/) which documents available properties and types, as well as the [JSON schema standard](http://json-schema.org/). In addition to this:

-   Properties should use snake_case 🐍
-   Ambiguous terms should be avoided, and property names should try to use understandable language, rather than “WooCommerce” terminology or setting names
-   Properties should be defined using US English, but the descriptions of fields should be localized
-   Multiple types are permitted, for example, using a `null` type if a value is not applicable
-   `sanitize_callback` and `validate_callback` are encouraged where possible to ensure data is received in the correct format before processing requests

If you’re struggling to define a consistent schema, your approach may be flawed. A common real-world example of this would be representing something like _Post Tags_. It may be tempting to use the Slug as the property field name in the response:

```php
tags: [
  "my-tag": {
    // ...tag data
  },
  "my-other-tag": {
    // ...tag data
  }
]
```

However, this is difficult to represent in Schema and is not predictable for the client. A better approach would be to use an array of data, with one of the properties being the Slug:

```php
tags: [
  {
    "slug": "my-tag",
    // ...tag data
  },
  {
    "slug": "my-other-tag",
    // ...tag data
  }
]
```

## Routes should be designed around resources with a single type of schema

Routes should be designed around resources (nouns) rather than operations (verbs). Routes should also return only one type of data defined by their Schema. For example:

| Route                    | Resource type | Expected data               |
| ------------------------ | ------------- | --------------------------- |
| `wc/store/v1/cart`       | Cart          | A cart object               |
| `wc/store/v1/cart/items` | Cart Item     | A list of cart item objects |
| `wc/store/v1/products`   | Product       | A list of product objects   |
| `wc/store/v1/products/1` | Product       | A product object            |

There are 2 notable exceptions to this rule in the Store API; _Errors_ and _Cart Operations_.

### Error Handling

Errors, including validation errors, should return an error response code (4xx or 5xx) and a [`WP_Error` object](https://developer.wordpress.org/reference/classes/wp_error/). The `AbstractRoute` class will handle the conversion of the `WP_Error` object into a valid JSON response.

Error messages should be localized, but do not need to be written with language aimed at customers (clients should use the given error code to create customer-facing notices as needed).

Error codes should have the prefix `woocommerce_rest_`.

### Cart Operations

Some endpoints are designed around operations to avoid clients needing to make multiple round trips to the API. This is purely for convenience.

An example would be the `wc/store/v1/cart/add-item` endpoint which accepts a quantity and product ID, but returns a full cart object, rather than just an updated list of items.

## Exposed data must belong to the current user or be non-sensitive

Resources, including customer and order data, should reflect only the current session. Do not return data for other customers as this would be a breach of privacy and security issue.

Store data such as settings (for example, store currency) is permitted in responses, but _private or sensitive data_ must be avoided. To allow more extensive access to data, you must use the authenticated [WC REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction).

Data returned from the API should not be [escaped](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) (this is left to the client rendering it), but it should be sanitized. For example, HTML should be run through [`wp_kses_post`](https://developer.wordpress.org/reference/functions/wp_kses_post/).

It is the client’s responsibility to properly escape data that comes from the API, but we should try to avoid returning data that is potentially unsafe.

## Collections of resources should be paginated

Large volumes of data should be paginated to avoid overwhelming the server. For example, returning a collection of products.

-   Use the response Headers `X-WP-Total`, `X-WP-TotalPages`, and Link to indicate available resources.
-   Use parameters `page` and `per_page` to retrieve certain pages.
-   The maximum allowed value for `per_page` is 100.

## API Responses should use standard HTTP status codes

When returning content, use a valid HTTP response code such as:

-   `200 OK` for successful responses (this is the default response code).
-   `201 Created` when creating a resource, for example, adding a new cart item or applying a new coupon.
-   `204 No Content` for successful deletes.
-   `400 Bad Request` when a required parameter is not set.
-   `403 Forbidden` when a request is not allowed, for example, if the provided security nonce is invalid.
-   `404 Not Found` if a resource does not exist.
-   `409 Conflict` if a resource cannot be updated, for example, if something in the cart is invalid and removed during the request.

A note on `DELETE` requests, a common pattern in the WordPress REST API is to return the deleted object. In the case of the Store API, we opt to return an empty response with status code `204 No Content` instead. This is more efficient.

[A full list of HTTP status codes can be found here.](https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml)

## Breaking changes should be avoided where possible

The Store API establishes a contract between itself and API consumers via the use of Schema. This contract should not be broken unless absolutely necessary. If a breaking change were necessary, a new version of the Store API would need to be released.

A breaking change is anything that changes the format of existing Schema, removes a Schema property, removes an existing route, or makes a backwards-incompatible change to anything public that may already be in use by consumers. Breaking changes can be avoided by [deprecating existing properties](http://json-schema.org/draft/2019-09/json-schema-validation.html#rfc.section.9.3) rather than removing them, or [deprecating routes](https://datatracker.ietf.org/doc/html/rfc8594) and replacing them with a different route if major changes are needed.

Non-breaking changes are always permitted without the need to increase the API version. Some examples of these include:

-   Adding new properties to schema
-   Adding new routes, endpoints, methods
-   Adding optional request parameters
-   Re-ordering response fields

The version will not increase for bug fixes unless the scope of the bug causes a backwards-incompatible change. Fixes would not be rolled back to past API versions with the exception of security issues that require backporting.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/guiding-principles.md)

<!-- /FEEDBACK -->


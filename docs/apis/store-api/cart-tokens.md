---
sidebar_label: Cart Tokens
sidebar_position: 3
---

# Cart Tokens 

Cart tokens can be used instead of cookies based sessions for headless interaction with carts. When using a `Cart-Token` a  [Nonce Token](/docs/apis/store-api/nonce-tokens) is not required.

## Obtaining a Cart Token

Successful requests to `/cart` endpoints return a `Cart-Token` header alongside the response. This contains a token which can later be sent as a request header to the Store API Cart and Checkout endpoints to identify the cart. Error responses caused by a cart session loading failure do not include `Cart-Token` or `Cart-Hash` headers.

The quickest method of obtaining a Cart Token is to make a GET request `/wp-json/wc/store/v1/cart` and observe the response headers. You should see a `Cart-Token` header there.

## How to use a Cart-Token

To use a `Cart-Token`, include it as a header with your request. The response will contain the current cart state from the session associated with the `Cart-Token`.

**Example:**

```sh
curl --header "Cart-Token: 12345" --request GET https://example-store.com/wp-json/wc/store/v1/cart
```

The same method will allow you to checkout using a `Cart-Token` on the `/checkout` route.

## Cross-origin requests

Browser requests from another origin can use a cart token without cookies. Set the request's credentials mode to `omit` when the origin is not allowed by the store.

To use browser credentials, such as login cookies, the storefront's origin must be allowed by WordPress using the `allowed_http_origins` or `allowed_http_origin` filter. Only allow origins that the store trusts with customer data.

## Logged-in customers

A `Cart-Token` for a guest cart works on its own. A `Cart-Token` for a customer account's cart only works while that customer is logged in, so it stops working when they log out. Send it alongside the customer's authentication, for example an application password. If it's sent without that authentication, or while a different customer is logged in, the Store API ignores the token and treats the request as if no token was sent.

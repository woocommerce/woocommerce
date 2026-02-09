---
sidebar_label: Cart Tokens
sidebar_position: 3
---

# Cart Tokens 

Cart tokens can be used instead of cookies based sessions for headless interaction with carts. When using a `Cart-Token` a  [Nonce Token](/docs/apis/store-api/nonce-tokens) is not required.

## Obtaining a Cart Token

Requests to `/cart` endpoints return a `Cart-Token` header alongside the response. This contains a token which can later be sent as a request header to the Store API Cart and Checkout endpoints to identify the cart.

The quickest method of obtaining a Cart Token is to make a GET request `/wp-json/wc/store/v1/cart` and observe the response headers. You should see a `Cart-Token` header there.

## How to use a Cart-Token

To use a `Cart-Token`, include it as a header with your request. The response will contain the current cart state from the session associated with the `Cart-Token`.

**Example:**

```sh
curl --header "Cart-Token: 12345" --request GET https://example-store.com/wp-json/wc/store/v1/cart
```

The same method will allow you to checkout using a `Cart-Token` on the `/checkout` route.

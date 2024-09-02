# Cart Tokens <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Obtaining a Cart Token](#obtaining-a-cart-token)
- [How to use a Cart-Token](#how-to-use-a-cart-token)

Cart tokens can be used instead of cookies based sessions for headless interaction with carts. When using a `Cart-Token` a  [Nonce Token](nonce-tokens.md) is not required.

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/cart-tokens.md)

<!-- /FEEDBACK -->


# Authentication and permission callbacks

The Store API has its own authentication model distinct from the rest of the WP REST API. Read this before adding any new route, especially one that mutates state.

## Where Store API auth lives

`Authentication::check_authentication()` is hooked on `rest_authentication_errors` for every `/wc/store/v1/*` request. It deliberately returns `true` to override WP's default cookie-nonce check, so **WP's built-in CSRF protection does not apply** to Store API routes.

```php
// plugins/woocommerce/src/StoreApi/Authentication.php
public function check_authentication( $result ) {
    // Enable Rate Limiting for logged-in users without 'edit posts' capability.
    if ( ! current_user_can( 'edit_posts' ) ) {
        $result = $this->apply_rate_limiting( $result );
    }
    return ! empty( $result ) ? $result : true;
}
```

The class docblock literally says *"The Store API does not require authentication"* — meaning it doesn't enforce nonces or capabilities globally. Each route is responsible for its own auth model.

## Nonce enforcement is in `AbstractCartRoute`

State-changing routes get their CSRF protection from `AbstractCartRoute::check_nonce()`, which:

- Is invoked on every request via [`get_response()`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/AbstractCartRoute.php), gated by `requires_nonce()` — which returns true on non-GET requests that don't carry a valid `Cart-Token` header. Cart-token-bearing requests are authenticated via the token instead and skip the nonce check.
- Verifies a `Nonce` header against the `wc_store_api` action inside [`check_nonce()`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/AbstractCartRoute.php).
- Rejects with `401 woocommerce_rest_missing_nonce` or `403 woocommerce_rest_invalid_nonce`.
- Hands back a fresh `Nonce` response header on every response (set in [`add_response_headers()`](../../../plugins/woocommerce/src/StoreApi/Routes/V1/AbstractCartRoute.php)) that the client echoes back on the next state-changing request.

Routes that extend `AbstractRoute` directly do **not** get this. They will accept any logged-in cookie session without a nonce check, which is a real CSRF surface.

## When to extend which abstract

| Use case | Extend | Why |
| --- | --- | --- |
| Read-only routes (catalog, public data) | `AbstractRoute` | No state to protect from CSRF. |
| Cart-related state mutation | `AbstractCartRoute` | Existing precedent; nonce + cart-session machinery wired up. |
| Login-required mutation routes (per-user preferences, account-scoped writes) | `AbstractCartRoute`, **or** implement equivalent nonce protection on `AbstractRoute` | Mutation via cookie auth without CSRF protection is unacceptable. |
| Read routes with own auth model (e.g. order ownership) | `AbstractRoute` + custom `permission_callback` | The auth check is the protection. |

If you find yourself extending `AbstractRoute` for a route that POSTs/DELETEs based on cookie auth, stop and reconsider. Either inherit from `AbstractCartRoute`, or document and implement an equivalent nonce flow.

## `permission_callback` conventions

| Use case | Pattern | Reference |
| --- | --- | --- |
| Guest-accessible | `'__return_true'` | [Cart.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/Cart.php), most cart routes |
| Login-required | `function () { return is_user_logged_in(); }` | [Patterns.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/Patterns.php) |
| Owner-only access | `[ $this, 'is_authorized' ]` | [Order.php](../../../plugins/woocommerce/src/StoreApi/Routes/V1/Order.php) |

**Don't use bare callable strings** like `'is_user_logged_in'`. They work but diverge from the codebase convention. Reviewers will look for the closure form. The closure also gives you a place to add capability checks later without changing the callback type.

```php
// ❌ Don't
'permission_callback' => 'is_user_logged_in',

// ✅ Do
'permission_callback' => function () {
    return is_user_logged_in();
},
```

## Application Passwords vs cookie sessions

Store API routes accept two auth methods, and they have different testing implications:

- **Application Passwords** (HTTP Basic Auth): authenticated as the user; **does not carry a cart session cookie**. Useful for testing routes that don't need cart state. Bypasses cookie nonce flows.
- **Cookie session**: full user identity + cart session. Required for any flow that reads from `WC()->cart` (e.g., `cart_item_key` lookups). Subject to nonce enforcement on cart-route mutations.

If a route needs to read `WC()->cart->cart_contents`, document that Application Password testing won't work for it — clients must use cookie auth.

## Anti-patterns to avoid

- **Routes that mutate state with `permission_callback => '__return_true'` and extend `AbstractRoute`.** No auth, no nonce — anyone can mutate. Only acceptable for guest carts where the user-id boundary is the cart token.
- **Routes that mutate state extending `AbstractRoute` with login-only auth and no nonce.** A logged-in shopper visiting a malicious site can be silently POSTed at via their cookie. Use `AbstractCartRoute` or implement equivalent nonce checks.
- **Bare-string `permission_callback`.** Stylistically inconsistent and makes future capability-check additions awkward.
- **Calling `Authentication::check_authentication()` directly.** It's an internal filter, not an API. Use the abstract base classes.

## Test that auth is wired correctly

For any route requiring auth, add tests covering:

1. **Unauthenticated request** → 401.
2. **Authenticated request without nonce** (for state-changing routes via cookie auth) → 401 `woocommerce_rest_missing_nonce`.
3. **Authenticated request with invalid nonce** → 403 `woocommerce_rest_invalid_nonce`.
4. **Cross-user access** (where applicable) → 403 or 404.

# POS Store API spike — key decisions

This directory is an architectural spike for routing mobile POS through the
WooCommerce Store API (instead of the REST API), so that checkout-time
extension hooks fire and product types that need fulfillment setup at
checkout time (gift cards, subscriptions, bookings, downloadables) work.

The spike implements a thin slice — `Context`, a session handler swap,
one POS route (`cart/add-item`), and one policy hook (stock override)
— to validate the architectural pattern end-to-end before the full
route set is built out. This document records the judgement calls
made along the way so they don't have to be re-derived later.

## Why a separate namespace (`wc/pos/v1`)

Considered: nesting POS routes under `wc/store/v1/pos/*`. Picked the
separate namespace because of opt-in vs opt-out asymmetry on Store API
middleware:

- Under `wc/store/v1/*`, Store API auto-applies CORS handling, rate
  limiting (including the strict 3-per-60s checkout rate limit),
  cart-token-header session swap, etc. We'd need to opt out of the
  ones we don't want via filters.
- Under `wc/pos/v1`, none of that fires. We opt in to what we want.

Future Store API changes (tighter checkout rate limits, additional
CORS rules) would automatically apply to POS unless someone notices
and excludes us — opt-in is more fragile.

[Agentic commerce] sets the precedent: it lives at `wc/agentic/v1`,
not `wc/store/v1/agentic/`.

[Agentic commerce]: https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/StoreApi/Routes/V1/Agentic

## Why POS routes extend Store API concrete routes directly

An earlier draft of this spike used a wrapper-delegation pattern: a
POS route held an injected delegate Store API route in its constructor
and forwarded `get_path` / `get_args` / `get_response` to it. That
worked but was an unnecessary layer.

Agentic commerce uses a simpler pattern: each agentic route extends
`AbstractCartRoute` directly and overrides what it needs to specialise.
For POS the same idea is even cleaner — extend the Store API
**concrete** route (`StoreApi\Routes\V1\CartAddItem`) so the
add-to-cart logic is inherited automatically and the subclass only
overrides three things:

- `get_args()` to substitute our permission_callback.
- `check_permission()` for the capability check.
- `requires_nonce()` to return false.

Result: `CartAddItem` is ~60 lines, no separate `AbstractRoute` base,
no DI plumbing to fetch a delegate. `ExtendSchema` and all pipeline
hooks fire identically to web because the route class IS the same
class, registered at a different URL.

## How session continuity works

The Store API's `AbstractCartRoute::add_response_headers` already
emits a `Cart-Token` HTTP response header on every cart response — a
JWT signed by `CartTokenUtils::get_cart_token` encoding the current
session's customer_id.

Mobile flow:

1. First request: no `Cart-Token` header. Server's `POSSessionHandler`
   generates a guest-style customer_id, the cart is added in that
   session, and the response carries a `Cart-Token` header.
2. Mobile captures the header.
3. Subsequent requests: mobile sends `Cart-Token: <jwt>` as a request
   header. The Store API's
   `Authentication::maybe_use_store_api_session_handler` detects the
   header and swaps in `StoreApi\SessionHandler` (the final,
   header-based handler), which loads the session by the JWT's
   user_id payload.

No subclass of `StoreApi\SessionHandler` is needed.
`POSSessionHandler` is only consulted on the very first request (when
no cart-token is present yet) — its sole job is to make sure the
generated customer_id isn't tied to the authenticated cashier user.

## Why URI-based context detection (`Context::is_pos_request()`)

The POS context flag needs to be available at session-handler-construction
time, which fires during `plugins_loaded` — well before any REST route
is matched. URI prefix detection works the same way the Store API
itself detects its own requests (`WooCommerce::is_store_api_request`)
and lets us answer "is this a POS request" from any callsite without
coordination.

A test override (`Context::set_test_override`) is provided so unit
tests can simulate POS context without faking the entire REST request
stack.

## Why `POSSessionHandler` extends `WC_Session_Handler` and only overrides `generate_customer_id()`

The default `WC_Session_Handler` keys carts by the authenticated WP
user ID whenever one is present. For POS that is the wrong scope —
multiple cashiers on multiple devices typically share one
store-manager account and would collide on a single cart row.

The migration-to-user-session block in the parent lives inside
`if ( $cookie )`, and mobile clients don't send the WC session cookie.
The parent's "no cookie → generate_customer_id" path runs, and the
`generate_customer_id()` override is sufficient.

## Why nonce check is disabled per-route (not via a filter)

POS requests are not cookie-authenticated (Application Password /
WPCOM bearer), so CSRF isn't a vector and the Store API cart-route
nonce check is moot.

An earlier draft used the `woocommerce_store_api_disable_nonce_check`
filter via a `NonceCheckPolicy` class. After moving to the direct
subclass pattern (mirroring agentic), the simpler answer is to
override `requires_nonce()` on each POS route to return `false` —
exactly what agentic does. The override is two lines per route; over
time, if the POS route set grows, a thin shared `AbstractPosCartRoute`
could collapse it.

## Identity model (from Thomas Roberts's reply on the P2 thread)

- `customer_id` (optional) — swapped into `$user` during the request,
  after the capability check. Persisted as the order's `_customer_user`.
- `agent_id` (the cashier) — stays out of `$user`, stored as order
  meta for audit. Never the customer of record.
- Guest sales (the common POS case): `customer_id` unset; cart-token
  still scopes the session; cashier still in meta.

Not implemented in this spike yet — will land when we add `/checkout`.

## Authentication

Reuses whatever auth the mobile apps already use (Application Passwords,
WPCOM/Jetpack bearer proxy, etc.) — these populate `wp_get_current_user()`
for both REST and Store API. No new auth scheme.

## Why `manage_woocommerce` as the default POS capability

POS routes operate on behalf of customers, mutate carts, create
orders, and (in future) record payments. `manage_woocommerce` is the
closest existing capability that maps to "trusted retail staff." A
future refinement might introduce a finer-grained `process_pos_orders`
capability, but that's a separate decision. For the spike,
`manage_woocommerce` is the conservative default and is overridable
per-route via the `REQUIRED_CAPABILITY` class constant.

## The load-bearing assumption to validate

Pipeline policy-point hooks. The architecture works cleanly **only
where the pipeline already exposes a filter at the policy decision
we want to influence**. Where filters exist (e.g.
`woocommerce_product_is_in_stock`), hooking them gated by POS context
is one-liner. Where they don't, we'd either need to upstream a filter
to Woo core (preferred long term) or accept a less clean workaround.

A representative example, verified in code:

- `WC_Product::is_in_stock()` is filterable via
  `woocommerce_product_is_in_stock` → POS can allow selling
  out-of-stock items via a single filter callback. Clean.
- The second-layer quantity-vs-remaining check at
  `src/StoreApi/Utilities/CartController.php:298` reads
  `$product->backorders_allowed()` directly, which may or may not be
  cleanly filterable depending on the code path.

A small per-policy-point audit is the highest-leverage next concrete
step.

## Correction: `/checkout` does require a `payment_method`

An earlier draft of the proposal claimed POS could POST `/checkout`
with no `payment_method`, relying on the Store API's `process_payment`
to silently no-op. That was wrong:
`CheckoutTrait::update_order_from_request` throws
`woocommerce_rest_checkout_missing_payment_method` (HTTP 400) when
the order needs payment, the request is POST, and no payment_method
is supplied. The integration test for `/checkout` (added in this
branch) catches this directly.

Implication: POS will need to send *some* `payment_method` slug at
checkout time. Options for the production design (out of scope for
this spike):

1. **Register a minimal `pos_pending` gateway** whose
   `process_payment` returns synchronously with the order left in
   `pending`, then the existing post-checkout flow (WooPayments
   `capture_terminal_payment` for cards, cash mark-paid endpoint
   for cash) takes over.
2. **Reuse an existing offline gateway** (`bacs`, `cod`) — works but
   the order's recorded payment method ends up misleading in admin.

Option 1 is the cleaner long-term shape. The integration test in
this spike uses a temporary `PosCheckoutTestGateway` defined inline
to prove the route works end-to-end.

## What this spike deliberately does NOT include

- **More than two routes.** `cart/add-item` and `/checkout` are the
  load-bearing pair; the rest (`cart/remove-item`, `cart/update-item`,
  `cart/apply-coupon`, etc.) is mechanical and additive.
- **Agent/customer identity swap.** No current route accepts a
  `customer_id` parameter, so there's nothing to swap yet.
- **A real production `pos_pending` payment gateway** — see correction
  above; integration tests stand up a temporary one inline.
- **Cash-paid endpoint** — additive, not load-bearing for the pattern.
- **Per-policy-point filter audit** — research/inventory work, not code.
- **Integration tests** against a real fulfillment extension (e.g.
  Gift Cards) — the natural next test step.

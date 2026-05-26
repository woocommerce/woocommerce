# POS Store API spike — key decisions

This directory is an architectural spike for routing mobile POS through the
WooCommerce Store API (instead of the REST API), so that checkout-time
extension hooks fire and product types that need fulfillment setup at
checkout time (gift cards, subscriptions, bookings, downloadables) work.

The spike intentionally implements only a thin slice — `Context`, a session
handler swap, one delegated route (`cart/add-item`), and one policy hook
(stock override) — to validate the architectural pattern end-to-end before
the full route set is built out. This document records the judgement calls
made along the way so they don't have to be re-derived later.

## Why a separate namespace (`wc/pos/v1`)

Considered: extending `wc/store/v1/*` with per-route POS-aware branching, vs
nesting POS routes under `wc/store/v1/pos/*`, vs a wholly separate
`wc/pos/v1`. Picked the last for three reasons:

1. **Auth / nonce / CSRF policy diverges.** Store API routes assume cookie
   sessions with a Store-API nonce header for writes. POS is authenticated
   via Application Passwords / WPCOM-bearer proxy and doesn't need CSRF
   defenses (no cookie, no cross-origin exposure). A separate namespace
   means the nonce middleware is structurally absent, not toggled.
2. **Avoids spreading `is_pos_request()` checks through Store API code.**
   That was the failure mode flagged by Thomas Roberts in the Rubik review
   on the [POS × Store API P2 thread] — front-end cart had the same shape
   and accumulated bug-prone state interactions. Separate routes keep the
   Store API code path policy-neutral.
3. **Wraps the Store API delegate explicitly.** A POS route is a thin
   permission/identity adapter around a Store API route handler. Making
   that explicit (`new CartAddItem( $storeApiCartAddItem )`) is easier to
   reason about than "this same route behaves differently depending on
   request flags."

[POS × Store API P2 thread]: https://peacockp2.wordpress.com/2026/03/12/pos-x-store-api/

## Why URI-based context detection (`Context::is_pos_request()`)

The POS context flag needs to be available at session-handler-construction
time, which fires during `plugins_loaded` — well before any REST route is
matched. URI prefix detection works the same way the Store API itself
detects its own requests (`WooCommerce::is_store_api_request`) and lets us
answer "is this a POS request" from any callsite without coordination.

A test override (`Context::set_test_override`) is provided so unit tests
can simulate POS context without faking the entire REST request stack.

## Why `POSSessionHandler` extends `WC_Session_Handler` and only overrides
`generate_customer_id()`

The default `WC_Session_Handler` keys carts by the authenticated WP user
ID whenever one is present. For POS that is the wrong scope — multiple
cashiers on multiple devices typically share one store-manager account and
would collide on a single cart row.

Initial draft overrode `init_session_cookie()` wholesale to skip the
`migrate_guest_session_to_user_session()` block. That ran into private
methods (`restore_session_data`, `is_session_cookie_valid`,
`is_session_expiring`) that we'd have had to either re-implement or
upstream as protected.

Cleaner read of the parent: the migration block lives inside
`if ( $cookie )`, and mobile clients don't send the WC session cookie.
So in practice the parent's "no cookie → generate_customer_id" path runs,
and the `generate_customer_id()` override is sufficient. The class is
deliberately minimal; if a future code path causes the parent's migration
branch to fire for POS requests, the right fix is to upstream the relevant
methods as protected and override them here rather than copy-paste.

## Open question: Cart-Token header transport vs `?session=`

The default `WC_Session_Handler` accepts a cart token via `?session=` query
parameter and treats it as a "one-time import" mechanism (clones the
referenced session into a new one). For headless API consumers (Block
checkout, mobile), Store API ships `StoreApi\SessionHandler` which reads
an `HTTP_CART_TOKEN` header and treats the token as the persistent session
identifier — cleaner for clients that don't have browser cookies.

`StoreApi\SessionHandler` is declared `final`, so we can't extend it.
Options for follow-up:

1. **Use `?session=` query transport** with the current handler (works,
   but each request clones the session — wasteful).
2. **Make `StoreApi\SessionHandler` non-final and extend it** for POS
   (upstream change, but cleanly mirrors the header-based flow).
3. **Add an explicit "issue cart token" endpoint** the mobile app calls
   once at sale start, then sends the token via `HTTP_CART_TOKEN` header
   on subsequent requests (more verbose for the client).

Out of scope for this spike. Recommend option 2 to Rubik when this lands
in review — the header-based flow is the right model for any non-browser
consumer and POS will share it with other future headless clients.

## Why route delegation via constructor injection

The `AbstractRoute` takes a fully-constructed Store API route handler in
its constructor and forwards `get_path()` / `get_args()` / `get_response`
to it. Alternative considered: have each POS route reach into the Store
API container itself.

Constructor injection is preferred because:

- It makes the dependency explicit in the type signature.
- The `Controller` becomes the single place that knows about Store API's
  container — POS routes themselves only know about their delegate.
- Trivial to substitute a mock for unit tests of POS-specific pre-flight
  behaviour (identity swap, error mapping) without spinning up the whole
  Store API container.

## Why `get_args()` rewrites `permission_callback` and `callback`

The Store API's `get_args()` returns endpoint definitions with
`permission_callback => '__return_true'` (Store API is unauthenticated).
For POS we substitute the POS capability check and a wrapper callback so
subclasses can intercept pre/post-delegation (identity swap, response
filtering) without re-implementing the route.

The wrapper for `callback` is deliberately added even when the default
`get_response` is a pure delegate — it costs nothing and makes the override
point obvious for future subclasses.

## Why `manage_woocommerce` as the default POS capability

POS routes operate on behalf of customers, mutate carts, create orders,
and (in future) record payments. `manage_woocommerce` is the closest
existing capability that maps to "trusted retail staff." A future
refinement might introduce a finer-grained `process_pos_orders` capability,
but introducing a new capability is a separate decision worth its own
discussion. For the spike, `manage_woocommerce` is the conservative
default and is overridable per-route via the `REQUIRED_CAPABILITY` class
constant.

## Why three RegisterHooksInterface classes instead of one

`SessionHandlerSwap`, `StockPolicy`, and `Routes\Controller` are each
registered separately in `class-woocommerce.php`. Could have collapsed
them into one. Kept separate because:

- Each owns exactly one concern (matches the existing
  `PointOfSaleEmailHandler` pattern).
- Policy hooks (stock, session, future email-suppression, future
  gateway-availability) all live in `PolicyHooks/` and follow the same
  shape — adding a new one is "copy `StockPolicy.php`, change the filter
  name and the body."
- Easy to selectively disable any one of them in tests without affecting
  the others.

## What this spike deliberately does NOT include

- **More than one route.** `cart/add-item` is the canonical example; the
  full set (`cart/*`, `checkout`) is mechanical and additive.
- **Agent/customer identity swap.** The wrapper has the hook point for it
  (`get_response` is overridable), but no current route accepts a
  `customer_id` parameter, so there's nothing to swap yet. Will come with
  `/checkout`.
- **Cash-paid endpoint.** Documented in the project proposal as the one
  net-new server-side endpoint; not in the architectural spike because
  it's straightforward additive work, not a load-bearing pattern.
- **Per-policy-point filter audit.** Stock is one example of the pattern.
  The full audit (visibility, gateway availability, email enabled, etc.)
  is the highest-leverage next concrete step, but is research/inventory
  work, not code.
- **Integration tests.** Unit tests cover the small surface area in this
  spike; an end-to-end test against a real cart pipeline + real
  fulfillment extension (e.g. Gift Cards) is the right way to validate
  the wrapper-delegation pattern preserves extension behaviour, and is
  the next test work after this spike merges.

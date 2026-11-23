# Session, cart token and persistent cart

**Source:**

- #57961 removed persistent carts; reverted in 10.1.2 (#60595 / #60605).
- #59530 (10.0.2): restored `init_session_cookie` creating a customer_id.
- #68398 kept the cart on logout; reverted in #68818.
- #57152: reverted a session / cart-token change.
- #57255 (9.8.2): cart init moved back inside the checkout route.
- #67769: Store API fatal when the cart failed to load.
- #67550 (11.0.1): one source for the cart token.
- #61561 (10.3.1): `get_session_data` missing on custom session handlers.
- Earlier review findings: a batch kept going after a failed cart load and a later sub-request cleared the cart.

**What broke:** Changes to when the cart is loaded, merged, saved, or cleared lost carts, broke checkout, and fatalled with custom session handlers. Several were reverted.

**Pattern to catch:** Changes to when or where the cart or session is loaded, saved, merged, or cleared, or to how the cookie or cart token identifies it. Includes login and logout transitions and custom `WC_Session` subclasses. Session is not the same as user: User Switching and impersonation swap `WP_User` mid-session.

**How to confirm:** Read `WC_Session_Handler` and `CartTokenUtils`. Trace guest to login to logout, and Store API batch paths. Grep for methods called on `WC()->session` that are not on the abstract `WC_Session`.

**Don't flag when:** Pure refactors where load and save order is unchanged and every method called exists on the abstract base.

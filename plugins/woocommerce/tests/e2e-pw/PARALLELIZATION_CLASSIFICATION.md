# E2E Parallelization Classification

This classification covers specs under `plugins/woocommerce/tests/e2e-pw/tests`, excluding `api-tests` and `blocks`.

The goal is to identify specs that can run in parallel against the same WordPress/WooCommerce test site, and specs that need serialization or isolated environments because they mutate global state.

## Buckets

- `parallel-safe`: Read-only or browser-session-only tests. These are good candidates for `fullyParallel`.
- `data-mutating`: Tests that create products, orders, coupons, customers, users, pages, posts, comments, or terms. These can run in parallel only if they use unique fixture data and reliable cleanup.
- `global-state`: Tests that mutate WooCommerce settings, WordPress options, payment gateways, tax/shipping settings, onboarding state, email feature flags/templates, shared users, or fixed shared content. These should run serially or in isolated environments.

## Parallel-Safe

These specs look safe to run in parallel against the shared test site:

- `tests/analytics/analytics-access.spec.ts`
- `tests/basic/basic.spec.ts`
- `tests/basic/dashboard-access.spec.ts`
- `tests/js-file-monitor/monitor-js-file-number.spec.ts`
- `tests/marketing/overview.spec.ts`
- `tests/my-account/my-account.spec.ts`
- `tests/user/lost-password.spec.ts`

These are currently grouped in the `e2e-parallel-safe` Playwright project.

## Data-Mutating

These specs mostly create and clean up scoped test data. They are potential candidates for a future parallel project after checking for fixed names, count assertions, and cleanup reliability.

- `tests/checkout/checkout-link.spec.ts`
- `tests/coupons/coupons.spec.ts`
- `tests/customer/customer-list.spec.ts`
- `tests/editor/command-palette.spec.ts`
- `tests/my-account/my-account-create-account.spec.ts`
- `tests/my-account/my-account-downloads.spec.ts`
- `tests/order/order-bulk-edit.spec.ts`
- `tests/order/order-coupon.spec.ts`
- `tests/order/order-refund.spec.ts`
- `tests/order/order-status-filter.spec.ts`
- `tests/product/create-product-attributes.spec.ts`
- `tests/product/create-variable-product.spec.ts`
- `tests/product/product-delete.spec.ts`
- `tests/product/product-edit.spec.ts`
- `tests/product/product-export.spec.ts`
- `tests/product/product-grouped.spec.ts`
- `tests/product/product-images.spec.ts`
- `tests/product/product-import-csv.spec.ts`
- `tests/product/product-linked-products.spec.ts`
- `tests/product/product-reviews.spec.ts`
- `tests/product/product-search.spec.ts`
- `tests/shop/shop-search-browse-sort.spec.ts`
- `tests/shop/shop-title-after-deletion.spec.ts`
- `tests/user/users-create.spec.ts`
- `tests/wp-core/create-page.spec.ts`
- `tests/wp-core/create-post.spec.ts`

## Fixed Shared Data

These mutate fixed shared entities. Treat them as `global-state` until made unique and self-cleaning:

- `tests/brands/create-product-brand.spec.ts`
    - Creates, edits, and deletes fixed `product_brand` terms such as `WooCommerce`, `WooCommerce Apparels`, and `WooCommerce Dummy`.
- `tests/wp-core/post-comments.spec.ts`
    - May disable Jetpack comments and leaves a comment on the fixed `hello-world` post.

## Global-State

These specs mutate shared WordPress or WooCommerce state and should not run in parallel against the same database.

### Analytics

- `tests/analytics/analytics-data.spec.ts`
- `tests/analytics/analytics-overview.spec.ts`
- `tests/analytics/analytics-settings.spec.ts`

Main risks: analytics options, report settings, tour options, generated products/orders/categories.

### Basic

- `tests/basic/page-loads.spec.ts`

Main risks: onboarding profile/options and generated commerce data.

### Cart And Checkout

- `tests/cart/add-to-cart.spec.ts`
- `tests/cart/cart.spec.ts`
- `tests/checkout/checkout-shortcode-custom-place-order-button.spec.ts`
- `tests/checkout/checkout.spec.ts`

Main risks: tax settings, default customer location, AJAX add-to-cart setting, payment gateways, custom button option, shared cart/checkout page behavior.

### Coupons

- `tests/coupons/cart-block-coupons.spec.ts`
- `tests/coupons/cart-checkout-coupons.spec.ts`
- `tests/coupons/cart-checkout-restricted-coupons.spec.ts`

Main risks: store address/currency settings, tax/payment settings, coupon/product/order setup with cross-test visibility.

### Email

- `tests/email/account-emails.spec.ts`
- `tests/email/editor-tracking-selectors.spec.ts`
- `tests/email/order-emails.spec.ts`
- `tests/email/settings-email-listing.spec.ts`
- `tests/email/settings-email.spec.ts`

Main risks: email improvement flags, WooCommerce email settings, Mailpit state, generated customers/orders.

### Email Editor

- `tests/email-editor/email-editor-loads.spec.ts`
- `tests/email-editor/email-editor-reset-template.spec.ts`
- `tests/email-editor/email-editor-settings-sidebar.spec.ts`
- `tests/email-editor/update-propagation/backward-compat.spec.ts`
- `tests/email-editor/update-propagation/core-flows.spec.ts`
- `tests/email-editor/update-propagation/round-trip-idempotency.spec.ts`
- `tests/email-editor/update-propagation/scope.spec.ts`

Main risks: email editor feature flags, email template posts/settings, helper plugin options, Tracks helper state.

### My Account

- `tests/my-account/my-account-addresses.spec.ts`
- `tests/my-account/my-account-pay-order.spec.ts`

Main risks: shared customer address state and payment gateway settings.

### Onboarding

- `tests/onboarding/add-product-task.spec.ts`
- `tests/onboarding/launch-your-store.spec.ts`
- `tests/onboarding/nox-onboarding.spec.ts`
- `tests/onboarding/onboarding-wizard.spec.ts`
- `tests/onboarding/setup-checklist.spec.ts`

Main risks: onboarding profile/options, site visibility options, active theme changes, setup task state.

### Orders

- `tests/order/create-order.spec.ts`
- `tests/order/customer-payment-page.spec.ts`
- `tests/order/order-edit.spec.ts`
- `tests/order/order-grace-period.spec.ts`
- `tests/order/review-order-page.spec.ts`

Main risks: tax classes/rates, tax enablement, shipping/customer settings, payment gateways, email verification grace period, review request feature flag.

### PayPal

- `tests/paypal/paypal.spec.ts`

Main risks: PayPal gateway settings.

### Products

- `tests/product/create-variations.spec.ts`
- `tests/product/product-create-simple.spec.ts`
- `tests/product/product-settings.spec.ts`
- `tests/product/product-tags-attributes.spec.ts`
- `tests/product/product-variable.spec.ts`
- `tests/product/update-variations.spec.ts`

Main risks: product settings, attribute lookup setting, tax enablement, shipping-related product behavior.

### Settings

- `tests/settings/colour-picker-swatch-height.spec.ts`
- `tests/settings/consumer-token.spec.ts`
- `tests/settings/settings-general.spec.ts`
- `tests/settings/settings-tax.spec.ts`
- `tests/settings/settings-ui-feature-flag.spec.ts`
- `tests/settings/settings-woo-com.spec.ts`
- `tests/settings/webhooks.spec.ts`

Main risks: WooCommerce settings pages, store address/currency/country settings, tax settings, review setting, Woo.com tracking/marketplace options, REST API keys, webhooks.

### Shipping

- `tests/shipping/shipping-classes.spec.ts`
- `tests/shipping/shipping-zones.spec.ts`

Main risks: shipping classes, zones, methods, and related settings.

### Shop

- `tests/shop/cart-redirection.spec.ts`

Main risks: cart redirect and product settings.

### Users

- `tests/user/users-manage.spec.ts`

Main risks: fixed user/customer mutations and shared user list assumptions.

## Suggested Playwright Projects

- `e2e-parallel-safe`: `fullyParallel`, shared environment.
- `e2e-data`: `fullyParallel` only after data fixtures are unique and cleanup is verified.
- `e2e-global-settings`: serial, or run against an isolated environment.
- `e2e-onboarding`: serial, or run against an isolated environment.
- `e2e-email`: serial, or run against an isolated environment.
- `e2e-checkout-cart-settings`: serial, or run against an isolated environment.
- `e2e-account-shared-user`: serial unless each spec gets its own customer.

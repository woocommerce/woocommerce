# Payment settings

Experimental settings screen for payment gateways, built with [wp-build](https://www.npmjs.com/package/@wordpress/build) and WordPress DataForm. It is behind the `payment-settings-screen` feature flag and can change in any release.

The build writes to `plugins/woocommerce/assets/client/routes`, which `Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreen` loads.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:routes
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:routes
```

## Adding a screen

A screen is experimental: every part of this can change in any release. See `plugins/woocommerce/tests/e2e/test-plugins/payment-settings-example` for a working example.

1. Register the screen with the `woocommerce_experimental_payment_settings_screens` filter. Give it a `title`, the `rest_path` of an endpoint that reads (`GET`) and saves (`POST` or `PUT`) the settings as one flat object, and any `scripts` to load on the screen.
2. Register fields with the Fields API on the `fields_api_init` action, using the entity kind `woo_settings` and the screen ID as the entity name.
3. Set the layout with the `get_entity_view_config_woo_settings_{id}` filter.

The screen opens at `admin.php?page=wc-payment-settings-wp-admin&p=/settings/{id}`.

To open a group as its own page, give it a `panel` layout with `openAs: { type: 'page' }` in View Config. WooCommerce shows a button where the group sits, and the group opens at `/settings/{id}/{group}` with breadcrumbs back. A sub-page can contain other sub-pages, each adding a segment to the path. To link to a sub-page from the extension's own control instead, use `openAs: { type: 'page', button: false }` and link to `admin.php?page=wc-payment-settings-wp-admin&p=/settings/{id}/{group}`. Links to the screen's own pages open in place.

Each page is its own form. Save and Discard cover the fields on the current page, and leaving a page with unsaved changes asks before discarding them.

A card's description can only be text. To include a link, register a read-only field, set its `render` in the screen's script module, and place it first in the card with `labelPosition: 'none'`. See the example plugin's Customer support card.

Add `classic_section` with the gateway's classic settings section (its `section` query argument) to redirect that page to the screen. Only GET requests redirect, other query arguments are passed to the route, and adding `wc_classic_settings=1` keeps the classic page. The screen then shows a "Use classic settings" link. To open an old link at a sub-page, turn its query arguments into a path with the `woocommerce_experimental_payment_settings_classic_location` filter.

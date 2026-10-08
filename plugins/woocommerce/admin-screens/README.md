# Admin screens

Admin screens built with [wp-build](https://www.npmjs.com/package/@wordpress/build), DataViews and DataForm. Each folder in `routes/` is one route.

The build writes to `plugins/woocommerce/assets/client/routes`, which `Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreen` loads.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

## Payment settings

Experimental settings screen for payment gateways. It is behind the `payment-settings-screen` feature flag, and every part of it can change in any release. See `plugins/woocommerce/tests/e2e/test-plugins/payment-settings-example` for a working example.

1. Register the screen with the `woocommerce_experimental_payment_settings_screens` filter. Give it a `title`, the `rest_path` of an endpoint that reads (`GET`) and saves (`POST` or `PUT`) the settings as one flat object, and any `scripts` to load on the screen.
2. Register fields with the Fields API on the `fields_api_init` action, using the entity kind `woo_settings` and the screen ID as the entity name.
3. Set the layout with the `get_entity_view_config_woo_settings_{id}` filter.

The screen opens at `admin.php?page=wc-payment-settings-wp-admin&p=/settings/{id}`.

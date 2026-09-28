# Payment settings

Experimental settings screen for payment gateways, built with [wp-build](https://www.npmjs.com/package/@wordpress/build) and WordPress DataForm. It is behind the `payment-settings-screen` feature flag and can change in any release.

The build writes to `plugins/woocommerce/assets/client/payment-settings`, which `Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreen` loads.

```sh
pnpm --filter=@woocommerce/payment-settings build
pnpm --filter=@woocommerce/payment-settings watch:build
```

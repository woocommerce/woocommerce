# Admin screens

Experimental admin pages built with wp-build, DataForm and core-data. Each folder in `routes/` is a wp-build route.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

The build writes to `plugins/woocommerce/assets/client/routes`.

## Payment settings

`routes/payment-settings` serves `/settings/$page`. A page exists when a core-data entity of kind `woo_settings_payments` is registered with `addEntities()` under that name. The page loads the entity's record with the same ID, its form layout from View Config, and the `label` and `fields` from the entity config. Save and Discard work on that one record.

Cheque is the Core example. It uses the existing `/wc/v3/payment_gateways/cheque` endpoint and doesn't replace the classic settings page or its links.

The screen needs the `payment-settings-screen` flag and WordPress's View Config API. Enable the flag with an mu-plugin:

```php
<?php
add_filter( 'woocommerce_admin_features', static function ( $features ) {
    $features[] = 'payment-settings-screen';
    return array_unique( $features );
} );
```

Then open `admin.php?page=wc-payment-settings-wp-admin&p=/settings/cheque`.

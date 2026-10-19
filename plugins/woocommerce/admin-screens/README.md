# Admin screens

Experimental admin pages built with wp-build, DataForm and core-data. Routes live in `routes/`; there is no exported screen-registration package.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

The build writes to `plugins/woocommerce/assets/client/routes`. Build the checkout before enabling the page.

## Offline payment settings

The `payment-settings-screen` and `rest-api-v4` flags, and WordPress's View Config API, are required. With those available, the existing settings links for direct bank transfer, check payments and cash on delivery open the new page. Otherwise they keep their existing destinations.

For development, enable both flags with an mu-plugin:

```php
<?php
add_filter( 'woocommerce_admin_features', static function ( $features ) {
    return array_unique( array_merge( $features, array( 'payment-settings-screen', 'rest-api-v4' ) ) );
} );
```

The routes are `/settings/bacs`, `/settings/cheque` and `/settings/cod` on `admin.php?page=wc-payment-settings-wp-admin`. The route registers the Core entities with `addEntities()` and reads and saves through `/wc/v4/settings/payment-gateways/{id}`. Fields are defined on the client; layouts come from View Config. Bank accounts use the gateway endpoint's `values.account_details`, which retains the existing `woocommerce_bacs_accounts` storage.

Check save/reload, discard, server errors, shipping restrictions and adding/reordering/removing bank accounts. Restore any test changes afterwards. Extension-provided pages and sub-pages are follow-up work, not supported by this PR.

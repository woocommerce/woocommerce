# Admin screens

Experimental admin pages built with wp-build, DataForm and core-data. Routes live in `routes/`; there is no exported screen-registration package.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

The build writes to `plugins/woocommerce/assets/client/routes`. Build the checkout before enabling the examples.

## Payment settings examples

The Core offline methods demonstrate the shared route, entity registration, fields and Save/Discard flow. They do not replace the existing payment settings pages or their links. Extension-provided pages and sub-pages are follow-up work.

The examples require the `payment-settings-screen` flag and WordPress's View Config API, not REST API v4. Enable the flag with an mu-plugin:

```php
<?php
add_filter( 'woocommerce_admin_features', static function ( $features ) {
    $features[] = 'payment-settings-screen';
    return array_unique( $features );
} );
```

Open `admin.php?page=wc-payment-settings-wp-admin&p=/settings/cheque`, or use `/settings/bacs` and `/settings/cod`. The route registers entities with `addEntities()` and resolves the URL parameter through core-data's registered entities. Fields are client-defined; layouts come from View Config.

## V3 data contract

The examples use `/wc/v3/payment_gateways/{id}`. Core offline gateways expose editable `instructions`, `enable_for_methods` and `enable_for_virtual` fields alongside the existing `settings` metadata. BACS also exposes `account_details`. Updates accept the same shapes returned by reads, so the page and Core's review-changes panel use ordinary entity saves.

Existing `settings` requests retain their legacy path. New-field requests validate before writing, reject conflicting legacy/new values, preserve omitted fields and use the existing gateway settings and BACS account options. An empty `account_details` array clears accounts. A single request is not a database transaction.

Check old/new settings round-trips, Save/Discard, shipping restrictions, bank-account editing and Core's review-changes panel. Restore test settings and remove the temporary flag afterwards. The classic BACS form preserves optional account country metadata through hidden inputs; its visible controls are unchanged.

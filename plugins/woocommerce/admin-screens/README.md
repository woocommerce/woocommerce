# Admin screens

Admin screens built with [wp-build](https://www.npmjs.com/package/@wordpress/build), DataViews and DataForm. Each folder in `routes/` is one route, and each folder in `packages/` is a script module that other plugins can import.

The build writes to `plugins/woocommerce/assets/client/routes`, which `Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens` loads.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

## Settings screens

Experimental settings screens that extensions register. They are behind the `payment-settings-screen` feature flag, and every part of them can change in any release. See `plugins/woocommerce/tests/e2e/test-plugins/payment-settings-example` for a working example.

Each screen is a route on the `wc-payment-settings` page. The extension registers the route with a script module, and the module renders the screen with `createSettingsScreen()` from the `@woocommerce/settings-screens` script module, built from `packages/settings-screens`. WooCommerce draws the form, saves it, and shows its breadcrumbs, so every screen looks and works the same.

A screen ID has one or two segments: `my-gateway`, or `my-gateway/advanced` for a sub-page. A sub-page is an ordinary screen: it registers its own fields, layout and module, and nothing is inherited from its parent. Sub-pages can share their parent's endpoint, so the endpoint must keep the stored value of any field a save leaves out. The only area so far is `payments`.

1. Register a script module that depends on `@woocommerce/settings-screens` and exports what `createSettingsScreen()` returns:

    ```js
    import { createSettingsScreen } from '@woocommerce/settings-screens';

    export const { route, stage } = createSettingsScreen( {
        id: 'my-gateway',
        area: 'payments',
        title: 'My gateway settings',
        restPath: '/my-gateway/v1/settings',
    } );
    ```

    `restPath` is an endpoint that reads (`GET`) and saves (`POST`) the settings as one flat object and returns the stored settings after a save. A sub-page also passes `parent: { id, title }`, which its breadcrumbs link to.
2. Register the screen's route on the `wc-payment-settings-wp-admin_init` action, which wp-build generates for the page, with `woocommerce_routes_register_wc_payment_settings_wp_admin_route( '/settings/my-gateway', 'my-gateway/settings-screen', 'my-gateway/settings-screen' )`. The module is both the route's content and route module. Use `/settings/{id}` as the path, with the same ID as `createSettingsScreen()`.
3. Register fields with the Fields API on the `wp_fields_api_init` action, using the entity kind `woo_settings_{area}` and the screen ID as the entity name. Field modules can add JavaScript parts, such as custom controls. A module only gets WordPress packages through the `window.wp` globals the page already loads.
4. Set the layout with the `get_entity_view_config_woo_settings_{area}_{id}` filter.

The screen opens at `admin.php?page=wc-payment-settings-wp-admin&p=/settings/{id}`, so a sub-page opens at `p=/settings/{gateway}/{sub-page}`. Moving between registered screens doesn't reload the page.

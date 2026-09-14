# Settings DataForm

A WooCommerce settings page using DataForm and [@wordpress/build](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-wp-build/).

## Getting started

From the repository root:

```bash
cd packages/js/settings-dataform
pnpm install
pnpm build
```

Run `pnpm start` to rebuild the package's local output when source files change.

Available commands from this package directory:

| Command | Purpose |
| --- | --- |
| `pnpm build` | Build the local package with `wp-build`. |
| `pnpm build:core` | Build directly into WooCommerce Core's asset directory. |
| `pnpm clean` | Remove the local build directory or link. |
| `pnpm prepare:core` | Clear Core's generated assets and link the local build directory to Core. |
| `pnpm start`, `pnpm watch`, `pnpm watch:build` | Watch source files and rebuild the local output. |
| `pnpm watch:core` | Watch source files and rebuild directly into Core. |
| `pnpm lint`, `pnpm lint:types`, `pnpm lint:lang:types` | Run TypeScript checks. |
| `pnpm wp-build` | Run the WordPress builder directly; append `--watch` to rebuild on changes. |

To build the package into WooCommerce Core, run from the repository root:

```bash
pnpm --filter @woocommerce/plugin-woocommerce build:project:settings-dataform
```

For development inside WooCommerce Core:

```bash
pnpm --filter @woocommerce/plugin-woocommerce watch:build:project:settings-dataform
```

These commands also run as part of Core's full build and watch commands. They link the package's `build/` directory to Core's asset directory, so the native `wp-build` watcher updates Core directly. Run `pnpm build` to restore local output. Stop any active watcher before switching between local and Core output. Restart the watcher after changing package metadata or `tsconfig.json`.

The page is implemented in `routes/settings/stage.tsx`, using `Page` from `@wordpress/admin-ui` for its title, subtitle, and content spacing. It edits product settings and provides Discard changes and Save changes actions. Runtime dependencies are declared in `routes/settings/package.json`.

## Data and form configuration

The router registers the core-data singleton entity `woo_settings/product` with `baseURL: '/wc/v4/settings/products'` and `key: false`. Its loader preloads the settings record; the stage loads the layout through `useViewConfig`. All entity selectors and actions omit the record ID, using the constants in `routes/settings/constants.ts`.

The endpoint returns a flat object keyed by setting ID:

```json
{
  "woocommerce_manage_stock": true,
  "woocommerce_weight_unit": "kg",
  "woocommerce_hold_stock_minutes": 60
}
```

`getEditedEntityRecord` supplies this object directly to DataForm. Values retain their boolean, numeric, string, or array types. `editEntityRecord` applies field edits to the singleton, and `saveEditedEntityRecord` sends the changed properties in one request to the same endpoint. Dirty state, saving state, and errors belong to that single record. Discard restores its persisted values. Failed saves keep the edits available for another attempt.

The package's [`ViewConfig` class](packages/settings-ui/src/ViewConfig.php) contributes the Shop pages, Measurements, Reviews, and Inventory groups through `get_entity_view_config_woo_settings_product`. It calls `WP_View_Config_Data::merge( $patch, 1 )` with the DataForm layout. The client requests only `form` through `useViewConfig`; field definitions remain in `packages/settings-ui/fields/product/`. Only registered fields with values in the settings response are passed to DataForm.

Core loads the PHP configuration on `init`, including REST requests, when `wp_get_entity_view_config()` is available. The WordPress/Gutenberg runtime must support View Config and `useViewConfig` with its `fields` argument. The page shows a loading indicator while data is resolving and displays settings or View Config request errors.

## Compatibility

The products endpoint now returns setting values directly, replacing the previous `{ id, title, description, values, groups }` envelope. Consumers must read the response itself instead of `response.values` and obtain field definitions and layout separately. Both flat update bodies and the existing `{ "values": { ... } }` update format are accepted; both return the flat response. Other v4 settings endpoints retain their existing formats.

Settings remain site-scoped through the existing option storage and permission checks. This change introduces no network options or new URL construction. Multisite behavior has not been tested.

## Verification

Run `pnpm lint:types` and `pnpm build:core` from this package. In the Settings DataForm page, verify that editing a checkbox, weight unit, or stock threshold enables Save changes, and that Discard changes restores the saved values. Save two edits together and confirm there is one update request to `/wc/v4/settings/products`, then reload to verify persistence and restore the original values. A negative stock threshold should prevent saving. A failed save should display an error and retain the edits.

## WordPress menu

Use WordPress 7.0 or later with WooCommerce active and build the Core assets. Open **WooCommerce → Settings DataForm**. No separate plugin activation is needed.

The `wpPlugin.pages` configuration generates the admin page, and `routes/settings/stage.tsx` provides its content. Core's `SettingsDataForm` class loads `assets/client/settings-dataform/build.php` and registers the submenu with the `manage_woocommerce` capability. WordPress supplies the `@wordpress/boot` module used by the generated page.

The admin page integration skips older WordPress versions, missing builds, front-end requests, AJAX, and network admin. The PHP View Config loads independently of the admin page so REST requests can resolve the form. On multisite, the menu belongs to each site's admin; the form configuration does not read or write site or network options.

## Build output

The builder discovers implementations under `packages/*/src/` and writes transpiled files into each package's `build/` and `build-module/` directories. WordPress bundles and generated PHP asset registration files go into this workspace's root `build/` directory. The settings UI package's `wpCopyFiles` configuration copies `ViewConfig.php` to `build/scripts/settings-ui/ViewConfig.php`, including during watch builds.

The settings UI implementation is bundled into the route. The generated admin page mounts it using WordPress's boot module.

This private package belongs to the main pnpm workspace and uses `@wordpress/build` from its `tooling` catalog. Core builds write through the local directory link into `plugins/woocommerce/assets/client/settings-dataform/`. Core's asset directory contains regular files and is included in the WooCommerce distribution.

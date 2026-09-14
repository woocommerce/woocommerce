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

The page is implemented in `routes/settings/stage.tsx`, using [`Page` from `@wordpress/admin-ui`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-admin-ui/) for its title, subtitle, and content spacing. It edits the store address and currency options from General settings. Runtime dependencies are declared in `routes/settings/package.json`.

The Page header provides section navigation and compact Discard changes and Save changes actions. Navigation follows the labeled groups in the form configuration (Store address and Currency options by default). Switching sections preserves edits; validation and saving cover the whole form. This configuration requires `@wordpress/admin-ui` 2.10.0, pinned in this package and its settings route.

## Data and form configuration

The router's `beforeLoad` registers the core-data entity `woo_settings/product` with `baseURL: '/wc/v4/settings/general'` and `key: 'id'`. Its `loader` preloads settings and View Config in parallel before the stage renders. The router and stage share entity and query constants from `routes/settings/constants.ts`, so `useEntityRecords` reads the preloaded values and available country/state and currency options from the same core-data cache. Request errors remain available to the stage's error and retry UI. `editEntityRecord` stores edits, and `getEditedEntityRecord` supplies the form values. Save calls `saveEditedEntityRecord` for each setting; core-data skips unchanged records and manages requests, persisted values, saving state, and errors. Failed updates remain editable. Discard restores each entity’s persisted value through `editEntityRecord`.

The layout comes from [`useViewConfig`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-views/) in `@wordpress/views`, using the entity key `kind: 'woo_settings'` and `name: 'product'`. `VIEW_CONFIG_FIELDS = [ 'form' ]` requests only the DataForm configuration through WordPress's core-data store. The `default_view` and `default_layouts` properties apply to DataViews lists and are not needed here. The View Config uses the same entity key as the settings records; the data and save endpoints remain General settings.

The package's [`ViewConfig` class](packages/settings-ui/src/ViewConfig.php) provides the Store address and Currency options sections through the [View Config API](https://developer.wordpress.org/block-editor/reference-guides/view-config-reference/) filter `get_entity_view_config_woo_settings_product`. It receives a `WP_View_Config_Data` container and calls `merge( $patch, 1 )` to contribute the form using configuration schema version 1. Additional callbacks can customize the layout through the same filter and must return the container. The router continues to register the client-side data entity.

Core loads the PHP configuration on `init`, including REST requests, when `wp_get_entity_view_config()` is available. The API is documented as introduced in WordPress 7.1.0; earlier runtimes retain the client fallback. After `init`, retrieve the configuration with `wp_get_entity_view_config( 'woo_settings', 'product' )`, or request `/wp/v2/view-config?kind=woo_settings&name=product` through the authenticated REST API. This configuration contains field layout metadata; setting values and saves continue to use WooCommerce's settings endpoint and its permissions.

When the endpoint reports a missing route/entity, or its form has no fields, the page uses `DEFAULT_FORM` from `stage.tsx`. Keep that fallback aligned with the PHP form when changing the default layout. Loading and error states use the matching core-data resolution; retry invalidates the settings and View Config resolutions before requesting them again. The WordPress/Gutenberg runtime must support `useViewConfig` and its `fields` argument.

Field definitions stay in the client; View Config controls grouping, order, and layout. Only supported settings returned by the WooCommerce API are rendered. Unknown field IDs and empty groups are removed. Permission and other request errors are shown with a retry action, rather than treated as missing configuration.

## Verification

Run `pnpm lint:types` and `pnpm build:core` from this package. In the Settings DataForm page, verify that editing enables Save changes, Discard changes restores the saved values, and a negative number of decimals prevents saving. Save a temporary address-line change, reload to verify persistence, then restore the original value.

## WordPress menu

Use WordPress 7.0 or later with WooCommerce active and build the Core assets. Open **WooCommerce → Settings DataForm**. No separate plugin activation is needed.

The `wpPlugin.pages` configuration generates the admin page, and `routes/settings/stage.tsx` provides its content. Core's `SettingsDataForm` class loads `assets/client/settings-dataform/build.php` and registers the submenu with the `manage_woocommerce` capability. WordPress supplies the `@wordpress/boot` module used by the generated page.

The admin page integration skips older WordPress versions, missing builds, front-end requests, AJAX, and network admin. The PHP View Config loads independently of the admin page so REST requests can resolve the form. On multisite, the menu belongs to each site's admin; the form configuration does not read or write site or network options.

## Build output

The builder discovers implementations under `packages/*/src/` and writes transpiled files into each package's `build/` and `build-module/` directories. WordPress bundles and generated PHP asset registration files go into this workspace's root `build/` directory. The settings UI package's `wpCopyFiles` configuration copies `ViewConfig.php` to `build/scripts/settings-ui/ViewConfig.php`, including during watch builds.

The settings UI implementation is bundled into the route. The generated admin page mounts it using WordPress's boot module.

This private package belongs to the main pnpm workspace and uses `@wordpress/build` from its `tooling` catalog. Core builds write through the local directory link into `plugins/woocommerce/assets/client/settings-dataform/`. Core's asset directory contains regular files and is included in the WooCommerce distribution.

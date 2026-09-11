# Settings DataForm

A minimal workspace for a new settings UI implementation using [@wordpress/build](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-wp-build/).

## Getting started

From the repository root:

```bash
cd packages/js/settings-dataform
pnpm install
pnpm build
```

Run `pnpm start` to rebuild the package's local output when source files change.

To build the package and copy its output into WooCommerce Core, run from the repository root:

```bash
pnpm --filter @woocommerce/plugin-woocommerce build:project:settings-dataform
```

For development inside WooCommerce Core:

```bash
pnpm --filter @woocommerce/plugin-woocommerce watch:build:project:settings-dataform
```

These commands also run as part of Core's full build and watch commands.

Start implementing the UI in `packages/settings-ui/src/index.tsx`. The starter component renders a heading. React and React DOM are available; add other dependencies to `packages/settings-ui/package.json` as needed.

## WordPress menu

Use WordPress 7.0 or later with WooCommerce active and build the Core assets. Open **WooCommerce → Settings DataForm**. No separate plugin activation is needed.

The `wpPlugin.pages` configuration generates the admin page, and `routes/settings/stage.tsx` provides its content. Core's `SettingsDataForm` class loads `assets/client/settings-dataform/build.php` and registers the submenu with the `manage_woocommerce` capability. WordPress supplies the `@wordpress/boot` module used by the generated page.

The integration skips older WordPress versions, missing builds, front-end requests, AJAX, and network admin. On multisite, the menu belongs to each site's admin.

## Build output

The builder discovers implementations under `packages/*/src/` and writes transpiled files into each package's `build/` and `build-module/` directories. WordPress bundles and generated PHP asset registration files go into this workspace's root `build/` directory.

The settings UI implementation is bundled into the route. The generated admin page mounts it using WordPress's boot module.

This private package belongs to the main pnpm workspace and uses `@wordpress/build` from its `tooling` catalog. Core builds copy the generated output into `plugins/woocommerce/assets/client/settings-dataform/`, which is included in the WooCommerce distribution.

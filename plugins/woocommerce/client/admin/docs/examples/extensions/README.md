# WooCommerce Admin Extension Examples

Choose the workflow that matches the example you want to try:

- **Inbox note:** [Build an example from this directory](#build-an-example-from-this-directory) using the WooCommerce monorepo. The `simple-inbox-note` example adds a note to **WooCommerce > Home**.
- **Analytics report or onboarding task:** [Scaffold a standalone extension](#scaffold-a-standalone-extension). The former `add-report` and `add-task` examples now use this workflow and do not require a WooCommerce checkout.

## Build an example from this directory

This directory holds smaller examples for contributors working in the WooCommerce monorepo. Start from a checkout that is set up and built as described in the repository's [Getting Started guide](../../../../../../../README.md#getting-started), and have Docker running for wp-env.

`WC_EXT` is a shell environment variable, not a command. Set it to the name of an example directory and run the `example` script of `@woocommerce/admin-library` from the monorepo root:

```bash
WC_EXT=simple-inbox-note pnpm --filter=@woocommerce/admin-library example
```

This copies the example to `plugins/woocommerce/client/simple-inbox-note` and compiles its JavaScript into that directory's `dist/` folder.

To load it in the WooCommerce wp-env, add it to the `plugins` list in `plugins/woocommerce/.wp-env.override.json`, keeping `.` (WooCommerce) and any entries you already have:

```json
{
    "plugins": [
        ".",
        "./client/simple-inbox-note"
    ]
}
```

Run `pnpm wc:env` from the monorepo root to apply the change. wp-env activates the plugins in this list, and activating the example adds its note. Open **WooCommerce > Home** in WordPress admin to see the "Hello From Inbox Note!" note in the inbox. If Home opens the setup wizard instead, select **Skip guided setup**, choose your store's country or region, and select **Go to my store**.

## Edit the example

Edit the files in `simple-inbox-note/` under this directory, not the generated copy, and run the `example` command again. The note is stored when the plugin is activated, so rebuilding alone does not update it. Under **Plugins > Installed Plugins**, deactivate and reactivate **WooCommerce Admin Simple Inbox Note Example**, then reload **WooCommerce > Home** to see your changes.

## Clean up

1. Under **Plugins > Installed Plugins**, deactivate **WooCommerce Admin Simple Inbox Note Example**. This removes its note.
2. Remove `./client/simple-inbox-note` from the `plugins` list in `plugins/woocommerce/.wp-env.override.json`, keeping `.` and any other entries.
3. Delete the generated `plugins/woocommerce/client/simple-inbox-note` directory; Git does not ignore it.
4. Run `pnpm wc:env` from the monorepo root to apply the configuration change.

## Scaffold a standalone extension

The former `add-report` and `add-task` examples now live in [`@woocommerce/create-woo-extension`](../../../../../../../packages/js/create-woo-extension/README.md), alongside other [variants](../../../../../../../packages/js/create-woo-extension/README.md#variants). The scaffold generates a standalone plugin with its own build and wp-env setup.

For this walkthrough, use Node.js 24.15.0 or a newer 24.x release, npm, PHP, and Composer, and have Docker running. From a directory outside the WooCommerce checkout, generate the report example:

```bash
npx @wordpress/create-block -t @woocommerce/create-woo-extension --variant=add-report my-extension-name
```

Wait for dependency installation and the production build to finish. Then start the generated plugin's WordPress environment:

```bash
cd my-extension-name
npx @wordpress/env start
```

The included configuration installs and activates WooCommerce and the generated plugin. Open `/wp-admin/` at the site URL printed by wp-env and sign in with username `admin` and password `password`.

If WooCommerce opens the setup wizard, select **Skip guided setup**, choose your store's country or region, and select **Go to my store**.

Open **Analytics > My Extension Name**. You should see an **Example Data** table containing four sample apple varieties; no products or orders are needed. The report's admin URL is `/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fmy-extension-name`.

When finished, run `npx @wordpress/env stop` from the generated plugin directory.

# WooCommerce Admin Extension Examples

To start a new extension from a working example, use [`@woocommerce/create-woo-extension`](../../../../../../../packages/js/create-woo-extension/README.md). It generates a standalone plugin with its own build and wp-env setup. For example, this creates a plugin that adds a report page under **Analytics**:

```bash
npx @wordpress/create-block -t @woocommerce/create-woo-extension --variant=add-report my-extension-name
```

The former `add-report` and `add-task` examples from this directory now live there, with other [variants](../../../../../../../packages/js/create-woo-extension/README.md#variants).

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

To change the example, edit the files in this directory, not the generated copy, and run the `example` command again. The note is added when the plugin is activated, so deactivate and reactivate it to see your changes. Delete the generated `plugins/woocommerce/client/<example>` directory when you are done; Git does not ignore it.

## Examples in this directory

- `simple-inbox-note` adds a note to the inbox on **WooCommerce > Home**.
- `add-abbreviated-notification` fills a slot in the Activity panel's Inbox drawer, which WooCommerce 11.2 removed. It still builds, but it no longer shows anything.

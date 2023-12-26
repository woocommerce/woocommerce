# Markdown Docs CLI tool

This is a CLI tool designed to generate JSON manifests of Markdown files in a directory.

This manifest is currently designed to be consumed by the [WooCommerce Docs](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-docs) plugin.

## Usage

This command is built on postinstall and can be run from monorepo root.

To create a manifest:

```
pnpm utils md-docs create <path-to-directory> <projectName>
```

### Arguments and options

To find out more about the arguments and options available, run:

```
pnpm utils md-docs create --help
```

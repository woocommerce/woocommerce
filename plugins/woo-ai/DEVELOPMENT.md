# Woo AI

Woo AI is a WooCommerce plugin that utilizes the power of artificial intelligence to enhance your eCommerce experience. With features like AI-powered product title optimization and automated product description generation, Woo AI is designed to boost your store's efficiency and sales potential.

## Getting Started

Please refer to [the Getting Started section of the WooCommerce Core `README.md`](https://github.com/woocommerce/woocommerce/blob/trunk/README.md) for a general-purpose guide on getting started. The rest of this document will assume that you've installed all of the prerequisites and setup described there.

## Plugin Development Environments

The plugin makes use of [the `@wordpress/env` package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
This supplies convenient commands for creating, destroying, cleaning, and testing WordPress environments.

```bash
# Make sure you are in the working directory of the plugin you are interested in setting up the environment for
cd plugins/woo-ai
# Start will create the environment if necessary or start an existing one
pnpm -- wp-env start
# Stop will, well, stop the environment
pnpm -- wp-env stop
# Destroy will remove all of the environment's files.
pnpm -- wp-env destroy
```

## Development

To enable Live(Hot) Reload when code is changed, run the following commands:

```text
pnpm install
pnpm start
```

To build the /woo-ai/ plugin directory (when loading the plugin via symlink), run:

```text
pnpm install
pnpm build
```

To build the plugin ZIP file, run:

```text
pnpm install
pnpm build:zip
```

See [wp-scripts](https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts) for more usage information.

## License

This plugin is licensed under the GPL v3 or later.

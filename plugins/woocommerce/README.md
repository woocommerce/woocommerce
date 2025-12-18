# [![WooCommerce](https://woocommerce.com/wp-content/themes/woo/images/logo-woocommerce@2x.png 'WooCommerce')](https://woocommerce.com/)

[![License](https://poser.pugx.org/woocommerce/woocommerce/license 'License')](https://packagist.org/packages/woocommerce/woocommerce)
![WordPress.org downloads](https://img.shields.io/wordpress/plugin/dt/woocommerce.svg 'WordPress.org downloads')
![WordPress.org rating](https://img.shields.io/wordpress/plugin/r/woocommerce.svg 'WordPress.org rating')
[![Build Status](https://github.com/woocommerce/woocommerce/actions/workflows/ci.yml/badge.svg?branch=trunk 'Build Status')](https://github.com/woocommerce/woocommerce/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/woocommerce/woocommerce/branch/trunk/graph/badge.svg 'codecov')](https://codecov.io/gh/woocommerce/woocommerce)

This is the WooCommerce Core plugin. Here you can browse the source and keep track of development. We recommend all developers to follow the [WooCommerce development blog](https://woocommerce.wordpress.com/) to stay up to date about everything happening in the project. You can also [follow @DevelopWC](https://twitter.com/DevelopWC) on Twitter for the latest development updates.

If you are not a developer, please use the [WooCommerce plugin page](https://wordpress.org/plugins/woocommerce/) on WordPress.org.

## Getting Started

### Quick start

Ensure your system meets [the requirements](../../README.md#getting-started) (TLDR: NVM, PNPM, PHP 7.4+, Composer are required for development).

Depending on the preferred environment for running the development instance of WooCommerce, you might need [Docker](https://docs.docker.com/get-docker/) as well. You can learn more about supported environments [here in the developer docs](https://developer.woocommerce.com/docs/setting-up-your-development-environment/).

Once you have verified the prerequisites, you can start the development environment:

```bash
## Watch for changes in WooCommerce and all of its dependencies.
pnpm --filter='@woocommerce/plugin-woocommerce' watch:build

# Start a wp-env based development environment, which will be accessible via http://localhost:8888/.
# This step is optional and you can skip it if you are running WooCommerce on a custom setup.
pnpm --filter='@woocommerce/plugin-woocommerce' env:dev
```

If desired, you can also run commands without `--filter='@woocommerce/plugin-woocommerce'` by running `pnpm <command>` from within the `plugins/woocommerce` directory.

## Building Components

There are three major client-side components included in WooCommerce Core that can be built, linted, and tested independently. We've organized these components
in this way to take advantage of caching to prevent unnecessarily performing expensive rebuilds when only working in one of them.

### `plugins/woocommerce/client/legacy`

This directory contains the Classic CSS and jQuery code for WooCommerce.

```bash
# Build the assets.
pnpm --filter='@woocommerce/plugin-woocommerce' build:classic-assets
# Lint the assets.
pnpm --filter='@woocommerce/classic-assets' lint
```

### `plugins/woocommerce/client/admin`

This directory contains the React-based admin interface.

```bash
# Build the React-based admin client.
pnpm --filter='@woocommerce/plugin-woocommerce' build:admin
# Lint the React-based admin client.
pnpm --filter='@woocommerce/admin-library' lint
# Test the React-based admin client.
pnpm --filter='@woocommerce/admin-library' test
# Watch the tests of the React-based admin client.
pnpm --filter='@woocommerce/admin-library' test:watch
# Run a type check over the React-based admin client's TypeScript files.
pnpm --filter='@woocommerce/admin-library' ts:check
```

### `plugins/woocommerce/client/blocks`

This directory contains the client for WooCommerce Blocks.

```bash
# Build the Blocks client.
pnpm --filter='@woocommerce/plugin-woocommerce' build:blocks
# Lint the Blocks client.
pnpm run --filter='@woocommerce/block-library' lint
# Test the Blocks client.
pnpm run --filter='@woocommerce/block-library' test
```

## PHP Code Analysis

WooCommerce uses [PHPStan](https://phpstan.org/) for static analysis of PHP code. PHPStan helps catch bugs and type errors before runtime.

```bash
# Run PHPStan analysis on the WooCommerce plugin.
composer phpstan

# Generate a baseline file to ignore existing errors (useful for initial setup).
composer phpstan:baseline
```

PHPStan configuration is stored in `phpstan.neon` at the root of the plugin directory. The analysis runs at level 8 (the second-strictest level) and includes WordPress-specific stubs via the `szepeviktor/phpstan-wordpress` extension.

## Documentation

-   [WooCommerce Documentation](https://woocommerce.com/)
-   [WooCommerce Developer Documentation](https://github.com/woocommerce/woocommerce/wiki)
-   [WooCommerce Code Reference](https://woocommerce.com/wc-apidocs/)
-   [WooCommerce REST API Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/)

## Reporting Security Issues

To disclose a security issue to our team, [please submit a report via HackerOne here](https://hackerone.com/automattic/).

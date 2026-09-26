# Getting Started <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Prerequisites](#prerequisites)
-   [Cloning the Git repository](#cloning-the-git-repository)
-   [Installing dependencies](#installing-dependencies)
-   [Building the Blocks client](#building-the-blocks-client)
-   [Running WooCommerce locally](#running-woocommerce-locally)
-   [Configuring your WordPress site](#configuring-your-wordpress-site)
-   [Create a plugin package in ZIP format](#create-a-plugin-package-in-zip-format)
-   [Linting](#linting)
-   [Developer Tools (Visual Studio Code)](#developer-tools-visual-studio-code)
    -   [EditorConfig](#editorconfig)
    -   [ESLint](#eslint)
    -   [Prettier](#prettier)
-   [Testing](#testing)

The Blocks client lives in the WooCommerce monorepo at `plugins/woocommerce/client/blocks` and ships as part of WooCommerce core, so there is no separate Blocks plugin to install or activate. This page covers the setup that is specific to it. The [monorepo README](../../../../../../README.md#getting-started) and the [WooCommerce plugin README](../../../../README.md#getting-started) are the source of truth for everything else.

## Prerequisites

You need Node.js, PNPM, PHP, and Composer. The [monorepo README](../../../../../../README.md#getting-started) lists the supported versions and links to install instructions for each. PNPM reads the Node version pinned in the repository and uses it for every script it runs, so you do not need to select one by hand.

Docker is needed as well if you want the `wp-env` environment described below.

## Cloning the Git repository

Clone the monorepo, either from the command line or with a Git client such as [GitHub Desktop](https://desktop.github.com/):

```sh
git clone https://github.com/woocommerce/woocommerce.git
```

## Installing dependencies

Install the JavaScript and PHP dependencies for the whole monorepo from its root directory:

```sh
pnpm install --frozen-lockfile
```

## Building the Blocks client

Building is required before the blocks work in WordPress. Both commands below compile the client with `webpack` and write the result to `plugins/woocommerce/assets/client/blocks`:

```sh
# Build the Blocks client once.
pnpm --filter='@woocommerce/plugin-woocommerce' build:blocks

# Rebuild whenever a file changes.
pnpm --filter='@woocommerce/block-library' watch:build
```

To build every part of the WooCommerce plugin instead of the Blocks client alone, run `pnpm --filter='@woocommerce/plugin-woocommerce' build`. The [JavaScript Build System](javascript-build-system.md) document explains how the webpack configuration is put together.

## Running WooCommerce locally

The quickest environment is `wp-env`, which the WooCommerce plugin provides:

```sh
pnpm --filter='@woocommerce/plugin-woocommerce' env:dev
```

It serves WordPress with WooCommerce active at `http://localhost:8888/`. Edit a page or post in the block editor, and the WooCommerce blocks are in the inserter.

You can also run WooCommerce on your own environment. In that case, make sure the `plugins/woocommerce` directory is available as a plugin in your site's `wp-content/plugins` folder.

## Configuring your WordPress site

The `wp-env` setup above already defines the constants below. On your own environment, add them to `wp-config.php`:

```php
define( 'JETPACK_AUTOLOAD_DEV', true );
define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', true );
```

`JETPACK_AUTOLOAD_DEV` makes the Jetpack autoloader prefer the packages in your checkout. The other two surface PHP notices and load unminified assets.

## Create a plugin package in ZIP format

Build a WooCommerce ZIP that you can install through WP Admin:

```sh
pnpm --filter='@woocommerce/plugin-woocommerce' build:zip
```

## Linting

Run the Blocks linters:

```sh
pnpm --filter='@woocommerce/block-library' lint
```

That covers JavaScript and TypeScript with ESLint, SCSS with Stylelint, and TypeScript declarations with `tsc`:

-   ESLint uses the package's own [`eslint.config.mjs`](../../eslint.config.mjs), which adds rules on top of the monorepo configuration.
-   Stylelint uses [`.stylelintrc.json`](../../.stylelintrc.json).

To lint or fix a single file, pass its path, relative to `plugins/woocommerce/client/blocks`, before any flags:

```sh
pnpm --filter='@woocommerce/block-library' lint:js assets/js/blocks/cart/metadata.tsx --fix
```

The PHP behind the blocks lives in `plugins/woocommerce/src/Blocks` and is linted with the plugin's PHPCS setup, which uses [`phpcs.xml`](../../../../phpcs.xml):

```sh
pnpm --filter='@woocommerce/plugin-woocommerce' lint:php:changes
```

Linters also run against staged files before each commit. If there are violations, the commit is blocked until they are fixed, unless you add the `--no-verify` flag.

## Developer Tools (Visual Studio Code)

We recommend configuring your editor to automatically check for syntax and lint errors. This will help you save time as you develop by automatically fixing minor formatting issues.

Here are some directions for setting up Visual Studio Code (most tools are also available for other editors).

### EditorConfig

[EditorConfig](https://editorconfig.org/) defines a standard configuration for setting up your editor, for example using tabs instead of spaces. You should install the [EditorConfig for VS Code extension](https://marketplace.visualstudio.com/items?itemName=editorconfig.editorconfig) and it will automatically configure your editor to match the rules defined in the repository's `.editorconfig` file.

### ESLint

[ESLint](https://eslint.org/) statically analyzes the code to find problems. The lint rules are integrated in the continuous integration process and must pass to be able to commit. You should install the [ESLint Extension](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint) for Visual Studio Code (see [eslint docs](https://eslint.org/docs/user-guide/integrations) for more editor integrations).

With the extension installed, ESLint will use the [`eslint.config.mjs`](../../eslint.config.mjs) file in the Blocks client for formatting rules. It will highlight issues as you code.

### Prettier

[Prettier](https://prettier.io/) is a tool that allows you to define an opinionated format, and automate fixing the code to match that format. Prettier and ESlint are similar, Prettier is more about formatting and style, while ESlint is for detecting coding errors.

To use Prettier, you should install the [Prettier - Code formatter](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode) extension in Visual Studio Code. You can then configure it to be the default formatter and to automatically fix issues on save, by adding the following to your settings.

```js
"[javascript]": {
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.formatOnSave": true
},
```

This will use the [`.prettierrc.js`](../../.prettierrc.js) file in the Blocks client and the version of Prettier installed in the monorepo.

## Testing

Run the Jest unit tests:

```sh
# Run the test suite.
pnpm --filter='@woocommerce/block-library' test:js

# Run a single test file.
pnpm --filter='@woocommerce/block-library' test:js -- path/to/test

# Update snapshots after intentional changes.
pnpm --filter='@woocommerce/block-library' test:update
```

To find out more about how to run automated JavaScript tests, check out the documentation on [JavaScript Testing](javascript-testing.md).

End-to-end tests for the blocks are part of the WooCommerce end-to-end suite in `plugins/woocommerce/tests/e2e`. The [E2E guidelines](e2e-guidelines.md) cover how to write and run them.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/contributors/getting-started.md)

<!-- /FEEDBACK -->

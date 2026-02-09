# Development

## Local Development

There are two ways to develop this package as a part of the WooCommerce plugin:

### 1. Using wp-env Mappings

You can extend your `.wp-env.override.json` file to map this package directly to have your changes up to date:

```json
{
  ...
  "env": {
    "development": {
      "mappings": {
        ...
        "wp-content/plugins/woocommerce/packages/email-editor": "../../packages/php/email-editor"
      }
    }
  }
}
```

### 2. Using Build and Watch Commands

Alternatively, you can use the watch command within the WooCommerce plugin to keep your changes synchronized during development:

Run the following command. It will watch for changes, rebuild assets if necessary, and sync the package files automatically:

```bash
  pnpm --filter='@woocommerce/plugin-woocommerce' watch:build:admin
```

Press `Ctrl+C` in the terminal to stop watching.

Using the watch command is particularly useful when you don't want to restart `wp-env` after making modifications to this package.

## Running Tests

We use [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to setup test environment in Docker.
Please install `wp-env` by running `npm install -g @wordpress/env` if you haven't already.

1. Run `composer run env:start` to run wp-env.
2. Run `composer run test:unit` to run unit tests.
3. Run `composer run test:integration` to run integration tests.
4. Run `composer run env:stop` to stop wp-env.

More detailed guide for writing tests can be found at [writing-tests.md](writing-tests.md)

Guide for writing E2E tests can be found at [packages/js/email-editor/writing-e2e-tests.md](../../../packages/js/email-editor/writing-e2e-tests.md)

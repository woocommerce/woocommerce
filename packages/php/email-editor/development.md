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

### 2. Using the Sync Script

Alternatively, you can use the sync script in the WooCommerce plugin to keep your changes synchronized:

1. For one-time synchronization:

```bash
pnpm --filter='@woocommerce/plugin-woocommerce' email-editor:copy-php-package
```

1. For continuous synchronization during development:

```bash
pnpm --filter='@woocommerce/plugin-woocommerce' email-editor:sync-php-package
```

This will watch for changes and sync them automatically. Press `Ctrl+C` to stop watching.

The sync method is particularly useful when you don't want to restart wp-env after making changes to this package.

## Running Tests

We use [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to setup test environment in Docker.
Please install `wp-env` by running `npm install -g @wordpress/env` if you haven't already.

1. Run `composer run env:start` to run wp-env.
2. Run `composer run test:unit` to run unit tests.
3. Run `composer run test:integration` to run integration tests.
4. Run `composer run env:stop` to stop wp-env.

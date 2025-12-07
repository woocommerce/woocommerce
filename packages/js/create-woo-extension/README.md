# @woocommerce/create-woo-extension

Create Woo Extension scaffolds a fully functional modern development environment for integrating with WooCommerce. Use it to build extensions leveraging the power of WooCommerce.

## Configuration Free

With build tooling already configured, getting started developing modern Javascript screens requires no effort. Create Woo Extension adds a React page integrating with WooCommerce Admin. Also included are PHP and Javascript unit testing, linting, and Prettier IDE configuration for WooCommerce and WordPress.

Add a project inside of your favorite environment setup or use the built in [wp-env](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) for easily setting up a local WordPress environment for building and testing plugins and themes. `wp-env` is configured to load the latest WooCommerce to easily start developing with a single command.

## Usage

```bash
npx @wordpress/create-block -t @woocommerce/create-woo-extension my-extension-name
```

Navigate to the newly created folder and get started.

```bash
cd my-extension-name
npm run start # Watch the javascript for changes

# Local development with wp-env (optional)
npm -g i @wordpress/env # If you don't already have wp-env
wp-env start # Start Wordpress environment
```

See the new plugin activated from the WordPress plugins page and navigate to `wp-admin/admin.php?page=wc-admin&path=%2Fmy-extension-name` to check it out.

## Variants

You can also create different types of WooCommerce extensions by specifying a variant.

```bash
npx @wordpress/create-block -t @woocommerce/create-woo-extension --variant=add-report my-extension-name
```

### Analytics Report Variants

These variants create example extensions for modifying WooCommerce Analytics pages.

- `add-report` - Creates a new example report page under the Analytics menu.
- `dashboard-section` - Adds a custom section to the Analytics Overview area.
- `sql-modification` - Adds a custom dropdown filter for SQL statements in the Products Analytics report. See the [Extending WooCommerce analytics Reports](https://developer.woocommerce.com/docs/features/analytics/extending-woocommerce-admin-reports/) tutorial for more information.
- `table-column` - Adds new column(s) to the Products Analytics report.

### Onboarding Variants

These variants create example extensions for modifying the WooCommerce onboarding experience.

- `add-task` - Creates a custom task for the onboarding task list. See the [Handling Merchant Onboarding](https://developer.woocommerce.com/docs/extensions/extension-onboarding/handling-merchant-onboarding/) tutorial for more information.

## Development

For development on this tool itself, you can also install from a local directory.

```bash
npx @wordpress/create-block -t ./path/to/woocommerce/packages/js/create-woo-extension my-extension-name
```

This is a template to used with [`@wordpress/create-block`](https://github.com/WordPress/gutenberg/tree/trunk/packages/create-block) to create a WooCommerce Extension starting point.

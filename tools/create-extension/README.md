# Create Extension

Scaffold a modern JavaScript WordPress plugin with WooCommerce tooling.

## Includes

-   [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts)
-   [WooCommerce Dependency Extraction Webpack Plugin](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/dependency-extraction-webpack-plugin)
-   [WooCommerce ESLint Plugin with WordPress Prettier](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/eslint-plugin)

### Usage

```
pnpm run create-extension
```

The script will create a sibling directory by a name of your choosing. Once you change directories into the new folder, install dependencies and start a development build.

```
pnpm install
pnpm start
```

### Running Examples

```
pnpm run create-extension --example add-task
```

#### Example Extensions

-   `add-report` - Create a "Hello World" report page.
-   `add-task` - Create a custom task for the onboarding task list.
-   `dashboard-section` - Adding a custom "section" to the new dashboard area.
-   `table-column` - An example of how to add column(s) to any report.
-   `sql-modification` - An example of how to modify SQL statements.
-   `add-abbreviated-notification` - An example of how to add a abbreviated notification under the Activity panel.
-   `payment-gateway-suggestions` - An example of how to add custom payment gateways to the WooCommerce task list.
-   `simple-inbox-note` - An example of how to add a inbox note to the WooCommerce inbox.
